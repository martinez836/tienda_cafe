<?php
require_once '../models/consultas.php';
require_once '../config/config.php';
require_once '../config/security.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validar que los datos JSON sean válidos
        $data = SecurityUtils::sanitizeJsonData($data);
        
        // Validar campo requerido
        SecurityUtils::validateRequiredKeys($data, ['mesa_id']);
        
        // Sanitizar entrada
        $mesa_id = SecurityUtils::sanitizeId($data['mesa_id'], 'ID de mesa');
        
        // Verificar si se solicita para edición
        $para_edicion = isset($data['para_edicion']) ? (bool)$data['para_edicion'] : false;
        
        $pdo = config::conectar();
        $consultas = new ConsultasMesero();
        $pedidos = $consultas->traerPedidosActivosPorMesa($pdo, $mesa_id);
        $resultado = [];
        
        foreach ($pedidos as $pedido) {
            // Usar la función correcta según el contexto
            if ($para_edicion) {
                $productos = $consultas->traerDetallePedidoParaEdicion($pdo, $pedido['idpedidos']);
            } else {
                $productos = $consultas->traerDetallePedido($pdo, $pedido['idpedidos']);
            }
            
            $resultado[] = [
                'pedido_id' => (int)$pedido['idpedidos'],
                'fecha_hora' => SecurityUtils::escapeHtml($pedido['fecha_hora_pedido']),
                'total_pedido' => (float)$pedido['total_pedido'],
                'token_utilizado' => SecurityUtils::escapeHtml($pedido['token_utilizado']),
                'productos' => $productos
            ];
        }
        
        echo json_encode([
            'success' => true,
            'pedidos' => $resultado
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al cargar los pedidos activos: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
} 