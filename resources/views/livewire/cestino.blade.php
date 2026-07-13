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
                    <div class="actions" style="flex:0;">
                        <button class="btn small" wire:click="ripristina('cliente', {{ $c->id }})">Ripristina</button>
                        <button class="btn small danger" wire:click="eliminaDefinitivo('cliente', {{ $c->id }})"
                                wire:confirm="ATTENZIONE: elimini DEFINITIVAMENTE «{{ $c->nome }}» e a cascata tutte le sue rassegne, uscite e file. Irreversibile. Procedere?">Elimina definitivamente</button>
                    </div>
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
                    <div class="actions" style="flex:0;">
                        <button class="btn small" wire:click="ripristina('rassegna', {{ $r->id }})">Ripristina</button>
                        <button class="btn small danger" wire:click="eliminaDefinitivo('rassegna', {{ $r->id }})"
                                wire:confirm="ATTENZIONE: elimini DEFINITIVAMENTE «{{ $r->titolo }}», le sue uscite, i PDF generati e i file. Irreversibile. Procedere?">Elimina definitivamente</button>
                    </div>
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
                    <div class="actions" style="flex:0;">
                        <button class="btn small" wire:click="ripristina('uscita', {{ $u->id }})">Ripristina</button>
                        <button class="btn small danger" wire:click="eliminaDefinitivo('uscita', {{ $u->id }})"
                                wire:confirm="ATTENZIONE: elimini DEFINITIVAMENTE questa uscita e i suoi file (screenshot/PDF). Irreversibile. Procedere?">Elimina definitivamente</button>
                    </div>
                </div>
            @empty
                <div class="empty">Nessuna uscita nel cestino.</div>
            @endforelse
        </div>
    </div>

    <div class="flash danger">La <strong>cancellazione definitiva</strong> è irreversibile e rimuove il record e tutti i suoi file dal disco (a cascata: cliente → rassegne → uscite). Non essendoci backup dei file (TECH-DEBT TD-001), i dati eliminati non si recuperano. Il log di audit resta comunque immutabile.</div>
</div>
