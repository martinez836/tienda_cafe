<?php
require_once '../models/consultas.php';
require_once '../config/config.php';
require_once '../config/security.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validar que los datos JSON sean válidos
    $data = SecurityUtils::sanitizeJsonData($data);
    
    // Validar campos requeridos
    SecurityUtils::validateRequiredKeys($data, ['mesa_id', 'productos']);
    
    // Sanitizar entradas principales
    $mesa_id = SecurityUtils::sanitizeId($data['mesa_id'], 'ID de mesa');
    $token = isset($data['token']) ? SecurityUtils::sanitizeToken($data['token']) : null;
    $total = isset($data['total']) ? SecurityUtils::sanitizePrice($data['total']) : 0;
    
    // Validar que productos sea un array
    if (!is_array($data['productos'])) {
        throw new Exception('Formato de productos inválido');
    }
    
    // Sanitizar cada producto
    $productos_sanitizados = [];
    foreach ($data['productos'] as $producto) {
        if (!is_array($producto)) {
            throw new Exception('Formato de producto inválido');
        }
        
        SecurityUtils::validateRequiredKeys($producto, ['id', 'cantidad', 'precio']);
        
        $productos_sanitizados[] = [
            'id' => SecurityUtils::sanitizeId($producto['id'], 'ID de producto'),
            'cantidad' => SecurityUtils::sanitizeQuantity($producto['cantidad']),
            'precio' => SecurityUtils::sanitizePrice($producto['precio']),
            'comentario' => isset($producto['comentario']) ? SecurityUtils::sanitizeComment($producto['comentario']) : ''
        ];
    }
    
    $pdo = config::conectar();
    $consultas = new ConsultasMesero();
    $pdo->beginTransaction();
    
    try {
        $pedidoId = $consultas->guardarPedido($pdo, $mesa_id, 1, $token); // 1 es el ID del usuario por defecto
        
        foreach ($productos_sanitizados as $producto) {
            $consultas->guardarDetallePedido($pdo, $producto, $pedidoId);
        }
        
        $consultas->actualizarTotalPedido($pdo, $total, $pedidoId);
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Pedido guardado correctamente',
            'pedido_id' => $pedidoId
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al guardar el pedido: ' . $e->getMessage()
    ]);
} 