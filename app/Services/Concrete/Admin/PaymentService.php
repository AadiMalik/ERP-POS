<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Status;
use App\Enums\SubscriptionEventType;
use App\Models\BusinessSubscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    protected InvoiceService $invoice_service;
    protected SubscriptionHistoryService $history_service;

    public function __construct(InvoiceService $invoice_service, SubscriptionHistoryService $history_service)
    {
        $this->invoice_service = $invoice_service;
        $this->history_service = $history_service;
    }

    /**
     * Records a manual payment against an invoice. Super-Admin-entered
     * payments are confirmed immediately; Business-Admin self-service
     * submissions are recorded pending until a Super Admin approves them.
     */
    public function record(SubscriptionInvoice $invoice, array $data, ?int $actor_id = null, bool $selfService = false): SubscriptionPayment
    {
        $actor_id = $actor_id ?? Auth::id();
        $status = $selfService ? Status::PENDING : Status::CONFIRMED;

        return DB::transaction(function () use ($invoice, $data, $actor_id, $status) {
            $payment = SubscriptionPayment::create([
                'subscription_payment_id' => generateUuid(),
                'subscription_invoice_id' => $invoice->subscription_invoice_id,
                'business_id' => $invoice->business_id,
                'amount' => $data['amount'] ?? $invoice->total,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'payment_reference' => $data['payment_reference'] ?? null,
                'payment_proof' => $data['payment_proof'] ?? null,
                'payment_gateway' => $data['payment_gateway'] ?? null,
                'gateway_transaction_id' => $data['gateway_transaction_id'] ?? null,
                'status' => $status,
                'paid_at' => $data['paid_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'is_deleted' => 0,
                'createdby_id' => $actor_id,
                'date_created' => now(),
            ]);

            $this->history_service->log(
                $invoice->business_id,
                $invoice->business_subscription_id,
                SubscriptionEventType::PAYMENT_RECORDED,
                null,
                $status,
                null,
                null,
                null,
                ['amount' => $payment->amount, 'method' => $payment->payment_method],
                $actor_id
            );

            if ($status === Status::CONFIRMED) {
                $this->applyConfirmedPayment($payment, $invoice);
            }

            return $payment;
        });
    }

    public function approve(SubscriptionPayment $payment, ?int $actor_id = null): void
    {
        $actor_id = $actor_id ?? Auth::id();

        DB::transaction(function () use ($payment, $actor_id) {
            $payment->update([
                'status' => Status::CONFIRMED,
                'updatedby_id' => $actor_id,
                'date_updated' => now(),
            ]);

            $invoice = $payment->invoice;

            $this->applyConfirmedPayment($payment, $invoice);

            $this->history_service->log(
                $payment->business_id,
                $invoice->business_subscription_id ?? null,
                SubscriptionEventType::PAYMENT_APPROVED,
                Status::PENDING,
                Status::CONFIRMED,
                null,
                null,
                null,
                ['amount' => $payment->amount],
                $actor_id
            );
        });
    }

    public function reject(SubscriptionPayment $payment, string $reason, ?int $actor_id = null): void
    {
        $actor_id = $actor_id ?? Auth::id();

        $payment->update([
            'status' => Status::REJECTED,
            'notes' => trim(($payment->notes ? $payment->notes . ' | ' : '') . 'Rejected: ' . $reason),
            'updatedby_id' => $actor_id,
            'date_updated' => now(),
        ]);

        $this->history_service->log(
            $payment->business_id,
            $payment->invoice->business_subscription_id ?? null,
            SubscriptionEventType::PAYMENT_REJECTED,
            Status::PENDING,
            Status::REJECTED,
            null,
            null,
            $reason,
            null,
            $actor_id
        );
    }

    /**
     * Updates the invoice status from its payments and, if the parent
     * subscription was waiting on payment, activates it.
     */
    protected function applyConfirmedPayment(SubscriptionPayment $payment, SubscriptionInvoice $invoice): void
    {
        $this->invoice_service->markPaidFromPayments($invoice->fresh());

        if (!$invoice->business_subscription_id) {
            return;
        }

        $subscription = BusinessSubscription::find($invoice->business_subscription_id);

        if ($subscription && $subscription->status === Status::PAYMENT_PENDING) {
            $subscription->update([
                'status' => Status::ACTIVE,
                'updatedby_id' => $payment->createdby_id,
                'date_updated' => now(),
            ]);
        }
    }
}
