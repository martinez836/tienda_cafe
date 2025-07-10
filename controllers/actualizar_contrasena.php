<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once '../config/security.php';
require_once '../models/consultas.php';
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar campos requeridos
        SecurityUtils::validateRequiredKeys($_POST, ['correo', 'codigo', 'nueva_contrasena']);

        // Sanitizar y validar entradas
        $correo = SecurityUtils::sanitizeEmail($_POST['correo']);
        $codigo = SecurityUtils::sanitizeRecoveryCode($_POST['codigo']);
        $nueva_contrasena = SecurityUtils::sanitizePassword($_POST['nueva_contrasena']);

        // Usar la clase de consultas para validar el token
        $pdo = config::conectar();
        $consultas = new ConsultasMesero();
        $token_data = $consultas->validarTokenRecuperacion($pdo, $correo, $codigo);

        if ($token_data) {
            // El código es válido, actualizar la contraseña
            $nueva_contrasena_hash = password_hash($nueva_contrasena, PASSWORD_BCRYPT);

            // Actualizar la contraseña del usuario
            $exito_actualizacion = $consultas->actualizarContrasenaUsuario($pdo, $correo, $nueva_contrasena_hash);

            if ($exito_actualizacion) {
                // Eliminar el código de recuperación usado
                $consultas->eliminarTokenRecuperacion($pdo, $correo, $codigo);

                echo json_encode(['success' => true, 'message' => 'Tu contraseña ha sido actualizada exitosamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Hubo un error al actualizar la contraseña. Por favor, inténtalo de nuevo.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'El código de recuperación no es válido o ha expirado. Por favor, solicita un nuevo código.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de solicitud no válido.']);
} 