<?php

require_once __DIR__ . '/MySQL.php';
require_once __DIR__ . '/../config/config.php';

class ConsultasMesero
{
    private $mysql;

    public function __construct()
    {
        $this->mysql = new MySql();
    }

    // MESAS
    public function traerMesas()
    {
        $consulta = "SELECT m.*, 
            (SELECT COUNT(*) FROM tokens_mesa t WHERE t.mesas_idmesas = m.idmesas AND t.estado_token = 'activo' AND t.fecha_hora_expiracion > NOW()) as tiene_token_activo,
            (SELECT t.token FROM tokens_mesa t WHERE t.mesas_idmesas = m.idmesas AND t.estado_token = 'activo' AND t.fecha_hora_expiracion > NOW() ORDER BY t.fecha_hora_generacion DESC LIMIT 1) as token_activo,
            (SELECT COUNT(*) FROM pedidos p WHERE p.mesas_idmesas = m.idmesas AND p.estados_idestados = 3) as tiene_pedido_confirmado,
            (SELECT COUNT(*) FROM pedidos p WHERE p.mesas_idmesas = m.idmesas AND p.estados_idestados = 4) as tiene_pedido_entregado,
            (SELECT COUNT(*) FROM pedidos p WHERE p.mesas_idmesas = m.idmesas AND p.estados_idestados = 5) as tiene_pedido_procesado
        FROM mesas m
        WHERE m.estados_idestados IN (1,5) ORDER BY m.nombre;";
        $mesas = $this->mysql->efectuarConsulta($consulta);
        // Refuerza: si token_activo es null o vacío, tiene_token_activo debe ser 0
        foreach ($mesas as &$mesa) {
            if (empty($mesa['token_activo']) || $mesa['tiene_token_activo'] == 0) {
                $mesa['token_activo'] = null;
                $mesa['tiene_token_activo'] = 0;
            }
        }
        return $mesas;
    }

    public function traerMesasOcupadas($pdo)
    {
        $stmt = $pdo->query("SELECT idmesas, nombre FROM mesas WHERE estados_idestados = 3");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarEstadoMesa($pdo, $mesaId, $nuevoEstado)
    {
        $stmt = $pdo->prepare("UPDATE mesas SET estados_idestados = ? WHERE idmesas = ?");
        $stmt->execute([$nuevoEstado, $mesaId]);
    }

    public function obtenerNombreMesa($pdo, $mesaId)
    {
        $stmt = $pdo->prepare("SELECT nombre FROM mesas WHERE idmesas = ?");
        $stmt->execute([$mesaId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['nombre'] : '';
    }

    // CATEGORIAS
    public function traerCategorias()
    {
        $consulta = "SELECT idcategorias, nombre_categoria FROM categorias WHERE estados_idestados = 1 ORDER BY nombre_categoria;";
        return $this->mysql->efectuarConsulta($consulta);
    }

    // PRODUCTOS
    public function traer_productos_por_categoria($categoria)
    {
        $consulta = "SELECT * FROM productos WHERE fk_categoria = ? AND estados_idestados = 1 ORDER BY nombre_producto;";
        $parametros = [$categoria];
        return $this->mysql->ejecutarSentenciaPreparada($consulta, "i", $parametros);
    }

    // PEDIDOS
    public function guardarPedido($pdo, $mesaId, $usuarioId, $token = null) {
        $stmt = $pdo->prepare("INSERT INTO pedidos (fecha_hora_pedido, total_pedido, estados_idestados, mesas_idmesas, usuarios_idusuarios, token_utilizado) VALUES (NOW(), 0, 3, ?, ?, ?)");
        $stmt->execute([$mesaId, $usuarioId, $token]);
        return $pdo->lastInsertId();
    }

    public function guardarDetallePedido($pdo, $detalle, $idPedido) {
        $stmt = $pdo->prepare("INSERT INTO detalle_pedidos (observaciones, precio_producto, cantidad_producto, subtotal, pedidos_idpedidos, productos_idproductos) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $detalle['comentario'],
            $detalle['precio'],
            $detalle['cantidad'],
            $detalle['precio'] * $detalle['cantidad'],
            $idPedido,
            $detalle['id']
        ]);
    }

    public function actualizarTotalPedido($pdo, $total, $idPedido) {
        $stmt = $pdo->prepare("UPDATE pedidos SET total_pedido = ? WHERE idpedidos = ?");
        $stmt->execute([$total, $idPedido]);
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

    public function traerDetallePedido($pdo, $pedidoId) {
        $stmt = $pdo->prepare("SELECT dp.productos_idproductos as id, pr.nombre_producto as nombre, dp.cantidad_producto as cantidad, dp.precio_producto as precio, dp.observaciones as comentario FROM detalle_pedidos dp JOIN productos pr ON pr.idproductos = dp.productos_idproductos WHERE dp.pedidos_idpedidos = ?");
        $stmt->execute([$pedidoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarPedidosActivosAMesaLibre($pdo, $mesaId) {
        $stmt = $pdo->prepare("UPDATE pedidos SET estados_idestados = 4 WHERE mesas_idmesas = ? AND estados_idestados = 1");
        $stmt->execute([$mesaId]);
    }

    // FUNCIONES FALTANTES PARA CONFIRMAR PEDIDO
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

    public function calcularTotalPedido($pdo, $pedidoId) {
        $stmt = $pdo->prepare("SELECT SUM(subtotal) as total FROM detalle_pedidos WHERE pedidos_idpedidos = ?");
        $stmt->execute([$pedidoId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    // TOKENS
    public function obtenerTokensPorMesa($pdo, $mesaId) {
        $stmt = $pdo->prepare("SELECT idtoken_mesa, token, fecha_hora_generacion, fecha_hora_expiracion, estado_token FROM tokens_mesa WHERE mesas_idmesas = ? ORDER BY fecha_hora_generacion DESC");
        $stmt->execute([$mesaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function traerTokensActivos($pdo) {
        $stmt = $pdo->query("SELECT t.token, t.fecha_hora_generacion, t.fecha_hora_expiracion, t.estado_token, m.nombre as mesa_nombre, m.idmesas FROM tokens_mesa t JOIN mesas m ON t.mesas_idmesas = m.idmesas WHERE t.estado_token = 'activo' AND t.fecha_hora_expiracion > NOW() ORDER BY t.fecha_hora_generacion DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

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

    // PEDIDOS POR TOKEN (usuario mesa)
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

    // CONFIRMAR PEDIDO (desde confirmar_pedido.php)
    public function confirmarPedidoCliente($pdo, $mesaId, $productos, $token = null) {
        // 1. Crear el pedido (con tipo_pedido y token_utilizado)
        $stmt = $pdo->prepare("INSERT INTO pedidos (fecha_hora_pedido, total_pedido, estados_idestados, mesas_idmesas, usuarios_idusuarios, tipo_pedido, token_utilizado) VALUES (NOW(), 0, 3, ?, 1, 'cliente', ?)");
        $stmt->execute([$mesaId, $token]);
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

    // TOKENS: Insertar nuevo token para una mesa
    public function insertarTokenMesa($pdo, $token, $expiracion, $mesa_id, $usuario_id) {
        $stmt = $pdo->prepare("INSERT INTO tokens_mesa (token, fecha_hora_generacion, fecha_hora_expiracion, estado_token, mesas_idmesas, usuarios_idusuarios) VALUES (?, NOW(), ?, 'activo', ?, ?)");
        $stmt->execute([$token, $expiracion, $mesa_id, $usuario_id]);
        return $pdo->lastInsertId();
    }

    // TOKENS: Cancelar token por idtoken_mesa
    public function cancelarTokenPorId($pdo, $idtoken) {
        $stmt = $pdo->prepare("UPDATE tokens_mesa SET estado_token = 'cancelado' WHERE idtoken_mesa = ?");
        $stmt->execute([$idtoken]);
        return $stmt->rowCount();
    }

    // TOKENS: Cancelar token por valor de token
    public function cancelarTokenPorValor($pdo, $token) {
        $stmt = $pdo->prepare("UPDATE tokens_mesa SET estado_token = 'cancelado' WHERE token = ? AND estado_token = 'activo'");
        $stmt->execute([$token]);
        return $stmt->rowCount();
    }

    // USUARIOS: Obtener el primer usuario
    public function getPrimerUsuario($pdo) {
        $stmt = $pdo->query("SELECT idusuarios FROM usuarios LIMIT 1");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // USUARIOS: Crear usuario por defecto
    public function crearUsuarioPorDefecto($pdo) {
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_usuario, contraseña_usuario, email_usuario, estados_idestados, rol_idrol) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['Mesero', 'password123', 'mesero@cafe.com', 1, 1]);
        return $pdo->lastInsertId();
    }

    // PEDIDOS: Cambiar un pedido específico a estado libre (4)
    public function liberarPedidoPorId($pdo, $pedido_id) {
        $stmt = $pdo->prepare("UPDATE pedidos SET estados_idestados = 4 WHERE idpedidos = ?");
        $stmt->execute([$pedido_id]);
        return $stmt->rowCount();
    }

    // RECUPERACIÓN DE CONTRASEÑA: Validar token de recuperación
    public function validarTokenRecuperacion($pdo, $correo, $codigo) {
        $stmt = $pdo->prepare("SELECT * FROM recuperacion WHERE correo_recuperacion = ? AND codigo_recuperacion = ?");
        $stmt->execute([$correo, $codigo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // RECUPERACIÓN DE CONTRASEÑA: Eliminar token usado
    public function eliminarTokenRecuperacion($pdo, $correo, $codigo) {
        $stmt = $pdo->prepare("DELETE FROM recuperacion WHERE correo_recuperacion = ? AND codigo_recuperacion = ?");
        $stmt->execute([$correo, $codigo]);
        return $stmt->rowCount();
    }

    // RECUPERACIÓN DE CONTRASEÑA: Actualizar contraseña de usuario
    public function actualizarContrasenaUsuario($pdo, $correo, $nueva_contrasena_hash) {
        $stmt = $pdo->prepare("UPDATE usuarios SET contrasena_usuario = ? WHERE email_usuario = ?");
        $stmt->execute([$nueva_contrasena_hash, $correo]);
        return $stmt->rowCount();
    }

    // USUARIOS: Verificar credenciales de inicio de sesión
    public function verificarCredencialesUsuario($pdo, $correo, $contrasena) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email_usuario = ? AND estados_idestados = 1 LIMIT 1");
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($usuario && password_verify($contrasena, $usuario['contrasena_usuario'])) {
            return $usuario;
        }
        return false;
    }

    // TOKENS: Obtener solo el token activo y vigente de una mesa (PDO)
    public function obtenerTokensActivosPorMesa($pdo, $mesaId) {
        $stmt = $pdo->prepare("SELECT idtoken_mesa, token, fecha_hora_generacion, fecha_hora_expiracion, estado_token 
            FROM tokens_mesa 
            WHERE mesas_idmesas = ? AND estado_token = 'activo' AND fecha_hora_expiracion > NOW()
            ORDER BY fecha_hora_generacion DESC
            LIMIT 1");
        $stmt->execute([$mesaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>