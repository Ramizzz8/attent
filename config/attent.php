<?php

return [
    /*
    | Modo demostración (web pública del portafolio): no se envían emails y los
    | enlaces mágicos se muestran en pantalla; el acceso del panel enseña las cuentas de prueba.
    */
    'demo' => (bool) env('DEMO_MODE', false),

    // Estadísticas de visitas sin cookies (GoatCounter autoalojado). Vacío = desactivado.
    'goatcounter' => env('GOATCOUNTER_URL'),

    /*
    | Primeros segmentos de URL que usa la aplicación y que no pueden ser
    | el slug de una empresa (el portal vive en /{slug}).
    */
    'slugs_reservados' => [
        'panel', 'demo', 'settings', 'login', 'logout', 'register', 'forgot-password', 'reset-password',
        'email', 'two-factor-challenge', 'user', 'passkeys', 'proveedor', 'adjuntos',
        'broadcasting', 'up', 'storage', 'build', 'api', 'sanctum', 'livewire',
    ],
];
