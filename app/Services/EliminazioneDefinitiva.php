<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Rassegna;
use App\Models\Uscita;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Cancellazione DEFINITIVA (fisica) di un record dal cestino. Deroga autorizzata alla regola
 * "nulla si cancella fisicamente" (regole-business.md §10). Irreversibile: rimuove il record
 * e, a cascata, i figli e i relativi file su disco. Non tocca il log di audit (immutabile).
 *
 * Da invocare solo dopo autorizzazione (Gate 'forceDelete') e conferma dell'utente.
 */
class EliminazioneDefinitiva
{
    public function elimina(Model $record): void
    {
        DB::transaction(function () use ($record) {
            match (true) {
                $record instanceof Cliente => $this->cliente($record),
                $record instanceof Rassegna => $this->rassegna($record),
                $record instanceof Uscita => $this->uscita($record),
                default => null,
            };
        });
    }

    private function cliente(Cliente $cliente): void
    {
        $cliente->rassegne()->withTrashed()->get()->each(fn (Rassegna $r) => $this->rassegna($r));
        $cliente->forceDelete();
    }

    private function rassegna(Rassegna $rassegna): void
    {
        $rassegna->uscite()->withTrashed()->get()->each(fn (Uscita $u) => $this->uscita($u));

        $rassegna->documentiGenerati()->get()->each(function ($doc) {
            $this->cancellaFile($doc->file_path);
            $doc->delete();
        });

        $rassegna->forceDelete();
    }

    private function uscita(Uscita $uscita): void
    {
        foreach ([$uscita->screenshot_path, $uscita->pdf_pagina_path, $uscita->file_caricato_path] as $path) {
            $this->cancellaFile($path);
        }
        $uscita->forceDelete();
    }

    private function cancellaFile(?string $path): void
    {
        if ($path) {
            Storage::disk(config('capture.disk'))->delete($path);
        }
    }
}
