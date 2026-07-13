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
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:flex-end;">
            <x-stato-rassegna :stato="$rassegna->stato" />
            @if ($puoModificare)
                <a class="btn small" href="{{ route('rassegne.edit', $rassegna) }}" wire:navigate style="text-decoration:none;">Modifica</a>
            @endif

            @if ($rassegna->stato === \App\Enums\StatoRassegna::InRaccolta && $puoModificare)
                <button class="btn small" wire:click="chiudiRaccolta" wire:confirm="Chiudere la raccolta? Si passa alla revisione e la scansione automatica si ferma.">Chiudi raccolta</button>
            @endif
            @if ($rassegna->stato === \App\Enums\StatoRassegna::InRevisione && $puoModificare)
                <button class="btn small" wire:click="chiudiRassegna">Chiudi rassegna</button>
            @endif
            @if ($puoRiaprire && in_array($rassegna->stato, [\App\Enums\StatoRassegna::Chiusa, \App\Enums\StatoRassegna::Riaperta], true))
                <button class="btn small" wire:click="riapri" wire:confirm="Riaprire la rassegna? Il PDF già generato resta; si genererà una nuova versione.">Riapri</button>
            @endif
        </div>
    </div>

    @include('partials.fasi-rassegna', ['rassegna' => $rassegna, 'corrente' => $faseCorrente])

    {{-- Metriche a colpo d'occhio (UX-02, mockup 05): dagli stati delle uscite --}}
    <div class="metrics">
        <div class="metric"><div class="label">Candidati da decidere</div><div class="value">{{ $metriche['candidati'] }}</div></div>
        <div class="metric"><div class="label">Da revisionare</div><div class="value">{{ $metriche['daRevisionare'] }}</div></div>
        <div class="metric"><div class="label">Approvate</div><div class="value">{{ $metriche['approvate'] }}</div></div>
        <div class="metric"><div class="label">Scartate</div><div class="value">{{ $metriche['scartate'] }}</div></div>
    </div>

    {{-- Prossimo passo contestuale (UX-01): un solo primario con conteggio --}}
    <div class="card">
        <h2>Prossimo passo</h2>
        <div class="actions" style="flex-direction:column;gap:10px;">
            <a class="btn wide {{ $prossimo === 'conferma' ? 'primary' : '' }}" @if ($prossimo === 'conferma') data-passo="conferma" @endif
               href="{{ route('rassegne.candidati', $rassegna) }}" wire:navigate style="text-decoration:none;text-align:center;">
                @if ($metriche['candidati'] === 1)
                    Conferma 1 candidato proposto
                @elseif ($metriche['candidati'] > 1)
                    Conferma i {{ $metriche['candidati'] }} candidati proposti
                @else
                    Candidati
                @endif
            </a>
            <a class="btn wide {{ $prossimo === 'revisiona' ? 'primary' : '' }}" @if ($prossimo === 'revisiona') data-passo="revisiona" @endif
               href="{{ route('rassegne.revisione', $rassegna) }}" wire:navigate style="text-decoration:none;text-align:center;">
                @if ($metriche['daRevisionare'] === 1)
                    Revisiona 1 uscita in attesa
                @elseif ($metriche['daRevisionare'] > 1)
                    Revisiona le {{ $metriche['daRevisionare'] }} uscite in attesa
                @elseif ($inCattura > 0)
                    Cattura in corso ({{ $inCattura }})
                @else
                    Revisiona le uscite catturate
                @endif
            </a>
            <a class="btn wide {{ in_array($prossimo, ['pdf', 'chiusa'], true) ? 'primary' : '' }}" @if (in_array($prossimo, ['pdf', 'chiusa'], true)) data-passo="pdf" @endif
               href="{{ route('rassegne.pdf', $rassegna) }}" wire:navigate style="text-decoration:none;text-align:center;">
                {{ $prossimo === 'chiusa' ? 'Vedi e scarica il PDF' : 'Ordina e genera il PDF' }}
            </a>
        </div>
        <div class="note" style="margin-top:14px;">{{ $nota }}</div>
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

    <div id="uscite"></div>
    <livewire:uscite.gestore :rassegna="$rassegna" :key="'uscite-'.$rassegna->id" />
</div>
