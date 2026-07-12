<?php

namespace App\Models;

use App\Enums\Rilevanza;
use App\Enums\StatoUscita;
use App\Enums\TipoMedia;
use Database\Factories\UscitaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Uscita extends Model
{
    /** @use HasFactory<UscitaFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'uscite';

    protected $fillable = [
        'rassegna_id',
        'testata_id',
        'titolo',
        'data_pubblicazione',
        'url',
        'tipo_media',
        'rilevanza',
        'stato',
        'punteggio_corrispondenza',
        'screenshot_path',
        'pdf_pagina_path',
        'testo_estratto',
        'file_caricato_path',
        'pagina_giornale',
        'errore_cattura',
        'posizione_pdf',
        'note',
        'data_rilevamento',
    ];

    protected function casts(): array
    {
        return [
            'data_pubblicazione' => 'date',
            'tipo_media' => TipoMedia::class,
            'rilevanza' => Rilevanza::class,
            'stato' => StatoUscita::class,
            'punteggio_corrispondenza' => 'integer',
            'posizione_pdf' => 'integer',
            'data_rilevamento' => 'datetime',
        ];
    }

    /** @return BelongsTo<Rassegna, $this> */
    public function rassegna(): BelongsTo
    {
        return $this->belongsTo(Rassegna::class);
    }

    /** @return BelongsTo<Testata, $this> */
    public function testata(): BelongsTo
    {
        return $this->belongsTo(Testata::class);
    }

    /**
     * Un'uscita approvata deve avere un materiale valido: screenshot (online)
     * oppure file caricato (carta, radio, TV, agenzia) — regole-business.md §7.
     */
    public function haMaterialeValido(): bool
    {
        return ! empty($this->screenshot_path) || ! empty($this->file_caricato_path);
    }
}
