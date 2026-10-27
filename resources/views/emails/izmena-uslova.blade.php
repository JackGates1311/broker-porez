<!DOCTYPE html>
<html lang="sr">
    <head>
        <meta charset="utf-8">
        <title>Izmene Uslova korišćenja i Politike privatnosti</title>
    </head>
    <body style="margin:0;padding:24px;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#212529;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td align="center">
                    <table role="presentation" width="480" cellpadding="0" cellspacing="0"
                           style="background:#ffffff;border-radius:8px;padding:32px;">
                        <tr>
                            <td>
                                <h1 style="font-size:20px;margin:0 0 16px;">Zdravo, {{ $korisnik->korisnicko_ime }}!</h1>
                                <p style="margin:0 0 16px;">
                                    Menjamo Uslove korišćenja i Politiku privatnosti za {{ config('app.name') }}.
                                    Nova verzija stupa na snagu <strong>{{ $vaziOd }}</strong>
                                </p>
                                @if ($izmene !== [])
                                    <p style="margin:0 0 8px;">Šta se menja:</p>
                                    <ul style="margin:0 0 16px;padding-left:20px;">
                                        @foreach ($izmene as $izmena)
                                            <li style="margin:0 0 6px;">{{ $izmena }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                                <p style="margin:0 0 24px;">
                                    Ceo tekst:
                                    <a href="{{ route('pravno.uslovi') }}" style="color:#0d6efd;">Uslovi korišćenja</a> i
                                    <a href="{{ route('pravno.politika') }}" style="color:#0d6efd;">Politika privatnosti</a>.
                                </p>
                                <p style="margin:0 0 24px;">
                                    Od {{ $vaziOd }}, pri sledećem otvaranju poreske knjige, zatražićemo da nove uslove potvrdite.
                                    Ako se ne slažete, u delu „Moj nalog” možete preuzeti sve svoje podatke i obrisati nalog.
                                </p>
                                <p style="text-align:center;margin:0 0 24px;">
                                    <a href="{{ route('nalog') }}"
                                       style="display:inline-block;background:#0d6efd;color:#ffffff;text-decoration:none;font-weight:bold;padding:12px 24px;border-radius:6px;">
                                        Otvori Moj nalog
                                    </a>
                                </p>
                                <p style="margin:0;color:#6c757d;font-size:13px;">
                                    Ovo je servisno obaveštenje o vašem nalogu na {{ config('app.name') }}, a ne reklamna poruka.
                                    Pitanja šaljite na {{ config('pravno.rukovalac.email') }}.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
