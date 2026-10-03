<?php

namespace App\Services\Obrasci;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blade → HTML → PDF (A4 položeno). DejaVu Sans pokriva ćirilicu.
 */
class ObrazacPdf
{
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
            'fontCache' => $privremeno,
            'chroot' => resource_path('views/obrasci'),
        ]);

        $dompdf = new Dompdf($opcije);
        $dompdf->loadHtml(view($view, $podaci)->render(), 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }
}
