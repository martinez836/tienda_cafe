<?php

require_once __DIR__ . '/../../config/admin_controller_auth.php';
require_once __DIR__ . '/../../models/consultasPedidos.php';

// Verificar que el usuario sea administrador
verificarAdminController();

header('Content-Type: application/json');

$consultas = new ConsultasPedidos();

$action = $_GET['action'] ?? '';

$response = ['success' => false, 'message' => 'Invalid action'];

try {
    switch ($action) {
        case 'get_all_orders':
            $pedidos = $consultas->getAllPedidos();
            $response = [
                'success' => true,
                'data' => $pedidos
            ];
            break;

        case 'get_order_detail':
            $idPedido = $_GET['id'] ?? null;
            if ($idPedido) {
                $detallePedido = $consultas->getDetallePedido($idPedido);
                if ($detallePedido) {
                    $response = [
                        'success' => true,
                        'data' => $detallePedido
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Pedido no encontrado'
                    ];
                }
            } else {
                $response = [
                    'success' => false,
                    'message' => 'ID de pedido no proporcionado'
                ];
            }
            break;

        // Puedes añadir más casos aquí para filtrar, buscar o actualizar pedidos, etc.

        default:
            $response = ['success' => false, 'message' => 'Invalid action provided.'];
            break;
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];
    error_log("Pedidos Controller Error: " . $e->getMessage());
}

echo json_encode($response);

?> 