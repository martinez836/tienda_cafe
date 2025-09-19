<?php
require_once '../../models/mesero/consultas_usuario_mesa.php';
require_once '../../config/config.php';
require_once '../../config/security.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitizar y validar el token
        $token = SecurityUtils::sanitizeToken($_POST['token'] ?? '');
        
        // Establecer la zona horaria
        date_default_timezone_set('America/Bogota');
        
        $pdo = config::conectar();
        $consultas = new consultas_usuario_mesa();
        $token_data = $consultas->validarToken($pdo, $token);

        if ($token_data) {
            echo json_encode([
                'success' => true,
                'mesa_id' => (int)$token_data['mesa_id'],
                'expiracion' => SecurityUtils::escapeHtml($token_data['fecha_hora_expiracion']),
                'expiracion_timestamp' => (int)$token_data['expiracion_timestamp'],
                'debug' => 'Token found and valid',
                'input_token' => SecurityUtils::escapeHtml($token)
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Token inválido o expirado',
                'debug' => 'No matching token',
                'input_token' => SecurityUtils::escapeHtml($token)
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al validar el token: ' . $e->getMessage(),
            'debug' => 'Exception',
            'exception' => SecurityUtils::escapeHtml($e->getMessage())
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido',
        'debug' => 'Not POST'
    ]);
} 