<div>
    <p class="crumbs">
        <a href="{{ route('rassegne.show', $rassegna) }}" wire:navigate>{{ $rassegna->titolo }}</a> / Genera PDF
    </p>

    @include('partials.fasi-rassegna', ['rassegna' => $rassegna, 'corrente' => 'pdf'])

    <div class="page-head">
        <h1 class="mt-0">Ordine delle uscite e generazione</h1>
        <p>Ordinamento proposto: rilevanza, poi data. Usa le frecce per riordinare — l'ordine è una scelta editoriale.</p>
    </div>

    <div style="display:grid;grid-template-columns:1.4fr 1fr;gap:16px;align-items:start;">
        <div class="card">
            <h2>Uscite nel PDF <span class="muted" style="font-weight:400;font-size:14px;">— {{ $uscite->count() }} approvate</span></h2>
            <div class="list">
                @forelse ($uscite as $i => $uscita)
                    <div class="row">
                        <span class="muted" style="width:16px;">{{ $i + 1 }}</span>
                        <div class="main">
                            <div class="title">{{ $uscita->testata->nome }}@if ($uscita->pagina_giornale) · {{ $uscita->pagina_giornale }}@endif</div>
                            <div class="sub">{{ $uscita->titolo }} · {{ $uscita->data_pubblicazione->format('d/m/Y') }}</div>
                        </div>
                        <span class="pill neutral">{{ $uscita->tipo_media->etichetta() }}</span>
                        <span class="pill success">{{ $uscita->rilevanza?->etichetta() ?? '—' }}</span>
                        <div style="display:flex;flex-direction:column;gap:2px;">
                            <button class="btn small" wire:click="spostaSu({{ $uscita->id }})" @disabled($i === 0) title="Su">▲</button>
                            <button class="btn small" wire:click="spostaGiu({{ $uscita->id }})" @disabled($i === $uscite->count() - 1) title="Giù">▼</button>
                        </div>
                    </div>
                @empty
                    <div class="empty">Nessuna uscita approvata. Approva le uscite in <a href="{{ route('rassegne.revisione', $rassegna) }}" wire:navigate>revisione</a>.</div>
                @endforelse
            </div>
            @if ($uscite->count() > 1)
                <div class="note" style="margin-top:14px;">L'ordine manuale prevale sulla proposta: è la scelta editoriale della rassegna.</div>
            @endif
        </div>

        <div>
            <div class="card">
                <h2>Riepilogo</h2>
                <table class="data">
                    <tr><td>Uscite incluse</td><td>{{ $uscite->count() }} approvate</td></tr>
                    <tr><td>Candidati pendenti</td><td>{{ $candidatiPendenti }}</td></tr>
                    <tr><td>Prossima versione</td><td>v{{ $prossimaVersione }}</td></tr>
                </table>

                @if (! $puoGenerare)
                    <div class="flash danger" style="margin-top:12px;">
                        <strong>Generazione bloccata:</strong>
                        <ul style="margin:6px 0 0;padding-left:18px;">
                            @foreach ($motivi as $m)<li>{{ $m }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <button class="btn primary wide" style="margin-top:14px;" wire:click="genera" @disabled(! $puoGenerare)>Genera PDF</button>
                <p class="muted" style="font-size:13px;margin:10px 0 0;">Il PDF si scarica e si invia a mano. Il sistema registra chi l'ha generato e quando.</p>
            </div>

            <div class="card" wire:poll.5s>
                <h2>Versioni</h2>
                @forelse ($documenti as $doc)
                    <div class="spread" style="padding:8px 0;border-bottom:1px solid var(--border);">
                        <div>
                            <div><strong>v{{ $doc->versione }}</strong> · {{ $doc->generato_il->format('d/m/Y H:i') }}</div>
                            <div class="muted" style="font-size:12px;">
                                {{ $doc->generatoDa?->name ?? '—' }} · {{ count($doc->uscite_incluse) }} uscite
                                @if ($doc->scaricato_il) · scaricato @endif
                            </div>
                        </div>
                        <a class="btn small" href="{{ route('documenti.download', $doc) }}">Scarica</a>
                    </div>
                @empty
                    <p class="muted" style="margin:0;">Nessuna versione ancora generata.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
