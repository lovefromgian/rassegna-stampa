<?php

namespace App\Livewire;

use App\Enums\StatoUscita;
use App\Models\Cliente;
use App\Models\DocumentoGenerato;
use App\Models\Rassegna;
use App\Models\Testata;
use App\Models\Uscita;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Statistiche per cliente e per testata (PROGRESS M5.24). Conta le uscite raccolte,
 * trasversalmente a rassegne e anni.
 */
class Statistiche extends Component
{
    public function render(): View
    {
        // Uscite per cliente (via rassegne).
        $perCliente = Cliente::query()
            ->withCount(['rassegne'])
            ->get()
            ->map(function (Cliente $c) {
                $c->uscite_totali = Uscita::whereHas('rassegna', fn ($q) => $q->where('cliente_id', $c->id))->count();

                return $c;
            })
            ->sortByDesc('uscite_totali')
            ->values();

        // Testate più presenti.
        $perTestata = Testata::query()
            ->withCount('uscite')
            ->orderByDesc('uscite_count')
            ->limit(15)
            ->get();

        // Uscite per tipo di media.
        $perTipo = Uscita::query()
            ->select('tipo_media', DB::raw('count(*) as n'))
            ->groupBy('tipo_media')
            ->pluck('n', 'tipo_media');

        return view('livewire.statistiche', [
            'perCliente' => $perCliente,
            'perTestata' => $perTestata,
            'perTipo' => $perTipo,
            'totali' => [
                'clienti' => Cliente::count(),
                'rassegne' => Rassegna::count(),
                'uscite' => Uscita::count(),
                'approvate' => Uscita::where('stato', StatoUscita::Approvato)->count(),
                'pdf' => DocumentoGenerato::count(),
            ],
        ]);
    }
}
