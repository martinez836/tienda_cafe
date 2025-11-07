<?php
require_once '../../models/mesero/consultas_mesero.php';

header('Content-Type: application/json');

// Función para validar y sanitizar observaciones
function validarObservaciones($observaciones) {
    if (empty($observaciones)) {
        return ''; // Observaciones vacías son válidas
    }
    
    // Convertir a string y limitar longitud
    $obs = trim((string)$observaciones);
    if (strlen($obs) > 255) {
        throw new Exception('Las observaciones no pueden exceder 255 caracteres');
    }
    
    // Verificar caracteres permitidos (letras, números, espacios y puntuación básica)
    if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s.,;:()¿?¡!\-_]*$/', $obs)) {
        throw new Exception('Las observaciones contienen caracteres no permitidos');
    }
    
    // Sanitizar: eliminar caracteres de control y espacios múltiples
    $obs = preg_replace('/\s+/', ' ', $obs); // Múltiples espacios a uno solo
    $obs = preg_replace('/[\x00-\x1F\x7F]/', '', $obs); // Eliminar caracteres de control
    
    return $obs;
}

try {
    // Leer datos JSON del body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('Datos JSON inválidos');
    }

    // Determinar acción según el método HTTP
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // ========== CREAR NUEVO PEDIDO ==========
        
        // Validar estructura de datos
        if (!isset($data['pedido']) || !isset($data['detalles'])) {
            throw new Exception('Estructura de datos incompleta');
        }

        $pedidoData = $data['pedido'];
        $detallesData = $data['detalles'];

        // Validar datos del pedido
        if (empty($pedidoData['mesa']) || empty($pedidoData['usuario']) || empty($detallesData)) {
            throw new Exception('Datos del pedido incompletos');
        }

        $consultasMesero = new consultas_mesero();
        
        // Insertar pedido principal
        $idPedido = $consultasMesero->insertarPedido(
            $pedidoData['mesa'],
            $pedidoData['usuario'],
            $pedidoData['fecha'],
            $pedidoData['estado'],
            $pedidoData['total'],
            'mesero', // tipo_pedido por defecto
            null      // token_utilizado (null para pedidos de mesero)
        );

        if (!$idPedido) {
            throw new Exception('Error al insertar el pedido');
        }

        // Insertar detalles del pedido
        $detallesInsertados = 0;
        foreach ($detallesData as $detalle) {
            // Validar y sanitizar observaciones antes de insertar
            $observacionesValidadas = validarObservaciones($detalle['observaciones'] ?? '');
            
            $resultado = $consultasMesero->insertarDetallePedido(
                $idPedido,
                $detalle['producto_id'],
                $detalle['cantidad'],
                $detalle['precio'],
                $detalle['subtotal'],
                $observacionesValidadas
            );
            
            if ($resultado) {
                $detallesInsertados++;
            }
        }

        if ($detallesInsertados === 0) {
            throw new Exception('No se pudieron insertar los detalles del pedido');
        }

        // Actualizar estado de la mesa si es necesario
        $consultasMesero->actualizarEstadoMesa($pedidoData['mesa'], 'ocupada_pedido');

        echo json_encode([
            'success' => true,
            'message' => 'Pedido creado correctamente',
            'pedido_id' => $idPedido,
            'detalles_insertados' => $detallesInsertados
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // ========== ACTUALIZAR PEDIDO EXISTENTE ==========
        
        // Validar estructura de datos para actualización
        if (!isset($data['pedido_id']) || !isset($data['detalles'])) {
            throw new Exception('Datos de actualización incompletos. Se requiere pedido_id y detalles.');
        }

        $pedidoId = intval($data['pedido_id']);
        $detallesData = $data['detalles'];
        $totalNuevosProductos = floatval($data['total_nuevos_productos'] ?? 0);

        // Validar que el pedido existe y está en estado modificable
        if (empty($pedidoId) || empty($detallesData)) {
            throw new Exception('ID de pedido o productos nuevos faltantes');
        }

        $consultasMesero = new consultas_mesero();
        
        // Verificar que el pedido existe y está activo
        $pedidoExistente = $consultasMesero->obtenerPedidoCompleto($pedidoId);
        if (!$pedidoExistente) {
            throw new Exception('El pedido especificado no existe');
        }

        // Obtener el total actual del pedido
        $totalActualResult = $consultasMesero->traerTotalPedidoActivo($pedidoId);
        $totalActual = !empty($totalActualResult) ? floatval($totalActualResult[0]['total']) : 0;

        // Insertar nuevos productos al pedido existente
        $detallesInsertados = 0;
        foreach ($detallesData as $detalle) {
            // Validar y sanitizar observaciones
            $observacionesValidadas = validarObservaciones($detalle['observaciones'] ?? '');
            
            $resultado = $consultasMesero->insertarDetallePedido(
                $pedidoId,
                $detalle['producto_id'],
                $detalle['cantidad'],
                $detalle['precio'],
                $detalle['subtotal'],
                $observacionesValidadas
            );
            
            if ($resultado) {
                $detallesInsertados++;
            }
        }

        if ($detallesInsertados === 0) {
            throw new Exception('No se pudieron agregar los nuevos productos al pedido');
        }

        // Actualizar el total del pedido sumando los nuevos productos
        $nuevoTotal = $totalActual + $totalNuevosProductos;
        $resultadoActualizacion = $consultasMesero->actualizarTotalPedido($pedidoId, $nuevoTotal);

        if (!$resultadoActualizacion) {
            throw new Exception('Error al actualizar el total del pedido');
        }

        echo json_encode([
            'success' => true,
            'message' => 'Pedido actualizado correctamente',
            'pedido_id' => $pedidoId,
            'detalles_insertados' => $detallesInsertados,
            'total_anterior' => $totalActual,
            'total_nuevo' => $nuevoTotal,
            'total_agregado' => $totalNuevosProductos
        ]);

    } else {
        throw new Exception('Método HTTP no permitido. Use POST para crear o PUT para actualizar.');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
