<?php

namespace App\Services\Concrete\Api;

use App\Enums\Status;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\OrderSource;
use App\Models\OrderType;
use App\Models\PaymentMethod;
use App\Models\WebsiteCartItem;
use App\Services\Concrete\Admin\OrderService;
use App\Services\Concrete\Admin\OrderSourceService;
use App\Services\Concrete\Admin\OrderTypeService;
use App\Services\Concrete\Admin\PaymentMethodService;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Website checkout / place-order orchestration.
 * Creates Hold orders via the shared OrderService::save() path with:
 * - order_source = WEBSITE
 * - order_type = DELIVERY
 * - sale_type = business default
 * - paid_amount left at 0 (payment pending) until admin confirms
 * Stock/GL remain untouched until OrderService::post() (existing admin flow).
 */
class WebsiteCheckoutService
{
    protected $cart_service;
    protected $order_service;
    protected $payment_method_service;
    protected $customer_order_service;
    protected $order_type_service;
    protected $order_source_service;

    public function __construct(
        WebsiteCartService $cart_service,
        OrderService $order_service,
        PaymentMethodService $payment_method_service,
        CustomerOrderService $customer_order_service,
        OrderTypeService $order_type_service,
        OrderSourceService $order_source_service
    ) {
        $this->cart_service = $cart_service;
        $this->order_service = $order_service;
        $this->payment_method_service = $payment_method_service;
        $this->customer_order_service = $customer_order_service;
        $this->order_type_service = $order_type_service;
        $this->order_source_service = $order_source_service;
    }

    /**
     * Website-only payment methods: COD + Bank Transfer.
     * Never returns POS-only tenders (cash/card/wallet/etc.).
     */
    public function getWebsitePaymentMethods(string $business_id): array
    {
        $this->payment_method_service->seedWebsiteDefaults($business_id);

        $methods = PaymentMethod::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->where('status', Status::ACTIVE)
            ->where(function ($q) {
                $q->where('type', 'cod')
                    ->orWhere(function ($q2) {
                        $q2->where('type', 'bank')->where('code', 'BANK');
                    });
            })
            ->orderByRaw("CASE WHEN type = 'cod' THEN 0 ELSE 1 END")
            ->orderBy('sort_order')
            ->get();

        return $methods->map(function ($method) {
            return [
                'id' => $method->payment_method_id,
                'code' => strtolower($method->code === 'BANK' ? 'bank_transfer' : $method->code),
                'name' => $method->type === 'cod' ? 'Cash on Delivery' : 'Bank Transfer',
                'type' => $method->type,
                'requires_receipt' => $method->type === 'bank',
            ];
        })->values()->all();
    }

    public function placeOrder(int $user_id, string $business_id, array $payload, ?UploadedFile $receipt = null): array
    {
        $client_request_id = $payload['client_request_id'] ?? null;

        if ($client_request_id) {
            $existing = Order::where('business_id', $business_id)
                ->where('client_request_id', $client_request_id)
                ->where('is_deleted', 0)
                ->first();

            if ($existing) {
                return $this->customer_order_service->find($user_id, $business_id, $existing->order_id);
            }
        }

        $payment_method_id = $payload['payment_method_id'] ?? null;
        $payment_code = strtolower((string) ($payload['payment_code'] ?? ''));

        $this->payment_method_service->seedWebsiteDefaults($business_id);
        $payment_method = $this->resolveWebsitePaymentMethod($business_id, $payment_method_id, $payment_code);

        if (!$payment_method) {
            throw new Exception('Invalid website payment method. Use COD or Bank Transfer.');
        }

        if ($payment_method->type === 'bank' && !$receipt) {
            throw new Exception('A payment receipt is required for bank transfer orders.');
        }

        $branch_id = $payload['branch_id'] ?? null;
        $cart = $this->cart_service->getOrCreateCart($user_id, $business_id, $branch_id);
        $cart_payload = $this->cart_service->getCart($user_id, $business_id, $branch_id);

        if (empty($cart_payload['items'])) {
            throw new Exception('Your cart is empty.');
        }

        foreach ($cart_payload['items'] as $item) {
            if (empty($item['in_stock'])) {
                throw new Exception('One or more cart items are out of stock.');
            }
        }

        $delivery_address = $this->formatDeliveryAddress($payload);

        $this->order_type_service->seedDefaults($business_id);
        $this->order_source_service->seedDefaults($business_id);

        $order_type_id = OrderType::where('business_id', $business_id)
            ->where('code', 'DELIVERY')
            ->where('is_deleted', 0)
            ->value('order_type_id');

        if (!$order_type_id) {
            throw new Exception('Delivery order type is not configured for this business.');
        }

        $order_source_id = OrderSource::where('business_id', $business_id)
            ->where('code', 'WEBSITE')
            ->where('is_deleted', 0)
            ->value('order_source_id');

        if (!$order_source_id) {
            throw new Exception('Website order source is not configured for this business.');
        }

        $sale_type_id = $cart_payload['sale_type']['id']
            ?? $this->cart_service->resolveDefaultSaleTypeId($business_id);

        $products = [];
        foreach ($cart_payload['items'] as $item) {
            $products[] = [
                'product_id' => $item['product_id'],
                'product_variation_id' => $item['product_variation_id'],
                'quantity' => $item['quantity'],
                'unit_id' => null,
                'sale_type_id' => $sale_type_id,
                // Never trust client unit prices - omit so OrderService prices from sale type.
            ];
        }

        $proof_filename = null;
        if ($receipt) {
            $proof_filename = $this->storePaymentProof($receipt);
        }

        DB::beginTransaction();

        try {
            $order_data = [
                'business_id' => $business_id,
                'branch_id' => $cart_payload['branch_id'],
                'warehouse_id' => $cart_payload['warehouse_id'],
                'customer_id' => $user_id,
                'order_type_id' => $order_type_id,
                'order_source_id' => $order_source_id,
                'sale_type_id' => $sale_type_id,
                'status' => 'hold',
                'delivery_address' => $delivery_address,
                'notes' => $payload['notes'] ?? null,
                'products' => $products,
                'payments' => [
                    [
                        'payment_method_id' => $payment_method->payment_method_id,
                        // Record intended tender amount; paid_amount on the order
                        // stays 0 until admin confirms (bank) or COD is collected.
                        'amount' => $cart_payload['totals']['total'],
                        'reference_no' => $payload['payment_reference'] ?? null,
                    ],
                ],
            ];

            if (!empty($cart->voucher_id)) {
                $order_data['voucher_id'] = $cart->voucher_id;
            } elseif (!empty($cart->voucher_code)) {
                $order_data['voucher_code'] = $cart->voucher_code;
            }

            if (!empty($payload['use_loyalty_points'])) {
                $order_data['use_loyalty_points'] = true;
            }

            $order_model = $this->order_service->save($order_data);

            if (!$order_model) {
                throw new Exception('Failed to create order.');
            }

            // Ensure payment line matches authoritative order total (never trust cart snapshot).
            OrderPayment::where('order_id', $order_model->order_id)->delete();
            OrderPayment::create([
                'order_payment_id' => generateUuid(),
                'order_id' => $order_model->order_id,
                'payment_method_id' => $payment_method->payment_method_id,
                'amount' => $order_model->total,
                'reference_no' => $payload['payment_reference'] ?? null,
                'is_deleted' => 0,
                'createdby_id' => Auth::id(),
                'date_created' => now(),
            ]);

            $order_model->update([
                'paid_amount' => 0,
                'payment_proof' => $proof_filename,
                'client_request_id' => $client_request_id,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            // Clear cart only after successful order creation.
            WebsiteCartItem::where('cart_id', $cart->cart_id)->delete();
            $cart->update([
                'voucher_id' => null,
                'voucher_code' => null,
                'date_updated' => now(),
            ]);

            DB::commit();

            return $this->customer_order_service->find($user_id, $business_id, $order_model->order_id);
        } catch (Exception $e) {
            DB::rollBack();

            if ($proof_filename) {
                $path = public_path('uploads/order_payment_proof/' . $proof_filename);
                if (File::exists($path)) {
                    File::delete($path);
                }
            }

            throw $e;
        }
    }

    /**
     * Admin confirms a bank-transfer receipt: marks payment as paid without posting stock/GL.
     * Order status stays Hold (or whatever it currently is) for the existing status workflow.
     */
    public function confirmPayment(string $order_id, int $admin_user_id): Order
    {
        $order = Order::with(['payments.paymentMethod', 'orderSource'])->findOrFail($order_id);

        if (($order->orderSource->code ?? null) !== 'WEBSITE') {
            throw new Exception('Only website orders can use this payment confirmation action.');
        }

        $due = max((float) $order->total - (float) $order->paid_amount, 0);
        if ($due <= 0.001) {
            throw new Exception('This order is already marked as paid.');
        }

        $has_bank = $order->payments->contains(function ($p) {
            return optional($p->paymentMethod)->type === 'bank';
        });

        if (!$has_bank) {
            throw new Exception('Payment confirmation from receipt applies to bank transfer orders.');
        }

        if (empty($order->payment_proof)) {
            throw new Exception('No payment receipt is attached to this order.');
        }

        $order->update([
            'paid_amount' => $order->total,
            'payment_confirmed_at' => now(),
            'payment_confirmed_by_id' => $admin_user_id,
            'updatedby_id' => $admin_user_id,
            'date_updated' => now(),
        ]);

        return $order->fresh(['payments.paymentMethod', 'user', 'orderSource']);
    }

    protected function resolveWebsitePaymentMethod(string $business_id, ?string $payment_method_id, string $payment_code): ?PaymentMethod
    {
        $query = PaymentMethod::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->where('status', Status::ACTIVE);

        if ($payment_method_id) {
            $method = (clone $query)->where('payment_method_id', $payment_method_id)->first();
        } else {
            $normalized = str_replace(['-', ' '], '_', $payment_code);
            if (in_array($normalized, ['cod', 'cash_on_delivery'], true)) {
                $method = (clone $query)->where('type', 'cod')->first();
            } elseif (in_array($normalized, ['bank', 'bank_transfer', 'banktransfer'], true)) {
                $method = (clone $query)->where('type', 'bank')->where('code', 'BANK')->first();
            } else {
                $method = null;
            }
        }

        if (!$method) {
            return null;
        }

        // Only COD (website-only) and Bank are allowed for website checkout.
        if ($method->type === 'cod') {
            return $method;
        }

        if ($method->type === 'bank' && strtoupper($method->code) === 'BANK') {
            return $method;
        }

        return null;
    }

    protected function formatDeliveryAddress(array $payload): string
    {
        $parts = array_filter([
            $payload['full_name'] ?? $payload['fullName'] ?? null,
            $payload['phone'] ?? null,
            $payload['email'] ?? null,
            $payload['address'] ?? null,
            $payload['city'] ?? null,
            $payload['zip'] ?? $payload['postal_code'] ?? null,
            $payload['country'] ?? null,
        ], fn ($v) => $v !== null && trim((string) $v) !== '');

        $address = implode(', ', $parts);

        if ($address === '') {
            throw new Exception('Delivery address is required.');
        }

        return $address;
    }

    protected function storePaymentProof(UploadedFile $file): string
    {
        $fileName = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
        $path = public_path('uploads/order_payment_proof');

        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $file->move($path, $fileName);

        return $fileName;
    }
}
