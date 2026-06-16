<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlerteSoldeGestionnaire extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public float $soldeActuel,
        public float $seuilSecurite,
        public array $suggestions
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🔴 Alerte — Solde en dessous du seuil de sécurité ({$this->soldeActuel} MAD)",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.alerte_solde',
        );
    }
}