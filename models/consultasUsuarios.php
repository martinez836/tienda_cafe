<?php

require_once __DIR__ . '/MySQL.php';
require_once __DIR__ . '/../config/config.php';

class ConsultasUsuarios
{
    private $mysql;

    public function __construct()
    {
        $this->mysql = new MySql();
    }

    public function getAllUsuarios() {
        try {
            // Asumiendo que la tabla de usuarios se llama 'usuarios' y tiene campos como idusuarios, nombre_usuario, email_usuario, rol_idrol
            $sql = "SELECT usuarios.idusuarios,
                estados.estado, 
                usuarios.nombre_usuario, 
                usuarios.email_usuario, 
                roles.nombre_rol, 
                roles.idrol,
                usuarios.estados_idestados,
                usuarios.rol_idrol
                FROM usuarios
                JOIN roles ON usuarios.rol_idrol = roles.idrol JOIN estados ON usuarios.estados_idestados = estados.idestados WHERE usuarios.estados_idestados = 1;";
            return $this->mysql->efectuarConsulta($sql);
        } catch (Exception $e) {
            error_log("Error getAllUsuarios: " . $e->getMessage());
            return [];
        }
    }

    public function traerRoles()
    {
        try {
            $sql = "SELECT idrol, nombre_rol FROM roles";
            return $this->mysql->efectuarConsulta($sql);    
        } catch (Exception $e) {
            error_log("Error traerRoles: " . $e->getMessage());
            return [];
        }
    }

    public function traerEstados()
    {
        try {
            // Asumiendo que la tabla de estados se llama 'estados' y tiene campos como idestados, nombre_estado
            $sql = "SELECT idestados,estado FROM estados";
            return $this->mysql->efectuarConsulta($sql);
        } catch (Exception $e) {
            error_log("Error traerEstados: " . $e->getMessage());
            return [];
        }
    }

    public function insertarUsuarios($nombre_usuario, $contrasena_usuario, $email_usuario, $rol_idrol)
    {
        try {
            $sql = "insert into usuarios(nombre_usuario,contrasena_usuario,email_usuario,rol_idrol,estados_idestados)
            values (?,?,?,?,?)";
            $parametros = [$nombre_usuario, $contrasena_usuario, $email_usuario, $rol_idrol, 1]; // 1 = Activo
            $stmt = $this->mysql->ejecutarSentenciaPreparada($sql, "sssii", $parametros);
            if ($stmt->rowCount() > 0) {
                return true;
            } else {
                return false;
            }
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function eliminarUsuario($id)
    {
        try{
            $sql = "update usuarios set estados_idestados = ? where idusuarios = ?";
            $parametros = [2,$id];
            $stm = $this->mysql->ejecutarSentenciaPreparada($sql, "ii", $parametros);
            if($stm->rowCount() > 0) 
            {
                return true;
            }
            else{
                return false;
            }
        }
        catch (Exception $e) {
            error_log("Error Eliminar: " . $e->getMessage());
            return [];
        }
    }



    public function editarUsuario($id,$nombre,$email,$rol)
    {
        try {
            $sql = "update usuarios set nombre_usuario = ?, email_usuario = ?, rol_idrol = ? where idusuarios = ?";
            $parametros = [$nombre,$email,$rol,$id];
            $stm = $this->mysql->ejecutarSentenciaPreparada($sql,'ssi', $parametros);  
            if($stm->rowCount() > 0)
            {
                return true;
            }
            else
            {
                return false;
            }
        } catch (Exception $e) {
            error_log("Error Editar: " . $e->getMessage());
            return [];
        }
    }
}

?>
