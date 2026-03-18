<?php

namespace App\Mail;

use App\Models\Acta;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ActaPdfFinalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Acta $acta,
        public string $pdfAbsolutePath
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Acta ' . $this->acta->acta_number . ' — Documento PDF',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.acta_pdf',
        );
    }

    public function build(): static
    {
        return $this->attach($this->pdfAbsolutePath, [
            'as'   => $this->acta->acta_number . '.pdf',
            'mime' => 'application/pdf',
        ]);
    }
}

