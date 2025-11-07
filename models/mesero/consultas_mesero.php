<?php

// Clase para gestionar las consultas del mesero
require_once __DIR__ . "/../MySQL.php";
require_once __DIR__ . "/../../config/config.php";



class consultas_mesero {
    private $mysql;

    public function __construct()
    {
    $this->mysql = new MySql();
    }

    /**
     * Obtiene todas las mesas activas del sistema, junto con su estado (ocupada, disponible, token activo).
     * Relacionada con la vista de selección de mesa y usada por controladores para mostrar el listado de mesas.
     */
    public function traerMesas()
    {
        $query = "
            SELECT 
                m.idmesas,
                m.nombre,
                m.estados_idestados,
                CASE 
                    WHEN EXISTS (
                        SELECT 1 FROM pedidos p 
                        WHERE p.mesas_idmesas = m.idmesas 
                        AND p.estados_idestados IN (3, 4)
                    ) THEN 'ocupada_pedido'
                    WHEN EXISTS (
                        SELECT 1 FROM tokens_mesa t 
                        WHERE t.mesas_idmesas = m.idmesas 
                        AND t.estado_token = 'activo' 
                        AND t.fecha_hora_expiracion > NOW()
                    ) THEN 'ocupada_token'
                    ELSE 'disponible'
                END as estado_mesa
            FROM mesas m 
            WHERE m.estados_idestados != 2
            ORDER BY m.nombre";

        try {
            return $this->mysql->efectuarConsulta($query);
        } catch (Exception $e) {
            throw new Exception("Error al obtener las mesas: " . $e->getMessage());
        }
    }

        /**
     * Devuelve todas las categorías activas de productos.
     * Usada por el controlador para cargar el select de categorías en la vista del mesero.
     */
    public function traerCategorias()
    {
        $query = "SELECT idcategorias, nombre_categoria 
        FROM categorias 
        WHERE estados_idestados = 1 
        ORDER BY nombre_categoria";

        try {
            return $this->mysql->efectuarConsulta($query);
        } catch (Exception $e) {
            throw new Exception("Error al obtener las categorías: " . $e->getMessage());
        }
    }

    /**
     * Devuelve los productos activos de una categoría específica.
     * Relacionada con la selección de categoría en la vista y el controlador de productos.
     */
    public function traerProductosPorCategoria($idcategorias)
    {
        $query = "
            SELECT 
                p.idproductos,
                p.nombre_producto,
                p.precio_producto,
                p.stock_producto,
                p.tipo_productos_idtipo_productos,
                tp.nombre_tipo
            FROM productos p
            INNER JOIN tipo_productos tp ON p.tipo_productos_idtipo_productos = tp.idtipo_productos
            WHERE p.fk_categoria = ? 
            AND p.estados_idestados = 1
            ORDER BY p.nombre_producto";

        try {
            $stmt = $this->mysql->ejecutarSentenciaPreparada2($query, [$idcategorias]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error al obtener los productos por categoría: " . $e->getMessage());
        }
    }

    // Método para insertar un pedido principal
    /**
     * Inserta un nuevo pedido principal en la base de datos.
     * RESPONSABILIDAD ÚNICA: Esta función SOLO inserta el pedido y retorna el ID.
     * El cambio de estado de la mesa debe hacerse aparte, llamando a actualizarEstadoMesa desde el controlador.
     */
    public function insertarPedido($mesa, $usuario, $fecha, $estado, $total, $tipoPedido = 'mesero', $tokenUtilizado = null)
    {
        $query = "INSERT INTO pedidos (mesas_idmesas, usuarios_idusuarios, fecha_hora_pedido, estados_idestados, total_pedido, tipo_pedido, token_utilizado) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";

        try {
            $this->mysql->ejecutarSentenciaPreparada2($query, [
                $mesa,
                $usuario,
                $fecha,
                $estado,
                $total,
                $tipoPedido,
                $tokenUtilizado
            ]);
            return $this->mysql->obtenerUltimoId();
        } catch (Exception $e) {
            throw new Exception("Error al insertar pedido: " . $e->getMessage());
        }
    }

    // Método para insertar detalle de pedido
    /**
     * Inserta un producto en el detalle de un pedido.
     * RESPONSABILIDAD ÚNICA: Esta función orquesta el flujo, pero cada acción está separada.
     * Retorna true si la inserción fue exitosa.
     */
    public function insertarDetallePedido($pedidoId, $productoId, $cantidad, $precio, $subtotal, $observaciones)
    {
        // 1. Validar stock
    $this->validarStockProducto($productoId, $cantidad);

    // 2. Consultar estado del pedido
    $estado = $this->obtenerEstadoPedidoSoloId($pedidoId);

        // 3. Determinar si es producto nuevo y actualizar estado si corresponde
        $esProductoNuevo = 0;
        if ($estado === 4) {
            $esProductoNuevo = 1;
            $this->actualizarEstadoPedido($pedidoId, 3);
        }

        // 4. Insertar detalle
    $exito = $this->insertarDetallePedidoEnBD($pedidoId, $productoId, $cantidad, $precio, $subtotal, $observaciones, $esProductoNuevo);

    // 5. Actualizar stock si corresponde
    $this->actualizarStockProducto($productoId, $cantidad);

        return $exito;
    }

    /**
     * Obtiene solo el estado_idestados de un pedido (entero o null)
     */
    private function obtenerEstadoPedidoSoloId($pedidoId)
    {
    $respuestaArray = $this->consultarEstadoPedidoActivo($pedidoId);
    return !empty($respuestaArray) ? (int)$respuestaArray[0]['estados_idestados'] : null;
    }

    /**
     * Inserta el detalle del pedido en la base de datos y retorna true si fue exitoso
     */
    private function insertarDetallePedidoEnBD($pedidoId, $productoId, $cantidad, $precio, $subtotal, $observaciones, $esProductoNuevo)
    {
        $query = "INSERT INTO detalle_pedidos (pedidos_idpedidos, productos_idproductos, cantidad_producto, precio_producto, subtotal, observaciones, es_producto_nuevo) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        try {
            $this->mysql->ejecutarSentenciaPreparada2($query, [
                $pedidoId,
                $productoId,
                $cantidad,
                $precio,
                $subtotal,
                $observaciones,
                $esProductoNuevo
            ]);
            return true;
        } catch (Exception $e) {
            throw new Exception("Error al insertar detalle de pedido: " . $e->getMessage());
        }
    }

    // Método para actualizar stock de producto
    /**
     * Actualiza el stock de un producto si es de tipo con stock limitado.
     * Llamada después de insertar un detalle de pedido para descontar el inventario.
     */
    public function actualizarStockProducto($productoId, $cantidadVendida)
    {
        $query = "
            UPDATE productos 
            SET stock_producto = stock_producto - ? 
            WHERE idproductos = ? 
            AND tipo_productos_idtipo_productos = 2"; // 2 con stock - 1 sin stock

        try {
            $this->mysql->ejecutarSentenciaPreparada2($query, [$cantidadVendida, $productoId]);
            return true;
        } catch (Exception $e) {
            throw new Exception("Error al actualizar stock: " . $e->getMessage());
        }
    }

    // Método para actualizar estado de mesa
    /**
     * Cambia el estado de una mesa (ocupada/disponible) según la acción realizada.
     * Usada por el controlador cuando se asigna o libera una mesa.
     */
    public function actualizarEstadoMesa($mesaId, $nuevoEstado)
    {
        // Determinar el estado correcto según el tipo
        $estadoId = 1; // Por defecto disponible
        switch ($nuevoEstado) {
            case 'ocupada_pedido':
            case 'ocupada':
                $estadoId = 3; // Estado ocupada
                break;
            case 'disponible':
                $estadoId = 1; // Estado disponible
                break;
        }

        $query = "UPDATE mesas SET estados_idestados = ? WHERE idmesas = ?";

        try {
            $this->mysql->ejecutarSentenciaPreparada2($query, [$estadoId, $mesaId]);
            return true; // Si no hay excepción, se actualizó correctamente
        } catch (Exception $e) {
            throw new Exception("Error al actualizar estado de mesa: " . $e->getMessage());
        }
    }

    // Método para obtener detalles de un pedido específico
    /**
     * Devuelve todos los productos del detalle de un pedido específico.
     * Usada por el controlador para mostrar el detalle de un pedido en la vista.
     */
    public function obtenerDetallesPedido($pedidoId)
    {
        $query = "
            SELECT 
                dp.iddetalle_pedidos,
                dp.cantidad_producto,
                dp.precio_producto,
                dp.subtotal,
                dp.observaciones,
                p.nombre_producto,
                p.idproductos
            FROM detalle_pedidos dp
            INNER JOIN productos p ON dp.productos_idproductos = p.idproductos
            WHERE dp.pedidos_idpedidos = ?
            ORDER BY dp.iddetalle_pedidos";

        try {
            $stmt = $this->mysql->ejecutarSentenciaPreparada2($query, [$pedidoId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error al obtener detalles del pedido: " . $e->getMessage());
        }
    }

    // Método para obtener información completa de un pedido
    /**
     * Devuelve toda la información de un pedido (cabecera), incluyendo mesa y usuario.
     * Usada por el controlador para mostrar información completa de un pedido.
     */
    public function obtenerPedidoCompleto($pedidoId)
    {
        $query = "
            SELECT 
                pe.idpedidos,
                pe.fecha_hora_pedido,
                pe.total_pedido,
                pe.tipo_pedido,
                pe.token_utilizado,
                pe.mesas_idmesas,
                pe.usuarios_idusuarios,
                pe.estados_idestados,
                m.nombre as nombre_mesa
            FROM pedidos pe
            INNER JOIN mesas m ON pe.mesas_idmesas = m.idmesas
            WHERE pe.idpedidos = ?";

        try {
            $stmt = $this->mysql->ejecutarSentenciaPreparada2($query, [$pedidoId]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return !empty($result) ? $result[0] : null;
        } catch (Exception $e) {
            throw new Exception("Error al obtener información del pedido: " . $e->getMessage());
        }
    }

    // Método para validar stock de los productos antes de insertar en la bd
    /**
     * Valida que haya suficiente stock antes de insertar un producto al pedido.
     * Llamada internamente por insertarDetallePedido para evitar sobreventa.
     */
    public function validarStockProducto($productoId, $cantidadSolicitada)
    {
        $query = "
            SELECT 
                p.stock_producto,
                p.tipo_productos_idtipo_productos,
                p.nombre_producto
            FROM productos p
            WHERE p.idproductos = ? AND p.estados_idestados = 1";

        try {
            $stmt = $this->mysql->ejecutarSentenciaPreparada2($query, [$productoId]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($result)) {
                throw new Exception("Producto no encontrado o inactivo");
            }

            $producto = $result[0];

            // Si es producto con stock (tipo 2), validar cantidad
            if ($producto['tipo_productos_idtipo_productos'] == 2) {
                if ($producto['stock_producto'] < $cantidadSolicitada) {
                    throw new Exception("Stock insuficiente para " . $producto['nombre_producto'] .
                        ". Disponible: " . $producto['stock_producto']);
                }
            }

            return true;
        } catch (Exception $e) {
            throw new Exception("Error al validar stock: " . $e->getMessage());
        }
    }

    /**
     * Obtener productos detallados de pedidos activos de una mesa
     * Para mostrar todos los productos individuales en proceso
     */
    /**
     * Devuelve todos los productos activos (en proceso o entregados) de los pedidos de una mesa.
     * Usada por el controlador para mostrar productos en proceso en la vista del mesero.
     */
    public function obtenerProductosActivosMesa($mesaId)
    {
        $query = "
            SELECT 
                pe.idpedidos,
                pe.fecha_hora_pedido,
                pe.total_pedido,
                dp.cantidad_producto as cantidad,
                dp.precio_producto as precio,
                dp.subtotal,
                dp.observaciones,
                pr.nombre_producto as nombre
            FROM pedidos pe
            INNER JOIN detalle_pedidos dp ON pe.idpedidos = dp.pedidos_idpedidos
            INNER JOIN productos pr ON dp.productos_idproductos = pr.idproductos
            WHERE pe.mesas_idmesas = ? 
            AND pe.estados_idestados IN (3, 4)
            ORDER BY pe.fecha_hora_pedido DESC, pr.nombre_producto ASC";

        try {
            $stmt = $this->mysql->ejecutarSentenciaPreparada2($query, [$mesaId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error al obtener productos activos de la mesa: " . $e->getMessage());
        }
    }

    // Método para obtener el total de un pedido activo que va a ser actualizado
    /**
     * Devuelve el total actual de un pedido específico.
     * Usada para mostrar el total actualizado en la vista y para cálculos en el backend.
     */
    public function traerTotalPedidoActivo($idPedido)
    {
        $query = "select total_pedido as total from pedidos where idpedidos = ?";
        try {
            $stmt = $this->mysql->ejecutarSentenciaPreparada2($query, [$idPedido]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error al obtener el total del pedido activo: " . $e->getMessage());
        }
    }

    // Método para actualizar el total de un pedido despues de agregar un producto nuevo
    /**
     * Actualiza el total de un pedido después de agregar o quitar productos.
     * Llamada por el controlador tras modificar el detalle del pedido.
     */
    public function actualizarTotalPedido($pedidoId, $nuevoTotal)
    {
        $query = "UPDATE pedidos SET total_pedido = ? WHERE idpedidos = ?";

        try {
            $this->mysql->ejecutarSentenciaPreparada2($query, [$nuevoTotal, $pedidoId]);
            return true;
        } catch (Exception $e) {
            throw new Exception("Error al actualizar total del pedido: " . $e->getMessage());
        }
    }

    // metodo para consultar el estado de un pedido necesario para validar si se puede agregar un producto nuevo
    /**
     * Consulta el estado actual de un pedido (por ejemplo, para saber si está entregado o en proceso).
     * Usada internamente por otras funciones del modelo y por el controlador para validaciones.
     */
    public function consultarEstadoPedidoActivo($pedidoId)
    {
        $query = "SELECT estados_idestados FROM pedidos WHERE idpedidos = ?";

        try {
            $stmt = $this->mysql->ejecutarSentenciaPreparada2($query, [$pedidoId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error al consultar el estado del pedido: " . $e->getMessage());
        }
    }

    // actualiza el estado del pedido a 3(confirmado) para que la cocina prepare el producto nuevo agregado al pedido existente
    public function actualizarEstadoPedido($pedidoId, $estado)
    {
        $query = "UPDATE pedidos SET estados_idestados = ? WHERE idpedidos = ?";

        try {
            $this->mysql->ejecutarSentenciaPreparada2($query, [$estado, $pedidoId]);
            return true;
        } catch (Exception $e) {
            throw new Exception("Error al actualizar el estado del pedido: " . $e->getMessage());
        }
    }

    // TOKENS: Cancelar token por idtoken_mesa
    public function cancelarTokenPorId($idtoken) {
        $query = "UPDATE tokens_mesa SET estado_token = 'cancelado' WHERE idtoken_mesa = ? AND estado_token = 'activo'";
        try {
            $stmt = $this->mysql->ejecutarSentenciaPreparada2($query, [$idtoken]);
            $filas = method_exists($stmt, 'rowCount') ? $stmt->rowCount() : 0;
            return $filas;
        } catch (Exception $e) {
            throw new Exception("Error al cancelar token por ID: " . $e->getMessage());
        }
    }

    // TOKENS: Cancelar token por valor de token
    public function cancelarTokenPorValor($token) {
        $query = "UPDATE tokens_mesa SET estado_token = 'cancelado' WHERE token = ? AND estado_token = 'activo'";
        try {
            $this->mysql->ejecutarSentenciaPreparada2($query, [$token]);
            return true;
        } catch (Exception $e) {
            throw new Exception("Error al cancelar token por valor: " . $e->getMessage());
        }
    }

    // TOKENS: Traer tokens activos
    public function traerTokensActivos() {
        $query = "SELECT t.idtoken_mesa, t.token, t.fecha_hora_generacion, t.fecha_hora_expiracion, t.estado_token, m.nombre as mesa_nombre, m.idmesas FROM tokens_mesa t JOIN mesas m ON t.mesas_idmesas = m.idmesas WHERE t.estado_token = 'activo' AND t.fecha_hora_expiracion > NOW() ORDER BY t.fecha_hora_generacion DESC";
        try {
            return $this->mysql->efectuarConsulta($query);
        } catch (Exception $e) {
            throw new Exception("Error al traer tokens activos: " . $e->getMessage());
        }
    }

    // TOKENS: Insertar nuevo token para una mesa
    public function insertarTokenMesa($token, $expiracion, $mesa_id, $usuario_id) {
        $query = "INSERT INTO tokens_mesa (token, fecha_hora_generacion, fecha_hora_expiracion, estado_token, mesas_idmesas, usuarios_idusuarios) VALUES (?, NOW(), ?, 'activo', ?, ?)";
        try {
            $this->mysql->ejecutarSentenciaPreparada2($query, [$token, $expiracion, $mesa_id, $usuario_id]);
            return $this->mysql->obtenerUltimoId();
        } catch (Exception $e) {
            throw new Exception("Error al insertar token para mesa: " . $e->getMessage());
        }
    }

    // TOKENS: Obtener solo el token activo y vigente de una mesa
    public function obtenerTokensActivosPorMesa($mesaId) {
        $query = "SELECT idtoken_mesa, token, fecha_hora_generacion, fecha_hora_expiracion, estado_token 
            FROM tokens_mesa 
            WHERE mesas_idmesas = ? AND estado_token = 'activo' AND fecha_hora_expiracion > NOW()
            ORDER BY fecha_hora_generacion DESC
            LIMIT 1";
        try {
            $stmt = $this->mysql->ejecutarSentenciaPreparada2($query, [$mesaId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error al obtener token activo de la mesa: " . $e->getMessage());
        }
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

    public function traerDetallePedido($pdo, $pedidoId) {
        // Mostrar todos los productos del pedido (para el resumen)
        $stmt = $pdo->prepare("SELECT dp.productos_idproductos as id, pr.nombre_producto as nombre, dp.cantidad_producto as cantidad, dp.precio_producto as precio, dp.observaciones as comentario, dp.es_producto_nuevo FROM detalle_pedidos dp JOIN productos pr ON pr.idproductos = dp.productos_idproductos WHERE dp.pedidos_idpedidos = ?");
        $stmt->execute([$pedidoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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


}
