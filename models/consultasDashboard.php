<?php

require_once __DIR__ . '/MySQL.php';
require_once __DIR__ . '/../config/config.php';

class ConsultasDashboard
{
    private $mysql;

    public function __construct()
    {
        $this->mysql = new MySql();
    }

    public function getTotalPedidos() {
        try {
            $sql = "SELECT COUNT(idpedidos) AS total_pedidos FROM pedidos;";
            $result = $this->mysql->efectuarConsulta($sql);
            return $result[0]['total_pedidos'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getTotalPedidos: " . $e->getMessage());
            return 0;
        }
    }

    public function getIngresosMesActual() {
        try {
            $sql = "SELECT SUM(total_pedido) AS ingresos_mes 
            FROM pedidos WHERE MONTH(fecha_hora_pedido) = MONTH(NOW()) 
            AND YEAR(fecha_hora_pedido) = YEAR(NOW()) 
            AND estados_idestados = 5;";
            $result = $this->mysql->efectuarConsulta($sql);
            return $result[0]['ingresos_mes'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getIngresosMesActual: " . $e->getMessage());
            return 0;
        }
    }

    public function getNuevosUsuariosMesActual() {
        try {
            $sql = "SELECT COUNT(idusuarios) AS nuevos_usuarios FROM usuarios; ";
            $result = $this->mysql->efectuarConsulta($sql);
            return $result[0]['nuevos_usuarios'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getNuevosUsuariosMesActual: " . $e->getMessage());
            return 0;
        }
    }

    public function getVentasDiarias() {
        try {
            $sql = "SELECT DATE(fecha_hora_pedido) as fecha, SUM(total_pedido) as total_ventas 
            FROM pedidos WHERE estados_idestados = 5 GROUP BY DATE(fecha_hora_pedido) 
            ORDER BY fecha ASC";
            return $this->mysql->efectuarConsulta($sql);
        } catch (Exception $e) {
            error_log("Error getVentasDiarias: " . $e->getMessage());
            return [];
        }
    }

    public function getUltimosPedidos() {
        try {
            $sql = "SELECT p.idpedidos, m.nombre AS nombre_mesa, p.estados_idestados AS status_id 
            FROM pedidos p JOIN mesas m ON p.mesas_idmesas = m.idmesas 
            ORDER BY p.fecha_hora_pedido DESC LIMIT 5;";
            return $this->mysql->efectuarConsulta($sql);
        } catch (Exception $e) {
            error_log("Error getUltimosPedidos: " . $e->getMessage());
            return [];
        }
    }
}

?>
