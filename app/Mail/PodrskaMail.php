<?php

namespace App\Mail;

use App\Models\PorukaPodrske;
use App\Models\PrilogPorukePodrske;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Poruka insajdera za sanduče podrške. From ostaje MAIL_FROM_ADDRESS (domen verifikovan u Brevu),
 * a Reply-To je korisnik, pa se na mejl odgovara direktno njemu.
 */
class PodrskaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PorukaPodrske $poruka,
    ) {}

    public function envelope(): Envelope
    {
        $korisnik = $this->poruka->korisnik;

        return new Envelope(
            replyTo: [new Address($korisnik->email, $korisnik->korisnicko_ime)],
            subject: "[Insajder #{$this->poruka->id}] ".($this->poruka->naslov ?? 'Povratna informacija'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.podrska',
            with: [
                'korisnik' => $this->poruka->korisnik,
                'prilozi' => $this->poruka->prilozi,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return $this->poruka->prilozi
            ->map(fn (PrilogPorukePodrske $prilog) => Attachment::fromStorageDisk('local', $prilog->putanja)
                ->as($prilog->naziv)
                ->withMime($prilog->mime))
            ->all();
    }
}
