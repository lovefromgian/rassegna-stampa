<div>
    <div class="page-head spread">
        <div>
            <h1 class="mt-0">Utenti</h1>
            <p>Chi accede al gestionale. Gli utenti li crea e gestisce il supervisore.</p>
        </div>
        <a class="btn primary" href="{{ route('utenti.create') }}" wire:navigate>+ Nuovo utente</a>
    </div>

    <div class="card">
        <div class="list">
            @forelse ($utenti as $u)
                <div class="row">
                    <div class="main">
                        <div class="title">{{ $u->name }} @unless ($u->attivo)<span class="pill danger">disattivato</span>@endunless</div>
                        <div class="sub">{{ $u->email }}</div>
                    </div>
                    <span class="pill {{ $u->isSupervisore() ? 'accent' : 'neutral' }}">{{ $u->ruolo->etichetta() }}</span>
                    <div class="actions" style="flex:0;">
                        <a class="btn small" href="{{ route('utenti.edit', $u) }}" wire:navigate>Modifica</a>
                        @if ($u->id !== auth()->id())
                            <button class="btn small {{ $u->attivo ? 'danger' : '' }}" wire:click="cambiaAttivazione({{ $u->id }})"
                                    wire:confirm="{{ $u->attivo ? 'Disattivare '.$u->name.'? Non potrà più accedere.' : 'Riattivare '.$u->name.'?' }}">
                                {{ $u->attivo ? 'Disattiva' : 'Attiva' }}
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="empty">Nessun utente.</div>
            @endforelse
        </div>
        <div class="note mt-3">Gli utenti non si cancellano: si <strong>disattivano</strong>, così il log di audit resta integro. Un utente disattivato non può più accedere.</div>
    </div>
</div>
