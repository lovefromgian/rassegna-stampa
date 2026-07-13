<?php

namespace App\Livewire\Clienti;

use App\Models\Cliente;
use App\Services\Audit;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Scheda extends Component
{
    public Cliente $cliente;

    public function mount(Cliente $cliente): void
    {
        Gate::authorize('view', $cliente);
        $this->cliente = $cliente;
    }

    /**
     * Elimina il cliente: solo supervisore (ClientePolicy::delete). Soft delete — nulla si
     * cancella fisicamente, è recuperabile (regole-business.md §10).
     */
    public function elimina()
    {
        Gate::authorize('delete', $this->cliente);

        $this->cliente->delete();
        Audit::registra('elimina_cliente', $this->cliente);

        session()->flash('success', 'Cliente eliminato (archiviato, recuperabile).');

        return $this->redirectRoute('clienti.index', navigate: true);
    }

    public function render(): View
    {
        $this->cliente->load(['rassegne' => fn ($q) => $q->latest()]);

        return view('livewire.clienti.scheda', [
            'puoModificare' => Gate::allows('update', $this->cliente),
            'puoEliminare' => Gate::allows('delete', $this->cliente),
        ]);
    }
}
