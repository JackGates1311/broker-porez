{{-- Kratko obaveštenje na mestu unosa: šta se čuva iz izvoda i koliko dugo se čuva sam fajl. --}}
<p class="small text-body-secondary mt-2 mb-0">
    Iz izvoda čuvamo samo transakcije potrebne za obračun, u vašem nalogu na serveru u EU.
    @if ($carobnjak ?? false)
        Sam CSV fajl ne čuvamo: briše se po završetku uvoza ili kad odustanete, a najkasnije posle 24 sata.
    @else
        Sam CSV fajl ne čuvamo: briše se odmah po uvozu.
    @endif
    Transakcije možete obrisati u svakom trenutku.
    <a href="{{ route('pravno.politika') }}" target="_blank" rel="noopener">Politika privatnosti</a>
</p>
