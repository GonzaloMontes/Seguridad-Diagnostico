<?php
// configuraciones/pdo_connect.php
// Conexión PDO dedicada para endpoints JSON (usar variables de entorno en producción).

declare(strict_types=1);

function pdo_connect_db(): PDO {
    $host = 'localhost';
    $dbname = 'halconturfsoft_login';
    $user = 'halconturfsoft_halconturfsoft';
    $pass = 'G6^rT8!bL2%qX5@vN9#pM3';

    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false, // Seguridad y tipos nativos
    ];
    return new PDO($dsn, $user, $pass, $options);
}
