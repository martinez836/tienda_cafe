<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('cafe_session');
    session_start();
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../config/config.php';


// Verificar si está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../views/login.php');
    exit();
}

// Permitir acceso a Cocina y Administrador
if ($_SESSION['usuario_rol'] !== 'Cocina' && $_SESSION['usuario_rol'] !== 'Administrador') {
    header('Location: ../views/login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cocina - Tienda de Café</title>
    <link href="../assets/cssBootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/estiloCocina.css">
</head>
<body>
    <div class="header">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="text-center flex-grow-1">
                <i class="fas fa-coffee coffee-icon"></i>
                <h1 class="d-inline">Tienda de Café</h1>
                <p class="mb-0 mt-2">Módulo de Cocina</p>
            </div>
            <a href="../controllers/logout.php" class="btn btn-outline-danger ms-3">Cerrar Sesión <i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>

    <div class="container mt-4">
        <div class="row g-4 flex-column-reverse flex-lg-row">
            <!-- Pedidos Pendientes de Preparación -->
            <div class="col-12 col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="fas fa-hourglass-start me-2"></i>
                        Pedidos Pendientes
                    </div>
                    <div class="card-body">
                        <div id="pedidos_pendientes">
                            <!-- Los pedidos pendientes se cargarán aquí -->
                            <div class="empty-state text-center py-5">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h5>No hay pedidos pendientes</h5>
                                <p  style="color: #8B5E3C;">Todos los pedidos han sido preparados.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detalles del Pedido y Acciones -->
            <div class="col-12 col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="fas fa-utensils me-2"></i>
                        Detalles del Pedido
                    </div>
                    <div class="card-body">
                        <div id="detalles_pedido">
                            <div class="empty-state text-center py-5">
                                <i class="fas fa-hand-pointer fa-3x mb-3"></i>
                                <h5>Selecciona un pedido</h5>
                                <p style="color: #8B5E3C;">Haz clic en un pedido de la lista para ver los detalles y prepararlo.</p>
                            </div>
                            <!-- Los detalles del pedido seleccionado se cargarán aquí -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/jsBootstrap/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/appCocina.js"></script>
</body>
</html> 