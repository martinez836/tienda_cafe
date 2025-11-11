<?php
require_once __DIR__ . '/MySQL.php';
require_once __DIR__ . '/../config/config.php';

class ConsultasMesa{
    private $mysql;
    public function __construct() {
        $this->mysql = new MySql();
    }

    public function agregar_mesa($nombre) {
        $stmt = $this->mysql->ejecutarSentenciaPreparada("SELECT COUNT(*) as total FROM mesas WHERE nombre = ? AND estados_idestados = 1", 's', [$nombre]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['total'] > 0) {
            return 'duplicado';
        } else {
            $stmt = $this->mysql->ejecutarSentenciaPreparada("INSERT INTO mesas (nombre, estados_idestados) VALUES (?, 1)", 's', [$nombre]);
            return $stmt->rowCount() > 0;
        }        
    }
    
    public function editar_nombre_mesa($nombre, $id) {
        $stmt = $this->mysql->ejecutarSentenciaPreparada("SELECT COUNT(*) as total FROM mesas WHERE nombre = ? AND idmesas != ? AND estados_idestados = 1", 'si', [$nombre, $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['total'] > 0) {
            return 'duplicado';
        } else {
            $stmt = $this->mysql->ejecutarSentenciaPreparada("UPDATE mesas SET nombre = ? WHERE idmesas = ?", 'si', [$nombre, $id]);
            return $stmt->rowCount() > 0;
        }
    }
    
    //Inactivar mesa
    public function inactivar_mesa($id) {
        $stmt = $this->mysql->ejecutarSentenciaPreparada("UPDATE mesas SET estados_idestados = 2 WHERE idmesas = ?", 'i', [$id]);
        return $stmt->rowCount() > 0;
    }
}

