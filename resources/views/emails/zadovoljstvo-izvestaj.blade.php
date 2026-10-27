@use('App\Models\OcenaZadovoljstva')
@php($sazetak = $izvestaj['sazetak'])
@php($prethodni = $izvestaj['prethodni'])
@php($broj = fn (?float $vrednost) => $vrednost === null ? '–' : number_format($vrednost, 2, ',', '.'))
<!DOCTYPE html>
<html lang="sr">
    <head>
        <meta charset="utf-8">
        <title>Zadovoljstvo insajdera</title>
    </head>
    <body style="margin:0;padding:24px;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#212529;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td align="center">
                    <table role="presentation" width="600" cellpadding="0" cellspacing="0"
                           style="background:#ffffff;border-radius:8px;padding:32px;">
                        <tr>
                            <td>
                                <h1 style="font-size:20px;margin:0 0 16px;">Zadovoljstvo insajdera za {{ \App\Services\ZadovoljstvoIzvestaj::nazivMeseca($izvestaj['mesec']) }}</h1>

                                <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;margin:0 0 24px;font-size:14px;">
                                    <tr style="color:#6c757d;font-size:12px;">
                                        <td style="padding:4px 0;"></td>
                                        <td style="padding:4px 0;text-align:right;">Ovaj mesec</td>
                                        <td style="padding:4px 0;text-align:right;">Prethodni</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:4px 0;">Odgovora</td>
                                        <td style="padding:4px 0;text-align:right;font-weight:bold;">{{ $sazetak['broj'] }}</td>
                                        <td style="padding:4px 0;text-align:right;">{{ $prethodni['broj'] }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:4px 0;">Prosečna ocena (1–5)</td>
                                        <td style="padding:4px 0;text-align:right;font-weight:bold;">{{ $broj($sazetak['prosek']) }}</td>
                                        <td style="padding:4px 0;text-align:right;">{{ $broj($prethodni['prosek']) }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:4px 0;">Zadovoljnih (ocena 4 ili 5)</td>
                                        <td style="padding:4px 0;text-align:right;font-weight:bold;">{{ $sazetak['zadovoljnih'] ?? '–' }}{{ $sazetak['zadovoljnih'] !== null ? ' %' : '' }}</td>
                                        <td style="padding:4px 0;text-align:right;">{{ $prethodni['zadovoljnih'] ?? '–' }}{{ $prethodni['zadovoljnih'] !== null ? ' %' : '' }}</td>
                                    </tr>
                                </table>

                                <p style="margin:0 0 8px;font-weight:bold;">Raspodela ocena</p>
                                <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;margin:0 0 24px;font-size:14px;">
                                    @foreach (array_reverse($sazetak['raspodela'], true) as $ocena => $koliko)
                                        <tr>
                                            <td style="padding:2px 8px 2px 0;width:24px;">{{ $ocena }}</td>
                                            <td style="padding:2px 0;">
                                                <div style="background:#2b4c9b;height:12px;width:{{ $sazetak['broj'] > 0 ? max(1, (int) round($koliko * 100 / $sazetak['broj'])) : 0 }}%;"></div>
                                            </td>
                                            <td style="padding:2px 0 2px 8px;width:32px;text-align:right;">{{ $koliko }}</td>
                                        </tr>
                                    @endforeach
                                </table>

                                @if ($sazetak['po_povodu'] !== [])
                                    <p style="margin:0 0 8px;font-weight:bold;">Po obrascu</p>
                                    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;margin:0 0 24px;font-size:14px;">
                                        @foreach ($sazetak['po_povodu'] as $povod => $podaci)
                                            <tr>
                                                <td style="padding:2px 0;">{{ OcenaZadovoljstva::POVODI[$povod] }}</td>
                                                <td style="padding:2px 0;text-align:right;">{{ $podaci['broj'] }} odg.</td>
                                                <td style="padding:2px 0;text-align:right;width:60px;font-weight:bold;">{{ $broj($podaci['prosek']) }}</td>
                                            </tr>
                                        @endforeach
                                    </table>
                                @endif

                                <p style="margin:0 0 8px;font-weight:bold;">Komentari ({{ count($izvestaj['komentari']) }})</p>
                                @forelse ($izvestaj['komentari'] as $komentar)
                                    <div style="margin:0 0 12px;padding:8px 12px;border-left:3px solid {{ $komentar['ocena'] <= 2 ? '#b3261e' : '#d5dae2' }};">
                                        <div style="font-size:12px;color:#6c757d;margin:0 0 4px;">
                                            Ocena {{ $komentar['ocena'] }} · {{ $komentar['korisnicko_ime'] ?? 'obrisan nalog' }} ·
                                            {{ OcenaZadovoljstva::POVODI[$komentar['povod']] ?? $komentar['povod'] }} · {{ $komentar['kreirano_at']->format('d.m.Y.') }}
                                        </div>
                                        <div style="white-space:pre-wrap;">{{ $komentar['komentar'] }}</div>
                                    </div>
                                @empty
                                    <p style="margin:0;color:#6c757d;">Nema komentara.</p>
                                @endforelse
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
