<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ContactFormSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $submission)
    {
    }

    public function envelope(): Envelope
    {
        $subject = filled($this->submission['subject'] ?? null)
            ? (string) $this->submission['subject']
            : 'Iletisim formu';

        return new Envelope(
            replyTo: [
                new Address(
                    (string) $this->submission['email'],
                    (string) $this->submission['name'],
                ),
            ],
            subject: 'ADH Iletisim Formu: '.Str::limit($subject, 80, ''),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-form-submitted',
            with: [
                'submission' => $this->submission,
            ],
        );
    }
}
