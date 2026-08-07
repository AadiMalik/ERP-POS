<?php

namespace App\Jobs;

use App\Models\PurchaseRequestQuotation;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Email\DTO\EmailData;
use App\Services\Concrete\Email\EmailService;
use App\Services\Concrete\SMS\DTO\SMSData;
use App\Services\Concrete\SMS\SMSService;
use App\Services\Concrete\Whatsapp\DTO\WhatsappData;
use App\Services\Concrete\Whatsapp\WhatsappService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Generates the quotation PDF and pushes it to a single supplier via the
 * requested channels. Dispatched once per supplier so a slow/failing
 * provider for one supplier never blocks another, and so the web request
 * that triggered it never has to wait on PDF rendering or outbound network
 * calls (see PurchaseRequestService::sendQuotation() and
 * PurchaseRequestQuotationService::save()).
 */
class SendPurchaseRequestQuotationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 10;
    public $timeout = 120;

    protected string $purchase_request_quotation_id;
    protected bool $send_email;
    protected bool $send_whatsapp;
    protected bool $send_sms;
    protected ?int $actor_id;

    public function __construct(
        string $purchase_request_quotation_id,
        bool $send_email,
        bool $send_whatsapp,
        bool $send_sms,
        ?int $actor_id
    ) {
        $this->purchase_request_quotation_id = $purchase_request_quotation_id;
        $this->send_email = $send_email;
        $this->send_whatsapp = $send_whatsapp;
        $this->send_sms = $send_sms;
        $this->actor_id = $actor_id;
    }

    public function handle(
        DocumentSendLogService $send_log,
        EmailService $email_service,
        WhatsappService $whatsapp_service,
        SMSService $sms_service
    ) {
        $quotation = PurchaseRequestQuotation::with([
            'supplier',
            'business',
            'createdby',
            'purchaseRequestQuotationDetails.product',
            'purchaseRequestQuotationDetails.productVariation',
            'purchaseRequestQuotationDetails.unit',
        ])->find($this->purchase_request_quotation_id);

        if (!$quotation) {
            return;
        }

        try {
            $pdf = Pdf::loadView('admin.purchase_request_quotation.pdf.pdf', compact('quotation'));

            $fileName = 'quotation_' . $quotation->purchase_request_quotation_no . '.pdf';
            $folder = public_path('uploads/quotations');

            if (!File::exists($folder)) {
                File::makeDirectory($folder, 0755, true);
            }

            $path = $folder . '/' . $fileName;
            $pdf->save($path);

            $quotation->update(['pdf_file' => $fileName]);
        } catch (\Throwable $e) {
            Log::error('Quotation PDF generation failed: ' . $e->getMessage());

            $send_log->log(
                $quotation->business_id,
                'purchase_request_quotation',
                $quotation->purchase_request_quotation_id,
                'print',
                null,
                'failed',
                $e->getMessage(),
                $this->actor_id
            );

            throw $e;
        }

        $send_log->log(
            $quotation->business_id,
            'purchase_request_quotation',
            $quotation->purchase_request_quotation_id,
            'print',
            null,
            'sent',
            null,
            $this->actor_id
        );

        if ($this->send_email) {
            $this->sendEmail($quotation, $fileName, $send_log, $email_service);
        }

        if ($this->send_whatsapp) {
            $this->sendWhatsapp($quotation, $fileName, $send_log, $whatsapp_service);
        }

        if ($this->send_sms) {
            $this->sendSms($quotation, $send_log, $sms_service);
        }
    }

    protected function sendEmail(
        PurchaseRequestQuotation $quotation,
        string $fileName,
        DocumentSendLogService $send_log,
        EmailService $email_service
    ) {
        $recipient = $quotation->supplier->email ?? null;

        try {
            $email = new EmailData([
                'to' => $recipient,
                'subject' => 'Purchase Request Quotation',
                'body' => 'Please find attached quotation.',
                'attachment' => public_path('uploads/quotations/' . $fileName),
                'attachment_name' => 'Quotation.pdf',
            ]);

            $response = $email_service->send($quotation->business_id, $email);

            $send_log->log(
                $quotation->business_id,
                'purchase_request_quotation',
                $quotation->purchase_request_quotation_id,
                'email',
                $recipient,
                $response['status'] ? 'sent' : 'failed',
                $response['status'] ? null : $response['message'],
                $this->actor_id
            );

            if (!$response['status']) {
                Log::error($response['message']);
            }
        } catch (\Throwable $e) {
            Log::error('Quotation email send failed: ' . $e->getMessage());

            $send_log->log(
                $quotation->business_id,
                'purchase_request_quotation',
                $quotation->purchase_request_quotation_id,
                'email',
                $recipient,
                'failed',
                $e->getMessage(),
                $this->actor_id
            );
        }
    }

    protected function sendWhatsapp(
        PurchaseRequestQuotation $quotation,
        string $fileName,
        DocumentSendLogService $send_log,
        WhatsappService $whatsapp_service
    ) {
        $recipient = $quotation->supplier->phone ?? null;

        try {
            $whatsapp = new WhatsappData([
                'phone' => $recipient,
                'message' => 'Please review attached quotation.',
                'attachment' => public_path('uploads/quotations/' . $fileName),
                'file_name' => 'Quotation.pdf',
            ]);

            $response = $whatsapp_service->send($quotation->business_id, $whatsapp);

            $send_log->log(
                $quotation->business_id,
                'purchase_request_quotation',
                $quotation->purchase_request_quotation_id,
                'whatsapp',
                $recipient,
                $response['status'] ? 'sent' : 'failed',
                $response['status'] ? null : $response['message'],
                $this->actor_id
            );

            if (!$response['status']) {
                Log::error($response['message']);
            }
        } catch (\Throwable $e) {
            Log::error('Quotation whatsapp send failed: ' . $e->getMessage());

            $send_log->log(
                $quotation->business_id,
                'purchase_request_quotation',
                $quotation->purchase_request_quotation_id,
                'whatsapp',
                $recipient,
                'failed',
                $e->getMessage(),
                $this->actor_id
            );
        }
    }

    protected function sendSms(
        PurchaseRequestQuotation $quotation,
        DocumentSendLogService $send_log,
        SMSService $sms_service
    ) {
        $recipient = $quotation->supplier->phone ?? null;

        try {
            $productLines = [];

            foreach ($quotation->purchaseRequestQuotationDetails as $index => $item) {
                if ($index == 2) {
                    break;
                }

                $product = $item->product?->name ?? '';
                $variation = $item->productVariation?->name;

                if (!empty($variation)) {
                    $product .= " ({$variation})";
                }

                $product .= " x{$item->requested_quantity}";

                $productLines[] = $product;
            }

            $remaining = count($quotation->purchaseRequestQuotationDetails) - count($productLines);

            $message = "Quotation Request\n";
            $message .= "Business: {$quotation->business->name}\n";
            $message .= "Quotation: {$quotation->purchase_request_quotation_no}\n";
            $message .= implode(", ", $productLines);

            if ($remaining > 0) {
                $message .= " +{$remaining} more";
            }

            $message .= "\n";
            $message .= $quotation->pdf_url;

            $sms = new SMSData([
                'phone' => $recipient,
                'message' => $message,
            ]);

            $response = $sms_service->send($quotation->business_id, $sms);

            $send_log->log(
                $quotation->business_id,
                'purchase_request_quotation',
                $quotation->purchase_request_quotation_id,
                'sms',
                $recipient,
                $response['status'] ? 'sent' : 'failed',
                $response['status'] ? null : $response['message'],
                $this->actor_id
            );

            if (!$response['status']) {
                Log::error($response['message']);
            }
        } catch (\Throwable $e) {
            Log::error('Quotation sms send failed: ' . $e->getMessage());

            $send_log->log(
                $quotation->business_id,
                'purchase_request_quotation',
                $quotation->purchase_request_quotation_id,
                'sms',
                $recipient,
                'failed',
                $e->getMessage(),
                $this->actor_id
            );
        }
    }
}
