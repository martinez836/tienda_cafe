<?php

require_once __DIR__ . '/MySQL.php';
require_once __DIR__ . '/../config/config.php';

class consultasCategorias{
    private $mysql;

    public function __construct()
    {
        $this->mysql = new MySql();
    }

    public function getAllCategorias() {
        try {
            $sql = "SELECT * FROM categorias ORDER BY nombre_categoria ASC";
            return $this->mysql->efectuarConsulta($sql);
        } catch (Exception $e) {
            throw new Exception('Error al obtener categorías: ' . $e->getMessage());
        }
    }
}


?>