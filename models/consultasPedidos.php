<?php

require_once __DIR__ . '/MySQL.php';
require_once __DIR__ . '/../config/config.php';

class ConsultasPedidos
{
    private $mysql;

    public function __construct()
    {
        $this->mysql = new MySql();
    }

    public function getAllPedidos() {
        try {
            $sql = "
                SELECT
                    pedidos.idpedidos,
                    pedidos.fecha_hora_pedido,
                    mesas.nombre AS nombre_mesa,
                    estados.estado AS estado_pedido,
                    usuarios.nombre_usuario AS nombre_usuario
                FROM pedidos
                JOIN mesas ON pedidos.mesas_idmesas = mesas.idmesas
                JOIN estados ON pedidos.estados_idestados = estados.idestados
                JOIN usuarios ON pedidos.usuarios_idusuarios = usuarios.idusuarios
                ORDER BY pedidos.fecha_hora_pedido DESC;
            ";
            return $this->mysql->efectuarConsulta($sql);
        } catch (Exception $e) {
            error_log("Error getAllPedidos: " . $e->getMessage());
            return [];
        }
    }

    public function getDetallePedido($idPedido) {
        try {
            // Obtener información principal del pedido
            $sqlPrincipal = "
                SELECT
                    pedidos.idpedidos,
                    pedidos.fecha_hora_pedido,
                    pedidos.total_pedido,
                    mesas.nombre AS nombre_mesa,
                    estados.estado AS estado_pedido,
                    usuarios.nombre_usuario AS nombre_usuario
                FROM pedidos
                JOIN mesas ON pedidos.mesas_idmesas = mesas.idmesas
                JOIN estados ON pedidos.estados_idestados = estados.idestados
                JOIN usuarios ON pedidos.usuarios_idusuarios = usuarios.idusuarios
                WHERE pedidos.idpedidos = ?
            ";
            
            $parametros = [$idPedido];
            $stmt = $this->mysql->ejecutarSentenciaPreparada($sqlPrincipal, "i", $parametros);
            $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$pedido) {
                return null;
            }
            
            // Obtener los productos del pedido
            $sqlProductos = "
                SELECT
                    detalle_pedidos.cantidad_producto,
                    detalle_pedidos.precio_producto,
                    detalle_pedidos.subtotal,
                    detalle_pedidos.observaciones AS observaciones,
                    productos.nombre_producto
                FROM detalle_pedidos
                JOIN productos ON detalle_pedidos.productos_idproductos = productos.idproductos
                WHERE detalle_pedidos.pedidos_idpedidos = ?
                ORDER BY detalle_pedidos.iddetalle_pedidos
            ";
            
            $stmtProductos = $this->mysql->ejecutarSentenciaPreparada($sqlProductos, "i", $parametros);
            $pedido['productos'] = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);
            
            return $pedido;
        } catch (Exception $e) {
            error_log("Error getDetallePedido: " . $e->getMessage());
            return null;
        }
    }

    public function getPedidosPorRango($fechaInicio, $fechaFin) {
        try {
            $sql = "
                SELECT
                    pedidos.idpedidos,
                    pedidos.fecha_hora_pedido,
                    pedidos.total_pedido,
                    usuarios.nombre_usuario
                FROM pedidos
                JOIN usuarios ON pedidos.usuarios_idusuarios = usuarios.idusuarios
                WHERE DATE(pedidos.fecha_hora_pedido) BETWEEN ? AND ?
                ORDER BY pedidos.fecha_hora_pedido ASC;
            ";
            $stmt = $this->mysql->ejecutarSentenciaPreparada($sql, '', [$fechaInicio, $fechaFin]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error getPedidosPorRango: ' . $e->getMessage());
            return [];
        }
    }
    
}

?>
