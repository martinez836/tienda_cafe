<?php

require_once __DIR__ . '/../../config/admin_controller_auth.php';
require_once __DIR__ . '/../../models/consultasDashboard.php';

// Verificar que el usuario sea administrador
verificarAdminController();

header('Content-Type: application/json');

$consultas = new ConsultasDashboard();

$action = $_GET['action'] ?? '';

$response = ['success' => false, 'message' => 'Invalid action'];

try {
    switch ($action) {
        case 'get_dashboard_data':
            $totalPedidos = $consultas->getTotalPedidos();
            $ingresosMesActual = $consultas->getIngresosMesActual();
            $nuevosUsuariosMesActual = $consultas->getNuevosUsuariosMesActual();
            $ventasDiarias = $consultas->getVentasDiarias();
            $ultimosPedidos = $consultas->getUltimosPedidos();

            // Formatear ventasDiarias para Chart.js
            $labelsVentas = [];
            $dataVentas = [];
            foreach ($ventasDiarias as $venta) {
                $labelsVentas[] = date('D', strtotime($venta['fecha'])); // Ej. Lun, Mar
                $dataVentas[] = (float)$venta['total_ventas'];
            }
            
            // Formatear últimos pedidos para el frontend
            $pedidosFormateados = [];
            foreach ($ultimosPedidos as $pedido) {
                $pedidosFormateados[] = [
                    'id' => $pedido['idpedidos'],
                    'table' => $pedido['nombre_mesa'],
                    'status' => ($pedido['status_id'] == 1) ? 'Activo' : (($pedido['status_id'] == 2) ? 'Inactivo' : 'Desconocido'),
                ];
            }

            $response = [
                'success' => true,
                'data' => [
                    'totalPedidos' => (int)$totalPedidos,
                    'ingresosMesActual' => (float)$ingresosMesActual,
                    'nuevosUsuariosMesActual' => (int)$nuevosUsuariosMesActual,
                    'ventasDiarias' => [
                        'labels' => $labelsVentas,
                        'data' => $dataVentas
                    ],
                    'ultimosPedidos' => $pedidosFormateados,
                    'debugUltimosPedidos' => $ultimosPedidos
                ]
            ];
            break;

        default:
            $response = ['success' => false, 'message' => 'Invalid action provided.'];
            break;
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];
    error_log("Dashboard Controller Error: " . $e->getMessage());
}

echo json_encode($response);

?> 