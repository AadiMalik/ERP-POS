<?php

namespace App\Services\Concrete\Email\DTO;

class EmailData
{
    public string $to;
    public string $subject;
    public string $body;
    public ?string $attachment = null;
    public ?string $attachmentName = null;
    public array $cc = [];
    public array $bcc = [];
    public function __construct(array $data)
    {
        $this->to = $data['to'];
        $this->subject = $data['subject'];
        $this->body = $data['body'];
        $this->attachment = $data['attachment'] ?? null;
        $this->attachmentName = $data['attachment_name'] ?? null;
        $this->cc = $data['cc'] ?? [];
        $this->bcc = $data['bcc'] ?? [];
    }
}
