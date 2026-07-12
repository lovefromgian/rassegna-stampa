<?php

namespace App\Models;

use App\Enums\StatoCliente;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    /** @use HasFactory<\Database\Factories\ClienteFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'clienti';

    protected $fillable = [
        'nome',
        'referente',
        'email_referente',
        'telefono',
        'destinatari_invio',
        'logo_path',
        'colore_accento',
        'note',
        'stato',
    ];

    protected function casts(): array
    {
        return [
            'destinatari_invio' => 'array',
            'stato' => StatoCliente::class,
        ];
    }

    /** @return HasMany<Rassegna, $this> */
    public function rassegne(): HasMany
    {
        return $this->hasMany(Rassegna::class);
    }
}
