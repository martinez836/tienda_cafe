<?php
require_once '../models/consultas.php';
require_once '../config/config.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['mesa_id'])) {
        throw new Exception('Falta el id de la mesa');
    }
    $mesaId = (int)$data['mesa_id'];
    $pdo = config::conectar();
    $consultas = new ConsultasMesero();
    // Buscar el token activo y vigente de la mesa usando PDO
    $tokens = $consultas->obtenerTokensActivosPorMesa($pdo, $mesaId);
    $tokenActivo = isset($tokens[0]) ? $tokens[0] : null;
    if (!$tokenActivo) {
        throw new Exception('No hay token activo para esta mesa');
    }
    $consultas->cancelarTokenPorId($pdo, $tokenActivo['idtoken_mesa']);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 