<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../models/consultasCajero.php';
require_once __DIR__ . '../../../plugins/fpdf/fpdf.php';

class FacturaPedidoPDF extends FPDF {
    public function Header() {
        // Espacio para el logo (texto)
        $this->SetY(10);
        $this->SetX(10);
        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor(0, 0, 0);
        $this->SetFillColor(255, 255, 255); // Blanco
        $this->Cell(35, 20, 'LOGO', 1, 0, 'C', true);
        // Título centrado
        $this->SetY(10);
        $this->SetX(50);
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(0,0,0);
        $this->SetFillColor(255,255,255);
        $this->Cell(140, 20, utf8_decode('Factura de Pedido'), 1, 0, 'C', true);
        $this->Ln(25);
    }
}

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
        $pdf = new FacturaPedidoPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.5);
        // Recuadro de datos principales (más compacto)
        $pdf->SetY(40);
        $pdf->SetX(10);
        $pdf->Rect(10, 40, 190, 20, 'D');
        $pdf->SetY(45);
        $pdf->SetX(15);
        $pdf->SetFont('Arial', '', 11);
        // Mesa a la izquierda, Mesero a la derecha
        $pdf->Cell(95, 8, utf8_decode('Mesa: ') . $pedido['cliente'], 0, 0, 'L');
        $pdf->Cell(85, 8, utf8_decode('Mesero: ') . $pedido['mesero'], 0, 1, 'R');
        $pdf->SetX(15);
        $pdf->Cell(180, 8, utf8_decode('Fecha y hora: ') . $pedido['hora'], 0, 1, 'L');
        // Menos espacio antes de la tabla
        $pdf->Ln(2);
        // Encabezado de tabla de productos (más compacto)
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(0,0,0);
        $pdf->Cell(100, 8, utf8_decode('Producto'), 1, 0, 'C');
        $pdf->Cell(40, 8, utf8_decode('Precio'), 1, 0, 'C');
        $pdf->Cell(40, 8, utf8_decode('Cantidad'), 1, 1, 'C');
        $pdf->SetFont('Arial', '', 11);
        foreach ($pedido['productos'] as $producto) {
            $pdf->Cell(100, 8, utf8_decode($producto['nombre']), 1, 0, 'L');
            $pdf->Cell(40, 8, '$' . number_format($producto['precio']), 1, 0, 'C');
            $pdf->Cell(40, 8, $producto['cantidad'], 1, 1, 'C');
        }
        // Total justo debajo de la tabla, alineado a la derecha
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(140, 8, utf8_decode('Total:'), 0, 0, 'R');
        $pdf->Cell(40, 8, '$' . number_format($pedido['total']), 0, 1, 'C');
        // Guardar PDF en carpeta o mostrarlo directamente
        $filename = __DIR__ . "/../../facturas/factura_pedido_{$pedido['numero']}.pdf";
        $pdf->Output('F', $filename);
    }
}

$pagos = new ProcesarPagoCajero();
$pagos->procesarPago();
