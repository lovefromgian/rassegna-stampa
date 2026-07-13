<?php

namespace App\Livewire\Concerns;

/**
 * Notifica l'utente da un'azione Livewire "in place" (senza redirect). I flash di sessione
 * non funzionano in questi casi perché il layout (dove sono renderizzati) non si ri-renderizza:
 * qui si emette un evento browser che un piccolo listener nel layout trasforma in un toast.
 */
trait NotificaUtente
{
    public function notifica(string $messaggio, string $tipo = 'success'): void
    {
        $this->dispatch('notifica', messaggio: $messaggio, tipo: $tipo);
    }
}
