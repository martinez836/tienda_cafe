<?php
require_once '../../config/admin_controller_auth.php';
require_once '../../models/consultasPedidos.php';
require_once '../../plugins/fpdf/fpdf.php';

verificarAdminController();

class ReporteEmpleadosIngresos extends FPDF
{
    private $empleados;
    public function __construct($empleados)
    {
        parent::__construct();
        $this->empleados = $empleados;
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
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, utf8_decode('Tienda de Café'), 0, 1, 'C');
        $this->SetFont('Arial', 'B', 14);
        $this->SetFillColor(230, 240, 255);
        $this->SetTextColor(40, 40, 40);
        $this->Cell(0, 12, utf8_decode('Empleados que más ingresos generaron'), 0, 1, 'C', true);
        $this->Ln(2);
        $this->SetFont('Arial', '', 11);
        $this->Cell(0, 8, utf8_decode('Fecha de generación: ') . date('d/m/Y H:i:s'), 0, 1, 'C');
        $this->Ln(5);

        // Tabla
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(70, 130, 180);
        $this->SetTextColor(255,255,255);
        $this->Cell(70, 8, utf8_decode('Empleado'), 1, 0, 'C', true);
        $this->Cell(40, 8, utf8_decode('Cantidad Ventas'), 1, 0, 'C', true);
        $this->Cell(50, 8, utf8_decode('Total Generado'), 1, 1, 'C', true);
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(0,0,0);
        $totalGeneral = 0;
        $fill = false;
        foreach ($this->empleados as $emp) {
            $this->SetFillColor($fill ? 240 : 255, $fill ? 248 : 255, $fill ? 255 : 255);
            $this->Cell(70, 7, utf8_decode($emp['empleado']), 1, 0, 'L', true);
            $this->Cell(40, 7, $emp['cantidad_ventas'], 1, 0, 'C', true);
            $this->Cell(50, 7, '$' . number_format($emp['total_generado'], 0, ',', '.'), 1, 1, 'R', true);
            $totalGeneral += $emp['total_generado'];
            $fill = !$fill;
        }
        $this->Ln(8);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, utf8_decode('Total general de ingresos: $') . number_format($totalGeneral, 0, ',', '.'), 0, 1, 'L');
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
        $filename = 'reporte_empleados_' . date('Y-m-d_H-i-s') . '.pdf';
        $filepath = __DIR__ . '/../../facturas/reportes/' . $filename;
        $this->Output('F', $filepath);
        return ['filename' => $filename, 'filepath' => $filepath];
    }
}

// Obtener datos de empleados
$consultas = new ConsultasPedidos();
$empleados = $consultas->getEmpleadosPorIngresos();

$pdf = new ReporteEmpleadosIngresos($empleados);
$pdf->AliasNbPages();
$result = $pdf->generarReporte();

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Reporte generado exitosamente',
    'filename' => $result['filename'],
    'filepath' => $result['filepath']
]);
