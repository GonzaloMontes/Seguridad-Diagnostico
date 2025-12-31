<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/objetos/generales/validar.php';
enforce_ip_security();

require_once __DIR__ . '/configuraciones/conexion_bd.php';
$conn = mysqli_connect_db();

// Lógica para desbloquear una IP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unblock_ip'])) {
    $ipToUnblock = $_POST['unblock_ip'];
    $stmt = $conn->prepare('DELETE FROM tbl_blocked_entities WHERE type = \'ip\' AND value = ?');
    $stmt->bind_param('s', $ipToUnblock);
    $stmt->execute();
    $stmt->close();
    header('Location: reporte_seguridad.php?unblock_success=1');
    exit();
}

// Recoger valores de los filtros
$searchIp = $_GET['search_ip'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

$sql = "SELECT * FROM tbl_security_alerts WHERE 1=1";
$params = [];
$types = '';

if (!empty($searchIp)) {
    $sql .= " AND source_ip LIKE ?";
    $params[] = '%' . $searchIp . '%';
    $types .= 's';
}
if (!empty($startDate)) {
    $sql .= " AND DATE(alert_time) >= ?";
    $params[] = $startDate;
    $types .= 's';
}
if (!empty($endDate)) {
    $sql .= " AND DATE(alert_time) <= ?";
    $params[] = $endDate;
    $types .= 's';
}

$sql .= " ORDER BY alert_time DESC";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$alerts = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Alertas de Seguridad</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; margin: 2em; background: #f8f9fa; color: #343a40; }
        h1 { font-size: 1.75rem; color: #dc3545; border-bottom: 2px solid #dc3545; padding-bottom: 10px; margin-bottom: 20px; }
        .container { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; table-layout: fixed; }
        th, td { border: 1px solid #dee2e6; padding: 12px; text-align: left; word-wrap: break-word; }
        th { background-color: #f2f2f2; font-weight: 600; }
        tr:nth-child(even) { background-color: #f8f9fa; }
        .nav-links { margin-bottom: 20px; }
        .nav-links .btn { margin-right: 10px; }
        .payload { font-family: monospace; background: #e9ecef; padding: 5px; border-radius: 4px; }
        .btn { padding: 8px 12px; border: none; border-radius: 4px; color: white; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-unblock { background-color: #28a745; }
        .btn-unblock:hover { background-color: #218838; }
        .filter-form { display: flex; gap: 15px; align-items: center; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-form input { padding: 8px; border: 1px solid #ced4da; border-radius: 4px; }
        .filter-form button { background-color: #007bff; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; }
        .filter-form button:hover { background-color: #0056b3; }
        .alert { padding: 15px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1><span style="color: #dc3545;">🛡️</span> Reporte de Alertas de Seguridad</h1>
        <div class="nav-links">
            <a href="diagnostico.php" class="btn" style="background-color: #17a2b8;">Ver Bloqueos Activos</a>
            <a href="historial_bloqueos.php" class="btn" style="background-color: #6c757d;">Ver Historial de Bloqueos</a>
        </div>

        <?php if (isset($_GET['unblock_success'])): ?>
            <div class="alert">Dirección IP desbloqueada correctamente.</div>
        <?php endif; ?>

        <form method="GET" action="reporte_seguridad.php" class="filter-form">
            <input type="text" name="search_ip" placeholder="Buscar por IP..." value="<?= htmlspecialchars($searchIp) ?>">
            <label>Desde:</label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
            <label>Hasta:</label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>">
            <button type="submit">Filtrar</button>
            <a href="reporte_seguridad.php" class="btn" style="background-color: #6c757d;">Limpiar</a>
        </form>

        <table>
            <thead>
                <tr>
                    <th style="width: 15%;">Fecha y Hora</th>
                    <th style="width: 15%;">IP Origen</th>
                    <th style="width: 10%;">Fuente</th>
                    <th style="width: 10%;">Campo</th>
                    <th style="width: 30%;">Payload Malicioso</th>
                    <th style="width: 10%;">Motivo</th>
                    <th style="width: 10%;">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($alerts)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">No se han registrado alertas de seguridad con los filtros actuales.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($alerts as $alert): ?>
                    <tr>
                        <td><?= htmlspecialchars($alert['alert_time']); ?></td>
                        <td><?= htmlspecialchars($alert['source_ip']); ?></td>
                        <td><?= htmlspecialchars($alert['input_source']); ?></td>
                        <td><?= htmlspecialchars($alert['input_key']); ?></td>
                        <td><code class="payload"><?= htmlspecialchars($alert['malicious_payload']); ?></code></td>
                        <td><?= htmlspecialchars($alert['reason']); ?></td>
                        <td>
                            <form method="POST" action="reporte_seguridad.php" style="margin:0;">
                                <input type="hidden" name="unblock_ip" value="<?= htmlspecialchars($alert['source_ip']) ?>">
                                <button type="submit" class="btn btn-unblock">Desbloquear IP</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const successAlert = document.querySelector('.alert');
            // Comprobar si el mensaje de éxito y el parámetro URL existen
            if (successAlert && window.location.search.includes('unblock_success=1')) {
                // Ocultar el mensaje con una transición suave después de 3 segundos
                setTimeout(() => {
                    successAlert.style.transition = 'opacity 0.5s ease';
                    successAlert.style.opacity = '0';
                    setTimeout(() => successAlert.style.display = 'none', 500); // Esperar a que termine la transición
                }, 3000); // 3 segundos

                // Limpiar el parámetro 'unblock_success' de la URL para que el mensaje no reaparezca al recargar
                const url = new URL(window.location);
                url.searchParams.delete('unblock_success');
                window.history.replaceState({ path: url.href }, '', url.href);
            }
        });
    </script>
</body>
</html>
