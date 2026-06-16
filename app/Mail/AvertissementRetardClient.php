<?php

namespace App\Mail;

use App\Models\Facture;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AvertissementRetardClient extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Facture $facture,
        public int $joursRetard
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "⚠️ Avertissement — Facture {$this->facture->numero} en retard de {$this->joursRetard} jours",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.avertissement_retard',
        );
    }
}