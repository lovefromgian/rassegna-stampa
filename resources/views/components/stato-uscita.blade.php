@props(['uscita'])

@php
    use App\Enums\StatoUscita;
    use App\Enums\StatoCattura;

    // Pill derivata da stato di business + stato tecnico della cattura.
    [$classe, $testo] = match (true) {
        $uscita->stato === StatoUscita::Scartato => ['danger', 'Scartato'],
        $uscita->stato === StatoUscita::Approvato => ['success', 'Approvata'],
        $uscita->stato === StatoUscita::Catturato => ['success', 'Catturata'],
        $uscita->stato_cattura === StatoCattura::Errore => ['warning', 'Cattura da rifare'],
        $uscita->stato_cattura === StatoCattura::InAttesa,
        $uscita->stato_cattura === StatoCattura::InCorso => ['neutral', 'In cattura'],
        $uscita->stato === StatoUscita::Confermato => ['accent', 'Confermata'],
        default => ['neutral', 'Candidato'],
    };
@endphp

<span class="pill {{ $classe }}">{{ $testo }}</span>
