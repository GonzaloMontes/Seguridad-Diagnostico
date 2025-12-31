<?php
/**
 * Configuración de conexión a base de datos MySQLi
 * Módulo: Diagnóstico de Seguridad
 */

// Zona horaria para timestamps correctos
date_default_timezone_set('America/Argentina/Buenos_Aires');

/**
 * Establece conexión MySQLi con la base de datos
 * @return mysqli|null Objeto de conexión MySQLi o null si falla
 */
function conectar_bd(): ?mysqli {
    $config = [
        'host' => 'localhost',
        'dbname' => 'halconturfsoft_login',
        'user' => 'halconturfsoft_halconturfsoft',
        'pass' => 'G6^rT8!bL2%qX5@vN9#pM3'
    ];

    // Desactivar reporte de errores
    $driver = new mysqli_driver();
    $modoReporteOriginal = $driver->report_mode;
    $driver->report_mode = MYSQLI_REPORT_OFF;

    // Intentar conexión con credenciales principales
    $conn = @new mysqli($config['host'], $config['user'], $config['pass'], $config['dbname']);

    // Si falla, intentar credenciales locales (XAMPP/WAMP)
    if ($conn->connect_error) {
        $conn = @new mysqli($config['host'], 'root', '', $config['dbname']);
        
        if ($conn->connect_error) {
            $driver->report_mode = $modoReporteOriginal;
            return null; // Modo MOCK
        }
    }

    $driver->report_mode = $modoReporteOriginal;
    $conn->set_charset('utf8mb4');
    return $conn;
}
