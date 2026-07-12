<div>
    <div class="page-head">
        <h1>Rassegne</h1>
        <p>Ogni rassegna monitora un comunicato o un periodo per un cliente.</p>
    </div>

    <div class="card">
        <div style="display:flex;gap:10px;margin-bottom:14px;">
            <input type="text" placeholder="Cerca rassegna" wire:model.live.debounce.300ms="ricerca" style="margin:0;">
            @if ($puoCreare)
                <a class="btn primary" href="{{ route('rassegne.create') }}" wire:navigate style="white-space:nowrap;text-decoration:none;">+ Nuova rassegna</a>
            @endif
        </div>

        <div class="list">
            @forelse ($rassegne as $rassegna)
                <a class="row" href="{{ route('rassegne.show', $rassegna) }}" wire:navigate style="text-decoration:none;color:inherit;">
                    <div class="main">
                        <div class="title">{{ $rassegna->titolo }}</div>
                        <div class="sub">
                            {{ $rassegna->cliente->nome }} · {{ $rassegna->uscite_count }} {{ $rassegna->uscite_count == 1 ? 'uscita' : 'uscite' }} ·
                            monitoraggio {{ $rassegna->monitoraggio_inizio->format('d/m') }}–{{ $rassegna->monitoraggio_fine->format('d/m/Y') }}
                        </div>
                    </div>
                    <x-stato-rassegna :stato="$rassegna->stato" />
                </a>
            @empty
                <div class="empty">Nessuna rassegna. {{ $puoCreare ? 'Creane una per iniziare.' : '' }}</div>
            @endforelse
        </div>

        <div style="margin-top:14px;">{{ $rassegne->links() }}</div>
    </div>
</div>
