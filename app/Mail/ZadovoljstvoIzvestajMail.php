<?php

namespace App\Mail;

use App\Services\ZadovoljstvoIzvestaj;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Mesečna statistika zadovoljstva insajdera za sanduče podrške (ZadovoljstvoIzvestaj::za()).
 */
class ZadovoljstvoIzvestajMail extends Mailable
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $izvestaj
     */
    public function __construct(
        public array $izvestaj,
    ) {}

    public function envelope(): Envelope
    {
        $sazetak = $this->izvestaj['sazetak'];

        return new Envelope(
            subject: 'Zadovoljstvo insajdera za '.ZadovoljstvoIzvestaj::nazivMeseca($this->izvestaj['mesec'])
                .' (ocena '.number_format((float) $sazetak['prosek'], 2, ',', '.').", {$sazetak['broj']} odgovora)",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.zadovoljstvo-izvestaj');
    }
}
