<?php

namespace App\Mail;

use App\Services\Concrete\Email\DTO\EmailData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommonMail extends Mailable
{

    public EmailData $emailData;

    public function __construct(EmailData $emailData)
    {

        $this->emailData = $emailData;
    }

    public function build()
    {
        $mail = $this->subject($this->emailData->subject);
        $data = $this->emailData->data;

        // Blade View
        if (!empty($this->emailData->view)) {

            $mail->view(
                $this->emailData->view,
                $data
            );
        }
        // HTML Body
        elseif (!empty($this->emailData->body)) {

            $mail->html($this->emailData->body);
        }

        if (!empty($this->emailData->text_view)) {
            $mail->text($this->emailData->text_view, $data);
        }

        if (!empty($this->emailData->attachment)) {

            $mail->attach(
                $this->emailData->attachment,
                [
                    'as' => $this->emailData->attachment_name
                ]
            );
        }

        return $mail;
    }
}
