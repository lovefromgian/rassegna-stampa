<div>
    <p class="crumbs">
        <a href="{{ route('clienti.index') }}" wire:navigate>Clienti</a> /
        <a href="{{ route('clienti.show', $rassegna->cliente) }}" wire:navigate>{{ $rassegna->cliente->nome }}</a> /
        {{ $rassegna->titolo }}
    </p>

    <div class="page-head spread">
        <div>
            <h1 class="mt-0">{{ $rassegna->titolo }}</h1>
            <p>
                @if ($rassegna->comunicato_data)
                    Comunicato del {{ $rassegna->comunicato_data->format('d/m/Y') }} ·
                @else
                    Rassegna di periodo ·
                @endif
                monitoraggio {{ $rassegna->monitoraggio_inizio->format('d/m/Y') }} → {{ $rassegna->monitoraggio_fine->format('d/m/Y') }}
            </p>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <x-stato-rassegna :stato="$rassegna->stato" />
            @if ($puoModificare)
                <a class="btn small" href="{{ route('rassegne.edit', $rassegna) }}" wire:navigate style="text-decoration:none;">Modifica</a>
            @endif
        </div>
    </div>

    <div class="card">
        <h2>Parole chiave</h2>
        <label class="field">Richieste</label>
        <div class="tags" style="margin-bottom:12px;">
            @forelse ($rassegna->parole_chiave ?? [] as $kw)
                <span class="pill accent">{{ $kw }}</span>
            @empty
                <span class="muted">nessuna</span>
            @endforelse
        </div>
        <label class="field">Da escludere</label>
        <div class="tags">
            @forelse ($rassegna->parole_escluse ?? [] as $kw)
                <span class="pill danger">{{ $kw }}</span>
            @empty
                <span class="muted">nessuna</span>
            @endforelse
        </div>
    </div>

    <div class="card">
        <h2>Uscite raccolte</h2>
        <div class="note">La raccolta candidati, la revisione e la generazione del PDF arrivano nelle milestone successive (M2–M4).</div>
    </div>
</div>
