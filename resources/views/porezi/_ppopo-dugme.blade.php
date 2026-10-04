{{-- Šalje formu sa šiframa (#ppopo-forma) na adresu PP OPO obrasca ove dividende. --}}
<button type="submit" form="ppopo-forma" class="btn btn-link btn-sm p-0 text-nowrap"
        formaction="{{ route('izvoz.pp-opo', $red->transakcija->id) }}"
        @disabled($red->bez_kursa)>PP OPO</button>
