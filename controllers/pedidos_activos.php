<?php
require_once '../models/consultas.php';
require_once '../config/config.php';

header('Content-Type: application/json');

try {
    $pdo = config::conectar();
    $consultas = new ConsultasMesero();
    
    // Obtener todas las mesas
    $mesas = $pdo->query("SELECT idmesas, nombre FROM mesas")->fetchAll(PDO::FETCH_ASSOC);
    
    $pedidos = [];
    foreach ($mesas as $mesa) {
        // Obtener pedidos activos de la mesa, ahora con JOIN a estados
        $stmt = $pdo->prepare("
            SELECT p.idpedidos, p.fecha_hora_pedido, p.total_pedido, p.token_utilizado, p.estados_idestados, e.estado as estado_nombre, p.usuarios_idusuarios
            FROM pedidos p
            INNER JOIN detalle_pedidos dp ON dp.pedidos_idpedidos = p.idpedidos
            LEFT JOIN tokens_mesa t ON t.token = p.token_utilizado
            JOIN estados e ON e.idestados = p.estados_idestados
            WHERE p.mesas_idmesas = ?
              AND p.estados_idestados IN (1,3,4)
              AND (
                (p.token_utilizado IS NOT NULL AND t.estado_token IN ('activo', 'usado') AND t.fecha_hora_expiracion > NOW())
                OR p.token_utilizado IS NULL
              )
            GROUP BY p.idpedidos
            ORDER BY p.fecha_hora_pedido DESC
        ");
        $stmt->execute([$mesa['idmesas']]);
        $pedidosMesa = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($pedidosMesa as $pedido) {
            // Obtener detalles del pedido
            $detalles = $consultas->traerDetallePedido($pdo, $pedido['idpedidos']);
            $pedidos[] = [
                'mesa_id' => $mesa['idmesas'],
                'mesa_nombre' => $mesa['nombre'],
                'pedido_id' => $pedido['idpedidos'],
                'productos' => $detalles,
                'estado_nombre' => $pedido['estado_nombre'],
                'estados_idestados' => $pedido['estados_idestados'],
                'usuario_id' => $pedido['usuarios_idusuarios']
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'pedidos' => $pedidos
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar los pedidos activos: ' . $e->getMessage()
    ]);
} 