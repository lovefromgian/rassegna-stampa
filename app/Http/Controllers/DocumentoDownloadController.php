<?php

namespace App\Http\Controllers;

use App\Models\DocumentoGenerato;
use App\Services\Audit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Download di una versione del PDF. Registra chi scarica e quando (audit §11) e segna
 * `scaricato_il` sulla versione. Il file vive sul disco Laravel.
 */
class DocumentoDownloadController extends Controller
{
    public function __invoke(DocumentoGenerato $documento): StreamedResponse
    {
        Gate::authorize('download', $documento);

        $disk = Storage::disk(config('capture.disk'));
        abort_unless($disk->exists($documento->file_path), 404);

        if ($documento->scaricato_il === null) {
            $documento->update(['scaricato_il' => now()]);
        }
        Audit::registra('scarica_pdf', $documento, ['versione' => $documento->versione]);

        $nomeFile = 'rassegna-'.$documento->rassegna_id.'-v'.$documento->versione.'.pdf';

        return $disk->download($documento->file_path, $nomeFile);
    }
}
