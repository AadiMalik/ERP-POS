<?php

namespace App\Services\Concrete\Api;

use App\Models\Order;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\File;

/**
 * Storefront "My Orders" reads against the shared ERP orders table.
 * Only returns orders owned by the authenticated user for the given business.
 */
class CustomerOrderService
{
    /**
     * Map ERP order statuses onto the storefront UI status vocabulary.
     */
    public function mapStatus(?string $status): string
    {
        $status = strtolower((string) $status);

        return match ($status) {
            'draft', 'hold' => 'processing',
            'posted', 'completed', 'confirmed' => 'delivered',
            'shipped' => 'shipped',
            'delivered' => 'delivered',
            'out_for_delivery' => 'out_for_delivery',
            'cancelled', 'void' => 'cancelled',
            'returned', 'refunded' => 'returned',
            'return_requested' => 'return_requested',
            default => $status ?: 'processing',
        };
    }

    public function list(int $user_id, string $business_id, array $params = []): array
    {
        $page = max(1, (int) ($params['page'] ?? 1));
        $per_page = min(50, max(1, (int) ($params['per_page'] ?? 20)));
        $status = $params['status'] ?? null;

        $query = Order::with([
            'details.product.productImages',
            'details.productVariation',
            'payments.paymentMethod',
            'statusHistory',
            'user',
            'orderSource',
            'orderType',
            'saleType',
        ])
            ->where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->where('is_deleted', 0)
            // Website hold orders must appear in My Orders immediately after checkout.
            // Exclude only POS-style drafts that the customer never placed.
            ->where(function ($q) {
                $q->where('status', '!=', 'draft')
                    ->orWhereHas('orderSource', function ($s) {
                        $s->where('code', 'WEBSITE');
                    });
            })
            ->orderByDesc('date_created');

        if ($status && $status !== 'all') {
            $erp_statuses = $this->erpStatusesForFilter($status);
            if (!empty($erp_statuses)) {
                $query->whereIn('status', $erp_statuses);
            }
        }

        $paginator = $query->paginate($per_page, ['*'], 'page', $page);

        return [
            'data' => collect($paginator->items())->map(fn ($o) => $this->mapOrder($o))->values()->all(),
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    public function find(int $user_id, string $business_id, string $order_id): array
    {
        $order = Order::with([
            'details.product.productImages',
            'details.productVariation',
            'payments.paymentMethod',
            'statusHistory',
            'user',
            'branch',
            'orderSource',
            'orderType',
            'saleType',
        ])
            ->where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->where('order_id', $order_id)
            ->where('is_deleted', 0)
            ->first();

        if (!$order) {
            throw new Exception('Order not found.');
        }

        return $this->mapOrder($order, true);
    }

    /**
     * Public track-order lookup. Requires order number + email or phone that
     * matches the order customer - never returns another customer's order.
     */
    public function track(string $business_id, string $order_number, ?string $email = null, ?string $phone = null): array
    {
        $email = $email ? strtolower(trim($email)) : null;
        $phone = $phone ? preg_replace('/\s+/', '', trim($phone)) : null;

        if (!$email && !$phone) {
            throw new Exception('Email or phone is required to track an order.');
        }

        $order = Order::with([
            'details.product.productImages',
            'details.productVariation',
            'payments.paymentMethod',
            'statusHistory',
            'user',
            'orderSource',
            'orderType',
            'saleType',
        ])
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->where(function ($q) use ($order_number) {
                $q->where('daily_order_id', $order_number)
                    ->orWhere('order_id', $order_number);
            })
            ->first();

        if (!$order || !$order->user) {
            throw new Exception('Order not found.');
        }

        $user_email = strtolower((string) ($order->user->email ?? ''));
        $user_phone = preg_replace('/\s+/', '', (string) ($order->user->phone ?? ''));

        $email_ok = $email && $user_email && hash_equals($user_email, $email);
        $phone_ok = $phone && $user_phone && (
            hash_equals($user_phone, $phone)
            || str_ends_with($user_phone, $phone)
            || str_ends_with($phone, $user_phone)
        );

        if (!$email_ok && !$phone_ok) {
            throw new Exception('Order not found.');
        }

        return $this->mapOrder($order, true);
    }

    protected function erpStatusesForFilter(string $filter): array
    {
        return match ($filter) {
            'processing' => ['draft', 'hold', 'processing'],
            'shipped' => ['shipped'],
            'out_for_delivery' => ['out_for_delivery'],
            'delivered' => ['posted', 'completed', 'confirmed', 'delivered'],
            'cancelled' => ['cancelled', 'void', 'returned', 'refunded', 'return_requested'],
            default => [],
        };
    }

    protected function mapOrder(Order $order, bool $detailed = false): array
    {
        $items = $order->details->map(function ($detail) {
            $images = $detail->product?->productImages?->pluck('image_url')->values()->all() ?? [];

            return [
                'productId' => $detail->product_id,
                'slug' => $detail->product->slug ?? null,
                'name' => $detail->product->name ?? 'Product',
                'image' => $images[0] ?? null,
                'variation' => $detail->productVariation->name ?? null,
                'product_variation_id' => $detail->product_variation_id,
                'qty' => (float) $detail->quantity,
                'unitPrice' => (float) $detail->final_unit_price,
                'lineTotal' => (float) $detail->total,
            ];
        })->values()->all();

        $paid = (float) ($order->paid_amount ?? 0);
        $total = (float) ($order->total ?? 0);
        $payment_status = 'pending';
        if ($paid <= 0) {
            $payment_status = 'pending';
        } elseif ($paid + 0.001 >= $total) {
            $payment_status = 'paid';
        } else {
            $payment_status = 'partially_paid';
        }

        if (in_array($this->mapStatus($order->status), ['cancelled', 'returned'], true)) {
            $payment_status = $paid > 0 ? 'refunded' : $payment_status;
        }

        $history = $order->statusHistory
            ->sortBy('date_created')
            ->map(function ($h) {
                return [
                    'status' => $this->mapStatus($h->to_status ?? $h->status ?? null),
                    'at' => optional($h->date_created)->toIso8601String(),
                ];
            })
            ->values()
            ->all();

        if (empty($history)) {
            $history[] = [
                'status' => $this->mapStatus($order->status),
                'at' => optional($order->date_created)->toIso8601String(),
            ];
        }

        $payment_method = null;
        $first_payment = $order->payments->first();
        if ($first_payment && $first_payment->paymentMethod) {
            $pm = $first_payment->paymentMethod;
            $payment_method = [
                'id' => $pm->payment_method_id,
                'code' => $pm->type === 'cod' ? 'cod' : ($pm->type === 'bank' ? 'bank_transfer' : strtolower($pm->code)),
                'name' => $pm->name,
                'type' => $pm->type,
            ];
        }

        $payload = [
            'id' => $order->order_id,
            'orderNumber' => $order->daily_order_id ?: $order->order_id,
            'userId' => $order->user_id,
            'email' => $order->user->email ?? null,
            'items' => $items,
            'subtotal' => (float) $order->subtotal,
            'discount' => (float) ($order->discount_amount ?? $order->discount ?? 0),
            'shipping' => 0,
            'tax' => (float) ($order->tax_amount ?? $order->tax ?? 0),
            'total' => $total,
            'paymentStatus' => $payment_status,
            'paymentMethod' => $payment_method,
            'orderType' => $order->orderType->name ?? null,
            'orderSource' => $order->orderSource->code ?? null,
            'saleType' => $order->saleType->name ?? null,
            'branchId' => $order->branch_id,
            'notes' => $order->notes,
            'status' => $this->mapStatus($order->status),
            'erpStatus' => $order->status,
            'statusHistory' => $history,
            'placedAt' => optional($order->date_created ?? $order->order_date)->toIso8601String(),
            'deliveryAddress' => $order->delivery_address,
            'hasPaymentProof' => !empty($order->payment_proof),
            'paymentConfirmedAt' => optional($order->payment_confirmed_at)->toIso8601String(),
        ];

        if ($detailed) {
            $payload['payments'] = $order->payments->map(function ($p) {
                return [
                    'method' => $p->paymentMethod->name ?? null,
                    'type' => $p->paymentMethod->type ?? null,
                    'amount' => (float) ($p->amount ?? 0),
                    'reference' => $p->reference_no,
                ];
            })->values()->all();

            if (!empty($order->payment_proof)) {
                $payload['paymentProofUrl'] = asset('public/uploads/order_payment_proof/' . $order->payment_proof);
            }
        }

        return $payload;
    }
}
