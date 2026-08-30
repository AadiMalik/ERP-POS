<?php

namespace App\Services\Concrete\Admin;

use App\Enums\RoleNames;
use App\Enums\Status;
use App\Jobs\GenerateSubscriptionInvoicePdfJob;
use App\Models\BusinessSubscription;
use App\Models\Package;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionSetting;
use Illuminate\Support\Facades\Auth;

class InvoiceService
{
    /**
     * Creates the billing artifact for a subscription period. $billing
     * accepts: discount (default 0), discount_type (percentage|fixed,
     * default percentage), tax (percentage, default 0), due_date, notes,
     * request_type (new|renew).
     * tax is always treated as a percentage of the post-discount subtotal -
     * there's no tax_type column, mirroring how business_subscriptions was
     * originally shaped.
     */
    public function create(BusinessSubscription $subscription, Package $package, array $billing = []): SubscriptionInvoice
    {
        $billing_cycle = $subscription->billing_cycle ?? $package->duration_type;
        $resolved = $package->priceForCycle($billing_cycle);
        if ($resolved === null) {
            throw new \Exception('Package price is not configured for the selected billing cycle.');
        }
        $subtotal = (float) $resolved;
        $discount = (float) ($billing['discount'] ?? 0);
        $discount_type = $billing['discount_type'] ?? 'percentage';
        $tax = (float) ($billing['tax'] ?? 0);

        $discount_amount = $discount_type === 'percentage'
            ? round($subtotal * $discount / 100, 2)
            : round($discount, 2);

        $taxable = max($subtotal - $discount_amount, 0);
        $tax_amount = round($taxable * $tax / 100, 2);
        $total = round($taxable + $tax_amount, 2);

        $requestType = $billing['request_type'] ?? null;
        if (!in_array($requestType, ['new', 'renew'], true)) {
            $requestType = $subscription->renewed_from_subscription_id ? 'renew' : 'new';
        }

        $attempts = 0;
        begin:
        $attempts++;
        try {
            $invoice = SubscriptionInvoice::create([
                'subscription_invoice_id' => generateUuid(),
                'business_id' => $subscription->business_id,
                'business_subscription_id' => $subscription->business_subscription_id,
                'package_id' => $package->package_id,
                'invoice_no' => $this->nextInvoiceNumber(),
                'billing_cycle' => $billing_cycle,
                'period_start' => $subscription->start_at,
                'period_end' => $subscription->end_at,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'discount_type' => $discount_type,
                'discount_amount' => $discount_amount,
                'tax' => $tax,
                'tax_amount' => $tax_amount,
                'total' => $total,
                'status' => Status::UNPAID,
                'due_date' => $billing['due_date'] ?? $subscription->start_at,
                'notes' => $billing['notes'] ?? null,
                'request_type' => $requestType,
                'is_deleted' => 0,
                'createdby_id' => Auth::id(),
                'date_created' => now(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Concurrent mint of the same invoice_no — retry a few times.
            if ($attempts < 5 && str_contains($e->getMessage(), 'subscription_invoices_invoice_no_unique')) {
                goto begin;
            }
            throw $e;
        }

        return $invoice;
    }

    public function generatePdf(SubscriptionInvoice $invoice): string
    {
        GenerateSubscriptionInvoicePdfJob::dispatch($invoice->subscription_invoice_id);

        return 'subscription_invoice_' . $invoice->invoice_no . '.pdf';
    }

    public function void(SubscriptionInvoice $invoice, string $reason, int $actor_id): void
    {
        $invoice->update([
            'status' => Status::VOID,
            'notes' => trim(($invoice->notes ? $invoice->notes . ' | ' : '') . 'Voided: ' . $reason),
            'updatedby_id' => $actor_id,
            'date_updated' => now(),
        ]);

        app(SubscriptionHistoryService::class)->log(
            $invoice->business_id,
            $invoice->business_subscription_id,
            \App\Enums\SubscriptionEventType::INVOICE_VOIDED,
            $invoice->status,
            Status::VOID,
            null,
            null,
            $reason,
            null,
            $actor_id
        );
    }

    /**
     * Soft-deletes an invoice and its payments. If the linked subscription is
     * still payment_pending, it is cancelled so it cannot become current later.
     */
    public function softDelete(SubscriptionInvoice $invoice, ?int $actor_id = null): void
    {
        $actor_id = $actor_id ?? Auth::id();

        $invoice->update([
            'is_deleted' => 1,
            'deletedby_id' => $actor_id,
            'date_deleted' => now(),
        ]);

        SubscriptionPayment::where('subscription_invoice_id', $invoice->subscription_invoice_id)
            ->where('is_deleted', 0)
            ->update([
                'is_deleted' => 1,
                'deletedby_id' => $actor_id,
                'date_deleted' => now(),
            ]);

        if ($invoice->business_subscription_id) {
            $subscription = BusinessSubscription::find($invoice->business_subscription_id);
            if ($subscription && $subscription->status === Status::PAYMENT_PENDING) {
                $subscription->update([
                    'status' => Status::CANCELLED,
                    'cancelled_at' => now(),
                    'updatedby_id' => $actor_id,
                    'date_updated' => now(),
                ]);
            }
        }
    }

    /**
     * Sums confirmed payments against an invoice, updates its status, and
     * mirrors the result back into business_subscriptions.payment_status so
     * that pre-existing column stays meaningfully populated for any code
     * that still reads it directly.
     */
    public function markPaidFromPayments(SubscriptionInvoice $invoice): void
    {
        $paid = $invoice->payments()->where('status', Status::CONFIRMED)->where('is_deleted', 0)->sum('amount');

        if ($paid <= 0) {
            $status = Status::UNPAID;
        } elseif ($paid >= (float) $invoice->total) {
            $status = Status::PAID;
        } else {
            $status = Status::PARTIALLY_PAID;
        }

        $invoice->update(['status' => $status]);

        if ($invoice->business_subscription_id) {
            BusinessSubscription::where('business_subscription_id', $invoice->business_subscription_id)
                ->update(['payment_status' => $status === Status::PAID ? 'paid' : 'unpaid']);
        }
    }

    public function pendingPaymentCount(): int
    {
        return SubscriptionPayment::where('status', Status::PENDING)
            ->where('is_deleted', 0)
            ->whereHas('invoice', function ($q) {
                $q->where('is_deleted', 0);
            })
            ->count();
    }

    public function notifySuperAdminPending(SubscriptionInvoice $invoice): void
    {
        $typeLabel = ($invoice->request_type === 'renew') ? 'Renewal' : 'New subscription';
        $businessName = $invoice->business->name ?? 'a business';

        try {
            app(NotificationDispatchService::class)->dispatch(
                'subscription_payment_pending',
                $invoice->business_id,
                null,
                $typeLabel . ' payment pending',
                $typeLabel . ' payment for ' . $businessName . ' (invoice ' . $invoice->invoice_no . ') awaits confirmation.',
                'subscription_invoice',
                $invoice->subscription_invoice_id,
                route('subscription-invoices.show', $invoice->subscription_invoice_id),
                [
                    'request_type' => $invoice->request_type,
                    'invoice_no' => $invoice->invoice_no,
                ],
                'pending_' . $invoice->subscription_invoice_id,
                [RoleNames::SUPERADMIN]
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function nextInvoiceNumber(): string
    {
        $prefix = SubscriptionSetting::current()->invoice_prefix ?: 'SUB-INV';

        // Use highest numeric suffix (not just latest date_created) and lock
        // so concurrent registrations cannot mint the same invoice_no.
        $last = SubscriptionInvoice::where('invoice_no', 'like', $prefix . '-%')
            ->orderByDesc('invoice_no')
            ->lockForUpdate()
            ->first();

        $next_number = 1;

        if ($last) {
            $next_number = (int) substr($last->invoice_no, strrpos($last->invoice_no, '-') + 1) + 1;
        }

        return sprintf('%s-%06d', $prefix, $next_number);
    }
}
