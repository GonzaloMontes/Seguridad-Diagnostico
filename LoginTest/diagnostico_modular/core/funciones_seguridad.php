<?php
/**
 * Funciones de seguridad para el módulo de diagnóstico
 * Extraídas de validar.php - Solo las necesarias para diagnóstico
 */

/**
 * Obtiene la IP real del cliente
 * @return string Dirección IP del cliente
 */
function obtener_ip_cliente(): string {
    if (($_SERVER['REMOTE_ADDR'] ?? '') === '::1') {
        return '127.0.0.1';
    }
    
    $headers = [
        'HTTP_CLIENT_IP', 
        'HTTP_X_FORWARDED_FOR', 
        'HTTP_X_FORWARDED', 
        'HTTP_X_CLUSTER_CLIENT_IP', 
        'HTTP_FORWARDED_FOR', 
        'HTTP_FORWARDED', 
        'REMOTE_ADDR'
    ];
    
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = explode(',', $_SERVER[$header])[0];
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    
    return '0.0.0.0';
}

/**
 * Verifica si una entidad está bloqueada
 * @param mysqli $conn Conexión a BD
 * @param string $tipo Tipo de entidad ('ip', 'user')
 * @param string $identificador Identificador de la entidad
 * @return string|false Motivo del bloqueo o false si no está bloqueada
 */
function verificar_bloqueo(mysqli $conn, string $tipo, string $identificador) {
    if (empty($identificador)) return false;
    
    $stmt = $conn->prepare(
        'SELECT block_reason FROM tbl_blocked_entities 
         WHERE entity_type = ? AND entity_identifier = ? 
         AND block_expires_at > NOW() LIMIT 1'
    );
    $stmt->bind_param('ss', $tipo, $identificador);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($motivo);
        $stmt->fetch();
        $stmt->close();
        return $motivo;
    }
    
    $stmt->close();
    return false;
}

/**
 * Sanitiza input de usuario
 * @param string|null $input Cadena a sanitizar
 * @return string Cadena sanitizada
 */
function sanitizar_input(?string $input): string {
    return $input === null ? '' : trim($input);
}
