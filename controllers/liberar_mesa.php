<?php
require_once '../models/consultas.php';
require_once '../config/config.php';
require_once '../config/security.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validar que los datos JSON sean válidos
    $data = SecurityUtils::sanitizeJsonData($data);
    
    // Validar campo requerido
    SecurityUtils::validateRequiredKeys($data, ['mesa']);
    
    // Sanitizar entradas
    $mesa = SecurityUtils::sanitizeId($data['mesa'], 'ID de mesa');
    $pedido_id = isset($data['pedido_id']) ? SecurityUtils::sanitizeId($data['pedido_id'], 'ID de pedido') : null;

    $pdo = config::conectar();
    $consultas = new ConsultasMesero();
    
    // Cambiar estado de la mesa a libre
    $consultas->actualizarEstadoMesa($pdo, $mesa, 4); // 4 = Libre
    
    // Si se especifica un pedido_id, cambiar solo ese pedido
    if ($pedido_id) {
        $consultas->liberarPedidoPorId($pdo, $pedido_id);
    } else {
        // Cambiar todos los pedidos activos de la mesa a libre
        $consultas->actualizarPedidosActivosAMesaLibre($pdo, $mesa);
    }

    echo json_encode(['success' => true, 'message' => 'Mesa liberada']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 