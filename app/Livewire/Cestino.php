<?php

namespace App\Livewire;

use App\Livewire\Concerns\NotificaUtente;
use App\Models\Cliente;
use App\Models\Rassegna;
use App\Models\Uscita;
use App\Services\Audit;
use App\Services\EliminazioneDefinitiva;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Cestino: elenca i record in soft delete (clienti, rassegne, uscite) e permette di
 * ripristinarli o eliminarli definitivamente, singolarmente o in blocco (selezione
 * multipla / svuota cestino). Strumento del supervisore.
 */
class Cestino extends Component
{
    use NotificaUtente;

    /** @var array<int, string> chiavi composite "tipo:id" (es. "cliente:5") */
    public array $selezionati = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSupervisore(), 403);
    }

    // ---- Azioni singole ----

    public function ripristina(string $tipo, int $id): void
    {
        $model = $this->trova($tipo, $id) ?? abort(404);
        Gate::authorize('restore', $model);
        $model->restore();
        Audit::registra('ripristina_'.$tipo, $model);
        $this->notifica(ucfirst($tipo).' ripristinato.');
    }

    public function eliminaDefinitivo(string $tipo, int $id, EliminazioneDefinitiva $servizio): void
    {
        $model = $this->trova($tipo, $id) ?? abort(404);
        Gate::authorize('forceDelete', $model);
        Audit::registra('elimina_definitiva_'.$tipo, $model, ['id' => $id]);
        $servizio->elimina($model);
        $this->notifica(ucfirst($tipo).' eliminato definitivamente.', 'error');
    }

    // ---- Azioni in blocco ----

    public function selezionaTutti(bool $tutti): void
    {
        $this->selezionati = $tutti ? $this->tutteLeChiavi() : [];
    }

    public function ripristinaSelezionati(): void
    {
        $n = 0;
        foreach ($this->coppieOrdinate() as [$tipo, $id]) {
            $model = $this->trova($tipo, $id);
            if (! $model) {
                continue; // già ripristinato/rimosso
            }
            Gate::authorize('restore', $model);
            $model->restore();
            Audit::registra('ripristina_'.$tipo, $model);
            $n++;
        }
        $this->selezionati = [];
        $this->notifica($n.' '.($n === 1 ? 'record ripristinato' : 'record ripristinati').'.');
    }

    public function eliminaSelezionati(EliminazioneDefinitiva $servizio): void
    {
        $n = $this->eliminaCoppie($this->coppieOrdinate(), $servizio);
        $this->selezionati = [];
        $this->notifica($n.' '.($n === 1 ? 'record eliminato' : 'record eliminati').' definitivamente.', 'error');
    }

    public function svuotaCestino(EliminazioneDefinitiva $servizio): void
    {
        $coppie = collect($this->tutteLeChiavi())->map(fn ($k) => explode(':', $k));
        $n = $this->eliminaCoppie($this->ordina($coppie), $servizio);
        $this->selezionati = [];
        $this->notifica("Cestino svuotato: {$n} record eliminati definitivamente.", 'error');
    }

    /**
     * Elimina definitivamente le coppie [tipo, id] già ordinate (cliente → rassegna →
     * uscita), saltando quelle già rimosse da una cascata. Ritorna quante ne ha eliminate.
     *
     * @param  iterable<array{0:string,1:int|string}>  $coppie
     */
    private function eliminaCoppie(iterable $coppie, EliminazioneDefinitiva $servizio): int
    {
        $n = 0;
        foreach ($coppie as [$tipo, $id]) {
            $model = $this->trova($tipo, (int) $id);
            if (! $model) {
                continue; // rimosso da una cascata precedente
            }
            Gate::authorize('forceDelete', $model);
            Audit::registra('elimina_definitiva_'.$tipo, $model, ['id' => (int) $id]);
            $servizio->elimina($model);
            $n++;
        }

        return $n;
    }

    /** Coppie selezionate ordinate: prima i clienti (cascata), poi rassegne, poi uscite. */
    private function coppieOrdinate(): Collection
    {
        return $this->ordina(collect($this->selezionati)->map(fn ($k) => explode(':', $k)));
    }

    private function ordina(Collection $coppie): Collection
    {
        $peso = ['cliente' => 0, 'rassegna' => 1, 'uscita' => 2];

        return $coppie->sortBy(fn ($p) => $peso[$p[0]] ?? 9)->values();
    }

    /** @return array<int, string> */
    private function tutteLeChiavi(): array
    {
        return collect()
            ->merge(Cliente::onlyTrashed()->pluck('id')->map(fn ($id) => "cliente:{$id}"))
            ->merge(Rassegna::onlyTrashed()->pluck('id')->map(fn ($id) => "rassegna:{$id}"))
            ->merge(Uscita::onlyTrashed()->pluck('id')->map(fn ($id) => "uscita:{$id}"))
            ->all();
    }

    private function trova(string $tipo, int $id): ?Model
    {
        return match ($tipo) {
            'cliente' => Cliente::onlyTrashed()->find($id),
            'rassegna' => Rassegna::onlyTrashed()->find($id),
            'uscita' => Uscita::onlyTrashed()->find($id),
            default => null,
        };
    }

    public function render(): View
    {
        $clienti = Cliente::onlyTrashed()->latest('deleted_at')->get();
        $rassegne = Rassegna::onlyTrashed()
            ->with(['cliente' => fn ($q) => $q->withTrashed()])
            ->latest('deleted_at')->get();
        $uscite = Uscita::onlyTrashed()
            ->with(['testata', 'rassegna' => fn ($q) => $q->withTrashed()])
            ->latest('deleted_at')->get();

        return view('livewire.cestino', [
            'clienti' => $clienti,
            'rassegne' => $rassegne,
            'uscite' => $uscite,
            'totale' => $clienti->count() + $rassegne->count() + $uscite->count(),
        ]);
    }
}
