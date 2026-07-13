<div>
    <div class="page-head spread">
        <div>
            <h1 class="mt-0">Cestino</h1>
            <p>Record eliminati (soft delete). Ripristinali o eliminali definitivamente, anche in blocco.</p>
        </div>
        @if ($totale > 0)
            <button class="btn danger" wire:click="svuotaCestino"
                    wire:confirm="Svuotare l'INTERO cestino? Elimini DEFINITIVAMENTE tutti i {{ $totale }} record e i loro file, a cascata. Irreversibile.">Svuota cestino</button>
        @endif
    </div>

    @if ($totale === 0)
        <div class="card"><div class="empty">Il cestino è vuoto.</div></div>
    @else
        <div class="card">
            <div class="spread mb-2">
                <label style="font-size:14px;color:var(--text-secondary);">
                    <input type="checkbox" style="width:auto;margin:0;" wire:change="selezionaTutti($event.target.checked)"
                           @checked(count($selezionati) === $totale)>
                    Seleziona tutti
                </label>
                <span class="muted" style="font-size:13px;">{{ count($selezionati) }} selezionati</span>
            </div>

            @if ($clienti->isNotEmpty())
                <h2>Clienti ({{ $clienti->count() }})</h2>
                <div class="list">
                    @foreach ($clienti as $c)
                        <div class="row">
                            <input type="checkbox" style="width:auto;" value="cliente:{{ $c->id }}" wire:model.live="selezionati">
                            <div class="main">
                                <div class="title">{{ $c->nome }}</div>
                                <div class="sub">eliminato il {{ $c->deleted_at->format('d/m/Y H:i') }}</div>
                            </div>
                            <div class="actions" style="flex:0;">
                                <button class="btn small" wire:click="ripristina('cliente', {{ $c->id }})">Ripristina</button>
                                <button class="btn small danger" wire:click="eliminaDefinitivo('cliente', {{ $c->id }})"
                                        wire:confirm="ATTENZIONE: elimini DEFINITIVAMENTE «{{ $c->nome }}» e a cascata le sue rassegne, uscite e file. Irreversibile.">Elimina</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($rassegne->isNotEmpty())
                <h2 class="mt-3">Rassegne ({{ $rassegne->count() }})</h2>
                <div class="list">
                    @foreach ($rassegne as $r)
                        <div class="row">
                            <input type="checkbox" style="width:auto;" value="rassegna:{{ $r->id }}" wire:model.live="selezionati">
                            <div class="main">
                                <div class="title">{{ $r->titolo }}</div>
                                <div class="sub">{{ $r->cliente?->nome ?? '—' }} · eliminata il {{ $r->deleted_at->format('d/m/Y H:i') }}</div>
                            </div>
                            <div class="actions" style="flex:0;">
                                <button class="btn small" wire:click="ripristina('rassegna', {{ $r->id }})">Ripristina</button>
                                <button class="btn small danger" wire:click="eliminaDefinitivo('rassegna', {{ $r->id }})"
                                        wire:confirm="ATTENZIONE: elimini DEFINITIVAMENTE «{{ $r->titolo }}», le sue uscite, i PDF e i file. Irreversibile.">Elimina</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($uscite->isNotEmpty())
                <h2 class="mt-3">Uscite ({{ $uscite->count() }})</h2>
                <div class="list">
                    @foreach ($uscite as $u)
                        <div class="row">
                            <input type="checkbox" style="width:auto;" value="uscita:{{ $u->id }}" wire:model.live="selezionati">
                            <div class="main">
                                <div class="title">{{ $u->testata->nome }} — {{ \Illuminate\Support\Str::limit($u->titolo, 70) }}</div>
                                <div class="sub">{{ $u->rassegna?->titolo ?? '—' }} · eliminata il {{ $u->deleted_at->format('d/m/Y H:i') }}</div>
                            </div>
                            <div class="actions" style="flex:0;">
                                <button class="btn small" wire:click="ripristina('uscita', {{ $u->id }})">Ripristina</button>
                                <button class="btn small danger" wire:click="eliminaDefinitivo('uscita', {{ $u->id }})"
                                        wire:confirm="ATTENZIONE: elimini DEFINITIVAMENTE questa uscita e i suoi file. Irreversibile.">Elimina</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="actions mt-3" style="max-width:520px;">
                <button class="btn" wire:click="ripristinaSelezionati" @disabled(! count($selezionati))>Ripristina selezionati</button>
                <button class="btn danger" wire:click="eliminaSelezionati" @disabled(! count($selezionati))
                        wire:confirm="Eliminare DEFINITIVAMENTE i {{ count($selezionati) }} record selezionati e i loro file, a cascata? Irreversibile.">Elimina definitivamente i selezionati</button>
            </div>
        </div>
    @endif

    <div class="flash danger mt-3">La <strong>cancellazione definitiva</strong> è irreversibile e rimuove i record e i loro file dal disco (a cascata: cliente → rassegne → uscite). Senza backup dei file (TECH-DEBT TD-001) i dati non si recuperano. Il log di audit resta comunque immutabile.</div>
</div>
