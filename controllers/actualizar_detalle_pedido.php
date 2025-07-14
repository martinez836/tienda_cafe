<?php
require_once '../models/consultas.php';
require_once '../config/config.php';
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['pedido_id'], $data['producto_id'], $data['cantidad'])) {
        throw new Exception('Datos incompletos');
    }
    $pedido_id = $data['pedido_id'];
    $producto_id = $data['producto_id'];
    $comentario = isset($data['comentario']) ? $data['comentario'] : null;
    $cantidad = (int)$data['cantidad'];
    $pdo = config::conectar();
    
    // Verificar que el pedido esté activo, confirmado o entregado
    $stmt = $pdo->prepare('SELECT estados_idestados FROM pedidos WHERE idpedidos = ?');
    $stmt->execute([$pedido_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || ($row['estados_idestados'] != 1 && $row['estados_idestados'] != 3 && $row['estados_idestados'] != 4)) {
        throw new Exception('El pedido no está activo, confirmado ni entregado');
    }
    
    $estado_actual = (int)$row['estados_idestados'];
    
    // Buscar el detalle
    $stmt = $pdo->prepare('SELECT iddetalle_pedidos, cantidad_producto FROM detalle_pedidos WHERE pedidos_idpedidos = ? AND productos_idproductos = ? AND observaciones '.($comentario === null ? 'IS NULL' : '= ?'));
    $params = [$pedido_id, $producto_id];
    if ($comentario !== null) $params[] = $comentario;
    $stmt->execute($params);
    $detalle = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($detalle) {
        if ($cantidad > 0) {
            // Actualizar cantidad
            $stmtUp = $pdo->prepare('UPDATE detalle_pedidos SET cantidad_producto = ?, subtotal = precio_producto * ? WHERE iddetalle_pedidos = ?');
            $stmtUp->execute([$cantidad, $cantidad, $detalle['iddetalle_pedidos']]);
        } else {
            // Eliminar producto del pedido
            $stmtDel = $pdo->prepare('DELETE FROM detalle_pedidos WHERE iddetalle_pedidos = ?');
            $stmtDel->execute([$detalle['iddetalle_pedidos']]);
        }
        // Actualizar total del pedido
        $stmtTotal = $pdo->prepare('UPDATE pedidos SET total_pedido = (SELECT SUM(subtotal) FROM detalle_pedidos WHERE pedidos_idpedidos = ?) WHERE idpedidos = ?');
        $stmtTotal->execute([$pedido_id, $pedido_id]);
        
        // Si el pedido estaba entregado (estado 4), cambiar a confirmado (3) para que aparezca en la cocina
        if ($estado_actual === 4) {
            $stmtEstado = $pdo->prepare('UPDATE pedidos SET estados_idestados = 3 WHERE idpedidos = ?');
            $stmtEstado->execute([$pedido_id]);
        }
        
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Detalle de producto no encontrado en el pedido');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 