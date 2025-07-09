<?php
require_once '../config/admin_controller_auth.php';
require_once '../models/consultas.php';

// Verificar que el usuario sea administrador
verificarAdminController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? null;

    if ($nombre) {
        $consultas = new consultas();
        $resultado = $consultas->agregar_mesa($nombre);
        if ($resultado === 'duplicado') {
            echo json_encode(['success' => false, 'error' => 'duplicado']);
        } else {
            echo json_encode(['success' => $resultado]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Nombre no proporcionado']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
} 