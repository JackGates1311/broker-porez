<?php

namespace App\Mail;

use App\Models\Korisnik;
use App\Services\ResetLozinkeService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetLozinkeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Korisnik $korisnik,
        public string $link,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Resetovanje lozinke – '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reset-lozinke',
            with: ['trajanjeMinuta' => ResetLozinkeService::TRAJANJE_LINKA_MINUTA],
        );
    }
}
