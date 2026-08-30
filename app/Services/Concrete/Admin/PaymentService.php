<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Status;
use App\Enums\SubscriptionEventType;
use App\Models\Business;
use App\Models\BusinessSubscription;
use App\Models\IntroBusinessRegistration;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionRenewalRequest;
use App\Services\Concrete\Email\DTO\EmailData;
use App\Services\Concrete\Email\EmailService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class PaymentService
{
    protected InvoiceService $invoice_service;
    protected SubscriptionHistoryService $history_service;
    protected EmailService $email_service;

    public function __construct(
        InvoiceService $invoice_service,
        SubscriptionHistoryService $history_service,
        EmailService $email_service
    ) {
        $this->invoice_service = $invoice_service;
        $this->history_service = $history_service;
        $this->email_service = $email_service;
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
                $this->applyConfirmedPayment($payment, $invoice, $actor_id, false);
            }

            return $payment;
        });
    }

    public function approve(SubscriptionPayment $payment, ?int $actor_id = null): void
    {
        $actor_id = $actor_id ?? Auth::id();

        if ($payment->status === Status::CONFIRMED) {
            throw new Exception('This payment is already confirmed and cannot be changed.');
        }

        if ($payment->status === Status::REJECTED) {
            throw new Exception('This payment was rejected and cannot be confirmed.');
        }

        if ($payment->status !== Status::PENDING) {
            throw new Exception('Only pending payments can be confirmed.');
        }

        DB::transaction(function () use ($payment, $actor_id) {
            $payment->update([
                'status' => Status::CONFIRMED,
                'updatedby_id' => $actor_id,
                'date_updated' => now(),
            ]);

            $invoice = $payment->invoice;

            $this->applyConfirmedPayment($payment, $invoice, $actor_id, true);

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

        if ($payment->status === Status::CONFIRMED) {
            throw new Exception('This payment is already confirmed and cannot be rejected.');
        }

        if ($payment->status === Status::REJECTED) {
            throw new Exception('This payment was already rejected and cannot be confirmed.');
        }

        if ($payment->status !== Status::PENDING) {
            throw new Exception('Only pending payments can be rejected.');
        }

        $payment->update([
            'status' => Status::REJECTED,
            'notes' => trim(($payment->notes ? $payment->notes . ' | ' : '') . 'Rejected: ' . $reason),
            'updatedby_id' => $actor_id,
            'date_updated' => now(),
        ]);

        $this->syncIntroRegistrationStatus($payment->business_id, 'rejected', $actor_id);

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
     * subscription was waiting on payment, activates it and applies
     * business expiry dates. Optionally emails the business with the PDF.
     */
    protected function applyConfirmedPayment(
        SubscriptionPayment $payment,
        SubscriptionInvoice $invoice,
        ?int $actor_id = null,
        bool $sendEmail = false
    ): void {
        $this->invoice_service->markPaidFromPayments($invoice->fresh());

        if (!$invoice->business_subscription_id) {
            return;
        }

        $subscription = BusinessSubscription::find($invoice->business_subscription_id);

        if ($subscription && $subscription->status === Status::PAYMENT_PENDING) {
            $subscription->update([
                'status' => Status::ACTIVE,
                'payment_status' => 'paid',
                'updatedby_id' => $actor_id ?? $payment->createdby_id,
                'date_updated' => now(),
            ]);
        }

        $business = Business::find($invoice->business_id);
        if ($business && $subscription) {
            $business->update([
                'package_id' => $subscription->package_id,
                'subscription_start' => $subscription->start_at,
                'subscription_end' => $subscription->end_at,
                'current_business_subscription_id' => $subscription->business_subscription_id,
                'grace_period_ends_at' => null,
                'status' => Status::ACTIVE,
                'updatedby_id' => $actor_id ?? $payment->createdby_id,
                'date_updated' => now(),
            ]);
        }

        $this->markLinkedRenewalRequestApproved($invoice, $actor_id);
        $this->syncIntroRegistrationStatus($invoice->business_id, 'activated', $actor_id);

        if ($sendEmail) {
            $this->sendPaymentConfirmedEmail($invoice->fresh(['business', 'package', 'payments']));
        }
    }

    protected function syncIntroRegistrationStatus(?string $businessId, string $status, ?int $actor_id = null): void
    {
        if (!$businessId) {
            return;
        }

        IntroBusinessRegistration::where('business_id', $businessId)
            ->where('is_deleted', 0)
            ->whereIn('status', ['pending', 'under_review', 'approved'])
            ->update([
                'status' => $status,
                'updatedby_id' => $actor_id,
                'date_updated' => now(),
            ]);
    }

    protected function markLinkedRenewalRequestApproved(SubscriptionInvoice $invoice, ?int $actor_id = null): void
    {
        $request = SubscriptionRenewalRequest::where('resulting_subscription_invoice_id', $invoice->subscription_invoice_id)
            ->where('status', Status::PENDING)
            ->where('is_deleted', 0)
            ->first();

        if (!$request) {
            $request = SubscriptionRenewalRequest::where('business_id', $invoice->business_id)
                ->where('status', Status::PENDING)
                ->where('is_deleted', 0)
                ->where(function ($q) use ($invoice) {
                    $q->whereNull('resulting_subscription_invoice_id')
                        ->orWhere('resulting_subscription_invoice_id', $invoice->subscription_invoice_id);
                })
                ->latest('date_created')
                ->first();
        }

        if (!$request) {
            return;
        }

        $request->update([
            'status' => Status::APPROVED,
            'reviewed_by' => $actor_id,
            'reviewed_at' => now(),
            'resulting_subscription_invoice_id' => $invoice->subscription_invoice_id,
            'updatedby_id' => $actor_id,
            'date_updated' => now(),
        ]);

        $this->history_service->log(
            $request->business_id,
            $invoice->business_subscription_id,
            SubscriptionEventType::RENEWAL_APPROVED,
            Status::PENDING,
            Status::APPROVED,
            null,
            null,
            'Approved via payment confirmation',
            null,
            $actor_id
        );
    }

    protected function sendPaymentConfirmedEmail(SubscriptionInvoice $invoice): void
    {
        $business = $invoice->business;
        $to = $business->email ?: $business->owner_email;

        if (!$to) {
            return;
        }

        try {
            $pdf = Pdf::loadView('admin.subscriptions.pdf.invoice', compact('invoice'));
            $dir = storage_path('app/temp');
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
            $path = $dir . DIRECTORY_SEPARATOR . 'invoice_' . $invoice->invoice_no . '.pdf';
            File::put($path, $pdf->output());

            $this->email_service->sendPlatform(new EmailData([
                'to' => $to,
                'subject' => 'Payment confirmed — Invoice ' . $invoice->invoice_no,
                'body' => '<p>Dear ' . e($business->name ?? 'Customer') . ',</p>'
                    . '<p>Your subscription payment has been <strong>confirmed</strong>.</p>'
                    . '<p>Invoice: <strong>' . e($invoice->invoice_no) . '</strong><br>'
                    . 'Amount: <strong>' . e(number_format((float) $invoice->total, 2)) . '</strong></p>'
                    . '<p>Your subscription is now active. The invoice is attached to this email.</p>',
                'attachment' => $path,
                'attachment_name' => 'invoice_' . $invoice->invoice_no . '.pdf',
            ]));

            @unlink($path);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
