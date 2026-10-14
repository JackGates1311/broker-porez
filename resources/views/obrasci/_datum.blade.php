{{-- Datum u kućicama дд мм гггг; prazne kućice kad je $datum null. $bezLegende za redove sticanja (4.7). --}}
@include('obrasci._kucice', [
    'vrednost' => $datum?->format('dmY'),
    'grupe' => [2, 2, 4],
    'legenda' => ($bezLegende ?? false) ? null : ['дд', 'мм', 'гггг'],
])
