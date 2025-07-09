<?php

require_once __DIR__ . '/../../models/consultasGraficas.php';

header('Content-Type: application/json');

$consultas = new ConsultasGraficas();

$action = $_GET['action'] ?? '';

$response = ['success' => false, 'message' => 'Invalid action'];

try {
    switch ($action) {
        case 'get_ventas_por_categoria':
            $data = $consultas->getVentasPorCategoria();
            $response = ['success' => true, 'data' => $data];
            break;

        case 'get_productos_mas_vendidos':
            $data = $consultas->getProductosMasVendidos();
            $response = ['success' => true, 'data' => $data];
            break;

        case 'get_tendencia_pedidos_mensual':
            $data = $consultas->getTendenciaPedidosMensual();
            $response = ['success' => true, 'data' => $data];
            break;

        case 'get_ingresos_anuales':
            $data = $consultas->getIngresosAnuales();
            $response = ['success' => true, 'data' => $data];
            break;

        case 'get_mesas_por_empleado':
            $data = $consultas->getMesasPorEmpleado();
            $response = ['success' => true, 'data' => $data];
            break;

        case 'get_cantidad_mesas_por_empleado':
            $data = $consultas->getCantidadMesasPorEmpleado();
            $response = ['success' => true, 'data' => $data];
            break;

        default:
            $response = ['success' => false, 'message' => 'Invalid action provided.'];
            break;
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];
    error_log("Graficas Controller Error: " . $e->getMessage());
}

echo json_encode($response);

?> 