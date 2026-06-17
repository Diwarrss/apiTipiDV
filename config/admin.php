<?php

declare(strict_types=1);

return [
    /** Único super-admin (solo tú). */
    'email' => env('ADMIN_EMAIL'),

    /** Hash bcrypt: php artisan admin:hash-password "tu-clave" */
    'password' => env('ADMIN_PASSWORD_HASH'),
];
