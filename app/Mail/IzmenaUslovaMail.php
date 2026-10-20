<?php

namespace App\Mail;

use App\Models\Korisnik;
use App\Support\Pravno;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Obaveštenje o novoj verziji Uslova korišćenja i Politike privatnosti. Servisna poruka
 * o ugovoru, ne reklama, pa ide svim korisnicima bez obzira na saglasnost za marketing.
 */
class IzmenaUslovaMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<string>  $izmene
     */
    public function __construct(
        public Korisnik $korisnik,
        public array $izmene,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Izmene Uslova korišćenja i Politike privatnosti – '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.izmena-uslova',
            with: ['vaziOd' => Pravno::vaziOdPrikaz()],
        );
    }
}
