<?php

namespace App\Models;

use App\Enums\RuoloUtente;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'ruolo',
        'attivo',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'ruolo' => RuoloUtente::class,
            'attivo' => 'boolean',
        ];
    }

    public function isSupervisore(): bool
    {
        return $this->ruolo === RuoloUtente::Supervisore;
    }

    public function isOperatore(): bool
    {
        return $this->ruolo === RuoloUtente::Operatore;
    }

    /** @return HasMany<DocumentoGenerato, $this> */
    public function documentiGenerati(): HasMany
    {
        return $this->hasMany(DocumentoGenerato::class, 'generato_da');
    }
}
