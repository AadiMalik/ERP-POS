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

    public EmailData $email;

    public function __construct(EmailData $email)
    {

        $this->email = $email;
    }

    public function build()
    {
        $mail = $this
            ->subject($this->email->subject)
            ->view('emails.common')
            ->with([
                'body' => $this->email->body
            ]);
        if ($this->email->attachment) {
            $mail->attach(
                $this->email->attachment,
                [
                    'as' => $this->email->attachmentName
                ]
            );
        }
        return $mail;
    }
}
