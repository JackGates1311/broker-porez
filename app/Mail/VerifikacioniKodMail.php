<?php

namespace App\Mail;

use App\Models\Korisnik;
use App\Services\VerifikacijaEmailaService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifikacioniKodMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Korisnik $korisnik,
        public string $kod,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Vaš verifikacioni kod – '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verifikacioni-kod',
            with: ['trajanjeMinuta' => VerifikacijaEmailaService::TRAJANJE_KODA_MINUTA],
        );
    }
}
