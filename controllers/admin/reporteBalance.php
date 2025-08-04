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

    public function Header()
    {
        // Espacio para el logo (texto)
        $this->SetY(10);
        $this->SetX(10);
        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor(40, 40, 40);
        $this->SetFillColor(220, 220, 255); // Azul muy suave
        $this->Cell(35, 20, 'LOGO', 1, 0, 'C', true);
        // Título centrado
        $this->SetY(10);
        $this->SetX(50);
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(255,255,255);
        $this->SetFillColor(70, 130, 180); // Azul principal
        $this->Cell(140, 20, utf8_decode('Tienda de Café'), 1, 0, 'C', true);
        $this->Ln(25);
        $this->SetTextColor(0,0,0);
    }

    public function generarReporte()
    {
        $this->AddPage();
        $this->SetFont('Arial', 'B', 14);
        $this->SetFillColor(230, 240, 255);
        $this->SetTextColor(40, 40, 40);
        $this->Cell(0, 12, utf8_decode('Balance General de Ventas'), 0, 1, 'C', true);
        $this->Ln(2);
        $this->SetFont('Arial', '', 11);
        $rango = 'del ' . date('d/m/Y', strtotime($this->fechaInicio)) . ' al ' . date('d/m/Y', strtotime($this->fechaFin));
        $this->Cell(0, 8, utf8_decode('Rango de fechas: ') . $rango, 0, 1, 'C');
        $this->Cell(0, 8, utf8_decode('Fecha de generación: ') . date('d/m/Y H:i:s'), 0, 1, 'C');
        $this->Ln(5);

        // Tabla de ventas
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(70, 130, 180);
        $this->SetTextColor(255,255,255);
        $this->Cell(20, 8, utf8_decode('ID'), 1, 0, 'C', true);
        $this->Cell(40, 8, utf8_decode('Fecha'), 1, 0, 'C', true);
        $this->Cell(70, 8, utf8_decode('Cliente'), 1, 0, 'C', true);
        $this->Cell(40, 8, utf8_decode('Total'), 1, 1, 'C', true);
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(0,0,0);
        $totalGeneral = 0;
        $totalPedidos = 0;
        $fill = false;
        foreach ($this->pedidos as $pedido) {
            $this->SetFillColor($fill ? 240 : 255, $fill ? 248 : 255, $fill ? 255 : 255);
            $this->Cell(20, 7, $pedido['idpedidos'], 1, 0, 'C', true);
            $fecha = date('d/m/Y', strtotime($pedido['fecha_hora_pedido']));
            $this->Cell(40, 7, $fecha, 1, 0, 'C', true);
            $cliente = $pedido['nombre_usuario'] ?? 'N/A';
            $this->Cell(70, 7, $cliente, 1, 0, 'L', true);
            $total = isset($pedido['total_pedido']) ? $pedido['total_pedido'] : 0;
            $this->Cell(40, 7, '$' . number_format($total, 0, ',', '.'), 1, 1, 'R', true);
            $totalGeneral += $total;
            $totalPedidos++;
            $fill = !$fill;
        }
        if ($totalPedidos === 0) {
            $this->Cell(170, 8, utf8_decode('No hay ventas en el rango seleccionado.'), 1, 1, 'C');
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
