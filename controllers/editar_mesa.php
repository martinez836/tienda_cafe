<?php
require_once '../models/consultas.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nombre = $_POST['nombre'] ?? null;

    if ($id && $nombre) {
        $consultas = new consultas();
        $resultado = $consultas->editar_nombre_mesa($id, $nombre);
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