<!DOCTYPE html>
<html lang="sr">
    <head>
        <meta charset="utf-8">
        <title>Poruka insajdera</title>
    </head>
    <body style="margin:0;padding:24px;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#212529;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td align="center">
                    <table role="presentation" width="600" cellpadding="0" cellspacing="0"
                           style="background:#ffffff;border-radius:8px;padding:32px;">
                        <tr>
                            <td>
                                <h1 style="font-size:20px;margin:0 0 16px;">{{ $poruka->naslov ?? 'Povratna informacija' }}</h1>
                                <div style="margin:0 0 24px;white-space:pre-wrap;line-height:1.5;">{{ $poruka->poruka }}</div>

                                @if ($prilozi->isNotEmpty())
                                    <p style="margin:0 0 8px;font-weight:bold;">Prilozi ({{ $prilozi->count() }})</p>
                                    <ul style="margin:0 0 24px;padding-left:20px;">
                                        @foreach ($prilozi as $prilog)
                                            <li>{{ $prilog->naziv }} ({{ max(1, (int) round($prilog->velicina / 1024)) }} KB)</li>
                                        @endforeach
                                    </ul>
                                @endif

                                <table role="presentation" cellpadding="0" cellspacing="0"
                                       style="width:100%;border-top:1px solid #dee2e6;font-size:13px;color:#6c757d;">
                                    <tr><td style="padding:8px 8px 2px 0;width:140px;">Poruka</td><td style="padding:8px 0 2px;">#{{ $poruka->id }}, {{ $poruka->kreirano_at?->timezone('Europe/Belgrade')->format('d.m.Y. H:i') }}</td></tr>
                                    <tr><td style="padding:2px 8px 2px 0;">Korisnik</td><td style="padding:2px 0;">{{ $korisnik->korisnicko_ime }} (#{{ $korisnik->id }})</td></tr>
                                    <tr><td style="padding:2px 8px 2px 0;">Email</td><td style="padding:2px 0;">{{ $korisnik->email }}</td></tr>
                                    @if ($poruka->stranica)
                                        <tr><td style="padding:2px 8px 2px 0;">Stranica</td><td style="padding:2px 0;word-break:break-all;">{{ $poruka->stranica }}</td></tr>
                                    @endif
                                    @if ($poruka->user_agent)
                                        <tr><td style="padding:2px 8px 2px 0;">Pregledač</td><td style="padding:2px 0;">{{ $poruka->user_agent }}</td></tr>
                                    @endif
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
