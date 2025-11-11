<?php
require_once '../models/mesero/consultas_mesero.php';
header('Content-Type: application/json');
try {
    $consultas = new consultas_mesero();
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