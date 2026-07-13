{{-- Mappa delle fasi della rassegna (UX-04): stepper persistente Candidati → Revisione →
     Ordine/PDF. `$rassegna` obbligatorio; `$corrente` = chiave della fase corrente
     ('candidati'|'revisione'|'pdf') o null. Conteggi dal modello (fonte unica, no duplica). --}}
@php
    use App\Enums\StatoUscita;

    $corrente = $corrente ?? null;
    $c = $rassegna->conteggiPerStato();
    $nCandidati = $c[StatoUscita::Candidato->value] ?? 0;
    $nConfermato = $c[StatoUscita::Confermato->value] ?? 0; // in cattura
    $nCatturato = $c[StatoUscita::Catturato->value] ?? 0;
    $nApprovate = $c[StatoUscita::Approvato->value] ?? 0;

    // Avanzamento del flusso, monotòno: 0 = si decidono i candidati, 1 = si revisionano le
    // catture, 2 = si ordina/genera il PDF. Una fase vuota resta "in attesa", non "completata".
    $progresso = match (true) {
        $nCandidati > 0 => 0,
        $nCatturato > 0 || $nConfermato > 0 => 1,
        $nApprovate > 0 => 2,
        default => 0,
    };
    $pdfGenerato = $rassegna->documentiGenerati()->exists();

    $fasi = [
        ['chiave' => 'candidati', 'num' => 1, 'nome' => 'Candidati', 'rotta' => 'rassegne.candidati', 'badge' => $nCandidati],
        ['chiave' => 'revisione', 'num' => 2, 'nome' => 'Revisione', 'rotta' => 'rassegne.revisione', 'badge' => $nCatturato],
        ['chiave' => 'pdf', 'num' => 3, 'nome' => 'Ordine / PDF', 'rotta' => 'rassegne.pdf', 'badge' => 0],
    ];

    $statoFase = function (string $chiave, int $i) use ($corrente, $progresso, $pdfGenerato): string {
        if ($corrente === $chiave) {
            return 'corrente';
        }
        if ($i < $progresso || ($chiave === 'pdf' && $pdfGenerato)) {
            return 'completata';
        }

        return 'attesa';
    };
@endphp

<nav class="fasi" aria-label="Fasi della rassegna">
    @foreach ($fasi as $i => $fase)
        @php $stato = $statoFase($fase['chiave'], $i); @endphp
        <a class="fase {{ $stato }}"
           href="{{ route($fase['rotta'], $rassegna) }}" wire:navigate
           data-fase="{{ $fase['chiave'] }}" data-stato="{{ $stato }}"
           @if ($corrente === $fase['chiave']) aria-current="step" @endif>
            <span class="fase-num">{{ $stato === 'completata' ? '✓' : $fase['num'] }}</span>
            <span class="fase-nome">{{ $fase['nome'] }}</span>
            @if ($fase['badge'] > 0)
                <span class="fase-badge">{{ $fase['badge'] }}</span>
            @endif
        </a>
        @if ($i < count($fasi) - 1)
            <span class="fase-sep" aria-hidden="true">→</span>
        @endif
    @endforeach
</nav>
