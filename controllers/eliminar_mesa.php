<?php
require_once '../config/admin_controller_auth.php';
require_once '../models/consultas.php';

// Verificar que el usuario sea administrador
verificarAdminController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;

    if ($id) {
        $consultas = new consultas();
        $resultado = $consultas->inactivar_mesa($id);
        echo json_encode(['success' => $resultado]);
    } else {
        echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
} 