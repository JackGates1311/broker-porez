<!DOCTYPE html>
<html lang="sr">
    <head>
        <meta charset="utf-8">
        <title>Resetovanje lozinke</title>
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
                                <p style="margin:0 0 24px;">Primili smo zahtev za promenu lozinke na {{ config('app.name') }}. Novu lozinku postavite klikom na dugme:</p>
                                <p style="text-align:center;margin:0 0 24px;">
                                    <a href="{{ $link }}"
                                       style="display:inline-block;background:#0d6efd;color:#ffffff;text-decoration:none;font-weight:bold;padding:12px 24px;border-radius:6px;">
                                        Postavi novu lozinku
                                    </a>
                                </p>
                                <p style="margin:0 0 8px;">Link važi {{ $trajanjeMinuta }} minuta i može se iskoristiti samo jednom.</p>
                                <p style="margin:0 0 16px;color:#6c757d;font-size:13px;word-break:break-all;">
                                    Ako dugme ne radi, otvorite ovu adresu: {{ $link }}
                                </p>
                                <p style="margin:0;color:#6c757d;font-size:13px;">
                                    Ako niste vi tražili promenu lozinke, slobodno ignorišite ovu poruku.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
