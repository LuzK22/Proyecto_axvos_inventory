<?php

namespace App\Mail;

use App\Models\ActaSignature;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ActaSigningRequest extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ActaSignature $signature
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Acta ' . $this->signature->acta->acta_number . ' — Firma requerida',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.acta_signing',
        );
    }
}
