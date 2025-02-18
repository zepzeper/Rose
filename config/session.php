<?php

use Rose\Support\Env;

return [
    'driver' => 'native',
    'encryption_key' => Env::get('SESSION_ENCRYPTION_KEY'),
    'cipher' => 'aes-256-gcm',
    
    'cookie' => 'rose_session',
    'lifetime' => 120, // minutes
    'path' => '/',
    'domain' => Env::get('SESSION_DOMAIN'),
    'secure' => Env::get('SESSION_SECURE_COOKIE', true),
    'http_only' => true,
    'same_site' => 'lax',
    
    'lottery' => [2, 100], // garbage collection lottery
];
