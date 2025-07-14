<?php
require_once '../models/consultas.php';
require_once '../config/config.php';
require_once '../config/security.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validar que los datos JSON sean válidos
        $data = SecurityUtils::sanitizeJsonData($data);
        
        // Validar campos requeridos
        SecurityUtils::validateRequiredKeys($data, ['pedido_id']);
        
        // Sanitizar entradas
        $pedido_id = SecurityUtils::sanitizeId($data['pedido_id'], 'ID de pedido');
        
        $pdo = config::conectar();
        $consultas = new ConsultasMesero();
        
        // Verificar que el pedido existe y está entregado
        $stmt = $pdo->prepare('SELECT estados_idestados FROM pedidos WHERE idpedidos = ?');
        $stmt->execute([$pedido_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            throw new Exception('El pedido no existe');
        }
        
        if ((int)$row['estados_idestados'] !== 4) {
            throw new Exception('El pedido no está entregado');
        }
        
        // Reactivar el pedido cambiando su estado a confirmado (3)
        $resultado = $consultas->reactivarPedidoEntregado($pdo, $pedido_id);
        
        if ($resultado > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Pedido reactivado correctamente. Ahora aparecerá en la cocina.',
                'pedido_id' => $pedido_id
            ]);
        } else {
            throw new Exception('No se pudo reactivar el pedido');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al reactivar el pedido: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
} 