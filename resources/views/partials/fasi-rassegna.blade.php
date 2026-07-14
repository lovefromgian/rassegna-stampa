{{-- Barra delle fasi della rassegna (UX-04, estesa): Candidati → Revisione → Approvate →
     Ordine/PDF → Scartate. `$rassegna` obbligatorio; `$corrente` = chiave della voce corrente
     ('candidati'|'revisione'|'approvate'|'pdf'|'scartate') o null. Conteggi dal modello
     (fonte unica). È la navigazione principale della rassegna: ogni voce porta alla sua
     schermata (Approvate/Scartate all'elenco filtrato delle uscite). --}}
@php
    use App\Enums\StatoUscita;

    $corrente = $corrente ?? null;
    $c = $rassegna->conteggiPerStato();
    $nCandidati = $c[StatoUscita::Candidato->value] ?? 0;
    $nConfermato = $c[StatoUscita::Confermato->value] ?? 0; // in cattura
    $nCatturato = $c[StatoUscita::Catturato->value] ?? 0;
    $nApprovate = $c[StatoUscita::Approvato->value] ?? 0;
    $nScartate = $c[StatoUscita::Scartato->value] ?? 0;

    // Avanzamento del flusso lineare (Candidati=0, Revisione=1, Ordine/PDF=2), monotòno.
    $progresso = match (true) {
        $nCandidati > 0 => 0,
        $nCatturato > 0 || $nConfermato > 0 => 1,
        $nApprovate > 0 => 2,
        default => 0,
    };
    $pdfGenerato = $rassegna->documentiGenerati()->exists();

    $fasi = [
        ['chiave' => 'candidati', 'num' => 1, 'nome' => 'Candidati', 'tipo' => 'flusso', 'idx' => 0, 'badge' => $nCandidati,
            'href' => route('rassegne.candidati', $rassegna)],
        ['chiave' => 'revisione', 'num' => 2, 'nome' => 'Revisione', 'tipo' => 'flusso', 'idx' => 1, 'badge' => $nCatturato,
            'href' => route('rassegne.revisione', $rassegna)],
        ['chiave' => 'approvate', 'num' => 3, 'nome' => 'Approvate', 'tipo' => 'vista', 'idx' => null, 'badge' => $nApprovate,
            'href' => route('rassegne.uscite', ['rassegna' => $rassegna, 'stato' => StatoUscita::Approvato->value])],
        ['chiave' => 'pdf', 'num' => 4, 'nome' => 'Ordine / PDF', 'tipo' => 'flusso', 'idx' => 2, 'badge' => 0,
            'href' => route('rassegne.pdf', $rassegna)],
        ['chiave' => 'scartate', 'num' => 5, 'nome' => 'Scartate', 'tipo' => 'vista', 'idx' => null, 'badge' => $nScartate,
            'href' => route('rassegne.uscite', ['rassegna' => $rassegna, 'stato' => StatoUscita::Scartato->value])],
    ];

    $statoFase = function (array $fase) use ($corrente, $progresso, $pdfGenerato): string {
        if ($corrente === $fase['chiave']) {
            return 'corrente';
        }
        if ($fase['tipo'] === 'vista') {
            return 'vista'; // Approvate/Scartate: elenchi navigabili, senza logica di avanzamento
        }
        if ($fase['idx'] < $progresso || ($fase['chiave'] === 'pdf' && $pdfGenerato)) {
            return 'completata';
        }

        return 'attesa';
    };
@endphp

<nav class="fasi" aria-label="Fasi della rassegna">
    @foreach ($fasi as $i => $fase)
        @php $stato = $statoFase($fase); @endphp
        <a class="fase {{ $stato }}"
           href="{{ $fase['href'] }}" wire:navigate
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
