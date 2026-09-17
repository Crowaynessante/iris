<?php

return [
    'host' => env('DB_HOST', '127.0.0.1'),
    'user' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', 'rooters'),
    'database' => env('DB_DATABASE', 'iris_db'),
    'port' => (int) env('DB_PORT', 3306),
];
