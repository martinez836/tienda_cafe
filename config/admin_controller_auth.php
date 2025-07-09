<?php
require_once 'config.php';

/**
 * Verificar que el usuario esté autenticado y tenga rol de administrador
 * Para controladores que devuelven JSON
 */
function verificarAdminController() {
    config::iniciarSesion();
    
    // Verificar si está logueado
    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'No autorizado. Debe iniciar sesión.'
        ]);
        exit();
    }
    
    // Verificar si tiene rol de administrador
    if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'Administrador') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Acceso denegado. Solo administradores pueden acceder a esta funcionalidad.'
        ]);
        exit();
    }
}

/**
 * Verificar que el usuario esté autenticado y tenga rol de administrador
 * Para controladores que devuelven contenido HTML o redirigen
 */
function verificarAdminControllerRedirect() {
    config::iniciarSesion();
    
    // Verificar si está logueado
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ../../views/login.php');
        exit();
    }
    
    // Verificar si tiene rol de administrador
    if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'Administrador') {
        // Redirigir a una página de error o al módulo correspondiente según el rol
        $rol = $_SESSION['usuario_rol'] ?? 'Sin rol';
        
        switch ($rol) {
            case 'Cajero':
                header('Location: ../../views/cajero.php');
                break;
            case 'Cocina':
                header('Location: ../../views/cocina.php');
                break;
            case 'Mesero':
                header('Location: ../../views/mesero.php');
                break;
            default:
                header('Location: ../../views/login.php');
                break;
        }
        exit();
    }
}
?> 