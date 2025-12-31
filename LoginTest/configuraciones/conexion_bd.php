<?php

// Forzar la zona horaria correcta para evitar conflictos de tiempo.
date_default_timezone_set('America/Argentina/Buenos_Aires');
function mysqli_connect_db() {
    // Datos de la base de datos de producción
    $host = 'localhost';
    $dbname = 'halconturfsoft_login';
    $user = 'halconturfsoft_halconturfsoft';
    $pass = 'G6^rT8!bL2%qX5@vN9#pM3';

    // Crear conexión
    $conn = new mysqli($host, $user, $pass, $dbname);

    // Comprobar conexión
    if ($conn->connect_error) {
        die("Error de conexión con MySQLi: " . $conn->connect_error);
    }

    // Establecer el charset
    $conn->set_charset('utf8mb4');

    return $conn;
}
?>
