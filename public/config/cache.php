<?php

return [

    'default' => env('CACHE_STORE', 'file'),
    'stores' => [
        'array' => [
            'driver' => 'array',
        ],
        'file' => [
            'driver' => 'file',
            'path' => storage_path('temp/cache/data'),
            'lock' => storage_path('temp/cache/data')
        ],
        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CACHE_CONNECTION'),
            'table' => env('DB_CACHE_TABLE'),
        ]
    ]
];
