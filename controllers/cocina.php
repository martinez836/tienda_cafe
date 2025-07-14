<?php
require_once '../models/consultasCocina.php';

$consultas = new ConsultasCocina();

header('Content-Type: application/json');

$accion = $_GET['action'] ?? '';

switch ($accion) {
    case 'traer_pedidos_pendientes':
        $pedidos = $consultas->traerPedidosPendientes();
        $pedidosFormateados = [];
        if ($pedidos) {
            foreach ($pedidos as $fila) {
                // Dividir la lista de items en elementos individuales y formatearlos
                $items_list = explode(' || ', $fila['items_list']);
                $items = [];
                foreach ($items_list as $item) {
                    preg_match('/(?<cantidad>\d+)x\s(?<nombre>[^\(]+)\s*\(?(?<observaciones>[^\)]*)\)?/', $item, $coincidencias);
                    $items[] = [
                        'name' => trim($coincidencias['nombre'] ?? ''),
                        'quantity' => (int)($coincidencias['cantidad'] ?? 1),
                        'observations' => trim($coincidencias['observaciones'] ?? '')
                    ];
                }
                
                // Agregar información sobre si son productos nuevos
                $esProductosNuevos = $fila['tipo_pedido'] === 'productos_nuevos';
                
                $pedidosFormateados[] = [
                    'id' => $fila['idpedidos'],
                    'table' => $fila['nombre_mesa'],
                    'time' => date('H:i A', strtotime($fila['fecha_hora_pedido'])),
                    'status' => ($fila['status_id'] == 1) ? 'pending' : 'unknown', // Asumiendo que 1 es pendiente
                    'items' => $items,
                    'es_productos_nuevos' => $esProductosNuevos,
                    'tipo_pedido' => $fila['tipo_pedido']
                ];
            }
        }
        echo json_encode(['success' => true, 'data' => $pedidosFormateados]);
        break;

    case 'marcarPedidoComoListo':
        $entrada = json_decode(file_get_contents('php://input'), true);
        $idPedido = $entrada['orderId'] ?? null;

        if ($idPedido) {
            $resultado = $consultas->marcarPedidoComoListo($idPedido);
            if ($resultado) {
                echo json_encode(['success' => true, 'message' => 'Pedido marcado como listo.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Fallo al marcar el pedido como listo.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'ID de pedido inválido.']);
        }
        break;

    case 'traerDetallesDeUnPedido':
        $idPedido = $_GET['id'] ?? null;

        if ($idPedido) {
            $pedido = $consultas->traerDetallesDeUnPedido($idPedido);
            if ($pedido) {
                // Formatear la hora y los ítems si es necesario
                $pedido['time'] = date('H:i A', strtotime($pedido['fecha_hora_pedido']));
                // Renombrar las claves de los ítems si es necesario para el frontend
                $items_formateados = [];
                foreach ($pedido['items'] as $item) {
                    $items_formateados[] = [
                        'name' => $item['nombre_producto'],
                        'quantity' => (int)$item['cantidad_producto'],
                        'observations' => $item['observaciones'] ?? ''
                    ];
                }
                $pedido['items'] = $items_formateados;

                echo json_encode(['success' => true, 'pedido' => $pedido]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Pedido no encontrado.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'ID de pedido no proporcionado.']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción inválida.']);
        break;
} 