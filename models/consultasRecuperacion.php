<?php

use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/MySQL.php';
require_once __DIR__ . '/../config/config.php';

class consultasRecuperacion
{
    private $mysql;

    public function __construct()
    {
        $this->mysql = new MySql();
    }


    // RECUPERACIÓN DE CONTRASEÑA: Validar token de recuperación
    public function validarTokenRecuperacion($pdo, $correo, $codigo)
    {
        $stmt = $pdo->prepare("SELECT * FROM recuperacion WHERE correo_recuperacion = ? AND codigo_recuperacion = ?");
        $stmt->execute([$correo, $codigo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // RECUPERACIÓN DE CONTRASEÑA: Eliminar token usado
    public function eliminarTokenRecuperacion($pdo, $correo, $codigo)
    {
        $stmt = $pdo->prepare("DELETE FROM recuperacion WHERE correo_recuperacion = ? AND codigo_recuperacion = ?");
        $stmt->execute([$correo, $codigo]);
        return $stmt->rowCount();
    }

    // RECUPERACIÓN DE CONTRASEÑA: Actualizar contraseña de usuario
    public function actualizarContrasenaUsuario($pdo, $correo, $nueva_contrasena_hash)
    {
        $stmt = $pdo->prepare("UPDATE usuarios SET contrasena_usuario = ? WHERE email_usuario = ?");
        $stmt->execute([$nueva_contrasena_hash, $correo]);
        return $stmt->rowCount();
    }

    // USUARIOS: Verificar credenciales de inicio de sesión
    public function verificarCredencialesUsuario($pdo, $correo, $contrasena)
    {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email_usuario = ? AND estados_idestados = 1 LIMIT 1");
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($usuario && password_verify($contrasena, $usuario['contrasena_usuario'])) {
            return $usuario;
        }
        return false;
    }

    // USUARIOS: Verificar si existe un correo en el sistema
    public function verificarCorreoExiste($pdo, $correo)
    {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email_usuario = ? AND estados_idestados = 1 LIMIT 1");
        $stmt->execute([$correo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // RECUPERACIÓN: Guardar código de recuperación
    public function guardarCodigoRecuperacion($pdo, $correo, $codigo)
    {
        $stmt = $pdo->prepare("INSERT INTO recuperacion (correo_recuperacion, codigo_recuperacion) VALUES (?, ?)");
        $stmt->execute([$correo, $codigo]);
        return $stmt->rowCount();
    }
}
