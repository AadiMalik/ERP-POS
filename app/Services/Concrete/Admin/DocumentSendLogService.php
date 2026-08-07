<?php

namespace App\Services\Concrete\Admin;

use App\Models\DocumentSendLog;

class DocumentSendLogService
{
    /**
     * Record the outcome of a single print/email/whatsapp/sms send attempt
     * for a document. Called from queued jobs, so the acting user id must be
     * passed explicitly rather than read from Auth (no web session in a
     * queue worker).
     */
    public function log(
        string $business_id,
        string $module,
        string $record_id,
        string $channel,
        ?string $recipient,
        string $status,
        ?string $message,
        ?int $createdby_id
    ) {
        return DocumentSendLog::create([
            'document_send_log_id' => generateUuid(),
            'business_id'          => $business_id,
            'module'               => $module,
            'record_id'            => $record_id,
            'channel'              => $channel,
            'recipient'            => $recipient,
            'status'               => $status,
            'message'              => $message,
            'createdby_id'         => $createdby_id,
            'date_created'         => now(),
        ]);
    }
}
