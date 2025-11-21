<?php

require_once __DIR__ . '/env.php';

function db_connect(): mysqli
{
    static $connection;
    if ($connection instanceof mysqli) {
        return $connection;
    }

    load_env(__DIR__ . '/../.env');

    $host = env('DB_HOST', 'localhost');
    $port = env('DB_PORT', '3306');
    $dbName = env('DB_NAME', 'data_kdiscmis');
    $user = env('DB_USER', 'root');
    $password = env('DB_PASSWORD', '');

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connection = new mysqli($host, $user, $password, $dbName, (int) $port);
    $connection->set_charset('utf8mb4');

    return $connection;
}
