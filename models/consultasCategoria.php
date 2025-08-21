<?php

require_once __DIR__ . '/MySQL.php';
require_once __DIR__ . '/../config/config.php';

class consultasCategoria
{
    private $mysql;

    public function __construct()
    {
        $this->mysql = new MySql();
    }

    public function crearCategoria($nombre_categoria)
    {
        try {
            $sql = "INSERT INTO categorias (nombre_categoria,estados_idestados) VALUES (?,1)";
            $params = [$nombre_categoria];
            $stmt = $this->mysql->ejecutarSentenciaPreparada($sql,'si', $params);
            if($stmt->rowCount() > 0) {
                return true;
            }
            else {
                return false;
            }
        } catch (Exception $e) {
            error_log("Error crearCategoria: " . $e->getMessage());
            return false;
        }
    }

    public function actualizarCategoria($nombre_categoria,$id_categoria)
    {
        try {
            $sql = "UPDATE categorias SET nombre_categoria = ? WHERE idcategorias = ?";
            $params = [$nombre_categoria, $id_categoria];
            $stmt = $this->mysql->ejecutarSentenciaPreparada($sql, 'si', $params);
            if ($stmt === false) {
            return false;
        }

        // Éxito aunque no haya cambiado ninguna fila
        return true;

        } catch (Exception $e) {
            error_log("Error actualizarCategoria: " . $e->getMessage());
            return false;
        }
    }

    public function inhabilitarCategoria($id_categoria)
    {
        try {
            $sql = "UPDATE categorias SET estados_idestados  = 2 WHERE idcategorias = ?";
            $params = [$id_categoria];
            $stmt = $this->mysql->ejecutarSentenciaPreparada($sql, 'i', $params);
            if($stmt->rowCount() > 0) {
                return true;
            }
            else{
                return false;   
            }
        } catch (Exception $e) {
            error_log("Error inhabilitarCategoria: " . $e->getMessage());
            return false;
        }
    }
}