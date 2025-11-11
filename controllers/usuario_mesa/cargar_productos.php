<?php
require_once '../../models/mesero/consultas_usuario_mesa.php';
require_once '../../config/security.php';
header('Content-Type: application/json');

try {
    // Leer datos JSON del body
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        throw new Exception('Datos JSON inválidos');
    }
    // Validar campo requerido
    SecurityUtils::validateRequiredKeys($data, ['idcategorias']);
    // Sanitizar y validar entrada
    $categoria = SecurityUtils::sanitizeId($data['idcategorias'], 'ID de categoría');
    
    $consultas = new consultas_usuario_mesa();
    $productos = $consultas->traer_productos_por_categoria($categoria);
    
    if (!$productos || $productos->rowCount() === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'No hay productos disponibles para esta categoría.',
            'html' => '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No hay productos disponibles para esta categoría.</div>'
        ]);
        exit;
    }
    
    $html = '';
    foreach ($productos as $producto) {
        $id = (int)$producto['idproductos'];
        $nombre = SecurityUtils::escapeHtml($producto['nombre_producto']);
        $precio = (float)$producto['precio_producto'];
        $stock = isset($producto['stock_producto']) ? (int)$producto['stock_producto'] : null;
        
        $html .= '<div class="col-md-4 mb-4">
            <div class="card h-100" data-id="' . $id . '">
                <div class="card-body">
                    <h5 class="card-title">' . $nombre . '</h5>';
        
        if ($stock !== null) {
            $html .= '<p class="card-text mb-1"><span class="badge bg-secondary">Stock: ' . $stock . '</span></p>';
        }
        
        $html .= '<p class="card-text">$' . number_format($precio, 2) . '</p>';
        
        if ($stock === null || $stock > 0) {
            $html .= '<div class="input-group mb-3">
                
                <button class="btn btn-primary" data-precio="' . $precio . '">
                    Agregar
                </button>
            </div>';
        } else {
            $html .= '<div class="alert alert-warning py-1 px-2 mb-0">Sin stock</div>';
        }
        
        $html .= '</div></div></div>';
    }
    
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'html' => '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>' . SecurityUtils::escapeHtml($e->getMessage()) . '</div>'
    ]);
}