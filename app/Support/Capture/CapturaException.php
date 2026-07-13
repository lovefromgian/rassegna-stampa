<?php

namespace App\Support\Capture;

use RuntimeException;

/**
 * Fallimento di cattura con messaggio leggibile per l'operatore (regole-business.md §4:
 * "ogni fallimento salva un errore_cattura leggibile"). Nessun fallimento va ignorato.
 */
class CapturaException extends RuntimeException {}
