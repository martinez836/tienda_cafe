<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../../models/consultasCajero.php';
header('Content-Type: application/json');

class pedidosCajeros
{
    
    public function traerPedidos()
    {
        try {
            $consultas = new consultasCajero();
            $pedidosPendientes = $consultas->traerPedidosPendientes();

            if (!$pedidosPendientes || count($pedidosPendientes) === 0) {
                echo json_encode(['data' => []]);
                exit;
            }

            $agrupado = [];

            foreach ($pedidosPendientes as $row) {
                $id = $row['idpedidos'];

                if (!isset($agrupado[$id])) {
                    $agrupado[$id] = [
                        'id' => (int)$id,
                        'numero' => 'P' . $id,
                        'cliente' => $row['nombre_mesa'],
                        'mesero' => $row["nombre_usuario"],
                        'hora' => date('g:i a', strtotime($row['fecha_hora_pedido'])),
                        'productos' => [],
                        'total' => 0
                    ];
                }

                $agrupado[$id]['productos'][] = [
                    'nombre' => $row['nombre_producto'] . ' x' . $row['cantidad_producto']
                ];

                $agrupado[$id]['total'] += (int)$row['subtotal'];
            }

            $resultado = array_values($agrupado);
            echo json_encode(['data' => $resultado], JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $th) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al procesar pedidos: ' . $th->getMessage()]);
        }
    }

    
}

// Instanciar y ejecutar
$pedidos = new pedidosCajeros();
$pedidos->traerPedidos();


