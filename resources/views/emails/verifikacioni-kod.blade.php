<!DOCTYPE html>
<html lang="sr">
    <head>
        <meta charset="utf-8">
        <title>Verifikacioni kod</title>
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
                                <p style="margin:0 0 24px;">Vaš kod za potvrdu email adrese na {{ config('app.name') }} je:</p>
                                <p style="font-size:32px;font-weight:bold;letter-spacing:8px;text-align:center;margin:0 0 24px;">
                                    {{ $kod }}
                                </p>
                                <p style="margin:0 0 8px;">Kod važi {{ $trajanjeMinuta }} minuta.</p>
                                <p style="margin:0;color:#6c757d;font-size:13px;">
                                    Ako niste vi kreirali nalog, slobodno ignorišite ovu poruku.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
