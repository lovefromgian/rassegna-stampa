<div wire:poll.8s>
    <p class="crumbs">
        <a href="{{ route('rassegne.show', $rassegna) }}" wire:navigate>{{ $rassegna->titolo }}</a> / Candidati
    </p>

    @include('partials.fasi-rassegna', ['rassegna' => $rassegna, 'corrente' => 'candidati'])

    <div class="page-head spread">
        <div>
            <h1 class="mt-0">Candidati da confermare</h1>
            <p>
                {{ $candidati->count() }} in attesa
                @if ($ultimaScansione) · ultima scansione {{ $ultimaScansione->format('d/m/Y H:i') }} @endif
                · ordinati per corrispondenza
            </p>
        </div>
        <button class="btn" wire:click="scansionaOra" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="scansionaOra">Scansiona ora</span>
            <span wire:loading wire:target="scansionaOra">Scansione…</span>
        </button>
    </div>

    <div class="card">
        <div class="spread" style="margin-bottom:12px;">
            <label style="font-size:14px;color:var(--text-secondary);">
                <input type="checkbox" style="width:auto;margin:0;"
                       wire:change="selezionaTutti($event.target.checked)"
                       @checked(count($selezionati) && count($selezionati) === $candidati->count())>
                Seleziona tutti
            </label>
            <span class="muted" style="font-size:13px;">{{ count($selezionati) }} selezionati</span>
        </div>

        <div class="list">
            @forelse ($candidati as $c)
                @php
                    [$classe, $etichetta] = match (true) {
                        ($c->punteggio_corrispondenza ?? 0) >= 67 => ['success', 'Corrispondenza alta'],
                        ($c->punteggio_corrispondenza ?? 0) >= 34 => ['neutral', 'Corrispondenza media'],
                        default => ['danger', 'Corrispondenza debole'],
                    };
                @endphp
                <div class="row" style="align-items:flex-start;">
                    <input type="checkbox" style="margin-top:4px;width:auto;" value="{{ $c->id }}" wire:model="selezionati">
                    <div class="main">
                        <div class="spread">
                            <span class="title">{{ $c->testata->nome }}</span>
                            <span class="sub" style="white-space:nowrap;">{{ $c->data_pubblicazione->format('d/m/Y') }}</span>
                        </div>
                        <div style="margin:2px 0 6px;">{{ $c->titolo }}</div>
                        @if ($c->testo_estratto)
                            <div class="sub" style="line-height:1.5;">{{ \Illuminate\Support\Str::limit($c->testo_estratto, 220) }}</div>
                        @endif
                        <div style="display:flex;gap:10px;align-items:center;margin-top:8px;flex-wrap:wrap;">
                            <span class="pill {{ $classe }}">{{ $etichetta }} ({{ $c->punteggio_corrispondenza ?? 0 }})</span>
                            @isset($sospetti[$c->id])
                                <span class="pill warning">Possibile duplicato di {{ $sospetti[$c->id] }}</span>
                            @endisset
                            @if ($c->url)
                                <a href="{{ $c->url }}" target="_blank" rel="noopener" style="font-size:13px;">Apri l'articolo ↗</a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty">Nessun candidato in attesa. Usa "Scansiona ora" oppure aggiungi un'uscita a mano dalla scheda.</div>
            @endforelse
        </div>

        @if ($candidati->isNotEmpty())
            <div class="actions" style="margin-top:16px;">
                <button class="btn" wire:click="scartaSelezionati" @disabled(! count($selezionati))>Scarta selezionati</button>
                <button class="btn primary" wire:click="confermaSelezionati" @disabled(! count($selezionati))>Conferma selezionati</button>
            </div>
        @endif

        <a class="btn wide" href="{{ route('rassegne.uscite', $rassegna) }}" wire:navigate style="margin-top:10px;text-decoration:none;text-align:center;">
            + Aggiungi un'uscita manualmente (URL o ritaglio cartaceo)
        </a>

        <div class="note" style="margin-top:14px;">
            Il falso positivo è fisiologico: un articolo con "gradi" ma non su Grado è segnalato come debole, non escluso da solo. Allo stesso modo un'uscita può sfuggire alla scansione: l'aggiunta manuale è parte del flusso.
        </div>
    </div>
</div>
