<?php

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

return [
    'bot' => [
        'token' => $_ENV['API_KEY'],
        'username' => $_ENV['BOT_USERNAME'],
        'owner' => $_ENV['OWNER_ID'],
        'webhook' => toBool($_ENV['WEBHOOK']),
    ],

    'database' => [
        'driver' => 'mysql',
        'host' => $_ENV['DB_HOST'],
        'database' => $_ENV['DB_NAME'],
        'username' => $_ENV['DB_USER'],
        'password' => $_ENV['DB_PASS'],
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_bin',
        'prefix' => '',
    ],
    'encryption_key' => $_ENV['ENCRYPTION_KEY'],
    'debug' => toBool($_ENV['DEBUG']),
    'tmp_dir' => __DIR__ . '/tmp',
    'php_path' => $_ENV['PHP_PATH'],
    'bulk_script' => __DIR__ . '/app/Admin/bulkMessage.php',
];