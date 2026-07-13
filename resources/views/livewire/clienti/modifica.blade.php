<div>
    <p class="crumbs">
        <a href="{{ route('clienti.index') }}" wire:navigate>Clienti</a> /
        {{ $cliente ? $cliente->nome.' / Impostazioni' : 'Nuovo cliente' }}
    </p>

    <div class="page-head">
        <h1 class="mt-0">{{ $cliente ? 'Impostazioni cliente' : 'Nuovo cliente' }}</h1>
        <p>Configurate una volta sola: ogni nuova rassegna del cliente le eredita.</p>
    </div>

    <form wire:submit="salva">
        <div class="card">
            <h2>Anagrafica</h2>
            <div class="form-grid">
                <div class="full">
                    <label class="field" for="nome">Nome</label>
                    <input type="text" id="nome" wire:model="nome" class="{{ $errors->has('nome') ? 'invalid' : '' }}">
                    @error('nome') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="field" for="referente">Referente</label>
                    <input type="text" id="referente" wire:model="referente">
                </div>
                <div>
                    <label class="field" for="email_referente">Email referente</label>
                    <input type="email" id="email_referente" wire:model="email_referente" class="{{ $errors->has('email_referente') ? 'invalid' : '' }}">
                    @error('email_referente') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="field" for="telefono">Telefono</label>
                    <input type="text" id="telefono" wire:model="telefono">
                </div>
                <div>
                    <label class="field" for="stato">Stato</label>
                    <select id="stato" wire:model="stato">
                        @foreach (\App\Enums\StatoCliente::cases() as $s)
                            <option value="{{ $s->value }}">{{ $s->etichetta() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Grafica del PDF</h2>
            <p class="hint m-0 mb-3">L'impaginazione è fissa. Si personalizzano solo logo e colore d'accento.</p>
            <label class="field" for="logo">Logo (copertina)</label>
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px;">
                <div class="shot" style="width:120px;min-height:80px;padding:8px;">
                    @if ($logo)
                        <img src="{{ $logo->temporaryUrl() }}" alt="Anteprima logo" style="max-width:100%;max-height:120px;">
                    @elseif ($cliente?->logo_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($cliente->logo_path) }}" alt="Logo cliente" style="max-width:100%;max-height:120px;">
                    @else
                        nessun logo
                    @endif
                </div>
                <input type="file" id="logo" wire:model="logo" accept="image/*" style="margin:0;">
            </div>
            @error('logo') <div class="field-error">{{ $message }}</div> @enderror
            <label class="field" for="colore_accento">Colore d'accento (bordi e intestazioni)</label>
            <div style="display:flex;align-items:center;gap:10px;">
                <span class="swatch" style="width:32px;height:32px;border-radius:6px;background:{{ $colore_accento ?: 'transparent' }};"></span>
                <input type="text" id="colore_accento" wire:model.live="colore_accento" placeholder="#E8836B" style="margin:0;max-width:160px;">
            </div>
            @error('colore_accento') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="card">
            <h2>Destinatari della rassegna</h2>
            <p class="hint m-0 mb-2">L'invio è manuale: il sistema produce il PDF, l'invio lo fa l'agenzia. Un indirizzo per riga.</p>
            <textarea wire:model="destinatari_invio" placeholder="ufficio.stampa@esempio.it&#10;sindaco@esempio.it"></textarea>
        </div>

        <div class="card">
            <label class="field" for="note">Note interne</label>
            <textarea id="note" wire:model="note"></textarea>
        </div>

        <div class="actions" style="max-width:340px;">
            <a class="btn" href="{{ $cliente ? route('clienti.show', $cliente) : route('clienti.index') }}" wire:navigate>Annulla</a>
            <button type="submit" class="btn primary">Salva</button>
        </div>
    </form>
</div>
