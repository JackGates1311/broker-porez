{{-- Polje korisničkog imena sa slobodnim predlozima kada je uneto ime zauzeto (registracija i Moj nalog). --}}
<label for="korisnicko_ime" class="form-label">Korisničko ime</label>
<input type="text" id="korisnicko_ime" name="korisnicko_ime" value="{{ old('korisnicko_ime', $vrednost ?? null) }}"
       class="form-control @error('korisnicko_ime') is-invalid @enderror"
       maxlength="50" autocomplete="username" required @if ($autofocus ?? false) autofocus @endif>
<div class="invalid-feedback">
    @error('korisnicko_ime') {{ $message }} @else Dozvoljena su slova, brojevi, crtice i donje crte. @enderror
</div>
@if (session('predlozi_korisnickog_imena'))
    <div class="form-text d-flex flex-wrap align-items-center gap-1" data-predlozi-za="korisnicko_ime">
        <span>Slobodna imena:</span>
        @foreach (session('predlozi_korisnickog_imena') as $predlog)
            <button type="button" class="btn btn-sm btn-outline-primary py-0" data-predlog="{{ $predlog }}">{{ $predlog }}</button>
        @endforeach
    </div>
@endif
