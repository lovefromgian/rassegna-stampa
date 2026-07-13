<div>
    @if ($rassegna)
        <p class="crumbs"><a href="{{ route('rassegne.show', $rassegna) }}" wire:navigate>{{ $rassegna->titolo }}</a> / Log azioni</p>
    @endif

    <div class="page-head">
        <h1 class="mt-0">Log azioni</h1>
        <p>Chi ha fatto cosa, e quando. Registro immutabile: non si modifica né si cancella.</p>
    </div>

    <div class="card">
        <div style="display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap;">
            <select wire:model.live="azione" style="margin:0;max-width:220px;">
                <option value="">Tutte le azioni</option>
                @foreach ($azioniDisponibili as $a)
                    <option value="{{ $a }}">{{ \App\Models\LogAzione::make(['azione' => $a])->etichetta() }}</option>
                @endforeach
            </select>
            <select wire:model.live="utenteId" style="margin:0;max-width:220px;">
                <option value="">Tutti gli utenti</option>
                @foreach ($utenti as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="list">
            @forelse ($voci as $voce)
                <div class="row">
                    <div class="main">
                        <div class="title">{{ $voce->user?->name ?? 'Sistema' }} — {{ $voce->etichetta() }}</div>
                        <div class="sub">
                            {{ class_basename($voce->entita_tipo) }} #{{ $voce->entita_id }} ·
                            {{ $voce->created_at->format('d/m/Y H:i') }}
                            @if ($voce->dettagli)
                                · {{ collect($voce->dettagli)->map(fn ($v, $k) => "$k: ".(is_array($v) ? json_encode($v) : $v))->implode(', ') }}
                            @endif
                        </div>
                    </div>
                    <span class="pill {{ $voce->categoria() }}">{{ $voce->etichetta() }}</span>
                </div>
            @empty
                <div class="empty">Nessuna azione registrata.</div>
            @endforelse
        </div>

        <div style="margin-top:14px;">{{ $voci->links() }}</div>
    </div>
</div>
