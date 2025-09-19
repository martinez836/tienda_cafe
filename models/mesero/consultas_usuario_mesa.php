
<?php

require_once '../../config/config.php';
require_once __DIR__. '/../MySQL.php';


class consultas_usuario_mesa {
    private $mysql;

    public function __construct() {
        $this->mysql = new MySql();
    }

    /**
     * Validar token y obtener información de la mesa asociada
     */
    public function validarToken($pdo, $token) {
        $sql = "SELECT t.*, m.idmesas as mesa_id FROM tokens_mesa t JOIN mesas m ON t.mesas_idmesas = m.idmesas WHERE t.token = ? AND t.estado_token = 'activo' AND t.fecha_hora_expiracion > NOW()";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$token]);
        $token_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($token_data) {
            $token_data['expiracion_timestamp'] = strtotime($token_data['fecha_hora_expiracion']) * 1000;
        }
        return $token_data;
    }

    public function traerDetallePedido($pdo, $pedidoId) {
    // Mostrar todos los productos del pedido (para el resumen)
    $stmt = $pdo->prepare("SELECT dp.productos_idproductos as id, pr.nombre_producto as nombre, dp.cantidad_producto as cantidad, dp.precio_producto as precio, dp.observaciones as comentario, dp.es_producto_nuevo FROM detalle_pedidos dp JOIN productos pr ON pr.idproductos = dp.productos_idproductos WHERE dp.pedidos_idpedidos = ?");
    $stmt->execute([$pedidoId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function traer_productos_por_categoria($categoria)
{
    $consulta = "SELECT * FROM productos WHERE fk_categoria = ? AND estados_idestados = 1 ORDER BY nombre_producto;";
    $parametros = [$categoria];
    return $this->mysql->ejecutarSentenciaPreparada2($consulta, $parametros);
}

public function traerPedidosActivosPorMesa($pdo, $mesaId) {
    // Solo pedidos con productos y token activo/vigente
    $stmt = $pdo->prepare("
        SELECT p.idpedidos, p.fecha_hora_pedido, p.total_pedido, p.token_utilizado, p.estados_idestados
        FROM pedidos p
        INNER JOIN detalle_pedidos dp ON dp.pedidos_idpedidos = p.idpedidos
        LEFT JOIN tokens_mesa t ON t.token = p.token_utilizado
        WHERE p.mesas_idmesas = ?
          AND p.estados_idestados IN (1,3,4)
          AND (
            (p.token_utilizado IS NOT NULL AND t.estado_token IN ('activo', 'usado') AND t.fecha_hora_expiracion > NOW())
            OR p.token_utilizado IS NULL
          )
        GROUP BY p.idpedidos
        ORDER BY p.fecha_hora_pedido DESC
    ");
    $stmt->execute([$mesaId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function traerDetallePedidoParaEdicion($pdo, $pedidoId) {
    // Primero verificar el estado del pedido
    $stmtEstado = $pdo->prepare("SELECT estados_idestados FROM pedidos WHERE idpedidos = ?");
    $stmtEstado->execute([$pedidoId]);
    $pedido = $stmtEstado->fetch(PDO::FETCH_ASSOC);
    
    // Si el pedido está entregado (estado 4), mostrar solo productos nuevos para edición
    if ($pedido && (int)$pedido['estados_idestados'] === 4) {
        $stmt = $pdo->prepare("SELECT dp.productos_idproductos as id, pr.nombre_producto as nombre, dp.cantidad_producto as cantidad, dp.precio_producto as precio, dp.observaciones as comentario, dp.es_producto_nuevo FROM detalle_pedidos dp JOIN productos pr ON pr.idproductos = dp.productos_idproductos WHERE dp.pedidos_idpedidos = ? AND dp.es_producto_nuevo = 1");
    } else {
        // Para otros estados, mostrar todos los productos
        $stmt = $pdo->prepare("SELECT dp.productos_idproductos as id, pr.nombre_producto as nombre, dp.cantidad_producto as cantidad, dp.precio_producto as precio, dp.observaciones as comentario, dp.es_producto_nuevo FROM detalle_pedidos dp JOIN productos pr ON pr.idproductos = dp.productos_idproductos WHERE dp.pedidos_idpedidos = ?");
    }
    $stmt->execute([$pedidoId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function traerCategorias()
{
    $consulta = "SELECT idcategorias, nombre_categoria FROM categorias WHERE estados_idestados = 1 ORDER BY nombre_categoria;";
    return $this->mysql->efectuarConsulta($consulta);
}

public function guardarDetallePedido($pdo, $detalle, $idPedido) {
    // Verificar si el pedido está entregado para marcar el producto como nuevo
    $stmtEstado = $pdo->prepare("SELECT estados_idestados FROM pedidos WHERE idpedidos = ?");
    $stmtEstado->execute([$idPedido]);
    $pedido = $stmtEstado->fetch(PDO::FETCH_ASSOC);
    
    // Si el pedido está en estado 'entregado' (4), entonces este producto es considerado 'nuevamente agregado'
    // independientemente de si un producto con el mismo ID existía antes
    $esProductoNuevo = ($pedido && (int)$pedido['estados_idestados'] === 4) ? 1 : 0;
    
    $stmt = $pdo->prepare("INSERT INTO detalle_pedidos (observaciones, precio_producto, cantidad_producto, subtotal, pedidos_idpedidos, productos_idproductos, es_producto_nuevo) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $detalle['comentario'],
        $detalle['precio'],
        $detalle['cantidad'],
        $detalle['precio'] * $detalle['cantidad'],
        $idPedido,
        $detalle['id'],
        $esProductoNuevo
    ]);
}

public function calcularTotalPedido($pdo, $pedidoId) {
    $stmt = $pdo->prepare("SELECT SUM(subtotal) as total FROM detalle_pedidos WHERE pedidos_idpedidos = ?");
    $stmt->execute([$pedidoId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ?? 0;
}

public function actualizarTotalPedido($pdo, $total, $idPedido) {
    $stmt = $pdo->prepare("UPDATE pedidos SET total_pedido = ? WHERE idpedidos = ?");
    $stmt->execute([$total, $idPedido]);
}

public function traerDetallePedidoPorProducto($pdo, $pedidoId, $productoId) {
    $stmt = $pdo->prepare("SELECT iddetalle_pedidos, cantidad_producto as cantidad FROM detalle_pedidos WHERE pedidos_idpedidos = ? AND productos_idproductos = ?");
    $stmt->execute([$pedidoId, $productoId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function actualizarCantidadDetallePedido($pdo, $pedidoId, $productoId, $nuevaCantidad) {
    $stmt = $pdo->prepare("UPDATE detalle_pedidos SET cantidad_producto = ?, subtotal = precio_producto * ? WHERE pedidos_idpedidos = ? AND productos_idproductos = ?");
    $stmt->execute([$nuevaCantidad, $nuevaCantidad, $pedidoId, $productoId]);
    return $stmt->rowCount();
}

public function confirmarPedidoCliente($pdo, $mesaId, $productos, $token = null, $usuarioId = 1) {
    // 1. Crear el pedido (con tipo_pedido y token_utilizado)
    $stmt = $pdo->prepare("INSERT INTO pedidos (fecha_hora_pedido, total_pedido, estados_idestados, mesas_idmesas, usuarios_idusuarios, tipo_pedido, token_utilizado) VALUES (NOW(), 0, 3, ?, ?, 'cliente', ?)");
    $stmt->execute([$mesaId, $usuarioId, $token]);
    $pedido_id = $pdo->lastInsertId();

    // 2. Insertar los productos del pedido
    $stmt = $pdo->prepare("INSERT INTO detalle_pedidos (observaciones, precio_producto, cantidad_producto, subtotal, pedidos_idpedidos, productos_idproductos) VALUES (?, ?, ?, ?, ?, ?)");
    $updateStockStmt = $pdo->prepare("UPDATE productos SET stock_producto = stock_producto - ? WHERE idproductos = ?");
    $checkStockStmt = $pdo->prepare("SELECT stock_producto FROM productos WHERE idproductos = ?");
    foreach ($productos as $producto) {
        // Validar stock antes de insertar
        $checkStockStmt->execute([$producto['id']]);
        $row = $checkStockStmt->fetch(PDO::FETCH_ASSOC);
        $stockDisponible = isset($row['stock_producto']) ? (int)$row['stock_producto'] : null;
        if ($stockDisponible !== null && $producto['cantidad'] > $stockDisponible) {
            // Eliminar el pedido creado para no dejar basura
            $pdo->prepare("DELETE FROM pedidos WHERE idpedidos = ?")->execute([$pedido_id]);
            throw new Exception("Stock insuficiente para el producto ID " . $producto['id']);
        }
        $subtotal = $producto['precio'] * $producto['cantidad'];
        $stmt->execute([
            $producto['comentario'] ?? null,
            $producto['precio'],
            $producto['cantidad'],
            $subtotal,
            $pedido_id,
            $producto['id']
        ]);
        // Descontar stock si no es null
        if (isset($producto['cantidad']) && $producto['cantidad'] !== null) {
            $updateStockStmt->execute([$producto['cantidad'], $producto['id']]);
        }
    }

    // 3. Calcular y actualizar el total del pedido (usando subconsulta)
    $stmt = $pdo->prepare("UPDATE pedidos SET total_pedido = (SELECT SUM(subtotal) FROM detalle_pedidos WHERE pedidos_idpedidos = ?) WHERE idpedidos = ?");
    $stmt->execute([$pedido_id, $pedido_id]);

    // 4. Invalidar el token
    $stmt = $pdo->prepare("UPDATE tokens_mesa SET estado_token = 'usado' WHERE mesas_idmesas = ? AND estado_token = 'activo'");
    $stmt->execute([$mesaId]);

    return $pedido_id;
}

public function traerPedidosPorMesaYToken($pdo, $mesaId, $token) {
    $stmt = $pdo->prepare("
        SELECT p.idpedidos, p.fecha_hora_pedido, p.total_pedido, p.token_utilizado
        FROM pedidos p
        INNER JOIN detalle_pedidos dp ON dp.pedidos_idpedidos = p.idpedidos
        INNER JOIN tokens_mesa t ON t.token = p.token_utilizado
        WHERE p.mesas_idmesas = ?
          AND p.token_utilizado = ?
          AND p.estados_idestados IN (1,3,4)
          AND t.estado_token IN ('activo', 'usado')
          AND t.fecha_hora_expiracion > NOW()
        GROUP BY p.idpedidos
        ORDER BY p.fecha_hora_pedido DESC
    ");
    $stmt->execute([$mesaId, $token]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

}
