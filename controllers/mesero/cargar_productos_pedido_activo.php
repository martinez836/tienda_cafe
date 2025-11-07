<?php
require_once '../../models/mesero/consultas_mesero.php';

header('Content-Type: application/json');

try {

    //validaciones
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    if (!isset($_POST['mesa_id']) || empty($_POST['mesa_id'])) {
        throw new Exception('ID de mesa requerido');
    }
    //---------------------------------------------------------------------------
    $mesaId = intval($_POST['mesa_id']);
    $consultasMesero = new consultas_mesero();

    // Obtener productos del pedido activo
    $productosActivos = $consultasMesero->obtenerProductosActivosMesa($mesaId);

    if (!empty($productosActivos)) {
        error_log("📋 Primer producto: " . json_encode($productosActivos[0]));
    }

    if (empty($productosActivos)) {
        echo json_encode([
            'success' => true,
            'html' => '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No hay pedidos activos en esta mesa.</div>'
        ]);
        exit;
    }

    // Obtener el ID del pedido del primer producto (todos los productos pertenecen al mismo pedido)
    $idPedido = $productosActivos[0]['idpedidos'];
    
    // Generar HTML para mostrar los productos - UN SOLO ENCABEZADO y UNA SOLA CARD para todo el pedido
    $html = '<div class="productos-activos-mesa">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="fas fa-receipt me-2"></i>Pedido #' . htmlspecialchars($idPedido) . '
                            </h6>
                            <span class="badge bg-dark">En proceso</span>
                        </div>
                    </div>
                    <div class="card-body">';

    // Recorrer todos los productos del pedido
    foreach ($productosActivos as $index => $producto) {
        /* $fechaFormateada = date('d/m/Y H:i', strtotime($producto['fecha_hora_pedido'])); */
        
        // Agregar separador entre productos (excepto para el primero)
        if ($index > 0) {
            $html .= '<hr class="my-3">';
        }
        
        $html .= '
            <div class="producto-item">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Producto:</strong> ' . htmlspecialchars($producto['nombre']) . '</p>
                        <p class="mb-1"><strong>Cantidad:</strong> ' . htmlspecialchars($producto['cantidad']) . '</p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Precio unitario:</strong> $' . number_format($producto['precio'], 0, ',', '.') . '</p>
                        <p class="mb-1"><strong>Subtotal:</strong> $' . number_format($producto['subtotal'], 0, ',', '.') . '</p>
                    </div>
                </div>';

        if (!empty($producto['observaciones'])) {
            $html .= '<div class="mt-2">
                        <strong>Observaciones:</strong> 
                        <span class="text-muted">' . htmlspecialchars($producto['observaciones']) . '</span>
                      </div>';
        }
    }

    // Calcular total del pedido
    $totalPedido = array_sum(array_column($productosActivos, 'subtotal'));

    // Cerrar la card del pedido y agregar el total
    $html .= '
                    </div>
                    <div class="card-footer bg-light">
                        <div class="text-center">
                            <h5 class="text-success mb-0">
                                <i class="fas fa-dollar-sign me-2"></i>Total del Pedido: $' . number_format($totalPedido, 0, ',', '.') . '
                            </h5>
                        </div>
                    </div>
                </div>
            </div>';

    //retorna el html generado con los productos

    echo json_encode([
        'success' => true,
        'html' => $html,
        'total_productos' => count($productosActivos),
        'total_pedido' => $totalPedido
    ]);
} catch (Exception $e) {
    $errorMsg = "Error en cargar_productos_pedido_activo: " . $e->getMessage();
    $errorTrace = $e->getTraceAsString();

    error_log("❌ " . $errorMsg);
    error_log("🔍 Trace: " . $errorTrace);

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar productos: ' . $e->getMessage(),
        'html' => '<div class="alert alert-danger">Error al cargar los productos del pedido activo.</div>',
        'debug_info' => [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
