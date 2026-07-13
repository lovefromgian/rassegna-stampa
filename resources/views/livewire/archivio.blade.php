<div>
    <div class="page-head">
        <h1 class="mt-0">Archivio</h1>
        <p>Cerca dentro il testo di tutte le uscite mai raccolte, di qualunque cliente e anno.</p>
    </div>

    <div class="card">
        <form wire:submit="$refresh" style="display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap;">
            <input type="text" wire:model="termine" placeholder="Cerca nel testo (es. Iulia Felix)" style="margin:0;flex:1;min-width:200px;">
            <select wire:model="clienteId" style="margin:0;max-width:200px;">
                <option value="">Tutti i clienti</option>
                @foreach ($clienti as $c)<option value="{{ $c->id }}">{{ $c->nome }}</option>@endforeach
            </select>
            <select wire:model="testataId" style="margin:0;max-width:200px;">
                <option value="">Tutte le testate</option>
                @foreach ($testate as $t)<option value="{{ $t->id }}">{{ $t->nome }}</option>@endforeach
            </select>
            <button type="submit" class="btn primary" style="white-space:nowrap;">Cerca</button>
        </form>

        @if ($risultati === null)
            <div class="empty">Scrivi un termine e premi Cerca per esplorare l'archivio.</div>
        @else
            <p class="muted" style="font-size:13px;margin:0 0 12px;">{{ $risultati->total() }} uscite trovate</p>
            <div class="list">
                @forelse ($risultati as $u)
                    <div class="row">
                        <div class="main">
                            <div class="title">{{ $u->testata->nome }}</div>
                            <div class="sub">
                                {{ $u->titolo }} · {{ $u->data_pubblicazione->format('d/m/Y') }} ·
                                {{ $u->rassegna->cliente->nome }} / {{ $u->rassegna->titolo }}
                            </div>
                            @if ($u->testo_estratto)
                                <div class="sub" style="margin-top:4px;">…{{ \Illuminate\Support\Str::limit(strip_tags($u->testo_estratto), 180) }}…</div>
                            @endif
                        </div>
                        <span class="pill neutral">{{ $u->tipo_media->etichetta() }}</span>
                    </div>
                @empty
                    <div class="empty">Nessuna uscita trovata per "{{ $termine }}".</div>
                @endforelse
            </div>
            <div style="margin-top:14px;">{{ $risultati->links() }}</div>
        @endif
    </div>
</div>
