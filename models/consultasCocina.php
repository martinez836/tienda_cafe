<?php

require_once __DIR__ . '/MySQL.php';
require_once __DIR__ . '/../config/config.php';

class ConsultasCocina
{
    private $mysql;

    public function __construct()
    {
        $this->mysql = new MySql();
    }

    public function traerPedidosPendientes()
    {
        try {
            $consulta = "
                SELECT
                    pedidos.idpedidos, 
                    pedidos.fecha_hora_pedido, 
                    mesas.nombre AS nombre_mesa, 
                    GROUP_CONCAT(CONCAT(detalle_pedidos.cantidad_producto, 'x ', productos.nombre_producto, ' (', IFNULL(detalle_pedidos.observaciones, ''), ')') SEPARATOR ' || ') AS items_list, 
                    pedidos.estados_idestados as status_id 
                FROM pedidos JOIN detalle_pedidos ON pedidos.idpedidos = detalle_pedidos.pedidos_idpedidos 
                JOIN productos ON detalle_pedidos.productos_idproductos = productos.idproductos 
                JOIN mesas ON pedidos.mesas_idmesas = mesas.idmesas 
                WHERE pedidos.estados_idestados = 3
                GROUP BY pedidos.idpedidos, pedidos.fecha_hora_pedido, mesas.nombre 
                ORDER BY pedidos.fecha_hora_pedido ASC
            ";
            return $this->mysql->efectuarConsulta($consulta);
        } catch (Exception $e) {
            throw new Exception("Error al traer pedidos pendientes: " . $e->getMessage());
        }
    }

    public function marcarPedidoComoListo($orderId)
    {
        try {
            $sql = "UPDATE pedidos SET estados_idestados = 4 WHERE idpedidos = ?";
            $parametros = [$orderId];
            $stmt = $this->mysql->ejecutarSentenciaPreparada($sql, "i", $parametros);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            throw new Exception("Error al marcar pedido como listo: " . $e->getMessage());
        }
    }

    public function traerDetallesDeUnPedido($idPedido)
    {
        try {
            $sql_principal = "
                SELECT
                    pedidos.idpedidos,
                    pedidos.fecha_hora_pedido,
                    mesas.nombre AS nombre_mesa,
                    pedidos.estados_idestados as status_id
                FROM pedidos
                JOIN mesas ON pedidos.mesas_idmesas = mesas.idmesas
                WHERE pedidos.idpedidos = ?
            ";
            $parametros_principal = [$idPedido];
            $resultado_principal = $this->mysql->ejecutarSentenciaPreparada($sql_principal, "i", $parametros_principal);
            $pedido = $resultado_principal->fetch(PDO::FETCH_ASSOC);

            if ($pedido) {
                $sql_items = "
                    SELECT
                        detalle_pedidos.cantidad_producto,
                        productos.nombre_producto,
                        detalle_pedidos.observaciones AS observaciones
                    FROM detalle_pedidos
                    JOIN productos ON detalle_pedidos.productos_idproductos = productos.idproductos
                    WHERE detalle_pedidos.pedidos_idpedidos = ?
                ";
                $parametros_items = [$idPedido];
                $resultado_items = $this->mysql->ejecutarSentenciaPreparada($sql_items, "i", $parametros_items);
                $pedido['items'] = $resultado_items->fetchAll(PDO::FETCH_ASSOC);
            }

            return $pedido;
        } catch (Exception $e) {
            throw new Exception("Error al traer detalles del pedido: " . $e->getMessage());
        }
    }
}

?>