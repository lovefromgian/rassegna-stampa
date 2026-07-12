<?php

namespace App\Livewire\Rassegne;

use App\Models\Rassegna;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Scheda extends Component
{
    public Rassegna $rassegna;

    public function mount(Rassegna $rassegna): void
    {
        Gate::authorize('view', $rassegna);
        $this->rassegna = $rassegna;
    }

    public function render(): View
    {
        $this->rassegna->load('cliente');

        return view('livewire.rassegne.scheda', [
            'puoModificare' => Gate::allows('update', $this->rassegna),
            'puoEliminare' => Gate::allows('delete', $this->rassegna),
            'puoRiaprire' => Gate::allows('riapri', $this->rassegna),
        ]);
    }
}
