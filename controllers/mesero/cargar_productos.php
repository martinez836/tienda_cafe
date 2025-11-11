<?php
require_once '../../models/mesero/consultas_mesero.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Leer datos JSON del body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('Datos JSON inválidos');
    }

    if (!isset($data['idcategorias']) || empty($data['idcategorias'])) {
        throw new Exception('ID de categoría requerido');
    }

    $idcategorias = intval($data['idcategorias']);
    
    $consultasMesero = new consultas_mesero();
    $productos = $consultasMesero->traerProductosPorCategoria($idcategorias);

    $html = "";
    
    if (empty($productos)) {
        echo json_encode([
            'success' => false,
            'message' => 'No hay productos disponibles en esta categoría'
        ]);
        exit;
    }

    foreach ($productos as $producto) {
        $stockInfo = "";
        $stockClass = "";
        $disabled = "";
        
        // Verificar si el producto maneja stock
        if ($producto['tipo_producto_idtipo_producto'] == 2) { // Con stock
            if ($producto['stock_producto'] <= 0) {
                $stockInfo = '<span class="badge bg-danger">Agotado</span>';
                $stockClass = "card-agotado";
                $disabled = "disabled";
            } else {
                $stockInfo = '<span class="badge bg-success">Stock: ' . $producto['stock_producto'] . '</span>';
            }
        } else {
            $stockInfo = '<span class="badge bg-info">Sin límite</span>';
        }

        $precio = number_format($producto['precio_producto'], 0, ',', '.');

        $html .= '
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="card h-100 shadow-sm product-card ' . $stockClass . '">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title text-truncate">' . htmlspecialchars($producto['nombre_producto']) . '</h5>
                    <p class="card-text text-muted">$' . $precio . '</p>
                    <div class="mb-2">' . $stockInfo . '</div>
                    <div class="mt-auto">
                        <button 
                            class="btn btn-primary btn-sm btn-agregar-producto w-100" 
                            data-product-id="' . $producto['idproductos'] . '"
                            data-product-name="' . htmlspecialchars($producto['nombre_producto']) . '"
                            data-product-price="' . $producto['precio_producto'] . '"
                            data-product-stock="' . ($producto['stock_producto'] ?? 'ilimitado') . '"
                            data-product-type="' . $producto['tipo_producto_idtipo_producto'] . '"
                            ' . $disabled . '>
                            <i class="fas fa-plus me-1"></i>Agregar
                        </button>
                    </div>
                </div>
            </div>
        </div>';
    }

    echo json_encode([
        'success' => true,
        'html' => $html,
        'count' => count($productos)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
