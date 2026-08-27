<?php

namespace App\Services\Concrete\Email\DTO;

class EmailData
{
    public string $to;

    public ?string $subject = null;
    // blade view (optional)
    public ?string $view = null;
    // blade data
    public array $data = [];
    // Direct HTML Body (optional)
    public ?string $body = null;
    // attachment
    public ?string $attachment = null;
    public ?string $attachment_name = null;
    public array $cc = [];
    public array $bcc = [];
    public ?string $text_view = null;

    public function __construct(array $data)
    {
        $this->to = $data['to'];
        $this->subject = $data['subject'] ?? null;
        $this->view = $data['view'] ?? null;
        $this->data = $data['data'] ?? [];
        $this->body = $data['body'] ?? null;
        $this->attachment = $data['attachment'] ?? null;
        $this->attachment_name = $data['attachment_name'] ?? null;
        $this->cc = $data['cc'] ?? [];
        $this->bcc = $data['bcc'] ?? [];
        $this->text_view = $data['text_view'] ?? null;
    }
}
