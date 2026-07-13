<div>
    <p class="crumbs">
        <a href="{{ route('rassegne.index') }}" wire:navigate>Rassegne</a> /
        {{ $rassegna ? 'Modifica' : 'Nuova rassegna' }}
    </p>

    <div class="page-head">
        <h1 class="mt-0">{{ $rassegna ? 'Modifica rassegna' : 'Nuova rassegna' }}</h1>
        <p>Definisci cosa cercare e per quanto tempo.</p>
    </div>

    <form wire:submit="salva">
        <div class="card">
            <h2>Cliente e titolo</h2>
            <div class="form-grid">
                <div>
                    <label class="field" for="cliente_id">Cliente</label>
                    <select id="cliente_id" wire:model="cliente_id" class="{{ $errors->has('cliente_id') ? 'invalid' : '' }}">
                        <option value="">— scegli —</option>
                        @foreach ($clienti as $c)
                            <option value="{{ $c->id }}">{{ $c->nome }}</option>
                        @endforeach
                    </select>
                    @error('cliente_id') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="field" for="titolo">Titolo della rassegna</label>
                    <input type="text" id="titolo" wire:model="titolo" class="{{ $errors->has('titolo') ? 'invalid' : '' }}">
                    @error('titolo') <div class="field-error">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Comunicato</h2>
            <p class="hint m-0 mb-3">Facoltativo: una rassegna può monitorare anche solo un periodo. Se c'è, la data precompila il periodo di monitoraggio.</p>
            <label class="field" for="comunicato_titolo">Titolo del comunicato</label>
            <input type="text" id="comunicato_titolo" wire:model="comunicato_titolo">
            <label class="field" for="comunicato_sottotitolo">Sottotitolo</label>
            <input type="text" id="comunicato_sottotitolo" wire:model="comunicato_sottotitolo">
            <label class="field" for="comunicato_data">Data del comunicato</label>
            <input type="date" id="comunicato_data" wire:model.live="comunicato_data" style="max-width:200px;">
            @error('comunicato_data') <div class="field-error">{{ $message }}</div> @enderror
            <label class="field" for="comunicato_testo">Testo del comunicato</label>
            <textarea id="comunicato_testo" wire:model="comunicato_testo" placeholder="Incolla qui il testo: aiuta a suggerire le parole chiave."></textarea>
        </div>

        <div class="card">
            <h2>Cosa cercare</h2>
            <label class="field" for="parole_chiave">Parole chiave richieste (una per riga)</label>
            <textarea id="parole_chiave" wire:model="parole_chiave" class="{{ $errors->has('parole_chiave') ? 'invalid' : '' }}" placeholder="Grado&#10;musei&#10;Iulia Felix"></textarea>
            @error('parole_chiave') <div class="field-error">{{ $message }}</div> @enderror
            <label class="field" for="parole_escluse">Parole da escludere (una per riga)</label>
            <textarea id="parole_escluse" wire:model="parole_escluse" placeholder="gradi&#10;grado di parentela"></textarea>
            <div class="note">Le esclusioni tagliano i falsi positivi. Un nome ambiguo come "Grado" ne genera parecchi.</div>
        </div>

        <div class="card">
            <h2>Periodo di monitoraggio</h2>
            <div class="form-grid">
                <div>
                    <label class="field" for="monitoraggio_inizio">Dal</label>
                    <input type="date" id="monitoraggio_inizio" wire:model="monitoraggio_inizio" class="{{ $errors->has('monitoraggio_inizio') ? 'invalid' : '' }}">
                    @error('monitoraggio_inizio') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="field" for="monitoraggio_fine">Al</label>
                    <input type="date" id="monitoraggio_fine" wire:model="monitoraggio_fine" class="{{ $errors->has('monitoraggio_fine') ? 'invalid' : '' }}">
                    @error('monitoraggio_fine') <div class="field-error">{{ $message }}</div> @enderror
                </div>
            </div>
            <p style="font-size:13px;color:var(--text-muted);margin:0;">Con un comunicato viene precompilato: data del comunicato + {{ $durataDefaultGiorni }} giorni.</p>
        </div>

        <div class="actions" style="max-width:340px;">
            <a class="btn" href="{{ $rassegna ? route('rassegne.show', $rassegna) : route('rassegne.index') }}" wire:navigate>Annulla</a>
            <button type="submit" class="btn primary">{{ $rassegna ? 'Salva' : 'Crea rassegna' }}</button>
        </div>
    </form>
</div>
