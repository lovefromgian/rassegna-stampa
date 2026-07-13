<div>
    <div class="card">
        <div class="spread" style="margin-bottom:12px;">
            <h2 style="margin:0;">Uscite raccolte ({{ $uscite->count() }})</h2>
            @if ($puoAggiungere && ! $mostraForm)
                <button class="btn primary small" wire:click="nuovaUscita">+ Aggiungi uscita</button>
            @endif
        </div>

        {{-- Form nuova uscita (aggiunta manuale) --}}
        @if ($mostraForm)
            <div class="card" style="background:var(--surface-alt);">
                <h2 style="font-size:15px;">Nuova uscita</h2>
                <form wire:submit="salvaUscita">
                    <div class="form-grid">
                        <div>
                            <label class="field" for="tipo_media">Tipo di media</label>
                            <select id="tipo_media" wire:model.live="tipo_media">
                                @foreach ($tipiMedia as $t)
                                    <option value="{{ $t->value }}">{{ $t->etichetta() }}</option>
                                @endforeach
                            </select>
                            @if ($isOnline)
                                <p class="muted" style="font-size:12px;margin:-8px 0 12px;">Online: dopo il salvataggio parte la cattura automatica.</p>
                            @else
                                <p class="muted" style="font-size:12px;margin:-8px 0 12px;">Media non online: carica il ritaglio/file qui sotto.</p>
                            @endif
                        </div>
                        <div>
                            <label class="field" for="testata_nome">Testata</label>
                            <input type="text" id="testata_nome" wire:model="testata_nome" class="{{ $errors->has('testata_nome') ? 'invalid' : '' }}" placeholder="Il Goriziano">
                            @error('testata_nome') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="full">
                            <label class="field" for="titolo">Titolo dell'articolo</label>
                            <input type="text" id="titolo" wire:model="titolo" class="{{ $errors->has('titolo') ? 'invalid' : '' }}">
                            @error('titolo') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="field" for="data_pubblicazione">Data di pubblicazione</label>
                            <input type="date" id="data_pubblicazione" wire:model="data_pubblicazione" class="{{ $errors->has('data_pubblicazione') ? 'invalid' : '' }}">
                            @error('data_pubblicazione') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        @if ($isOnline)
                            <div>
                                <label class="field" for="url">URL</label>
                                <input type="text" id="url" wire:model="url" class="{{ $errors->has('url') ? 'invalid' : '' }}" placeholder="https://…">
                                @error('url') <div class="field-error">{{ $message }}</div> @enderror
                            </div>
                        @else
                            <div>
                                <label class="field" for="pagina_giornale">Pagina (es. "pag 55")</label>
                                <input type="text" id="pagina_giornale" wire:model="pagina_giornale">
                            </div>
                            <div class="full">
                                <label class="field" for="fileRitaglio">Ritaglio / file (jpg, png, pdf)</label>
                                <input type="file" id="fileRitaglio" wire:model="fileRitaglio" accept="image/*,application/pdf">
                                @error('fileRitaglio') <div class="field-error">{{ $message }}</div> @enderror
                            </div>
                        @endif
                    </div>
                    <div class="actions" style="max-width:300px;">
                        <button type="button" class="btn" wire:click="annulla">Annulla</button>
                        <button type="submit" class="btn primary">Salva uscita</button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Elenco uscite --}}
        <div class="list">
            @forelse ($uscite as $uscita)
                <div class="row" style="align-items:flex-start;">
                    <div class="main">
                        <div class="title">{{ $uscita->testata->nome }}@if ($uscita->pagina_giornale) · {{ $uscita->pagina_giornale }} @endif</div>
                        <div class="sub">{{ $uscita->titolo }}</div>
                        <div class="sub">
                            {{ $uscita->data_pubblicazione->format('d/m/Y') }} · {{ $uscita->tipo_media->etichetta() }}
                            @if ($uscita->url) · <a href="{{ $uscita->url }}" target="_blank" rel="noopener">apri</a> @endif
                        </div>

                        @if ($uscita->stato_cattura === $statiInCattura[0] || $uscita->stato_cattura === $statiInCattura[1])
                            <div class="note" style="margin-top:8px;" wire:poll.3s>Cattura in corso… l'esito compare qui appena il worker la elabora.</div>
                        @endif

                        @if ($uscita->errore_cattura)
                            <div class="flash danger" style="margin-top:8px;">
                                <strong>Cattura fallita:</strong> {{ $uscita->errore_cattura }}
                            </div>
                        @endif

                        @if ($uscita->screenshot_path)
                            <div style="margin-top:8px;">
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk(config('capture.disk'))->url($uscita->screenshot_path) }}"
                                     alt="Screenshot" style="max-width:220px;max-height:160px;border:1px solid var(--border);border-radius:6px;">
                            </div>
                        @endif

                        {{-- Sostituzione manuale del file --}}
                        @if ($uscitaFileId === $uscita->id)
                            <div style="margin-top:8px;display:flex;gap:8px;align-items:center;">
                                <input type="file" wire:model="fileSostitutivo" accept="image/*,application/pdf">
                                <button class="btn small primary" wire:click="salvaFileSostitutivo">Carica</button>
                                <button class="btn small" wire:click="$set('uscitaFileId', null)">Annulla</button>
                            </div>
                            @error('fileSostitutivo') <div class="field-error">{{ $message }}</div> @enderror
                        @endif
                    </div>

                    <div style="display:flex;flex-direction:column;gap:6px;align-items:flex-end;">
                        <x-stato-uscita :uscita="$uscita" />
                        <div class="actions" style="flex-wrap:wrap;justify-content:flex-end;">
                            @if ($uscita->richiedeCatturaWeb() && $uscita->stato_cattura !== $statiInCattura[0] && $uscita->stato_cattura !== $statiInCattura[1])
                                <button class="btn small" wire:click="avviaCattura({{ $uscita->id }})">
                                    {{ $uscita->errore_cattura ? 'Ricattura' : 'Cattura' }}
                                </button>
                            @endif
                            <button class="btn small" wire:click="$set('uscitaFileId', {{ $uscita->id }})">Sostituisci file</button>
                            @if ($uscita->stato !== \App\Enums\StatoUscita::Scartato)
                                <button class="btn small danger" wire:click="scarta({{ $uscita->id }})"
                                        wire:confirm="Scartare questa uscita? Resta archiviata e recuperabile.">Scarta</button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty">Nessuna uscita. Aggiungine una a mano oppure attendi le scansioni automatiche (M4).</div>
            @endforelse
        </div>
    </div>
</div>
