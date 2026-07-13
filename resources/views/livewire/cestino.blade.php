<div>
    <div class="page-head">
        <h1 class="mt-0">Cestino</h1>
        <p>Record eliminati (soft delete). Puoi ripristinarli: nulla viene cancellato fisicamente.</p>
    </div>

    <div class="card">
        <h2>Clienti eliminati ({{ $clienti->count() }})</h2>
        <div class="list">
            @forelse ($clienti as $c)
                <div class="row">
                    <div class="main">
                        <div class="title">{{ $c->nome }}</div>
                        <div class="sub">eliminato il {{ $c->deleted_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <button class="btn small" wire:click="ripristina('cliente', {{ $c->id }})">Ripristina</button>
                </div>
            @empty
                <div class="empty">Nessun cliente nel cestino.</div>
            @endforelse
        </div>
    </div>

    <div class="card">
        <h2>Rassegne eliminate ({{ $rassegne->count() }})</h2>
        <div class="list">
            @forelse ($rassegne as $r)
                <div class="row">
                    <div class="main">
                        <div class="title">{{ $r->titolo }}</div>
                        <div class="sub">{{ $r->cliente?->nome ?? '—' }} · eliminata il {{ $r->deleted_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <button class="btn small" wire:click="ripristina('rassegna', {{ $r->id }})">Ripristina</button>
                </div>
            @empty
                <div class="empty">Nessuna rassegna nel cestino.</div>
            @endforelse
        </div>
    </div>

    <div class="card">
        <h2>Uscite eliminate ({{ $uscite->count() }})</h2>
        <div class="list">
            @forelse ($uscite as $u)
                <div class="row">
                    <div class="main">
                        <div class="title">{{ $u->testata->nome }} — {{ \Illuminate\Support\Str::limit($u->titolo, 70) }}</div>
                        <div class="sub">{{ $u->rassegna?->titolo ?? '—' }} · eliminata il {{ $u->deleted_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <button class="btn small" wire:click="ripristina('uscita', {{ $u->id }})">Ripristina</button>
                </div>
            @empty
                <div class="empty">Nessuna uscita nel cestino.</div>
            @endforelse
        </div>
    </div>

    <div class="note">La cancellazione definitiva non è prevista: l'archivio storico è il valore del sistema e nulla si cancella fisicamente (CLAUDE.md §6).</div>
</div>
