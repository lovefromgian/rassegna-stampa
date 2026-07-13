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
        <h2>Prossimo passo</h2>
        <div class="actions" style="flex-direction:column;gap:10px;">
            <a class="btn wide" href="{{ route('rassegne.candidati', $rassegna) }}" wire:navigate style="text-decoration:none;text-align:center;">Conferma i candidati proposti</a>
            <a class="btn wide" href="{{ route('rassegne.revisione', $rassegna) }}" wire:navigate style="text-decoration:none;text-align:center;">Revisiona le uscite catturate</a>
            <a class="btn primary wide" href="{{ route('rassegne.pdf', $rassegna) }}" wire:navigate style="text-decoration:none;text-align:center;">Ordina e genera il PDF</a>
        </div>
        <div class="note" style="margin-top:14px;">Il PDF si genera solo quando nessuna uscita resta in stato "candidato" e ogni uscita approvata ha uno screenshot valido.</div>
    </div>

    <div id="uscite"></div>
    <livewire:uscite.gestore :rassegna="$rassegna" :key="'uscite-'.$rassegna->id" />
</div>
