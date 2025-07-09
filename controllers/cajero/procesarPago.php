<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../models/consultasCajero.php';
require_once __DIR__ . '../../../plugins/fpdf/fpdf.php';

class ProcesarPagoCajero
{
    
    public function procesarPago()
    {
        try {
             
            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['numero'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Faltan datos para cambiar el estado del pedido']);
                exit;
            }

            $idpedido = (int)str_replace('P', '', $input['numero']);
            $consultas = new consultasCajero();

            
            $resultado = $consultas->cambiarEstadoPedido($idpedido, 5);

            if ($resultado) {
                $detalles = $consultas->obtenerDetallesPedido($idpedido);

                if (!$detalles || count($detalles) === 0) {
                    throw new Exception("No se encontraron detalles para el pedido");
                }

                // Reorganizar datos
                $pedido = [
                    'numero' => $idpedido,
                    'cliente' => $detalles[0]['nombre_mesa'], // puedes cambiar por cliente si lo tienes
                    'mesero' => $detalles[0]['nombre_usuario'],
                    'hora' => $detalles[0]['fecha_hora_pedido'],
                    'productos' => [],
                    'total' => 0
                ];

                foreach ($detalles as $row) {
                    $pedido['productos'][] = [
                        'nombre' => $row['nombre_producto'],
                        'precio' => $row['precio_unitario'],
                        'cantidad' => $row['cantidad_producto']
                    ];
                    $pedido['total'] += $row['subtotal'];
                }

                // Generar PDF con la estructura correcta
                $this->generarPdf($pedido);
                echo json_encode(['success' => true, 'message' => 'Estado del pedido actualizado correctamente']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado del pedido']);
            }
        } catch (\Throwable $th) {
            //throw $th;
             http_response_code(500);
            echo json_encode(['error' => 'Error al procesar pedidos: ' . $th->getMessage()]);
        }
    }

    private function generarPdf($pedido)
    {
        $pdf = new FPDF();
        $pdf->AddPage();

        // Título
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'Factura de Pedido', 0, 1, 'C');

        // Información del cliente
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, 'Mesa: ' . $pedido['cliente'], 0, 1);
        $pdf->Cell(0, 10, 'Mesero: ' . $pedido['mesero'], 0, 1);
        $pdf->Cell(0, 10, 'Fecha y hora: ' . $pedido['hora'], 0, 1);

        // Línea
        $pdf->Ln(5);
        $pdf->Cell(0, 0, '', 'T'); // línea horizontal

        // Detalles de productos
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(100, 10, 'Producto', 0);
        $pdf->Cell(40, 10, 'Precio', 0);
        $pdf->Cell(40, 10, 'Cantidad', 0);
        $pdf->Ln();

        $pdf->SetFont('Arial', '', 12);
        foreach ($pedido['productos'] as $producto) {
            $pdf->Cell(100, 10, $producto['nombre'], 0);
            $pdf->Cell(40, 10, '$' . number_format($producto['precio']), 0);
            $pdf->Cell(40, 10, $producto['cantidad'], 0);
            $pdf->Ln();
        }

        // Total
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'Total: $' . number_format($pedido['total']), 0, 1, 'R');

        // Guardar PDF en carpeta o mostrarlo directamente
        $filename = __DIR__ . "/../../facturas/factura_pedido_{$pedido['numero']}.pdf";
        $pdf->Output('F', $filename);
    }
}

$pagos = new ProcesarPagoCajero();
$pagos->procesarPago();
