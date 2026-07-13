<div>
    <div class="page-head">
        <h1>Rassegne</h1>
        <p>Ogni rassegna monitora un comunicato o un periodo per un cliente.</p>
    </div>

    <div class="card">
        <div class="toolbar">
            <input type="text" placeholder="Cerca rassegna" wire:model.live.debounce.300ms="ricerca" style="margin:0;">
            @if ($puoCreare)
                <a class="btn primary nowrap" href="{{ route('rassegne.create') }}" wire:navigate>+ Nuova rassegna</a>
            @endif
        </div>

        <div class="list">
            @forelse ($rassegne as $rassegna)
                <a class="row plain" href="{{ route('rassegne.show', $rassegna) }}" wire:navigate>
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

        <div class="mt-3">{{ $rassegne->links() }}</div>
    </div>
</div>
