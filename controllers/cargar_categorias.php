<?php
require_once '../models/consultas.php';
header('Content-Type: application/json');
try {
    $consultas = new ConsultasMesero();
    $categorias = $consultas->traerCategorias();
    $result = [];
    if ($categorias) {
        foreach ($categorias as $cat) {
            $result[] = [
                'idcategorias' => $cat['idcategorias'],
                'nombre_categoria' => $cat['nombre_categoria']
            ];
        }
    }
    echo json_encode(['success' => true, 'categorias' => $result]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 