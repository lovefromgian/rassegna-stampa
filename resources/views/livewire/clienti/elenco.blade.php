<div>
    <div class="page-head">
        <h1>Clienti</h1>
        <p>Ogni cliente raccoglie le sue rassegne. Le impostazioni grafiche del PDF si configurano una volta sola.</p>
    </div>

    <div class="card">
        <div class="toolbar">
            <input type="text" placeholder="Cerca cliente" wire:model.live.debounce.300ms="ricerca" style="margin:0;">
            @if ($puoCreare)
                <a class="btn primary nowrap" href="{{ route('clienti.create') }}" wire:navigate>+ Nuovo cliente</a>
            @endif
        </div>

        <div class="list">
            @forelse ($clienti as $cliente)
                @php
                    $iniziali = \Illuminate\Support\Str::of($cliente->nome)->explode(' ')
                        ->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                @endphp
                <a class="row plain" href="{{ route('clienti.show', $cliente) }}" wire:navigate>
                    <div class="avatar">{{ $iniziali }}</div>
                    <div class="main">
                        <div class="title">{{ $cliente->nome }}</div>
                        <div class="sub">{{ $cliente->rassegne_count }} {{ $cliente->rassegne_count == 1 ? 'rassegna' : 'rassegne' }}</div>
                    </div>
                    @if ($cliente->stato === \App\Enums\StatoCliente::Attivo)
                        <span class="pill success">Attivo</span>
                    @else
                        <span class="pill neutral">Archiviato</span>
                    @endif
                </a>
            @empty
                <div class="empty">Nessun cliente. {{ $puoCreare ? 'Creane uno per iniziare.' : '' }}</div>
            @endforelse
        </div>

        @unless ($puoCreare)
            <p style="font-size:13px;color:var(--text-muted);margin:14px 0 0;">Solo il supervisore crea, modifica o elimina un cliente. Vedi l'elenco in sola lettura.</p>
        @endunless

        <div class="mt-3">{{ $clienti->links('vendor.pagination.rassegna') }}</div>
    </div>
</div>
