<?php

return [
    'driver' => 'native',
    'encryption_key' => getenv('SESSION_ENCRYPTION_KEY'),
    'cipher' => 'aes-256-gcm',
    
    'cookie' => 'rose_session',
    'lifetime' => 120, // minutes
    'path' => '/',
    'domain' => getenv('SESSION_DOMAIN'),
    'secure' => getenv('SESSION_SECURE_COOKIE', true),
    'http_only' => true,
    'same_site' => 'lax',
    
    'lottery' => [2, 100], // garbage collection lottery
];
