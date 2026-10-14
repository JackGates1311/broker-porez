<?php

namespace App\Services\Obrasci;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blade → HTML → PDF (A4 položeno). Zvanični PDF-ovi su uspravne strane sa zarotiranim
 * sadržajem; odštampano je isto. Fontovi iz resources/fonts (Liberation, ćirilica) imaju
 * iste širine slova kao Times (PPDG-3R) i Calibri (Carlito, PP OPO) na obrascima.
 */
class ObrazacPdf
{
    /** @var list<array{string, string, string}> porodica, težina, fajl u resources/fonts */
    private const FONTOVI = [
        ['Liberation Serif', 'normal', 'LiberationSerif-Regular.ttf'],
        ['Liberation Serif', 'bold', 'LiberationSerif-Bold.ttf'],
        ['Carlito', 'normal', 'Carlito-Regular.ttf'],
        ['Carlito', 'bold', 'Carlito-Bold.ttf'],
    ];

    /**
     * @param  array<string, mixed>  $podaci
     */
    public function preuzimanje(string $view, array $podaci, string $nazivFajla): Response
    {
        return response($this->pdf($view, $podaci), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$nazivFajla.'"',
        ]);
    }

    /**
     * @param  array<string, mixed>  $podaci
     */
    public function pdf(string $view, array $podaci): string
    {
        $privremeno = storage_path('app/dompdf');
        File::ensureDirectoryExists($privremeno);

        $opcije = new Options([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => false,
            'tempDir' => $privremeno,
            'fontDir' => $privremeno,
            'fontCache' => $privremeno,
            'chroot' => [resource_path('views/obrasci'), resource_path('fonts')],
        ]);

        $dompdf = new Dompdf($opcije);
        foreach (self::FONTOVI as [$porodica, $tezina, $fajl]) {
            $dompdf->getFontMetrics()->registerFont(
                ['family' => $porodica, 'weight' => $tezina, 'style' => 'normal'],
                resource_path('fonts/'.$fajl),
            );
        }
        $dompdf->loadHtml(view($view, $podaci)->render(), 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }
}
