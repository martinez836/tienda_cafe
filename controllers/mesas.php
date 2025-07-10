<?php
require_once '../models/consultas.php';
header('Content-Type: application/json');
try {
    $consultas = new ConsultasMesero();
    $mesas = $consultas->traerMesas();
    echo json_encode([
        'success' => true,
        'mesas' => $mesas
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar las mesas: ' . $e->getMessage()
    ]);
} 