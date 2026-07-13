<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Motore di cattura pagine
    |--------------------------------------------------------------------------
    |
    | Configurazione della cattura via Playwright/Chromium (scripts/capture.js).
    | Il disco è quello di Laravel: oggi 'public', domani S3 cambiando solo qui
    | (regole-business.md §12).
    |
    */

    'node_binary' => env('CAPTURE_NODE_BINARY', 'node'),

    'script_path' => base_path('scripts/capture.cjs'),

    'timeout' => (int) env('CAPTURE_TIMEOUT', 120),

    // Disco Laravel su cui persistere screenshot / PDF pagina.
    'disk' => env('CAPTURE_DISK', 'public'),

    // Cartelle sul disco per gli artefatti.
    'path_screenshot' => 'catture/screenshot',
    'path_pdf' => 'catture/pdf',

    // Impaginazione PDF: gli screenshot più alti di (larghezza × questo rapporto) vengono
    // ritagliati in alto, così restano a tutta larghezza ma stanno con il testo in una
    // pagina. ~1.3 = altezza utile / larghezza utile di una pagina A4 sotto l'intestazione.
    'pdf_crop_ratio' => (float) env('CAPTURE_PDF_CROP_RATIO', 1.1),

];
