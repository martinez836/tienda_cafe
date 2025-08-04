<?php
require_once '../../config/admin_controller_auth.php';
require_once '../../config/timezone.php';
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

    // Función para el pie de página personalizado
    function Footer()
    {
        // Posición a 1.5 cm del final
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Color del texto en gris
        $this->SetTextColor(128);
        // Número de página
        $this->Cell(0, 10, utf8_decode('Pagina ') . $this->PageNo() . utf8_decode(' de {nb}'), 0, 0, 'C');
    }

    // Función para el encabezado personalizado
    function Header()
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
        $this->Cell(140, 20, utf8_decode('TIENDA DE CAFE'), 1, 0, 'C', true);
        $this->Ln(25);
        // Restaurar color de texto
        $this->SetTextColor(0,0,0);
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

            // Crear el PDF con paginación
            $this->AliasNbPages();
            $this->AddPage();
            $this->SetFont('Arial', 'B', 16);
            
            // Título del reporte
            $this->SetFont('Arial', 'B', 14);
            $this->SetFillColor(230, 240, 255);
            $this->SetTextColor(40, 40, 40);
            $this->Cell(0, 12, utf8_decode('REPORTE DE INVENTARIO - TIENDA DE CAFE'), 0, 1, 'C', true);
            $this->Ln(5);
            
            // Fecha del reporte
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 10, utf8_decode('Fecha del reporte: ') . utf8_decode(getFechaHoraLocal()) . utf8_decode(' (Hora local Colombia)'), 0, 1, 'L');
            $this->Ln(5);

            // Resumen general
            if (!empty($resumenStock)) {
                $this->SetFont('Arial', 'B', 12);
                $this->Cell(0, 10, utf8_decode('RESUMEN GENERAL DEL INVENTARIO'), 0, 1, 'L');
                $this->SetFont('Arial', '', 10);
                
                $resumen = $resumenStock[0];
                $this->Cell(0, 8, utf8_decode('Total de productos con stock: ') . $resumen['total_productos'], 0, 1);
                $this->Cell(0, 8, utf8_decode('Productos con stock disponible: ') . $resumen['con_stock'], 0, 1);
                $this->Cell(0, 8, utf8_decode('Productos con bajo stock (MENOR A 10): ') . $resumen['bajo_stock'], 0, 1);
                $this->Cell(0, 8, utf8_decode('Productos sin stock: ') . $resumen['sin_stock'], 0, 1);
                $this->Ln(10);
            }

            // Tabla de productos
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(0, 10, utf8_decode('LISTADO COMPLETO DE PRODUCTOS'), 0, 1, 'L');
            $this->Ln(5);

            // Encabezados de la tabla
            $this->SetFont('Arial', 'B', 9);
            $this->SetFillColor(70, 130, 180); // Azul principal
            $this->SetTextColor(255,255,255);
            $this->Cell(15, 8, utf8_decode('ID'), 1, 0, 'C', true);
            $this->Cell(50, 8, utf8_decode('NOMBRE'), 1, 0, 'C', true);
            $this->Cell(25, 8, utf8_decode('PRECIO'), 1, 0, 'C', true);
            $this->Cell(20, 8, utf8_decode('STOCK'), 1, 0, 'C', true);
            $this->Cell(35, 8, utf8_decode('CATEGORIA'), 1, 0, 'C', true);
            $this->Cell(25, 8, utf8_decode('ESTADO'), 1, 0, 'C', true);
            $this->Cell(20, 8, utf8_decode('TIPO'), 1, 1, 'C', true);
            $this->SetTextColor(0,0,0);

            // Datos de productos
            $this->SetFont('Arial', '', 8);
            $fill = false;
            
            foreach ($productos as $producto) {
                // Verificar si necesitamos una nueva página
                if ($this->GetY() > 250) {
                    $this->AddPage();
                    // Repetir encabezados de tabla en nueva página
                    $this->SetFont('Arial', 'B', 9);
                    $this->SetFillColor(70, 130, 180);
                    $this->SetTextColor(255,255,255);
                    $this->Cell(15, 8, utf8_decode('ID'), 1, 0, 'C', true);
                    $this->Cell(50, 8, utf8_decode('NOMBRE'), 1, 0, 'C', true);
                    $this->Cell(25, 8, utf8_decode('PRECIO'), 1, 0, 'C', true);
                    $this->Cell(20, 8, utf8_decode('STOCK'), 1, 0, 'C', true);
                    $this->Cell(35, 8, utf8_decode('CATEGORIA'), 1, 0, 'C', true);
                    $this->Cell(25, 8, utf8_decode('ESTADO'), 1, 0, 'C', true);
                    $this->Cell(20, 8, utf8_decode('TIPO'), 1, 1, 'C', true);
                    $this->SetFont('Arial', '', 8);
                    $this->SetTextColor(0,0,0);
                }
                // Alternar color de fondo de filas
                if ($producto['stock_producto'] === null || $producto['stock_producto'] == 0) {
                    $this->SetFillColor(255, 200, 200); // Rojo claro para sin stock
                } elseif ($producto['stock_producto'] <= 10) {
                    $this->SetFillColor(255, 255, 200); // Amarillo claro para bajo stock
                } else {
                    $this->SetFillColor($fill ? 240 : 255, $fill ? 248 : 255, $fill ? 255 : 255); // Alternancia azul muy claro
                }
                $this->Cell(15, 6, $producto['idproductos'], 1, 0, 'C', true);
                $this->Cell(50, 6, utf8_decode(substr($producto['nombre_producto'], 0, 20)), 1, 0, 'L', true);
                $this->Cell(25, 6, '$' . number_format($producto['precio_producto'], 0, ',', '.'), 1, 0, 'R', true);
                $stock = $producto['stock_producto'] === null || $producto['stock_producto'] == 0 ? utf8_decode('Sin stock') : $producto['stock_producto'];
                $this->Cell(20, 6, $stock, 1, 0, 'C', true);
                $this->Cell(35, 6, utf8_decode($producto['nombre_categoria'] ?? 'Sin categoria'), 1, 0, 'L', true);
                $estado = $producto['estados_idestados'] == 1 ? utf8_decode('Activo') : utf8_decode('Inactivo');
                $this->Cell(25, 6, $estado, 1, 0, 'C', true);
                $tipo = $producto['tipo_producto_idtipo_producto'] == 1 ? utf8_decode('Sin stock') : utf8_decode('Con stock');
                $this->Cell(20, 6, $tipo, 1, 1, 'C', true);
                $fill = !$fill;
            }

            $this->Ln(10);

            // Estadísticas por categoría
            if (!empty($estadisticasCategoria)) {
                $this->AddPage();
                $this->SetFont('Arial', 'B', 12);
                $this->Cell(0, 10, utf8_decode('ESTADISTICAS POR CATEGORIA'), 0, 1, 'L');
                $this->Ln(5);

                // Encabezados de la tabla de categorías
                $this->SetFont('Arial', 'B', 9);
                $this->SetFillColor(70, 130, 180);
                $this->SetTextColor(255,255,255);
                $this->Cell(50, 8, utf8_decode('CATEGORIA'), 1, 0, 'C', true);
                $this->Cell(25, 8, utf8_decode('TOTAL'), 1, 0, 'C', true);
                $this->Cell(25, 8, utf8_decode('CON STOCK'), 1, 0, 'C', true);
                $this->Cell(25, 8, utf8_decode('BAJO STOCK'), 1, 0, 'C', true);
                $this->Cell(25, 8, utf8_decode('SIN STOCK'), 1, 0, 'C', true);
                $this->Cell(35, 8, utf8_decode('STOCK TOTAL'), 1, 1, 'C', true);
                $this->SetTextColor(0,0,0);

                // Datos de categorías
                $this->SetFont('Arial', '', 8);
                foreach ($estadisticasCategoria as $categoria) {
                    // Verificar si necesitamos una nueva página
                    if ($this->GetY() > 250) {
                        $this->AddPage();
                        // Repetir encabezados de tabla en nueva página
                        $this->SetFont('Arial', 'B', 9);
                        $this->SetFillColor(70, 130, 180);
                        $this->SetTextColor(255,255,255);
                        $this->Cell(50, 8, utf8_decode('CATEGORIA'), 1, 0, 'C', true);
                        $this->Cell(25, 8, utf8_decode('TOTAL'), 1, 0, 'C', true);
                        $this->Cell(25, 8, utf8_decode('CON STOCK'), 1, 0, 'C', true);
                        $this->Cell(25, 8, utf8_decode('BAJO STOCK'), 1, 0, 'C', true);
                        $this->Cell(25, 8, utf8_decode('SIN STOCK'), 1, 0, 'C', true);
                        $this->Cell(35, 8, utf8_decode('STOCK TOTAL'), 1, 1, 'C', true);
                        $this->SetFont('Arial', '', 8);
                        $this->SetTextColor(0,0,0);
                    }
                    
                    $this->Cell(50, 6, utf8_decode($categoria['nombre_categoria'] ?? 'Sin categoria'), 1, 0, 'L');
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
                $this->Cell(0, 10, utf8_decode('ALERTAS DE STOCK'), 0, 1, 'L');
                $this->Ln(5);

                // Productos sin stock
                if (!empty($productosSinStock)) {
                    $this->SetFont('Arial', 'B', 10);
                    $this->SetTextColor(255, 0, 0);
                    $this->Cell(0, 8, utf8_decode('PRODUCTOS SIN STOCK:'), 0, 1, 'L');
                    $this->SetTextColor(0, 0, 0);
                    $this->SetFont('Arial', '', 9);
                    
                    foreach ($productosSinStock as $producto) {
                        // Verificar si necesitamos una nueva página
                        if ($this->GetY() > 250) {
                            $this->AddPage();
                            $this->SetFont('Arial', 'B', 12);
                            $this->Cell(0, 10, utf8_decode('ALERTAS DE STOCK (CONTINUACION)'), 0, 1, 'L');
                            $this->Ln(5);
                            $this->SetFont('Arial', 'B', 10);
                            $this->SetTextColor(255, 0, 0);
                            $this->Cell(0, 8, utf8_decode('PRODUCTOS SIN STOCK (CONTINUACION):'), 0, 1, 'L');
                            $this->SetTextColor(0, 0, 0);
                            $this->SetFont('Arial', '', 9);
                        }
                        $this->Cell(0, 6, utf8_decode('• ' . $producto['nombre_producto'] . ' - Categoria: ' . ($producto['nombre_categoria'] ?? 'Sin categoria') . ' - Precio: $' . number_format($producto['precio_producto'], 0, ',', '.')), 0, 1);
                    }
                    $this->Ln(5);
                }

                // Productos con bajo stock
                if (!empty($productosBajoStock)) {
                    $this->SetFont('Arial', 'B', 10);
                    $this->SetTextColor(255, 165, 0);
                    $this->Cell(0, 8, utf8_decode('PRODUCTOS CON BAJO STOCK (MENOR A 10):'), 0, 1, 'L');
                    $this->SetTextColor(0, 0, 0);
                    $this->SetFont('Arial', '', 9);
                    
                    foreach ($productosBajoStock as $producto) {
                        // Verificar si necesitamos una nueva página
                        if ($this->GetY() > 250) {
                            $this->AddPage();
                            $this->SetFont('Arial', 'B', 12);
                            $this->Cell(0, 10, utf8_decode('ALERTAS DE STOCK (CONTINUACION)'), 0, 1, 'L');
                            $this->Ln(5);
                            $this->SetFont('Arial', 'B', 10);
                            $this->SetTextColor(255, 165, 0);
                            $this->Cell(0, 8, utf8_decode('PRODUCTOS CON BAJO STOCK (CONTINUACION):'), 0, 1, 'L');
                            $this->SetTextColor(0, 0, 0);
                            $this->SetFont('Arial', '', 9);
                        }
                        $this->Cell(0, 6, utf8_decode('• ' . $producto['nombre_producto'] . ' - Stock: ' . $producto['stock_producto'] . ' - Categoria: ' . ($producto['nombre_categoria'] ?? 'Sin categoria') . ' - Precio: $' . number_format($producto['precio_producto'], 0, ',', '.')), 0, 1);
                    }
                }
            }

            // Generar el PDF
            $filename = 'reporte_inventario_' . getFechaHoraLocal('Y-m-d_H-i-s') . '.pdf';
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