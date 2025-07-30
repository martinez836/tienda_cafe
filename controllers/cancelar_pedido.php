<?php
require_once '../models/consultas.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$pedido_id = isset($input['pedido_id']) ? (int)$input['pedido_id'] : 0;

if (!$pedido_id) {
    echo json_encode(['success' => false, 'message' => 'ID de pedido inválido']);
    exit;
}

try {
    $consultas = new ConsultasMesero();
    $pdo = (new MySql());
    $pdo = (new ReflectionClass($pdo))->getProperty('pdo');
    $pdo->setAccessible(true);
    $pdo = $pdo->getValue((new MySql()));
    // Obtener estado actual
    $stmt = $pdo->prepare('SELECT estados_idestados FROM pedidos WHERE idpedidos = ?');
    $stmt->execute([$pedido_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado']);
        exit;
    }
    if ((int)$row['estados_idestados'] !== 3) {
        echo json_encode(['success' => false, 'message' => 'Solo se puede cancelar un pedido en estado confirmado.']);
        exit;
    }
    // Cambiar a estado cancelado (2)
    $stmt = $pdo->prepare('UPDATE pedidos SET estados_idestados = 2 WHERE idpedidos = ?');
    $stmt->execute([$pedido_id]);
    echo json_encode(['success' => true, 'message' => 'Pedido cancelado correctamente.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al cancelar el pedido: ' . $e->getMessage()]);
} 