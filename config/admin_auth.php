<?php
require_once 'config.php';

/**
 * Verificar que el usuario esté autenticado y tenga rol de administrador
 * Si no cumple las condiciones, redirige al login o muestra error
 */
function verificarAdmin() {
    config::iniciarSesion();
    
    // Verificar si está logueado
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ../login.php');
        exit();
    }
    
    // Verificar si tiene rol de administrador
    if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'Administrador') {
        // Redirigir a una página de error o al módulo correspondiente según el rol
        $rol = $_SESSION['usuario_rol'] ?? 'Sin rol';
        
        switch ($rol) {
            case 'Cajero':
                header('Location: ../cajero.php');
                break;
            case 'Cocina':
                header('Location: ../cocina.php');
                break;
            case 'Mesero':
                header('Location: ../mesero.php');
                break;
            default:
                header('Location: ../login.php');
                break;
        }
        exit();
    }
}

/**
 * Obtener información del usuario administrador actual
 */
function obtenerUsuarioAdmin() {
    return [
        'id' => $_SESSION['usuario_id'] ?? null,
        'nombre' => $_SESSION['usuario_nombre'] ?? 'Administrador',
        'email' => $_SESSION['usuario_email'] ?? '',
        'rol' => $_SESSION['usuario_rol'] ?? 'Administrador'
    ];
}
?> 