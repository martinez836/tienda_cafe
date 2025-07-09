<?php

require_once __DIR__ . '/MySQL.php';
require_once __DIR__ . '/../config/config.php';

class ConsultasLogin
{
    private $mysql;

    public function __construct()
    {
        $this->mysql = new MySql();
    }

    /**
     * Autenticar usuario por email y contraseña
     */
    public function autenticarUsuario($email, $password)
    {
        try {
            $sql = "SELECT usuarios.idusuarios, usuarios.nombre_usuario, usuarios.email_usuario, usuarios.contrasena_usuario, 
                        roles.nombre_rol, roles.idrol, estados.estado
                    FROM usuarios 
                    JOIN roles ON usuarios.rol_idrol = roles.idrol 
                    JOIN estados ON usuarios.estados_idestados = estados.idestados
                    WHERE usuarios.email_usuario = ? and estados.estado = 'Activo'";
            
            $parametros = [$email];
            $stmt = $this->mysql->ejecutarSentenciaPreparada($sql, 's', $parametros);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario && password_verify($password, $usuario['contrasena_usuario'])) {
                return $usuario;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error autenticarUsuario: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener información del usuario por ID
     */
    public function obtenerUsuarioPorId($id)
    {
        try {
            $sql = "SELECT usuarios.idusuarios, usuarios.nombre_usuario, usuarios.email_usuario, 
                        roles.nombre_rol, roles.idrol, estados.estado
                    FROM usuarios 
                    JOIN roles ON usuarios.rol_idrol = roles.idrol 
                    JOIN estados ON usuarios.estados_idestados = estados.idestados
                    WHERE usuarios.idusuarios = ?";
            
            $parametros = [$id];
            $stmt = $this->mysql->ejecutarSentenciaPreparada($sql, 'i', $parametros);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obtenerUsuarioPorId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si un email existe
     */
    public function emailExiste($email)
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM usuarios WHERE email_usuario = ?";
            $parametros = [$email];
            $stmt = $this->mysql->ejecutarSentenciaPreparada($sql, 's', $parametros);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['total'] > 0;
        } catch (Exception $e) {
            error_log("Error emailExiste: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambiar contraseña de usuario
     */
    public function cambiarContraseña($idUsuario, $nuevaPassword)
    {
        try {
            $passwordHash = password_hash($nuevaPassword, PASSWORD_BCRYPT);
            
            $sql = "UPDATE usuarios SET contrasena_usuario = ? WHERE idusuarios = ?";
            $parametros = [$passwordHash, $idUsuario];
            $stmt = $this->mysql->ejecutarSentenciaPreparada($sql, 'si', $parametros);
            
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Error cambiarContraseña: " . $e->getMessage());
            return false;
        }
    }
}

?>
