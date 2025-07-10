<?php
require_once '../../config/admin_controller_auth.php';

// Verificar que el usuario sea administrador
verificarAdminController();

$filename = $_GET['filename'] ?? '';

if (empty($filename)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Nombre de archivo no proporcionado']);
    exit;
}

$filepath = __DIR__ . '/../../facturas/reportes/' . $filename;

if (!file_exists($filepath)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Archivo no encontrado']);
    exit;
}

// Configurar headers para descarga
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Leer y enviar el archivo
readfile($filepath);
exit;
