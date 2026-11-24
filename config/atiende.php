<?php

return [
    /*
    | Primeros segmentos de URL que usa la aplicación y que no pueden ser
    | el slug de una empresa (el portal vive en /{slug}).
    */
    'slugs_reservados' => [
        'panel', 'settings', 'login', 'logout', 'register', 'forgot-password', 'reset-password',
        'email', 'two-factor-challenge', 'user', 'passkeys', 'proveedor', 'adjuntos',
        'broadcasting', 'up', 'storage', 'build', 'api', 'sanctum', 'livewire',
    ],
];
