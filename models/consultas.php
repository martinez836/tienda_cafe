<?php
require_once __DIR__ . '/MySQL.php';
require_once __DIR__ . '/../config/config.php';

class consultas
{
    private $mysql;

    public function __construct()
    {
        $this->mysql = new MySql();
    }

    public function traer_mesas()
    {
        $query = 'SELECT * FROM mesas WHERE estados_idestados  = 1';
        $stmt = $this->mysql->efectuarConsulta($query);
        return $stmt;
    }

    public function traer_categorias()
    {
        $query = 'SELECT * FROM categorias';
        $stmt = $this->mysql->efectuarConsulta($query);
        return $stmt;
    }
    public function traer_productos_por_categoria($categoria)
    {
        $query =
            "select * from productos where fk_categoria = :categoria order by nombre_producto asc;";
        $parameters = [':categoria' => $categoria];
        $stmt = $this->mysql->ejecutarSentenciaPreparada($query, 's', $parameters);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function editar_nombre_mesa($id, $nombre)
    {
        if ($this->existe_mesa_nombre($nombre, $id)) {
            return 'duplicado';
        }
        $query = "UPDATE mesas SET nombre = :nombre WHERE idmesas = :id";
        $parameters = [':nombre' => $nombre, ':id' => $id];
        $stmt = $this->mysql->ejecutarSentenciaPreparada($query, 'si', $parameters);
        return $stmt->rowCount() > 0;
    }

    public function existe_mesa_nombre($nombre, $excluir_id = null) {
        $query = "SELECT COUNT(*) as total FROM mesas WHERE nombre = :nombre AND estados_idestados = 1";
        $parameters = [':nombre' => $nombre];
        $types = 's';
        if ($excluir_id !== null) {
            $query .= " AND idmesas != :id";
            $parameters[':id'] = $excluir_id;
            $types .= 'i';
        }
        $stmt = $this->mysql->ejecutarSentenciaPreparada($query, $types, $parameters);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row && $row['total'] > 0;
    }

    public function agregar_mesa($nombre)
    {
        if ($this->existe_mesa_nombre($nombre)) {
            return 'duplicado';
        }
        $query = "INSERT INTO mesas (nombre, estados_idestados) VALUES (:nombre, 1)";
        $parameters = [':nombre' => $nombre];
        $stmt = $this->mysql->ejecutarSentenciaPreparada($query, 's', $parameters);
        return $stmt->rowCount() > 0;
    }

    public function inactivar_mesa($id)
    {
        $query = "UPDATE mesas SET estados_idestados = 2 WHERE idmesas = :id";
        $parameters = [':id' => $id];
        $stmt = $this->mysql->ejecutarSentenciaPreparada($query, 'i', $parameters);
        return $stmt->rowCount() > 0;
    }
};
