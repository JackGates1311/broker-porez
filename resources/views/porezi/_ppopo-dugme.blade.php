{{-- Šalje formu sa šiframa (#ppopo-forma) na adresu PP OPO obrasca ove dividende (PDF ili XML za ePorezi). --}}
{{-- Tooltip je na omotaču: onemogućeno Bootstrap dugme ne prima miš, pa svoj title ne bi pokazalo. --}}
@php($opis = $red->bez_kursa ? 'Nije moguće bez NBS kursa' : 'Preuzmi PP OPO (PDF)')
@php($opisXml = $red->bez_kursa ? 'Nije moguće bez NBS kursa' : 'Preuzmi PP OPO (XML za ePorezi)')
{{-- PDF i XML ikonica uvek u istoj liniji, i u uskoj koloni. --}}
<span class="d-inline-flex flex-nowrap align-items-center">
<span class="d-inline-block" title="{{ $opis }}">
    <button type="submit" form="ppopo-forma" class="preuzmi-pdf btn btn-sm border-0 p-1 lh-1"
            formaction="{{ route('izvoz.pp-opo', $red->transakcija->id) }}"
            aria-label="{{ $opis }}"
            @disabled($red->bez_kursa)>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-filetype-pdf" viewBox="0 0 16 16" aria-hidden="true">
            <path fill-rule="evenodd" d="M14 4.5V14a2 2 0 0 1-2 2h-1v-1h1a1 1 0 0 0 1-1V4.5h-2A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v9H2V2a2 2 0 0 1 2-2h5.5zM1.6 11.85H0v3.999h.791v-1.342h.803q.43 0 .732-.173.305-.175.463-.474a1.4 1.4 0 0 0 .161-.677q0-.375-.158-.677a1.2 1.2 0 0 0-.46-.477q-.3-.18-.732-.179m.545 1.333a.8.8 0 0 1-.085.38.57.57 0 0 1-.238.241.8.8 0 0 1-.375.082H.788V12.48h.66q.327 0 .512.181.185.183.185.522m1.217-1.333v3.999h1.46q.602 0 .998-.237a1.45 1.45 0 0 0 .595-.689q.196-.45.196-1.084 0-.63-.196-1.075a1.43 1.43 0 0 0-.589-.68q-.396-.234-1.005-.234zm.791.645h.563q.371 0 .609.152a.9.9 0 0 1 .354.454q.118.302.118.753a2.3 2.3 0 0 1-.068.592 1.1 1.1 0 0 1-.196.422.8.8 0 0 1-.334.252 1.3 1.3 0 0 1-.483.082h-.563zm3.743 1.763v1.591h-.79V11.85h2.548v.653H7.896v1.117h1.606v.638z"/>
        </svg>
    </button>
</span>
<span class="d-inline-block" title="{{ $opisXml }}">
    <button type="submit" form="ppopo-forma" class="preuzmi-pdf btn btn-sm border-0 p-1 lh-1"
            formaction="{{ route('izvoz.pp-opo.xml', $red->transakcija->id) }}"
            aria-label="{{ $opisXml }}"
            @disabled($red->bez_kursa)>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-filetype-xml" viewBox="0 0 16 16" aria-hidden="true">
            <path fill-rule="evenodd" d="M14 4.5V14a2 2 0 0 1-2 2v-1a1 1 0 0 0 1-1V4.5h-2A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v9H2V2a2 2 0 0 1 2-2h5.5zM3.527 11.85h-.893l-.823 1.439h-.036L.943 11.85H.012l1.227 1.983L0 15.85h.861l.853-1.415h.035l.85 1.415h.908l-1.254-1.992zm.954 3.999v-2.66h.038l.952 2.159h.516l.946-2.16h.038v2.661h.715V11.85h-.8l-1.14 2.596h-.025L4.58 11.85h-.806v3.999zm4.71-.674h1.696v.674H8.4V11.85h.791z"/>
        </svg>
    </button>
</span>
</span>
