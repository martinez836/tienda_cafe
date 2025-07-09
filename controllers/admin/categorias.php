<?php
require_once '../../config/admin_controller_auth.php';
require_once '../../models/consultasCategorias.php';

// Verificar que el usuario sea administrador
verificarAdminController();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$consultas = new ConsultasCategorias();

try {
    switch ($action) {
        case 'getAllCategorias':
            $categorias = $consultas->getAllCategorias();
            echo json_encode(['success' => true, 'data' => $categorias]);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    } 
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
