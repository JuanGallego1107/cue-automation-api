<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    // Rutas que deben tener cabeceras CORS
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Orígenes permitidos — agrega aquí la URL de tu frontend en producción también
    'allowed_origins' => [
        'http://localhost:5173',   // Vite dev server
        'http://127.0.0.1:5173',   // Alternativa loopback
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // IMPORTANTE: debe ser true para que Sanctum pueda enviar/recibir cookies
    // y para que Axios pueda adjuntar el header Authorization
    'supports_credentials' => true,

];
