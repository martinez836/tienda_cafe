<?php
require_once '../../models/mesero/consultas_mesero.php';
require_once '../../config/config.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['idtoken_mesa'])) {
        throw new Exception('Falta el id del token');
    }
    $idtoken = (int)$data['idtoken_mesa'];
    $consultas = new consultas_mesero();
    $filas = $consultas->cancelarTokenPorId($idtoken);
    if ($filas < 1) {
        echo json_encode([
            'success' => false,
            'message' => 'No se pudo cancelar el token (no se afectó ningún registro)',
            'debug_idtoken' => $idtoken,
            'debug_sql' => "UPDATE tokens_mesa SET estado_token = 'cancelado' WHERE idtoken_mesa = $idtoken AND estado_token = 'activo'"
        ]);
        exit;
    }
    echo json_encode(['success' => true, 'filas_afectadas' => $filas, 'debug_idtoken' => $idtoken]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 