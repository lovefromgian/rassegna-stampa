<?php

namespace App\Models;

use App\Enums\TipoMedia;
use Database\Factories\TestataFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Testata extends Model
{
    /** @use HasFactory<TestataFactory> */
    use HasFactory;

    protected $table = 'testate';

    protected $fillable = [
        'nome',
        'sito',
        'tipo_prevalente',
        'logo_path',
    ];

    protected function casts(): array
    {
        return [
            'tipo_prevalente' => TipoMedia::class,
        ];
    }

    /** @return HasMany<Uscita, $this> */
    public function uscite(): HasMany
    {
        return $this->hasMany(Uscita::class);
    }
}
