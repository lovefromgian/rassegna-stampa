<?php

namespace App\Jobs;

use App\Enums\StatoCattura;
use App\Enums\StatoUscita;
use App\Models\Uscita;
use App\Support\Capture\CapturaException;
use App\Support\Capture\PageCapturer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Cattura di un'uscita online. SEMPRE in coda, mai sincrona nella richiesta HTTP
 * (CLAUDE.md §4). Usa il motore dietro interfaccia (PageCapturer) e persiste gli
 * artefatti tramite il disco Laravel (regole-business.md §12).
 *
 * Al successo: screenshot + PDF + testo salvati, stato_cattura = completata, l'uscita
 * passa a `catturato`. Al fallimento: errore_cattura leggibile, stato_cattura = errore;
 * l'uscita resta `confermato` e va risolta (ricattura / caricamento manuale) o scartata,
 * mai ignorata. Il recupero è guidato dall'operatore: nessun retry automatico silenzioso.
 */
class CatturaUscita implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public Uscita $uscita) {}

    public function handle(PageCapturer $capturer): void
    {
        $uscita = $this->uscita->fresh();

        if (! $uscita || ! $uscita->richiedeCatturaWeb()) {
            return; // niente pagina web da fotografare (media manuale o uscita sparita)
        }

        $uscita->update(['stato_cattura' => StatoCattura::InCorso]);

        try {
            $risultato = $capturer->cattura($uscita->url);

            $disk = config('capture.disk');
            $base = Str::uuid();

            $screenshotPath = config('capture.path_screenshot').'/'.$base.'.png';
            Storage::disk($disk)->put($screenshotPath, $risultato->screenshot);

            $pdfPath = null;
            if ($risultato->pdfPagina !== null) {
                $pdfPath = config('capture.path_pdf').'/'.$base.'.pdf';
                Storage::disk($disk)->put($pdfPath, $risultato->pdfPagina);
            }

            $uscita->update([
                'screenshot_path' => $screenshotPath,
                'pdf_pagina_path' => $pdfPath,
                'testo_estratto' => $risultato->testoEstratto,
                'titolo' => $uscita->titolo ?: ($risultato->titolo ?? $uscita->titolo),
                'stato_cattura' => StatoCattura::Completata,
                'stato' => StatoUscita::Catturato,
                'errore_cattura' => null,
                'cattura_completata_il' => now(),
            ]);
        } catch (CapturaException $e) {
            $this->registraErrore($uscita, $e->getMessage());
        } catch (Throwable $e) {
            $this->registraErrore($uscita, 'Errore imprevisto durante la cattura: '.$e->getMessage());
        }
    }

    private function registraErrore(Uscita $uscita, string $messaggio): void
    {
        $uscita->update([
            'stato_cattura' => StatoCattura::Errore,
            'errore_cattura' => $messaggio,
        ]);
    }
}
