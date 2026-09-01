<?php

return [
   'paths' => ['api/*', 'sanctum/csrf-cookie'],

'allowed_methods' => ['*'],

'allowed_origins' => [
    'https://makbos.vercel.app',
    'http://localhost:5173', // Tetap pertahankan untuk pengujian lokal
],

'allowed_origins_patterns' => [],

'allowed_headers' => ['*'],

'supports_credentials' => true,
];
