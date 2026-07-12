<?php

namespace App\Enums;

enum TipoMedia: string
{
    case Online = 'online';
    case Carta = 'carta';
    case Radio = 'radio';
    case Tv = 'tv';
    case Agenzia = 'agenzia';
    case SocialBlog = 'social_blog';

    public function etichetta(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::Carta => 'Carta stampata',
            self::Radio => 'Radio',
            self::Tv => 'TV',
            self::Agenzia => 'Agenzia di stampa',
            self::SocialBlog => 'Social / Blog',
        };
    }

    /**
     * Solo `online` è cercabile automaticamente (docs/regole-business.md §2).
     * Gli altri tipi nascono da inserimento manuale.
     */
    public function cercabileAutomaticamente(): bool
    {
        return $this === self::Online;
    }
}
