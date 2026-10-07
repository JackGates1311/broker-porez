{{-- Izbor obračunskog perioda; menja se odmah (resources/js/dashboard.ts). --}}
<form method="GET" class="d-flex align-items-center gap-2" data-auto-submit>
    <label for="period" class="text-body-secondary small text-nowrap">Period:</label>
    <select id="period" name="period" class="form-select form-select-sm" style="min-width: 13rem">
        <option value="sve" @selected($period->kod === 'sve')>Cela istorija</option>
        @foreach ($godine as $godina)
            @if (empty($samoGodine))
                <optgroup label="{{ $godina }}.">
                    <option value="{{ $godina }}" @selected($period->kod === (string) $godina)>Cela {{ $godina }}.</option>
                    <option value="{{ $godina }}-H2" @selected($period->kod === "{$godina}-H2")>II polugodište {{ $godina }}.</option>
                    <option value="{{ $godina }}-H1" @selected($period->kod === "{$godina}-H1")>I polugodište {{ $godina }}.</option>
                </optgroup>
            @else
                <option value="{{ $godina }}" @selected($period->kod === (string) $godina)>{{ $godina }}.</option>
            @endif
        @endforeach
    </select>
    <noscript><button type="submit" class="btn btn-sm btn-outline-primary">Prikaži</button></noscript>
</form>
