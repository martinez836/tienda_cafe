<?php
require_once '../config/config.php';
require_once '../models/consultas.php';
require_once '../config/security.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_name('cafe_session');
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validar que los datos JSON sean válidos
        $data = SecurityUtils::sanitizeJsonData($data);
        
        // Validar campos requeridos
        SecurityUtils::validateRequiredKeys($data, ['mesa_id', 'productos']);
        
        // Sanitizar entradas principales
        $mesa_id = SecurityUtils::sanitizeId($data['mesa_id'], 'ID de mesa');
        $token_utilizado = isset($data['token']) ? SecurityUtils::sanitizeToken($data['token']) : null;
        
        // Validar que productos sea un array
        if (!is_array($data['productos'])) {
            throw new Exception('Formato de productos inválido');
        }
        
        // Sanitizar cada producto
        $productos_sanitizados = [];
        foreach ($data['productos'] as $producto) {
            if (!is_array($producto)) {
                throw new Exception('Formato de producto inválido');
            }
            
            SecurityUtils::validateRequiredKeys($producto, ['id', 'cantidad', 'precio']);
            
            $productos_sanitizados[] = [
                'id' => SecurityUtils::sanitizeId($producto['id'], 'ID de producto'),
                'cantidad' => SecurityUtils::sanitizeQuantity($producto['cantidad']),
                'precio' => SecurityUtils::sanitizePrice($producto['precio']),
                'comentario' => isset($producto['comentario']) ? SecurityUtils::sanitizeComment($producto['comentario']) : ''
            ];
        }
        
        $pdo = config::conectar();
        $consultas = new ConsultasMesero();
        
        $pedido_id_modificar = isset($data['pedido_id']) ? (int)$data['pedido_id'] : null;
        
        // Generar un token único para prevenir duplicados
        $token_procesamiento = md5(uniqid() . time());
        if (!isset($_SESSION['ultimo_token_procesamiento'])) {
            $_SESSION['ultimo_token_procesamiento'] = '';
        }
        
        // Buscar pedido activo para la mesa
        $pedidoActivo = $consultas->traerPedidosActivosPorMesa($pdo, $mesa_id);
        $pedido_id = null;
        $estado_actual = null;
        
        if ($pedido_id_modificar) {
            // Buscar el pedido específico por id
            foreach ($pedidoActivo as $p) {
                if ((int)$p['idpedidos'] === $pedido_id_modificar) {
                    $pedido_id = $pedido_id_modificar;
                    $estado_actual = (int)$p['estados_idestados'];
                    break;
                }
            }
            if (!$pedido_id) {
                throw new Exception('El pedido seleccionado no está activo o no existe');
            }
        } else if ($pedidoActivo && count($pedidoActivo) > 0) {
            $pedido_id = (int)$pedidoActivo[0]['idpedidos'];
            $estado_actual = (int)$pedidoActivo[0]['estados_idestados'];
        }
        
        if ($pedido_id) {
            // 1. Obtener todos los productos actuales del pedido
            $productos_actuales = $consultas->traerDetallePedido($pdo, $pedido_id);
            $ids_actuales = array_map(function($p) { return $p['id']; }, $productos_actuales);
            $ids_nuevos = array_map(function($p) { return $p['id']; }, $productos_sanitizados);

            if ($estado_actual === 4) {
                // Estado entregado: agregar productos como nuevos line items
                // No eliminar ni modificar productos existentes
                $productos_agregados = 0;
                
                // Verificar que realmente hay productos para agregar
                if (empty($productos_sanitizados)) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'No hay productos para agregar',
                        'pedido_id' => $pedido_id
                    ]);
                    exit;
                }
                
                // Debug: ver qué productos se están enviando
                error_log("Productos a agregar: " . json_encode($productos_sanitizados));
                error_log("Cantidad de productos a agregar: " . count($productos_sanitizados));
                
                foreach ($productos_sanitizados as $producto) {
                    // Siempre agregar como nuevo line item, permitiendo duplicados
                    $consultas->guardarDetallePedido($pdo, $producto, $pedido_id);
                    $productos_agregados++;
                    error_log("Producto agregado: " . $producto['nombre'] . " x" . $producto['cantidad']);
                }
                
                // Actualizar total del pedido
                $total_actual = $consultas->calcularTotalPedido($pdo, $pedido_id);
                $consultas->actualizarTotalPedido($pdo, $total_actual, $pedido_id);
                
                // Cambiar estado a confirmado si se agregaron productos nuevos
                if ($productos_agregados > 0) {
                    $stmt = $pdo->prepare("UPDATE pedidos SET estados_idestados = 3 WHERE idpedidos = ?");
                    $stmt->execute([$pedido_id]);
                    
                    // Los productos ya se marcan como nuevos en guardarDetallePedido
                    // cuando el pedido está en estado 4 (entregado)
                    
                    // Actualizar token de procesamiento
                    $_SESSION['ultimo_token_procesamiento'] = $token_procesamiento;
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Se agregaron ' . $productos_agregados . ' productos nuevos al pedido entregado',
                        'pedido_id' => $pedido_id
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'message' => 'No se agregaron productos nuevos (ya existían con los mismos comentarios)',
                        'pedido_id' => $pedido_id
                    ]);
                }
            } else {
                // Estado confirmado: permitir modificar y eliminar productos
                // Solo eliminar productos si no estamos en modo "adicionar" (carrito no vacío)
                if (!empty($productos_sanitizados)) {
                    // Eliminar productos que ya no están en el nuevo pedido
                    foreach ($ids_actuales as $id_existente) {
                        if (!in_array($id_existente, $ids_nuevos)) {
                            $stmt = $pdo->prepare("DELETE FROM detalle_pedidos WHERE pedidos_idpedidos = ? AND productos_idproductos = ?");
                            $stmt->execute([$pedido_id, $id_existente]);
                        }
                    }
                }
                // Para cada producto recibido:
                foreach ($productos_sanitizados as $producto) {
                    $detalleExistente = $consultas->traerDetallePedidoPorProducto($pdo, $pedido_id, $producto['id']);
                    if ($detalleExistente) {
                        $consultas->actualizarCantidadDetallePedido($pdo, $pedido_id, $producto['id'], $producto['cantidad']);
                    } else {
                        $consultas->guardarDetallePedido($pdo, $producto, $pedido_id);
                    }
                }
                // Actualizar total del pedido
                $total_actual = $consultas->calcularTotalPedido($pdo, $pedido_id);
                $consultas->actualizarTotalPedido($pdo, $total_actual, $pedido_id);
                echo json_encode([
                    'success' => true,
                    'message' => 'Pedido actualizado correctamente',
                    'pedido_id' => $pedido_id
                ]);
            }
        } else {
            // No hay pedido activo, crear uno nuevo
            $pdo->beginTransaction();
            try {
                // Obtener el id del usuario de la sesión
                if (!isset($_SESSION['usuario_id'])) {
                    throw new Exception('Sesión de usuario no encontrada. Por favor, inicie sesión nuevamente.');
                }
                $usuario_id = (int)$_SESSION['usuario_id'];
                
                // Al crear un nuevo pedido, usar el estado recibido si existe
                $nuevo_estado = isset($data['nuevo_estado']) ? (int)$data['nuevo_estado'] : 3;
                
                $pedido_id = $consultas->confirmarPedidoCliente($pdo, $mesa_id, $productos_sanitizados, $token_utilizado, $usuario_id);
                $pdo->commit();
                echo json_encode([
                    'success' => true,
                    'message' => 'Nuevo pedido creado correctamente',
                    'pedido_id' => $pedido_id
                ]);
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $msg = $e->getMessage();
                if (strpos($msg, 'Stock insuficiente') !== false) {
                    echo json_encode([
                        'success' => false,
                        'message' => $msg
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Error al procesar el pedido: ' . $msg
                    ]);
                }
            }
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al procesar el pedido: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
} 