<?php

namespace App\Livewire\Clienti;

use App\Models\Cliente;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Elenco extends Component
{
    use WithPagination;

    public string $ricerca = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Cliente::class);
    }

    public function updatingRicerca(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $clienti = Cliente::query()
            ->when($this->ricerca !== '', fn ($q) => $q->where('nome', 'like', '%'.$this->ricerca.'%'))
            ->withCount('rassegne')
            ->orderBy('nome')
            ->paginate(15);

        return view('livewire.clienti.elenco', [
            'clienti' => $clienti,
            'puoCreare' => Gate::allows('create', Cliente::class),
        ]);
    }
}
