<?php

namespace App\Mail;

use App\Models\Facture;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RappelFactureClient extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Facture $facture,
        public int $joursRestants
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Rappel — Facture {$this->facture->numero} arrive à échéance dans {$this->joursRestants} jours",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.rappel_facture',
        );
    }
}