<?php
require_once '../../config/admin_controller_auth.php';
require_once '../../models/consultasProductos.php';
require_once '../../plugins/fpdf/fpdf.php';

// Verificar que el usuario sea administrador
verificarAdminController();

class ReporteInventario extends FPDF
{
    private $consultas;

    public function __construct()
    {
        parent::__construct();
        $this->consultas = new ConsultasProductos();
    }

    public function generarReporte()
    {
        try {
            // Obtener todos los productos
            $productos = $this->consultas->getAllProductos();
            $resumenStock = $this->consultas->getResumenStock();
            $productosBajoStock = $this->consultas->getProductosBajoStock();
            $productosSinStock = $this->consultas->getProductosSinStock();
            $estadisticasCategoria = $this->consultas->getEstadisticasPorCategoria();

            // Crear el PDF
            $this->AddPage();
            $this->SetFont('Arial', 'B', 16);
            
            // Título del reporte
            $this->Cell(0, 10, 'REPORTE DE INVENTARIO - TIENDA DE CAFE', 0, 1, 'C');
            $this->Ln(5);
            
            // Fecha del reporte
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 10, 'Fecha del reporte: ' . date('d/m/Y H:i:s'), 0, 1, 'L');
            $this->Ln(5);

            // Resumen general
            if (!empty($resumenStock)) {
                $this->SetFont('Arial', 'B', 12);
                $this->Cell(0, 10, 'RESUMEN GENERAL DEL INVENTARIO', 0, 1, 'L');
                $this->SetFont('Arial', '', 10);
                
                $resumen = $resumenStock[0];
                $this->Cell(0, 8, 'Total de productos con stock: ' . $resumen['total_productos'], 0, 1);
                $this->Cell(0, 8, 'Productos con stock disponible: ' . $resumen['con_stock'], 0, 1);
                $this->Cell(0, 8, 'Productos con bajo stock (MENOR A 10): ' . $resumen['bajo_stock'], 0, 1);
                $this->Cell(0, 8, 'Productos sin stock: ' . $resumen['sin_stock'], 0, 1);
                $this->Ln(10);
            }

            // Tabla de productos
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(0, 10, 'LISTADO COMPLETO DE PRODUCTOS', 0, 1, 'L');
            $this->Ln(5);

            // Encabezados de la tabla
            $this->SetFont('Arial', 'B', 9);
            $this->SetFillColor(200, 200, 200);
            $this->Cell(15, 8, 'ID', 1, 0, 'C', true);
            $this->Cell(50, 8, 'NOMBRE', 1, 0, 'C', true);
            $this->Cell(25, 8, 'PRECIO', 1, 0, 'C', true);
            $this->Cell(20, 8, 'STOCK', 1, 0, 'C', true);
            $this->Cell(35, 8, 'CATEGORIA', 1, 0, 'C', true);
            $this->Cell(25, 8, 'ESTADO', 1, 0, 'C', true);
            $this->Cell(20, 8, 'TIPO', 1, 1, 'C', true);

            // Datos de productos
            $this->SetFont('Arial', '', 8);
            $fill = false;
            
            foreach ($productos as $producto) {
                // Determinar el color de fondo según el stock
                if ($producto['stock_producto'] === null || $producto['stock_producto'] == 0) {
                    $this->SetFillColor(255, 200, 200); // Rojo claro para sin stock
                } elseif ($producto['stock_producto'] <= 10) {
                    $this->SetFillColor(255, 255, 200); // Amarillo claro para bajo stock
                } else {
                    $this->SetFillColor(255, 255, 255); // Blanco para stock normal
                }

                $this->Cell(15, 6, $producto['idproductos'], 1, 0, 'C', true);
                $this->Cell(50, 6, substr($producto['nombre_producto'], 0, 20), 1, 0, 'L', true);
                $this->Cell(25, 6, '$' . number_format($producto['precio_producto'], 0, ',', '.'), 1, 0, 'R', true);
                
                // Stock
                $stock = $producto['stock_producto'] === null || $producto['stock_producto'] == 0 ? 'Sin stock' : $producto['stock_producto'];
                $this->Cell(20, 6, $stock, 1, 0, 'C', true);
                
                $this->Cell(35, 6, $producto['nombre_categoria'] ?? 'Sin categoría', 1, 0, 'L', true);
                
                // Estado
                $estado = $producto['estados_idestados'] == 1 ? 'Activo' : 'Inactivo';
                $this->Cell(25, 6, $estado, 1, 0, 'C', true);
                
                // Tipo
                $tipo = $producto['tipo_producto_idtipo_producto'] == 1 ? 'Sin stock' : 'Con stock';
                $this->Cell(20, 6, $tipo, 1, 1, 'C', true);
            }

            $this->Ln(10);

            // Estadísticas por categoría
            if (!empty($estadisticasCategoria)) {
                $this->AddPage();
                $this->SetFont('Arial', 'B', 12);
                $this->Cell(0, 10, 'ESTADISTICAS POR CATEGORIA', 0, 1, 'L');
                $this->Ln(5);

                // Encabezados de la tabla de categorías
                $this->SetFont('Arial', 'B', 9);
                $this->SetFillColor(200, 200, 200);
                $this->Cell(50, 8, 'CATEGORÍA', 1, 0, 'C', true);
                $this->Cell(25, 8, 'TOTAL', 1, 0, 'C', true);
                $this->Cell(25, 8, 'CON STOCK', 1, 0, 'C', true);
                $this->Cell(25, 8, 'BAJO STOCK', 1, 0, 'C', true);
                $this->Cell(25, 8, 'SIN STOCK', 1, 0, 'C', true);
                $this->Cell(35, 8, 'STOCK TOTAL', 1, 1, 'C', true);

                // Datos de categorías
                $this->SetFont('Arial', '', 8);
                foreach ($estadisticasCategoria as $categoria) {
                    $this->Cell(50, 6, $categoria['nombre_categoria'] ?? 'Sin categoría', 1, 0, 'L');
                    $this->Cell(25, 6, $categoria['total_productos'], 1, 0, 'C');
                    $this->Cell(25, 6, $categoria['con_stock'], 1, 0, 'C');
                    $this->Cell(25, 6, $categoria['bajo_stock'], 1, 0, 'C');
                    $this->Cell(25, 6, $categoria['sin_stock'], 1, 0, 'C');
                    $this->Cell(35, 6, $categoria['stock_total'], 1, 1, 'C');
                }
                $this->Ln(10);
            }

            // Sección de alertas
            if (!empty($productosBajoStock) || !empty($productosSinStock)) {
                $this->AddPage();
                $this->SetFont('Arial', 'B', 12);
                $this->Cell(0, 10, 'ALERTAS DE STOCK', 0, 1, 'L');
                $this->Ln(5);

                // Productos sin stock
                if (!empty($productosSinStock)) {
                    $this->SetFont('Arial', 'B', 10);
                    $this->SetTextColor(255, 0, 0);
                    $this->Cell(0, 8, 'PRODUCTOS SIN STOCK:', 0, 1, 'L');
                    $this->SetTextColor(0, 0, 0);
                    $this->SetFont('Arial', '', 9);
                    
                    foreach ($productosSinStock as $producto) {
                        $this->Cell(0, 6, '• ' . $producto['nombre_producto'] . ' - Categoría: ' . ($producto['nombre_categoria'] ?? 'Sin categoría') . ' - Precio: $' . number_format($producto['precio_producto'], 0, ',', '.'), 0, 1);
                    }
                    $this->Ln(5);
                }

                // Productos con bajo stock
                if (!empty($productosBajoStock)) {
                    $this->SetFont('Arial', 'B', 10);
                    $this->SetTextColor(255, 165, 0);
                    $this->Cell(0, 8, 'PRODUCTOS CON BAJO STOCK (MENOR A 10):', 0, 1, 'L');
                    $this->SetTextColor(0, 0, 0);
                    $this->SetFont('Arial', '', 9);
                    
                    foreach ($productosBajoStock as $producto) {
                        $this->Cell(0, 6, '• ' . $producto['nombre_producto'] . ' - Stock: ' . $producto['stock_producto'] . ' - Categoría: ' . ($producto['nombre_categoria'] ?? 'Sin categoría') . ' - Precio: $' . number_format($producto['precio_producto'], 0, ',', '.'), 0, 1);
                    }
                }
            }

            // Pie de página
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 10, 'Página ' . $this->PageNo() . '/{nb}', 0, 0, 'C');

            // Generar el PDF
            $filename = 'reporte_inventario_' . date('Y-m-d_H-i-s') . '.pdf';
            $filepath = __DIR__ . '/../../facturas/reportes/' . $filename;
            $this->Output('F', $filepath);
            
            // Devolver respuesta JSON con la información del archivo
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Reporte generado exitosamente',
                'filename' => $filename,
                'filepath' => $filepath
            ]);

        } catch (Exception $e) {
            // Si hay error, devolver JSON con el error
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error al generar reporte: ' . $e->getMessage()]);
        }
    }
}

// Generar el reporte
$reporte = new ReporteInventario();
$reporte->generarReporte();
?> 