<?php

require_once '../../models/mesero/consultas_mesero.php';
require_once '../../config/config.php';
require_once '../../config/security.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validar que los datos JSON sean válidos
        $data = SecurityUtils::sanitizeJsonData($data);
        
        // Validar campos requeridos
        SecurityUtils::validateRequiredKeys($data, ['mesa_id', 'token']);
        
        // Sanitizar entradas
        $mesa_id = SecurityUtils::sanitizeId($data['mesa_id'], 'ID de mesa');
        $token = SecurityUtils::sanitizeToken($data['token']);
        
        $pdo = config::conectar();
        $consultas = new consultas_mesero();
        $pedidos = $consultas->traerPedidosPorMesaYToken($pdo, $mesa_id, $token);
        $resultado = [];
        
        foreach ($pedidos as $pedido) {
            $productos = $consultas->traerDetallePedido($pdo, $pedido['idpedidos']);
            $resultado[] = [
                'pedido_id' => (int)$pedido['idpedidos'],
                'fecha_hora' => SecurityUtils::escapeHtml($pedido['fecha_hora_pedido']),
                'total_pedido' => (float)$pedido['total_pedido'],
                'token_utilizado' => SecurityUtils::escapeHtml($pedido['token_utilizado']),
                'productos' => $productos
            ];
        }
        
        echo json_encode([
            'success' => true,
            'pedidos' => $resultado,
            'debug' => [
                'mesa_id' => $mesa_id,
                'token' => SecurityUtils::escapeHtml($token),
                'pedidos_encontrados' => count($resultado)
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al cargar los pedidos del usuario: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
} 