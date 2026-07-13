<div>
    <p class="crumbs">
        <a href="{{ route('rassegne.show', $rassegna) }}" wire:navigate>{{ $rassegna->titolo }}</a> / Revisione
    </p>

    @include('partials.fasi-rassegna', ['rassegna' => $rassegna, 'corrente' => 'revisione'])

    <div class="page-head">
        <h1 class="mt-0">Revisione uscita
            @if ($uscita)<span class="muted" style="font-size:15px;font-weight:400;">— {{ $indice }} di {{ $totale }}</span>@endif
        </h1>
        <p>Verifica la cattura, correggi i metadati, assegna la rilevanza.</p>
    </div>

    @if (! $uscita)
        <div class="card">
            <div class="empty">
                <p><strong>Revisione completata.</strong> Nessuna uscita catturata in attesa.</p>
                <a class="btn primary" href="{{ route('rassegne.pdf', $rassegna) }}" wire:navigate style="text-decoration:none;">Vai all'ordine e generazione PDF</a>
            </div>
        </div>
    @else
        <div style="display:grid;grid-template-columns:1.3fr 1fr;gap:16px;align-items:start;">
            <div class="card">
                <div class="spread" style="margin-bottom:12px;">
                    <h2 style="margin:0;">Anteprima cattura</h2>
                    @if ($uscita->haMaterialeValido())
                        <span class="pill success">Materiale presente</span>
                    @else
                        <span class="pill warning">Nessun materiale</span>
                    @endif
                </div>

                @if ($uscita->screenshot_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk(config('capture.disk'))->url($uscita->screenshot_path) }}"
                         alt="Screenshot" style="width:100%;border:1px solid var(--border);border-radius:6px;max-height:520px;object-fit:cover;object-position:top;">
                @elseif ($uscita->file_caricato_path)
                    <div class="note">File caricato a mano: {{ basename($uscita->file_caricato_path) }}</div>
                @else
                    <div class="shot" style="min-height:200px;">Nessuno screenshot né file. Ricattura o carica un file dalla scheda uscite.</div>
                @endif

                @if ($uscita->errore_cattura)
                    <div class="flash danger" style="margin-top:10px;"><strong>Errore cattura:</strong> {{ $uscita->errore_cattura }}</div>
                @endif

                <div class="actions" style="margin-top:12px;">
                    @if ($uscita->richiedeCatturaWeb())
                        <button class="btn" wire:click="ricattura">Ricattura</button>
                    @endif
                    <a class="btn" href="{{ route('rassegne.uscite', $rassegna) }}" wire:navigate style="text-decoration:none;text-align:center;">Gestisci file</a>
                </div>
                <div class="note" style="margin-top:12px;">Se il banner cookie copre l'articolo o il paywall lo tronca, ricattura oppure carica a mano un file dalla scheda uscite.</div>
            </div>

            <div>
                <div class="card">
                    <h2>Dati dell'uscita</h2>
                    <table class="data">
                        <tr><td>Testata</td><td>{{ $uscita->testata->nome }}</td></tr>
                        <tr><td>Titolo</td><td class="right">{{ $uscita->titolo }}</td></tr>
                        <tr><td>Data</td><td>{{ $uscita->data_pubblicazione->format('d/m/Y') }}</td></tr>
                        @if ($uscita->url)
                            <tr><td>Link</td><td class="right"><a href="{{ $uscita->url }}" target="_blank" rel="noopener">apri</a></td></tr>
                        @endif
                        <tr><td>Testo estratto</td><td>{{ $uscita->testo_estratto ? number_format(mb_strlen($uscita->testo_estratto), 0, ',', '.').' caratteri' : '—' }}</td></tr>
                        <tr><td>Rilevata il</td><td>{{ $uscita->data_rilevamento->format('d/m H:i') }}</td></tr>
                    </table>
                </div>

                <div class="card">
                    <h2>Classificazione</h2>
                    <label class="field" for="tipo_media">Tipo di media</label>
                    <select id="tipo_media" wire:model="tipo_media" class="{{ $errors->has('tipo_media') ? 'invalid' : '' }}">
                        @foreach ($tipiMedia as $t)
                            <option value="{{ $t->value }}">{{ $t->etichetta() }}</option>
                        @endforeach
                    </select>

                    <label class="field" for="rilevanza">Rilevanza</label>
                    <select id="rilevanza" wire:model="rilevanza" class="{{ $errors->has('rilevanza') ? 'invalid' : '' }}">
                        @foreach ($rilevanze as $r)
                            <option value="{{ $r->value }}">{{ $r->etichetta() }}</option>
                        @endforeach
                    </select>
                    @error('rilevanza') <div class="field-error">{{ $message }}</div> @enderror

                    <label class="field" for="note">Note interne</label>
                    <textarea id="note" wire:model="note" placeholder="Visibili solo al team"></textarea>
                </div>

                <div class="actions">
                    <button class="btn danger" wire:click="scarta" wire:confirm="Scartare questa uscita?">Scarta</button>
                    <button class="btn primary" wire:click="approva">Approva e vai avanti</button>
                </div>
            </div>
        </div>
    @endif
</div>
