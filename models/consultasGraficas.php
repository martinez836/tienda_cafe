<?php

require_once __DIR__ . '/MySQL.php';
require_once __DIR__ . '/../config/config.php';

class ConsultasGraficas
{
    private $mysql;

    public function __construct()
    {
        $this->mysql = new MySql();
    }

    public function getVentasPorCategoria() {
        $sql = "SELECT categorias.nombre_categoria AS categoria, 
                SUM(detalle_pedidos.precio_producto * detalle_pedidos.cantidad_producto) AS total_ventas 
                FROM detalle_pedidos 
                JOIN productos ON detalle_pedidos.productos_idproductos = productos.idproductos 
                JOIN categorias ON productos.fk_categoria = categorias.idcategorias 
                GROUP BY categorias.nombre_categoria; ";
        return $this->mysql->efectuarConsulta($sql);
    }

    public function getProductosMasVendidos() {
        $sql = "SELECT 
                productos.nombre_producto AS producto, 
                SUM(detalle_pedidos.cantidad_producto) AS cantidad_vendida 
                FROM detalle_pedidos 
                JOIN productos ON detalle_pedidos.productos_idproductos = productos.idproductos 
                GROUP BY productos.nombre_producto 
                ORDER BY cantidad_vendida DESC 
                LIMIT 5; ";
        return $this->mysql->efectuarConsulta($sql);
    }

    public function getTendenciaPedidosMensual() {
        $sql = "SELECT DATE_FORMAT(pedidos.fecha_hora_pedido, '%Y-%m') AS mes, COUNT(*) AS total_pedidos
                FROM pedidos
                GROUP BY mes
                ORDER BY mes";
        return $this->mysql->efectuarConsulta($sql);
    }

    public function getIngresosAnuales() {
        $sql = "SELECT 
                YEAR(pedidos.fecha_hora_pedido) AS año, 
                SUM(detalle_pedidos.precio_producto * detalle_pedidos.cantidad_producto) AS total_ingresos 
                FROM detalle_pedidos 
                JOIN pedidos ON pedidos.idpedidos = detalle_pedidos.pedidos_idpedidos 
                GROUP BY año 
                ORDER BY año;";
        return $this->mysql->efectuarConsulta($sql);
    }

    public function getMesasPorEmpleado() {
        $sql = "SELECT
                usuarios.nombre_usuario AS usuarios,
                SUM(detalle_pedidos.precio_producto * detalle_pedidos.cantidad_producto) AS total_ingresos
                FROM detalle_pedidos
                JOIN pedidos ON pedidos.idpedidos = detalle_pedidos.pedidos_idpedidos
                JOIN usuarios ON pedidos.usuarios_idusuarios = usuarios.idusuarios
                GROUP BY usuarios.nombre_usuario
                ORDER BY total_ingresos DESC;";
        return $this->mysql->efectuarConsulta($sql);
    }

    public function getCantidadMesasPorEmpleado() {
        $sql = "SELECT usuarios.nombre_usuario AS usuario, COUNT(DISTINCT pedidos.mesas_idmesas) AS cantidad_mesas
                FROM pedidos
                JOIN usuarios ON pedidos.usuarios_idusuarios = usuarios.idusuarios
                GROUP BY usuarios.nombre_usuario
                ORDER BY cantidad_mesas DESC;";
        return $this->mysql->efectuarConsulta($sql);
    }

    public function getRecaudoPorMes() {
        $sql = "SELECT DATE_FORMAT(pedidos.fecha_hora_pedido, '%M %Y') AS mes, SUM(detalle_pedidos.precio_producto * detalle_pedidos.cantidad_producto) AS total_recaudo
                FROM detalle_pedidos
                JOIN pedidos ON pedidos.idpedidos = detalle_pedidos.pedidos_idpedidos
                GROUP BY mes
                ORDER BY MIN(pedidos.fecha_hora_pedido)";
        return $this->mysql->efectuarConsulta($sql);
    }
}

?>
