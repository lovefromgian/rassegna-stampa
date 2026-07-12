<div>
    <p class="crumbs"><a href="{{ route('clienti.index') }}" wire:navigate>Clienti</a> / {{ $cliente->nome }}</p>

    <div class="page-head spread">
        <div>
            <h1 class="mt-0">{{ $cliente->nome }}</h1>
            <p>{{ $cliente->referente ? $cliente->referente.' · ' : '' }}{{ $cliente->email_referente ?: 'nessun referente' }}</p>
        </div>
        <div class="actions" style="flex:0;">
            @if ($puoModificare)
                <a class="btn" href="{{ route('clienti.edit', $cliente) }}" wire:navigate style="text-decoration:none;">Impostazioni</a>
            @endif
            @if (auth()->user()->can('create', \App\Models\Rassegna::class))
                <a class="btn primary" href="{{ route('rassegne.create', ['cliente' => $cliente->id]) }}" wire:navigate style="text-decoration:none;">+ Nuova rassegna</a>
            @endif
        </div>
    </div>

    <div class="metrics">
        <div class="metric"><div class="label">Rassegne totali</div><div class="value">{{ $cliente->rassegne->count() }}</div></div>
        <div class="metric"><div class="label">Stato</div><div class="value" style="font-size:18px;">{{ $cliente->stato->etichetta() }}</div></div>
        <div class="metric">
            <div class="label">Colore d'accento</div>
            <div class="value" style="font-size:16px;">
                @if ($cliente->colore_accento)
                    <span class="swatch" style="background:{{ $cliente->colore_accento }};"></span> {{ $cliente->colore_accento }}
                @else <span class="muted">—</span> @endif
            </div>
        </div>
        <div class="metric"><div class="label">Destinatari</div><div class="value">{{ count($cliente->destinatari_invio ?? []) }}</div></div>
    </div>

    <div class="card">
        <h2>Rassegne</h2>
        <div class="list">
            @forelse ($cliente->rassegne as $rassegna)
                <a class="row" href="{{ route('rassegne.show', $rassegna) }}" wire:navigate style="text-decoration:none;color:inherit;">
                    <div class="main">
                        <div class="title">{{ $rassegna->titolo }}</div>
                        <div class="sub">
                            @if ($rassegna->comunicato_data)
                                Comunicato {{ $rassegna->comunicato_data->format('d/m/Y') }} ·
                            @else
                                Rassegna di periodo ·
                            @endif
                            monitoraggio {{ $rassegna->monitoraggio_inizio->format('d/m') }}–{{ $rassegna->monitoraggio_fine->format('d/m/Y') }}
                        </div>
                    </div>
                    <x-stato-rassegna :stato="$rassegna->stato" />
                </a>
            @empty
                <div class="empty">Nessuna rassegna per questo cliente.</div>
            @endforelse
        </div>
    </div>
</div>
