<?php
require_once '../../config/admin_controller_auth.php';
require_once '../../models/consultasPedidos.php';
require_once '../../plugins/fpdf/fpdf.php';

verificarAdminController();

class ReporteBalanceVentas extends FPDF
{
    private $pedidos;
    private $fechaInicio;
    private $fechaFin;

    public function __construct($pedidos, $fechaInicio, $fechaFin)
    {
        parent::__construct();
        $this->pedidos = $pedidos;
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
    }

    public function generarReporte()
    {
        $this->AddPage();
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'Tienda de Café', 0, 1, 'C');
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'Balance General de Ventas', 0, 1, 'C');
        $this->Ln(2);
        $this->SetFont('Arial', '', 11);
        $rango = 'del ' . date('d/m/Y', strtotime($this->fechaInicio)) . ' al ' . date('d/m/Y', strtotime($this->fechaFin));
        $this->Cell(0, 8, 'Rango de fechas: ' . $rango, 0, 1, 'C');
        $this->Cell(0, 8, 'Fecha de generación: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
        $this->Ln(5);

        // Tabla de ventas
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(200, 200, 200);
        $this->Cell(20, 8, 'ID', 1, 0, 'C', true);
        $this->Cell(40, 8, 'Fecha', 1, 0, 'C', true);
        $this->Cell(70, 8, 'Cliente', 1, 0, 'C', true);
        $this->Cell(40, 8, 'Total', 1, 1, 'C', true);
        $this->SetFont('Arial', '', 10);
        $totalGeneral = 0;
        $totalPedidos = 0;
        foreach ($this->pedidos as $pedido) {
            $this->Cell(20, 7, $pedido['idpedidos'], 1, 0, 'C');
            $fecha = date('d/m/Y', strtotime($pedido['fecha_hora_pedido']));
            $this->Cell(40, 7, $fecha, 1, 0, 'C');
            $cliente = $pedido['nombre_usuario'] ?? 'N/A';
            $this->Cell(70, 7, $cliente, 1, 0, 'L');
            $total = isset($pedido['total_pedido']) ? $pedido['total_pedido'] : 0;
            $this->Cell(40, 7, '$' . number_format($total, 0, ',', '.'), 1, 1, 'R');
            $totalGeneral += $total;
            $totalPedidos++;
        }
        if ($totalPedidos === 0) {
            $this->Cell(170, 8, 'No hay ventas en el rango seleccionado.', 1, 1, 'C');
        }
        $this->Ln(8);
        // Resumen final
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, 'Resumen Final', 0, 1, 'L');
        $this->SetFont('Arial', '', 11);
        $this->Cell(0, 8, 'Total de pedidos: ' . $totalPedidos, 0, 1, 'L');
        $this->Cell(0, 8, 'Total general de ventas: $' . number_format($totalGeneral, 0, ',', '.'), 0, 1, 'L');
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Página ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
        $filename = 'balance_ventas_' . date('Y-m-d_H-i-s') . '.pdf';
        $filepath = __DIR__ . '/../../facturas/reportes/' . $filename;
        $this->Output('F', $filepath);
        return ['filename' => $filename, 'filepath' => $filepath];
    }
}

// Recibir fechas por GET
$fechaInicio = $_GET['fecha_inicio'] ?? null;
$fechaFin = $_GET['fecha_fin'] ?? null;
if (!$fechaInicio || !$fechaFin) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Debe proporcionar fecha_inicio y fecha_fin']);
    exit;
}

// Obtener pedidos en el rango de fechas
$consultas = new ConsultasPedidos();
$pedidos = $consultas->getPedidosPorRango($fechaInicio, $fechaFin);

$pdf = new ReporteBalanceVentas($pedidos, $fechaInicio, $fechaFin);
$pdf->AliasNbPages();
$result = $pdf->generarReporte();

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Reporte generado exitosamente',
    'filename' => $result['filename'],
    'filepath' => $result['filepath']
]);
