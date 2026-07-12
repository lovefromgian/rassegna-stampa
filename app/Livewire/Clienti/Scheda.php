<?php

namespace App\Livewire\Clienti;

use App\Models\Cliente;
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

    public function render(): View
    {
        $this->cliente->load(['rassegne' => fn ($q) => $q->latest()]);

        return view('livewire.clienti.scheda', [
            'puoModificare' => Gate::allows('update', $this->cliente),
            'puoEliminare' => Gate::allows('delete', $this->cliente),
        ]);
    }
}
