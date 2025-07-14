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

    public function generarReporte()
    {
        $this->AddPage();
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'Tienda de Café', 0, 1, 'C');
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'Empleados que más ingresos generaron', 0, 1, 'C');
        $this->Ln(2);
        $this->SetFont('Arial', '', 11);
        $this->Cell(0, 8, 'Fecha de generación: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
        $this->Ln(5);

        // Tabla
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(200, 200, 200);
        $this->Cell(70, 8, 'Empleado', 1, 0, 'C', true);
        $this->Cell(40, 8, 'Cantidad Ventas', 1, 0, 'C', true);
        $this->Cell(50, 8, 'Total Generado', 1, 1, 'C', true);
        $this->SetFont('Arial', '', 10);

        $totalGeneral = 0;
        foreach ($this->empleados as $emp) {
            $this->Cell(70, 7, $emp['empleado'], 1, 0, 'L');
            $this->Cell(40, 7, $emp['cantidad_ventas'], 1, 0, 'C');
            $this->Cell(50, 7, '$' . number_format($emp['total_generado'], 0, ',', '.'), 1, 1, 'R');
            $totalGeneral += $emp['total_generado'];
        }
        $this->Ln(8);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, 'Total general de ingresos: $' . number_format($totalGeneral, 0, ',', '.'), 0, 1, 'L');
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Página ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
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
