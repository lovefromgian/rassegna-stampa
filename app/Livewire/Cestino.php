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
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Cestino: elenca i record in soft delete (clienti, rassegne, uscite) e permette di
 * ripristinarli. Strumento del supervisore. La cancellazione definitiva NON è prevista:
 * "nulla si cancella fisicamente" (CLAUDE.md §6, regole-business.md §10).
 */
class Cestino extends Component
{
    use NotificaUtente;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSupervisore(), 403);
    }

    public function ripristina(string $tipo, int $id): void
    {
        $model = $this->trovato($tipo, $id);
        Gate::authorize('restore', $model);

        $model->restore();
        Audit::registra('ripristina_'.$tipo, $model);

        $this->notifica(ucfirst($tipo).' ripristinato.');
    }

    /**
     * Cancellazione DEFINITIVA (fisica), irreversibile: solo supervisore (Gate forceDelete),
     * deroga autorizzata a §10. Registra l'audit PRIMA di distruggere il record.
     */
    public function eliminaDefinitivo(string $tipo, int $id, EliminazioneDefinitiva $servizio): void
    {
        $model = $this->trovato($tipo, $id);
        Gate::authorize('forceDelete', $model);

        Audit::registra('elimina_definitiva_'.$tipo, $model, ['id' => $id]);
        $servizio->elimina($model);

        $this->notifica(ucfirst($tipo).' eliminato definitivamente.', 'error');
    }

    private function trovato(string $tipo, int $id): Model
    {
        return match ($tipo) {
            'cliente' => Cliente::onlyTrashed()->findOrFail($id),
            'rassegna' => Rassegna::onlyTrashed()->findOrFail($id),
            'uscita' => Uscita::onlyTrashed()->findOrFail($id),
            default => abort(404),
        };
    }

    public function render(): View
    {
        return view('livewire.cestino', [
            'clienti' => Cliente::onlyTrashed()->latest('deleted_at')->get(),
            'rassegne' => Rassegna::onlyTrashed()
                ->with(['cliente' => fn ($q) => $q->withTrashed()])
                ->latest('deleted_at')->get(),
            'uscite' => Uscita::onlyTrashed()
                ->with(['testata', 'rassegna' => fn ($q) => $q->withTrashed()])
                ->latest('deleted_at')->get(),
        ]);
    }
}
