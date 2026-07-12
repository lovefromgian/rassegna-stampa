<?php

namespace App\Models;

use App\Enums\StatoRassegna;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rassegna extends Model
{
    /** @use HasFactory<\Database\Factories\RassegnaFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'rassegne';

    protected $fillable = [
        'cliente_id',
        'titolo',
        'comunicato_titolo',
        'comunicato_sottotitolo',
        'comunicato_data',
        'comunicato_testo',
        'comunicato_file_path',
        'parole_chiave',
        'parole_escluse',
        'monitoraggio_inizio',
        'monitoraggio_fine',
        'stato',
    ];

    protected function casts(): array
    {
        return [
            'comunicato_data' => 'date',
            'parole_chiave' => 'array',
            'parole_escluse' => 'array',
            'monitoraggio_inizio' => 'date',
            'monitoraggio_fine' => 'date',
            'stato' => StatoRassegna::class,
        ];
    }

    /** @return BelongsTo<Cliente, $this> */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /** @return HasMany<Uscita, $this> */
    public function uscite(): HasMany
    {
        return $this->hasMany(Uscita::class);
    }

    /** @return HasMany<DocumentoGenerato, $this> */
    public function documentiGenerati(): HasMany
    {
        return $this->hasMany(DocumentoGenerato::class);
    }

    /**
     * Rassegne con periodo di monitoraggio attivo: inizio ≤ oggi ≤ fine.
     * Usato dallo scheduler per la scansione giornaliera (regole-business.md §2).
     *
     * @param  Builder<Rassegna>  $query
     */
    public function scopeConPeriodoAttivo(Builder $query): void
    {
        $oggi = now()->toDateString();
        $query->whereDate('monitoraggio_inizio', '<=', $oggi)
            ->whereDate('monitoraggio_fine', '>=', $oggi);
    }
}
