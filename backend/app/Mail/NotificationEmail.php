<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email transactionnel simple (titre + contenu).
 *
 * En développement, Mailpit intercepte les messages (http://localhost:8025) ;
 * en test, MAIL_MAILER=array et Mail::fake() permet de les vérifier.
 */
class NotificationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $sujet,
        public string $contenu,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->sujet);
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p>'.nl2br(e($this->contenu)).'</p>',
        );
    }
}
