<?php

namespace App\Livewire;

use App\Models\Cliente;
use App\Models\Testata;
use App\Models\Uscita;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Archivio: ricerca full-text sul testo estratto di tutte le uscite mai raccolte,
 * trasversale a clienti e anni (mockup 09). Su MySQL usa l'indice FULLTEXT; su PostgreSQL
 * (produzione) ripiega su ILIKE; su SQLite (dev/test) su LIKE. La fonte resta il testo catturato.
 */
class Archivio extends Component
{
    use WithPagination;

    public string $termine = '';

    public ?int $clienteId = null;

    public ?int $testataId = null;

    public function updating(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $risultati = null;

        if (trim($this->termine) !== '') {
            $query = Uscita::query()
                ->with(['testata', 'rassegna.cliente'])
                ->whereNotNull('testo_estratto');

            $driver = DB::connection()->getDriverName();
            if ($driver === 'mysql') {
                $query->whereFullText('testo_estratto', $this->termine);
            } else {
                // PostgreSQL: LIKE è case-sensitive → ILIKE. SQLite: LIKE è già case-insensitive.
                $op = $driver === 'pgsql' ? 'ilike' : 'like';
                $t = '%'.$this->termine.'%';
                $query->where(fn ($q) => $q->where('testo_estratto', $op, $t)->orWhere('titolo', $op, $t));
            }

            if ($this->testataId) {
                $query->where('testata_id', $this->testataId);
            }
            if ($this->clienteId) {
                $query->whereHas('rassegna', fn ($q) => $q->where('cliente_id', $this->clienteId));
            }

            $risultati = $query->latest('data_pubblicazione')->paginate(20);
        }

        return view('livewire.archivio', [
            'risultati' => $risultati,
            'clienti' => Cliente::orderBy('nome')->get(['id', 'nome']),
            'testate' => Testata::orderBy('nome')->get(['id', 'nome']),
        ]);
    }
}
