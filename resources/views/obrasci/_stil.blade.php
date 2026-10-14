{{--
    Mere su u mm, izmerene na zvaničnim obrascima (A4 položeno, 297 × 210 mm).
    $font: 'serif' za PPDG-3R (Times na skenu → Liberation Serif, iste širine slova),
    'sans' za PP OPO (Calibri → Carlito, iste širine slova); fontove registruje ObrazacPdf.
    $k / $kv: širina i visina kućice, $kr: razmak između grupa kućica, $fk: slova u kućicama,
    $fl / $flr / $fls: veličina natpisa ispod kućica (дд мм гггг), razmak do kućica u mm i razmak
    između slova u pt (na skenu PPDG-3R natpisi su razvučeni).
--}}
<style>
    @page { margin: {{ $margine }}; }
    * { font-family: {{ $font === 'serif' ? 'Liberation Serif' : 'Carlito' }}; }
    body { margin: 0; font-size: {{ $fs }}pt; color: #000; line-height: 1.15; }
    .prelom { page-break-before: always; }

    table.mreza { border-collapse: collapse; }
    /* Samo ćelije same mreže, ne i ugnežđene kućice/polja. */
    table.mreza > tbody > tr > th, table.mreza > tbody > tr > td, table.mreza > tr > th, table.mreza > tr > td { border: 0.75pt solid #000; padding: 0 0.5mm; vertical-align: middle; text-align: center; font-weight: normal; }
    table.mreza > tbody > tr > .levo, table.mreza > tr > .levo { text-align: left; }
    table.mreza > tbody > tr > th, table.mreza > tr > th { line-height: 1.05; }

    .deo { padding: 0 0 0.6mm 0.4mm; line-height: 1; white-space: nowrap; }

    /* Kućice |_|: ivica levo i dole, poslednja u grupi i desno. */
    table.kt { border-collapse: separate; border-spacing: 0; margin: 0 auto; }
    table.kt td { border: 0; padding: 0; width: {{ $k }}mm; height: {{ $kv }}mm; border-left: 0.75pt solid #000; border-bottom: 0.75pt solid #000; text-align: center; vertical-align: bottom; font-size: {{ $fk }}pt; line-height: 1; }
    table.kt td.kraj { border-right: 0.75pt solid #000; }
    .levo > table.kt { margin-left: 0; }
    table.kt td.r { border: 0; }
    table.kt td.l { border: 0; width: auto; height: auto; padding-top: {{ $flr }}mm; font-size: {{ $fl }}pt; line-height: 0.8; letter-spacing: {{ $fls ?? 0 }}pt; vertical-align: top; }

    /* Polje ⌊ ⌋. */
    table.pt { border-collapse: separate; border-spacing: 0; margin: 0 auto; }
    table.pt td { border: 0; padding: 0 0.7mm; height: {{ $kv }}mm; border-left: 0.75pt solid #000; border-right: 0.75pt solid #000; border-bottom: 0.75pt solid #000; text-align: left; vertical-align: bottom; line-height: 1; white-space: nowrap; }
    table.pt td.desno { text-align: right; }

</style>
