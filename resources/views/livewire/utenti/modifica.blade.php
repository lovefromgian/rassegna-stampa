<div>
    <p class="crumbs"><a href="{{ route('utenti.index') }}" wire:navigate>Utenti</a> / {{ $utente ? $utente->name : 'Nuovo utente' }}</p>

    <div class="page-head">
        <h1 class="mt-0">{{ $utente ? 'Modifica utente' : 'Nuovo utente' }}</h1>
        <p>Ruolo: supervisore (tutto) oppure operatore (no anagrafica, no eliminazioni).</p>
    </div>

    <form wire:submit="salva">
        <div class="card">
            <div class="form-grid">
                <div>
                    <label class="field" for="name">Nome</label>
                    <input type="text" id="name" wire:model="name" class="{{ $errors->has('name') ? 'invalid' : '' }}">
                    @error('name') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="field" for="email">Email</label>
                    <input type="email" id="email" wire:model="email" class="{{ $errors->has('email') ? 'invalid' : '' }}">
                    @error('email') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="field" for="ruolo">Ruolo</label>
                    <select id="ruolo" wire:model="ruolo">
                        @foreach ($ruoli as $r)
                            <option value="{{ $r->value }}">{{ $r->etichetta() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="field" for="password">Password @if ($utente)<span class="muted">(lascia vuoto per non cambiarla)</span>@endif</label>
                    <input type="password" id="password" wire:model="password" class="{{ $errors->has('password') ? 'invalid' : '' }}" autocomplete="new-password">
                    @error('password') <div class="field-error">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="actions" style="max-width:340px;">
            <a class="btn" href="{{ route('utenti.index') }}" wire:navigate>Annulla</a>
            <button type="submit" class="btn primary">Salva</button>
        </div>
    </form>
</div>
