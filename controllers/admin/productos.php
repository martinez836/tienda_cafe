<?php

require_once '../../config/admin_controller_auth.php';
require_once '../../models/consultasProductos.php';

// Verificar que el usuario sea administrador
verificarAdminController();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$consultas = new ConsultasProductos();

try {
    switch ($action) {
        case 'getAllProductos':
            $productos = $consultas->getAllProductos();
            echo json_encode(['success' => true, 'data' => $productos]);
            break;
        case 'getProducto':
            $id = $_GET['id'] ?? 0;
            $producto = $consultas->getProducto($id);
            echo json_encode(['success' => true, 'data' => $producto]);
            break;
        case 'createProducto':
            $data = json_decode(file_get_contents('php://input'), true);
            $consultas->createProducto($data);
            echo json_encode(['success' => true, 'message' => 'Producto creado exitosamente']);
            break;
        case 'updateProducto':
            $data = json_decode(file_get_contents('php://input'), true);
            $consultas->updateProducto($data);
            echo json_encode(['success' => true, 'message' => 'Producto actualizado exitosamente']);
            break;
        case 'deleteProducto':
            $data = json_decode(file_get_contents('php://input'), true);
            $consultas->deleteProducto($data['id']);
            echo json_encode(['success' => true, 'message' => 'Producto eliminado exitosamente']);
            break;
        case 'getProductosBajoStock':
            $productos = $consultas->getProductosBajoStock();
            echo json_encode(['success' => true, 'data' => $productos]);
            break;
        case 'getProductosSinStock':
            $productos = $consultas->getProductosSinStock();
            echo json_encode(['success' => true, 'data' => $productos]);
            break;
        case 'getResumenStock':
            $resumen = $consultas->getResumenStock();
            echo json_encode(['success' => true, 'data' => $resumen]);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?> 