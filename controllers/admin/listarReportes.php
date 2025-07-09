<?php
require_once '../../config/admin_controller_auth.php';

// Verificar que el usuario sea administrador
verificarAdminController();

header('Content-Type: application/json');

$reportesDir = __DIR__ . '/../../facturas/reportes/';
$reportes = [];

try {
    if (is_dir($reportesDir)) {
        $archivos = scandir($reportesDir);
        
        foreach ($archivos as $archivo) {
            if ($archivo !== '.' && $archivo !== '..' && pathinfo($archivo, PATHINFO_EXTENSION) === 'pdf') {
                $filepath = $reportesDir . $archivo;
                $reportes[] = [
                    'filename' => $archivo,
                    'size' => filesize($filepath),
                    'created' => date('Y-m-d H:i:s', filemtime($filepath)),
                    'size_formatted' => formatBytes(filesize($filepath))
                ];
            }
        }
        
        // Ordenar por fecha de creación (más reciente primero)
        usort($reportes, function($a, $b) {
            return strtotime($b['created']) - strtotime($a['created']);
        });
    }
    
    echo json_encode(['success' => true, 'data' => $reportes]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?> 