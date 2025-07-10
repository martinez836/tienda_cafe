<?php
require_once '../models/consultas.php';
require_once '../config/config.php';
require_once '../config/security.php';
session_start();

// Establecer la zona horaria para Colombia
date_default_timezone_set('America/Bogota');

header('Content-Type: application/json');

try {
    $mesa_id = null;
    
    // Procesar diferentes tipos de solicitudes
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Cancelar token por ID
        if (isset($_POST['cancelar_token'])) {
            $idtoken = SecurityUtils::sanitizeId($_POST['cancelar_token'], 'ID del token');
            $pdo = config::conectar();
            $consultas = new ConsultasMesero();
            $consultas->cancelarTokenPorId($pdo, $idtoken);
            echo json_encode(['success' => true, 'message' => 'Token cancelado']);
            exit;
        }
        
        // Cancelar token por valor
        if (isset($_POST['cancelar_token_por_valor'])) {
            $token = SecurityUtils::sanitizeToken($_POST['cancelar_token_por_valor']);
            $pdo = config::conectar();
            $consultas = new ConsultasMesero();
            $consultas->cancelarTokenPorValor($pdo, $token);
            echo json_encode(['success' => true, 'message' => 'Token cancelado']);
            exit;
        }
        
        // Obtener tokens activos
        if (isset($_POST['activos'])) {
            $pdo = config::conectar();
            $consultas = new ConsultasMesero();
            $tokens = $consultas->traerTokensActivos($pdo);
            echo json_encode(['success' => true, 'tokens' => $tokens]);
            exit;
        }
        
        // Generar token para mesa específica
        if (isset($_POST['mesa_id'])) {
            $mesa_id = SecurityUtils::sanitizeId($_POST['mesa_id'], 'ID de mesa');
        }
    }
    
    // Procesar solicitudes GET
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['mesa_id'])) {
        $mesa_id = SecurityUtils::sanitizeId($_GET['mesa_id'], 'ID de mesa');
    }
    
    // Procesar solicitudes JSON
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (isset($data['mesa'])) {
            $mesa_id = SecurityUtils::sanitizeId($data['mesa'], 'ID de mesa');
        } elseif (isset($data['mesa_id'])) {
            $mesa_id = SecurityUtils::sanitizeId($data['mesa_id'], 'ID de mesa');
        }
    }
    
    if (!$mesa_id) {
        throw new Exception('Mesa no especificada');
    }
    
    $pdo = config::conectar();
    $consultas = new ConsultasMesero();
    
    // Verificar si existe al menos un usuario en la tabla usuarios
    $usuario = $consultas->getPrimerUsuario($pdo);
    if (!$usuario) {
        // Si no hay usuarios, crear uno por defecto con la estructura correcta
        $usuario_id = $consultas->crearUsuarioPorDefecto($pdo);
    } else {
        $usuario_id = (int)$usuario['idusuarios'];
    }

    // Generar token de 4 dígitos
    $token = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

    // Tiempo de expiración: 15 minutos desde ahora
    $expiracion = date('Y-m-d H:i:s', strtotime('+30 minutes'));

    // Usar función del modelo para insertar el token
    $consultas = new ConsultasMesero();
    $consultas->insertarTokenMesa($pdo, $token, $expiracion, $mesa_id, $usuario_id);

    echo json_encode(['success' => true, 'token' => $token, 'expira' => $expiracion]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 