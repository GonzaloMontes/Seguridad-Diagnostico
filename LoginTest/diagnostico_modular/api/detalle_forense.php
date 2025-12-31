<?php
/**
 * API JSON: Detalle forense de bloqueos por IP
 * Muestra logins fallidos del último minuto antes del bloqueo
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../../configuraciones/conexion_bd.php';

function responder(array $datos, int $codigo = 200): void {
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $ip = filter_input(INPUT_GET, 'ip', FILTER_VALIDATE_IP);
    if (!$ip) responder(['error' => 'Parámetro ip inválido'], 400);
    
    $conn = mysqli_connect_db();

    // Obtener información del bloqueo actual (si está bloqueado)
    $stmt = $conn->prepare("SELECT block_reason, created_at, block_expires_at FROM tbl_blocked_entities WHERE entity_type = 'ip' AND entity_identifier = ? LIMIT 1");
    $stmt->bind_param('s', $ip);
    $stmt->execute();
    $stmt->store_result();
    
    $bloqueo = null;
    $fecha_referencia = null;
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($motivo, $creado, $expira);
        $stmt->fetch();
        $bloqueo = ['block_reason' => $motivo, 'created_at' => $creado, 'block_expires_at' => $expira];
        $fecha_referencia = $creado;
    }
    $stmt->close();
    
    // Si no está bloqueado actualmente, buscar el último bloqueo en el historial
    if (!$bloqueo) {
        $stmt = $conn->prepare("SELECT block_reason, blocked_at FROM tbl_block_history WHERE ip_address = ? ORDER BY blocked_at DESC LIMIT 1");
        $stmt->bind_param('s', $ip);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($motivo, $bloqueado);
            $stmt->fetch();
            $bloqueo = ['block_reason' => $motivo, 'created_at' => $bloqueado, 'block_expires_at' => null];
            $fecha_referencia = $bloqueado;
        }
        $stmt->close();
    }
    
    $mapa = ['security' => 'Actividad sospechosa', 'failed_attempt' => 'Múltiples intentos fallidos'];
    
    $resumen = [
        'motivo_actual' => $bloqueo ? ($mapa[$bloqueo['block_reason']] ?? $bloqueo['block_reason']) : null,
        'bloqueado_desde' => $bloqueo['created_at'] ?? null,
        'expira' => $bloqueo['block_expires_at'] ?? null
    ];
    
    // Obtener eventos de seguridad del ÚLTIMO MINUTO antes del bloqueo (excepto LOGIN_EXITOSO)
    $intentos_detalle = [];
    if ($fecha_referencia) {
        // Eventos de seguridad en el último minuto antes del bloqueo
        $stmt = $conn->prepare("
            SELECT alert_timestamp, input_key, malicious_payload 
            FROM tbl_security_alerts 
            WHERE source_ip = ? 
            AND input_key != 'LOGIN_EXITOSO'
            AND alert_timestamp <= ?
            AND alert_timestamp >= DATE_SUB(?, INTERVAL 1 MINUTE)
            ORDER BY alert_timestamp DESC
            LIMIT 20
        ");
        $stmt->bind_param('sss', $ip, $fecha_referencia, $fecha_referencia);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($hora, $tipo_evento, $payload);
        
        while ($stmt->fetch()) {
            // Extraer usuario del payload (formato: "Usuario: admin")
            $usuario = 'Desconocido';
            if (preg_match('/Usuario:\s*(\S+)/', $payload, $matches)) {
                $usuario = $matches[1];
            }
            
            // Mapeo de tipos de eventos
            $tipo_legible = [
                'LOGIN_FALLIDO' => 'Login fallido',
                'IP_BLOQUEADA_AUTO' => 'IP bloqueada automáticamente',
                'ACCESO_IP_BLOQUEADA' => 'Intento de acceso con IP bloqueada'
            ];
            
            $intentos_detalle[] = [
                'hora' => $hora,
                'usuario' => $usuario,
                'tipo' => $tipo_legible[$tipo_evento] ?? $tipo_evento,
                'ip' => $ip
            ];
        }
        $stmt->close();
    }
    
    // Contar intentos por usuario
    $intentos_por_usuario = [];
    foreach ($intentos_detalle as $intento) {
        $user = $intento['usuario'];
        if (!isset($intentos_por_usuario[$user])) {
            $intentos_por_usuario[$user] = ['user' => $user, 'cantidad' => 0, 'ult_hora' => $intento['hora']];
        }
        $intentos_por_usuario[$user]['cantidad']++;
    }
    
    $datos = [
        'ip' => $ip,
        'resumen' => $resumen,
        'intentos_fallidos' => [
            'total' => count($intentos_detalle),
            'por_usuario' => array_values($intentos_por_usuario),
            'detalle' => $intentos_detalle
        ],
        'alertas' => [],
        'historial_bloqueos' => [],
        'entrada' => null
    ];
    
    $conn->close();
    responder($datos);
    
} catch (Throwable $e) {
    error_log('[detalle_forense.php] Error: ' . $e->getMessage());
    responder(['error' => 'internal_error'], 500);
}
