<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoGenerato extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentoGeneratoFactory> */
    use HasFactory;

    protected $table = 'documenti_generati';

    protected $fillable = [
        'rassegna_id',
        'versione',
        'file_path',
        'generato_da',
        'generato_il',
        'scaricato_il',
        'uscite_incluse',
    ];

    protected function casts(): array
    {
        return [
            'versione' => 'integer',
            'generato_il' => 'datetime',
            'scaricato_il' => 'datetime',
            'uscite_incluse' => 'array',
        ];
    }

    /** @return BelongsTo<Rassegna, $this> */
    public function rassegna(): BelongsTo
    {
        return $this->belongsTo(Rassegna::class);
    }

    /** @return BelongsTo<User, $this> */
    public function generatoDa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generato_da');
    }
}
