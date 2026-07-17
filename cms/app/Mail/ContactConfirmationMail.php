<?php

namespace App\Mail;

use App\Models\ContactRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactRequest $contact) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Votre demande Faty Style — {$this->contact->reference}");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.contact-confirmation');
    }
}
