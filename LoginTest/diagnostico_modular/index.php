<?php
/**
 * Controlador principal del módulo de diagnóstico
 * Punto de entrada único - Maneja lógica y renderiza vista
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Cargar dependencias
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/core/funciones_seguridad.php';
require_once __DIR__ . '/core/funciones_bd.php';

$conn = conectar_bd();

// Variables para la vista
$message = $_GET['message'] ?? '';
$message_type = $_GET['type'] ?? '';

// === LÓGICA POST: Desbloquear IP ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unblock_ip'])) {
    $ip_to_unblock = $_POST['unblock_ip'];
    
    try {
        desbloquear_ip($conn, $ip_to_unblock);
        header('Location: index.php?unblock_success=1');
        exit();
    } catch (Exception $e) {
        $msg = 'Error al desbloquear: ' . $e->getMessage();
        $type = 'danger';
        header('Location: index.php?message=' . urlencode($msg) . '&type=' . $type);
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

// Renderizar vista
require_once __DIR__ . '/vistas/panel_principal.php';
