{{-- Šalje formu sa šiframa (#ppopo-forma) na adresu PP OPO obrasca ove dividende. --}}
{{-- Tooltip je na omotaču: onemogućeno Bootstrap dugme ne prima miš, pa svoj title ne bi pokazalo. --}}
@php($opis = $red->bez_kursa ? 'Nije moguće bez NBS kursa' : 'Preuzmi PP OPO (PDF)')
<span class="d-inline-block" title="{{ $opis }}">
    <button type="submit" form="ppopo-forma" class="preuzmi-pdf btn btn-sm border-0 p-1 lh-1"
            formaction="{{ route('izvoz.pp-opo', $red->transakcija->id) }}"
            aria-label="{{ $opis }}"
            @disabled($red->bez_kursa)>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-arrow-down" viewBox="0 0 16 16" aria-hidden="true">
            <path d="M8.5 6.5a.5.5 0 0 0-1 0v3.793L6.354 9.146a.5.5 0 1 0-.708.708l2 2a.5.5 0 0 0 .708 0l2-2a.5.5 0 0 0-.708-.708L8.5 10.293z"/>
            <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2M9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z"/>
        </svg>
    </button>
</span>
