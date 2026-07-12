<?php

namespace App\Livewire\Rassegne;

use App\Models\Rassegna;
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
        Gate::authorize('viewAny', Rassegna::class);
    }

    public function updatingRicerca(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $rassegne = Rassegna::query()
            ->with('cliente')
            ->when($this->ricerca !== '', fn ($q) => $q->where('titolo', 'like', '%'.$this->ricerca.'%'))
            ->withCount('uscite')
            ->latest()
            ->paginate(15);

        return view('livewire.rassegne.elenco', [
            'rassegne' => $rassegne,
            'puoCreare' => Gate::allows('create', Rassegna::class),
        ]);
    }
}
