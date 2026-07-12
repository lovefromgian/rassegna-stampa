@props(['stato'])

@php
    $classe = match ($stato) {
        \App\Enums\StatoRassegna::InRaccolta => 'success',
        \App\Enums\StatoRassegna::InRevisione => 'warning',
        \App\Enums\StatoRassegna::Chiusa => 'accent',
        \App\Enums\StatoRassegna::Riaperta => 'warning',
    };
@endphp

<span class="pill {{ $classe }}">{{ $stato->etichetta() }}</span>
