<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Exceptions\PaymentGateways\PaymentGatewayException;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Repository\Repository;
use App\Services\PaymentGateways\PaymentGatewayManager;
use App\Services\PaymentGateways\PaymentGatewayProviderRegistry;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

/**
 * Read-only listing/filters + refunds for the CMS's Payment Transactions
 * screen. Clearly separate from POS payment reports - this only ever reads
 * payment_transactions (Website/Mobile gateway payments), never POS tenders.
 */
class PaymentTransactionService
{
    use Auditable;

    protected $model_payment_transaction;
    protected $payment_gateway_manager;

    public function __construct(PaymentGatewayManager $payment_gateway_manager)
    {
        $this->model_payment_transaction = new Repository(new PaymentTransaction());
        $this->payment_gateway_manager = $payment_gateway_manager;
    }

    public function getData($obj)
    {
        $wh = [];
        $orderBy = Filter::ORDERBY;

        if (isset($obj['orderBy']) && $obj['orderBy'] != 0 && $obj['orderBy'] != "") {
            $orderBy = $obj['orderBy'];
        }
        foreach (['business_id', 'payment_gateway_id', 'provider_code', 'environment', 'payment_method_code', 'status', 'currency'] as $field) {
            if (!empty($obj[$field])) {
                $wh[] = [$field, $obj[$field]];
            }
        }
        if (!empty($obj['order_id'])) {
            $wh[] = ['order_id', $obj['order_id']];
        }
        if (!empty($obj['internal_reference'])) {
            $wh[] = ['internal_reference', 'like', '%' . $obj['internal_reference'] . '%'];
        }
        if (!empty($obj['gateway_transaction_id'])) {
            $wh[] = ['gateway_transaction_id', 'like', '%' . $obj['gateway_transaction_id'] . '%'];
        }
        if (!empty($obj['amount'])) {
            $wh[] = ['amount', $obj['amount']];
        }

        $q = $this->model_payment_transaction->getModel()::where($wh)->with(['order', 'paymentGateway']);

        if (!empty($obj['start_date'])) {
            $q->where('date_created', '>=', Carbon::parse($obj['start_date'])->startOfDay());
        }
        if (!empty($obj['end_date'])) {
            $q->where('date_created', '<=', Carbon::parse($obj['end_date'])->endOfDay());
        }

        $allow_roles = [RoleNames::SUPERADMIN, RoleNames::BUSINESSADMIN];
        $q = applyRoleScope($q, $allow_roles)->orderBy('date_created', $orderBy ?: 'desc');

        return DataTables::of($q)
            ->addColumn('order_number', fn ($item) => $item->order?->daily_order_id ?? $item->order_id)
            ->addColumn('gateway', fn ($item) => $item->paymentGateway?->display_name ?? $item->provider_code)
            ->addColumn('status_badge', function ($item) {
                $map = [
                    'paid' => 'success', 'authorized' => 'info', 'refunded' => 'secondary',
                    'partially_refunded' => 'secondary', 'failed' => 'danger', 'cancelled' => 'danger',
                    'expired' => 'danger', 'disputed' => 'warning',
                ];
                $color = $map[$item->status] ?? 'primary';
                return "<span class='badge bg-label-{$color}'>" . ucfirst(str_replace('_', ' ', $item->status)) . '</span>';
            })
            ->addColumn('action', function ($item) {
                $refund = '';
                if ($item->status === 'paid' || $item->status === 'partially_refunded') {
                    $refund = "<button type='button' class='btn btn-icon btn-outline-warning mr-2' id='refundPaymentTransaction' data-id='{$item->payment_transaction_id}'><i class='fa fa-undo'></i></button>";
                }
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     href='" . route('payment-transaction.show', $item->payment_transaction_id) . "'>
                    <i class='fa fa-eye'></i>
                    </a>
                    {$refund}
                ";
            })
            ->rawColumns(['status_badge', 'action'])
            ->make(true);
    }

    public function getById($payment_transaction_id)
    {
        return $this->model_payment_transaction->getModel()::with(['order', 'paymentGateway'])->findOrFail($payment_transaction_id);
    }

    /**
     * Shared refund entrypoint used both by the CMS Payment Transactions
     * screen and OrderReturnService's approval-time refund hook - one
     * implementation of "call the provider, record the result, sync the
     * order" rather than two.
     */
    public function refundTransaction(string $payment_transaction_id, ?float $amount = null): PaymentTransaction
    {
        $original = $this->model_payment_transaction->getModel()::with('paymentGateway')->findOrFail($payment_transaction_id);

        if (!in_array($original->status, ['paid', 'partially_refunded'], true)) {
            throw new Exception('Only a paid transaction can be refunded.');
        }

        $gateway = $original->paymentGateway;
        $provider = PaymentGatewayProviderRegistry::find($original->provider_code);

        if (!$gateway || !$provider || empty($provider['supports_refund'])) {
            throw new Exception('This gateway does not support refunds.');
        }

        $remaining = (float) $original->amount - (float) $original->refunded_amount;
        $amount = $amount ?? $remaining;

        if ($amount <= 0 || $amount > $remaining + 0.001) {
            throw new Exception('Refund amount must be greater than zero and not exceed the remaining paid amount.');
        }

        $adapter = $this->payment_gateway_manager->adapterFor($gateway);

        DB::beginTransaction();
        try {
            $result = $adapter->refund($gateway, $original, $amount);

            $refund_transaction = $this->model_payment_transaction->create([
                'payment_transaction_id' => generateUuid(),
                'business_id' => $original->business_id,
                'order_id' => $original->order_id,
                'user_id' => $original->user_id,
                'payment_gateway_id' => $original->payment_gateway_id,
                'provider_code' => $original->provider_code,
                'environment' => $original->environment,
                'payment_method_code' => $original->payment_method_code,
                'client_platform' => $original->client_platform,
                'internal_reference' => (string) \Illuminate\Support\Str::uuid(),
                'gateway_transaction_id' => $result->gatewayTransactionId,
                'amount' => $amount,
                'currency' => $original->currency,
                'status' => $result->status,
                'verified_at' => now(),
                'verification_method' => 'manual',
                'refund_of_transaction_id' => $original->payment_transaction_id,
                'meta' => $result->meta,
                'createdby_id' => Auth::id(),
                'date_created' => now(),
            ]);

            $new_refunded_total = (float) $original->refunded_amount + $amount;
            $this->model_payment_transaction->update([
                'refunded_amount' => $new_refunded_total,
                'status' => $new_refunded_total >= (float) $original->amount - 0.001 ? 'refunded' : 'partially_refunded',
                'date_updated' => now(),
            ], $original->payment_transaction_id);

            $order = Order::find($original->order_id);
            if ($order) {
                $order->update([
                    'paid_amount' => max((float) $order->paid_amount - $amount, 0),
                    'updatedby_id' => Auth::id(),
                    'date_updated' => now(),
                ]);
            }

            $this->logActivity('payment-transaction', $refund_transaction->payment_transaction_id, 'refunded', null, ['amount' => $amount, 'original' => $original->payment_transaction_id], null, $original->business_id);

            DB::commit();
            return $refund_transaction;
        } catch (PaymentGatewayException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
