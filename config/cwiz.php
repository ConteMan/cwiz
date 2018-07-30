<?php

return [
    'username'     => env('CWIZ_USERNAME', ''),
    'password'     => env('CWIZ_PASSWORD', ''),
    'cache_prefix' => env('CWIZ_CACHE_PREFIX', 'cwiz_cache:'),
    'redis_config' => env('CWIZ_REDIS_CONFIG', 'default'),
];