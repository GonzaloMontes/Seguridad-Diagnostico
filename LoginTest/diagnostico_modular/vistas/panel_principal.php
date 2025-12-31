<?php
/**
 * Vista principal del panel de diagnóstico
 * Gestión de IPs bloqueadas y análisis forense
 */

// Headers HTTP
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de IPs Bloqueadas</title>
    <link rel="stylesheet" href="diagnostico_modular/assets/css/diagnostico.css">
</head>
<body>
    <!-- Contenedor principal: IPs bloqueadas activas -->
    <div class="container">
        <h1>Gestión de IPs Bloqueadas</h1>
        <a href="principal.php" class="btn" style="background-color: #17a2b8; margin-bottom: 20px;">Página Principal</a>

        <?php if (isset($_GET['unblock_success'])): ?>
            <script>
                // Señal para recarga de login si es necesario
                localStorage.setItem('ip_unblocked', Date.now());
            </script>
        <?php elseif ($message): ?>
            <div class="alert alert-<?= htmlspecialchars($message_type, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <!-- Formulario de filtros -->
        <form method="GET" action="diagnostico.php" class="filter-form">
            <input type="text" name="search_ip" placeholder="Buscar por IP..." 
                   value="<?= htmlspecialchars($searchIp, ENT_QUOTES, 'UTF-8'); ?>">
            <label for="start_date">Desde:</label>
            <input type="date" id="start_date" name="start_date" 
                   value="<?= htmlspecialchars($startDate, ENT_QUOTES, 'UTF-8') ?>">
            <label for="end_date">Hasta:</label>
            <input type="date" id="end_date" name="end_date" 
                   value="<?= htmlspecialchars($endDate, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="diagnostico.php" class="btn btn-secondary">Limpiar</a>
        </form>

        <!-- Tabla de IPs bloqueadas -->
        <table>
            <thead>
                <tr>
                    <th>Dirección IP</th>
                    <th>Fecha de Bloqueo</th>
                    <th>Estado</th>
                    <th>Bloqueo Actual</th>
                    <th>Últimos Bloqueos</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($blockedIps)): ?>
                    <tr><td colspan="6" style="text-align: center;">No hay direcciones IP bloqueadas.</td></tr>
                <?php else: foreach ($blockedIps as $ip_data): ?>
                <tr>
                    <td><?= htmlspecialchars($ip_data['entity_identifier'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars($ip_data['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <?php 
                            $reason_text = 'Indefinido';
                            if ($ip_data['block_reason'] === 'security') {
                                $reason_text = 'Seguridad';
                            } elseif ($ip_data['block_reason'] === 'failed_attempt') {
                                $reason_text = 'Intentos Fallidos';
                            }
                            echo htmlspecialchars($reason_text, ENT_QUOTES, 'UTF-8');
                        ?>
                    </td>
                    <td>
                        <button class="btn btn-detail" 
                                onclick="abrirDetalle('<?= htmlspecialchars($ip_data['entity_identifier'], ENT_QUOTES, 'UTF-8'); ?>', true, '<?= htmlspecialchars(substr($ip_data['created_at'], 0, 10), ENT_QUOTES, 'UTF-8'); ?>')">
                            Ver detalle
                        </button>
                    </td>
                    <td>
                        <button class="btn btn-detail" 
                                onclick="abrirDetalle('<?= htmlspecialchars($ip_data['entity_identifier'], ENT_QUOTES, 'UTF-8'); ?>')">
                            Ver detalle
                        </button>
                    </td>
                    <td>
                        <form method="POST" action="diagnostico.php" style="margin:0;" 
                              onsubmit="return confirmUnblock('<?= htmlspecialchars($ip_data['entity_identifier'], ENT_QUOTES, 'UTF-8'); ?>');">
                            <input type="hidden" name="unblock_ip" 
                                   value="<?= htmlspecialchars($ip_data['entity_identifier'], ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="btn btn-unblock">Desbloquear</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Contenedor secundario: Historial de bloqueos -->
    <div class="container" style="margin-top: 25px;">
        <h1>Historial de IPs / Usuarios bloqueados</h1>
        
        <form method="GET" action="diagnostico.php" class="filter-form">
            <input type="text" name="hist_search" placeholder="Buscar por IP o Usuario..." 
                   value="<?= htmlspecialchars($histSearch, ENT_QUOTES, 'UTF-8'); ?>">
            <label for="hist_start_date">Desde:</label>
            <input type="date" id="hist_start_date" name="hist_start_date" 
                   value="<?= htmlspecialchars($histStartDate, ENT_QUOTES, 'UTF-8') ?>">
            <label for="hist_end_date">Hasta:</label>
            <input type="date" id="hist_end_date" name="hist_end_date" 
                   value="<?= htmlspecialchars($histEndDate, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="hist_page" value="1">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="diagnostico.php" class="btn btn-secondary">Limpiar</a>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>IP</th>
                    <th>Motivo</th>
                    <th>Bloqueado</th>
                    <th>Desbloqueado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($histRows)): ?>
                    <tr><td colspan="6" style="text-align:center;">No hay registros en el historial con los filtros actuales.</td></tr>
                <?php else: foreach ($histRows as $h): ?>
                    <tr>
                        <td><?= htmlspecialchars($h['entity_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars($h['ip_address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars($h['block_reason'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars($h['blocked_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars($h['unblocked_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php $ipForDetalle = htmlspecialchars($h['ip_address'] ?? '', ENT_QUOTES, 'UTF-8'); if ($ipForDetalle): ?>
                                <button class="btn btn-detail" onclick="abrirDetalle('<?= $ipForDetalle ?>')">Ver detalle</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

        <!-- Paginación -->
        <?php if ($histTotalPages > 1): ?>
        <div style="margin-top: 12px; display:flex; gap:6px; flex-wrap: wrap;">
            <?php for ($p=1; $p<=$histTotalPages; $p++): ?>
                <?php
                    $qs = $_GET;
                    $qs['hist_page'] = (string)$p;
                    $href = 'diagnostico.php?' . http_build_query($qs);
                ?>
                <a class="btn" 
                   style="background-color: <?= $p === $histPage ? '#343a40' : '#007bff' ?>;" 
                   href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>"><?= $p ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal Detalle Forense -->
    <div class="modal-backdrop" id="detailModal" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="modal">
            <div class="modal-header">
                <strong>Detalle del bloqueo</strong>
                <button class="close-btn" onclick="cerrarDetalle()">Cerrar</button>
            </div>
            <div class="modal-body">
                <div id="detailContent">Cargando...</div>
            </div>
        </div>
    </div>

    <script src="diagnostico_modular/assets/js/diagnostico.js"></script>
</body>
</html>
