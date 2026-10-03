@if ($datum)
    @include('obrasci._kucice', ['vrednost' => $datum->format('d.m.Y')])
    <div class="pomoc">дд &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; мм &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; гггг</div>
@else
    @include('obrasci._kucice', ['vrednost' => '  .  .    '])
@endif
