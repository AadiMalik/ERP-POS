<?php

namespace App\Jobs;

use App\Models\SubscriptionInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Renders and stores a subscription invoice PDF. Mirrors
 * SendPurchaseRequestQuotationJob's shape - one job per invoice, so a slow
 * PDF render never blocks the web request that triggered invoice creation.
 */
class GenerateSubscriptionInvoicePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 10;
    public $timeout = 120;

    protected string $subscription_invoice_id;

    public function __construct(string $subscription_invoice_id)
    {
        $this->subscription_invoice_id = $subscription_invoice_id;
    }

    public function handle()
    {
        $invoice = SubscriptionInvoice::with(['business', 'package', 'payments'])
            ->find($this->subscription_invoice_id);

        if (!$invoice) {
            return;
        }

        try {
            $pdf = Pdf::loadView('admin.subscriptions.pdf.invoice', compact('invoice'));

            $fileName = 'invoice_' . $invoice->invoice_no . '.pdf';
            $folder = public_path('uploads/subscription_invoices');

            if (!File::exists($folder)) {
                File::makeDirectory($folder, 0755, true);
            }

            $pdf->save($folder . '/' . $fileName);
        } catch (\Throwable $e) {
            Log::error('Subscription invoice PDF generation failed: ' . $e->getMessage());

            throw $e;
        }
    }
}
