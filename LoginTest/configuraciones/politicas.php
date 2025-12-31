<?php

// Definir la contraseña por defecto para el usuario administrador.
// Es muy recomendable cambiar esta contraseña después de la instalación.
define('ADMIN_PASSWORD', 'admin2024!');
// configuraciones/politicas.php

// Políticas de bloqueo de seguridad.
// Se definen aquí para ser usadas de forma consistente en toda la aplicación.

if (!defined('ATTEMPT_WINDOW_MINUTES')) {
    define('ATTEMPT_WINDOW_MINUTES', 5); // Ventana de tiempo en minutos para contar intentos
}


if (!defined('MAX_IP_ATTEMPTS')) {
    define('MAX_IP_ATTEMPTS', 3); // Bloqueo temporal de IP tras 3 intentos
}

if (!defined('MAX_CONSECUTIVE_USER_ATTEMPTS_FOR_PERMABAN')) {
    define('MAX_CONSECUTIVE_USER_ATTEMPTS_FOR_PERMABAN', 3); // Bloqueo permanente de cuenta tras 3 intentos
}

if (!defined('IP_BLOCK_TIME_SECONDS')) {
    define('IP_BLOCK_TIME_SECONDS', 30);
}

// Bloqueo Permanente de IP (tras insistir en una cuenta ya bloqueada)
// Se activa si se intenta acceder a una cuenta que ya se encuentra bloqueada.
define('PERMANENT_BLOCK_DURATION_SECONDS', 3153600000); // 100 años
