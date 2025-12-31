<?php
/**
 * Funciones de base de datos para diagnóstico
 * Operaciones sobre bloqueos, alertas e intentos
 */

/**
 * Obtiene IPs bloqueadas con filtros opcionales
 */
function obtener_ips_bloqueadas(mysqli $conn, string $buscarIp = '', string $fechaDesde = '', string $fechaHasta = ''): array {
    $sql = "SELECT entity_identifier, created_at, block_reason FROM tbl_blocked_entities WHERE entity_type = 'ip'";
    $params = [];
    $tipos = '';
    
    if (!empty($buscarIp)) {
        $sql .= " AND entity_identifier LIKE ?";
        $params[] = "%{$buscarIp}%";
        $tipos .= 's';
    }
    if (!empty($fechaDesde)) {
        $sql .= " AND DATE(created_at) >= ?";
        $params[] = $fechaDesde;
        $tipos .= 's';
    }
    if (!empty($fechaHasta)) {
        $sql .= " AND DATE(created_at) <= ?";
        $params[] = $fechaHasta;
        $tipos .= 's';
    }
    
    $sql .= " ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) return [];
    
    if (!empty($tipos)) {
        $refs = [];
        foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
        array_unshift($refs, $tipos);
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }
    
    $stmt->execute();
    $stmt->store_result();
    $resultado = [];
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($ip, $fecha, $motivo);
        while ($stmt->fetch()) {
            $resultado[] = ['entity_identifier' => $ip, 'created_at' => $fecha, 'block_reason' => $motivo];
        }
    }
    $stmt->close();
    
    return $resultado;
}

/**
 * Desbloquea una IP y la mueve al historial
 */
function desbloquear_ip(mysqli $conn, string $ip): bool {
    $conn->begin_transaction();
    
    try {
        $stmt = $conn->prepare("SELECT entity_type, entity_identifier, block_reason, created_at FROM tbl_blocked_entities WHERE entity_type = 'ip' AND entity_identifier = ?");
        $stmt->bind_param('s', $ip);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows === 0) {
            $stmt->close();
            throw new Exception('IP no encontrada');
        }
        
        $stmt->bind_result($tipo, $identificador, $motivo, $fecha_bloqueo);
        $stmt->fetch();
        $stmt->close();
        
        $stmt_hist = $conn->prepare("INSERT INTO tbl_block_history (entity_type, entity_identifier, block_reason, ip_address, blocked_at, unblocked_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt_hist->bind_param('sssss', $tipo, $identificador, $motivo, $ip, $fecha_bloqueo);
        $stmt_hist->execute();
        $stmt_hist->close();
        
        $stmt_del = $conn->prepare("DELETE FROM tbl_blocked_entities WHERE entity_type = 'ip' AND entity_identifier = ?");
        $stmt_del->bind_param('s', $ip);
        $stmt_del->execute();
        $stmt_del->close();
        
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Obtiene historial de bloqueos con filtros y paginación
 */
function obtener_historial_bloqueos(mysqli $conn, string $buscar = '', string $fechaDesde = '', string $fechaHasta = '', int $pagina = 1, int $porPagina = 10): array {
    // Contar total
    $sqlCount = "SELECT COUNT(*) FROM tbl_block_history WHERE 1=1";
    $tipos = '';
    $params = [];
    
    if (!empty($buscar)) {
        $sqlCount .= " AND ip_address LIKE ?";
        $params[] = "%{$buscar}%";
        $tipos .= 's';
    }
    if (!empty($fechaDesde)) {
        $sqlCount .= " AND DATE(blocked_at) >= ?";
        $params[] = $fechaDesde;
        $tipos .= 's';
    }
    if (!empty($fechaHasta)) {
        $sqlCount .= " AND DATE(blocked_at) <= ?";
        $params[] = $fechaHasta;
        $tipos .= 's';
    }
    
    $stmtCount = $conn->prepare($sqlCount);
    if (!$stmtCount) return ['total' => 0, 'datos' => []];
    
    if (!empty($tipos)) {
        $refs = [];
        foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
        array_unshift($refs, $tipos);
        call_user_func_array([$stmtCount, 'bind_param'], $refs);
    }
    $stmtCount->execute();
    $stmtCount->bind_result($total);
    $stmtCount->fetch();
    $stmtCount->close();
    
    // Obtener datos paginados
    $offset = ($pagina - 1) * $porPagina;
    $sql = "SELECT entity_type, block_reason, ip_address, blocked_at, unblocked_at FROM tbl_block_history WHERE 1=1";
    $tiposData = '';
    $paramsData = [];
    
    if (!empty($buscar)) {
        $sql .= " AND ip_address LIKE ?";
        $paramsData[] = "%{$buscar}%";
        $tiposData .= 's';
    }
    if (!empty($fechaDesde)) {
        $sql .= " AND DATE(blocked_at) >= ?";
        $paramsData[] = $fechaDesde;
        $tiposData .= 's';
    }
    if (!empty($fechaHasta)) {
        $sql .= " AND DATE(blocked_at) <= ?";
        $paramsData[] = $fechaHasta;
        $tiposData .= 's';
    }
    
    $sql .= " ORDER BY blocked_at DESC LIMIT ? OFFSET ?";
    $tiposData .= 'ii';
    $paramsData[] = $porPagina;
    $paramsData[] = $offset;
    
    $stmt = $conn->prepare($sql);
    $refs = [];
    foreach ($paramsData as $k => $v) { $refs[$k] = &$paramsData[$k]; }
    array_unshift($refs, $tiposData);
    call_user_func_array([$stmt, 'bind_param'], $refs);
    
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($tipo, $motivo, $ip, $bloqueado, $desbloqueado);
    
    $datos = [];
    while ($stmt->fetch()) {
        $datos[] = [
            'entity_type' => $tipo,
            'block_reason' => $motivo,
            'ip_address' => $ip,
            'blocked_at' => $bloqueado,
            'unblocked_at' => $desbloqueado
        ];
    }
    $stmt->close();
    
    return ['total' => (int)$total, 'datos' => $datos];
}
