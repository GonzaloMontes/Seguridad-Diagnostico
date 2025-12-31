<?php
/**
 * Módulo de Diagnóstico de Seguridad
 * Versión modular - Mantiene URL original diagnostico.php
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Cargar dependencias modulares
require_once __DIR__ . '/configuraciones/conexion_bd.php';
require_once __DIR__ . '/diagnostico_modular/core/funciones_seguridad.php';
require_once __DIR__ . '/diagnostico_modular/core/funciones_bd.php';

$conn = mysqli_connect_db();

// Variables para la vista
$message = $_GET['message'] ?? '';
$message_type = $_GET['type'] ?? '';

// === LÓGICA POST: Desbloquear IP ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unblock_ip'])) {
    $ip_to_unblock = $_POST['unblock_ip'];
    
    try {
        desbloquear_ip($conn, $ip_to_unblock);
        header('Location: diagnostico.php?unblock_success=1');
        exit();
    } catch (Exception $e) {
        $msg = 'Error al desbloquear: ' . $e->getMessage();
        $type = 'danger';
        header('Location: diagnostico.php?message=' . urlencode($msg) . '&type=' . $type);
        exit();
    }
}

// === LÓGICA GET: Filtros de IPs bloqueadas ===
$searchIp = sanitizar_input($_GET['search_ip'] ?? '');
$startDate = sanitizar_input($_GET['start_date'] ?? '');
$endDate = sanitizar_input($_GET['end_date'] ?? '');

$blockedIps = obtener_ips_bloqueadas($conn, $searchIp, $startDate, $endDate);

// === LÓGICA GET: Historial con paginación ===
$histSearch = sanitizar_input($_GET['hist_search'] ?? '');
$histStartDate = sanitizar_input($_GET['hist_start_date'] ?? date('Y-m-d'));
$histEndDate = sanitizar_input($_GET['hist_end_date'] ?? date('Y-m-d'));
$histPage = max(1, (int)($_GET['hist_page'] ?? 1));
$histPerPage = 10;

$historialData = obtener_historial_bloqueos(
    $conn, 
    $histSearch, 
    $histStartDate, 
    $histEndDate, 
    $histPage, 
    $histPerPage
);

$histRows = $historialData['datos'];
$histTotalRows = $historialData['total'];
$histTotalPages = max(1, (int)ceil($histTotalRows / $histPerPage));

// Ajustar página si excede el total
if ($histPage > $histTotalPages) {
    $histPage = $histTotalPages;
}

$conn->close();

// Renderizar vista modular
require_once __DIR__ . '/diagnostico_modular/vistas/panel_principal.php';
