<?php

namespace App\Livewire\Clienti;

use App\Enums\StatoCliente;
use App\Models\Cliente;
use App\Services\Audit;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Creazione/modifica cliente. SOLO supervisore: il vincolo è applicato lato server
 * con Gate::authorize sia al montaggio sia al salvataggio (non solo nascondendo la UI).
 */
class Modifica extends Component
{
    use WithFileUploads;

    public ?Cliente $cliente = null;

    /** Nuovo logo caricato (opzionale). Sostituisce il precedente. */
    #[Validate('nullable|image|max:2048')]
    public ?TemporaryUploadedFile $logo = null;

    #[Validate('required|string|max:255')]
    public string $nome = '';

    #[Validate('nullable|string|max:255')]
    public string $referente = '';

    #[Validate('nullable|email|max:255')]
    public string $email_referente = '';

    #[Validate('nullable|string|max:50')]
    public string $telefono = '';

    /** Testo grezzo: una email per riga, convertito in array al salvataggio. */
    #[Validate('nullable|string')]
    public string $destinatari_invio = '';

    #[Validate('nullable|string|max:7')]
    public string $colore_accento = '';

    #[Validate('nullable|string')]
    public string $note = '';

    public string $stato = 'attivo';

    public function mount(?Cliente $cliente = null): void
    {
        if ($cliente && $cliente->exists) {
            Gate::authorize('update', $cliente);
            $this->cliente = $cliente;
            $this->nome = $cliente->nome;
            $this->referente = $cliente->referente ?? '';
            $this->email_referente = $cliente->email_referente ?? '';
            $this->telefono = $cliente->telefono ?? '';
            $this->destinatari_invio = implode("\n", $cliente->destinatari_invio ?? []);
            $this->colore_accento = $cliente->colore_accento ?? '';
            $this->note = $cliente->note ?? '';
            $this->stato = $cliente->stato->value;
        } else {
            Gate::authorize('create', Cliente::class);
        }
    }

    public function salva(): void
    {
        // Ri-autorizzazione lato server: la UI nascosta non basta.
        $this->cliente
            ? Gate::authorize('update', $this->cliente)
            : Gate::authorize('create', Cliente::class);

        $dati = $this->validate([
            'stato' => [Rule::enum(StatoCliente::class)],
        ]);

        $destinatari = collect(preg_split('/\r\n|\r|\n/', $this->destinatari_invio))
            ->map(fn ($r) => trim($r))
            ->filter()
            ->values()
            ->all();

        $payload = [
            'nome' => $this->nome,
            'referente' => $this->referente ?: null,
            'email_referente' => $this->email_referente ?: null,
            'telefono' => $this->telefono ?: null,
            'destinatari_invio' => $destinatari,
            'colore_accento' => $this->colore_accento ?: null,
            'note' => $this->note ?: null,
            'stato' => $this->stato,
        ];

        // Logo: scritto SEMPRE tramite il disco Laravel (mai percorsi assoluti).
        // Oggi disco locale 'public', domani S3 cambiando solo la configurazione.
        if ($this->logo) {
            if ($this->cliente?->logo_path) {
                Storage::disk('public')->delete($this->cliente->logo_path);
            }
            $payload['logo_path'] = $this->logo->store('loghi', 'public');
        }

        if ($this->cliente) {
            $this->cliente->update($payload);
            Audit::registra('modifica_cliente', $this->cliente);
            $messaggio = 'Cliente aggiornato.';
        } else {
            $this->cliente = Cliente::create($payload);
            Audit::registra('crea_cliente', $this->cliente);
            $messaggio = 'Cliente creato.';
        }

        session()->flash('success', $messaggio);
        $this->redirectRoute('clienti.show', $this->cliente, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.clienti.modifica');
    }
}
