<?php
/**
 * Constantes del sistema de diagnóstico
 * Define políticas de bloqueo y configuraciones
 */

// Políticas de intentos fallidos
define('VENTANA_INTENTOS_MINUTOS', 5);      // Ventana de tiempo para contar intentos
define('MAX_INTENTOS_IP', 3);                // Intentos antes de bloquear IP
define('MAX_INTENTOS_USUARIO', 3);           // Intentos antes de bloquear cuenta
define('TIEMPO_BLOQUEO_IP_SEGUNDOS', 30);    // Duración bloqueo temporal

// Bloqueo permanente (5 años)
define('DURACION_BLOQUEO_PERMANENTE', 157680000); // 5 años en segundos
