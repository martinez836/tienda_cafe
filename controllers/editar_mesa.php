<?php
require_once '../config/admin_controller_auth.php';
require_once '../models/consultas.php';

// Verificar que el usuario sea administrador
verificarAdminController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nombre = $_POST['nombre'] ?? null;

    if ($id && $nombre) {
        $consultas = new ConsultasMesero();
        $resultado = $consultas->editar_nombre_mesa($nombre, $id);
        if ($resultado === 'duplicado') {
            echo json_encode(['success' => false, 'error' => 'duplicado']);
        } else {
            echo json_encode(['success' => $resultado]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}