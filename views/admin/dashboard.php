<?php
require_once '../../config/admin_auth.php';

// Verificar que el usuario sea administrador
verificarAdmin();

// Obtener información del usuario administrador
$usuario = obtenerUsuarioAdmin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sistema de Café</title>
    <link href="../../assets/cssBootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/notificaciones.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-coffee me-2"></i>Admin Café
            </a>
            <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas" aria-label="Toggle sidebar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#">Bienvenido, <?php echo htmlspecialchars($usuario['nombre']); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="#" id="btnNotificaciones" title="Notificaciones de Stock">
                            <i class="fas fa-bell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="badgeNotificaciones" style="display: none;">
                                0
                            </span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../cocina.php">Módulo de Cocina</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../cajero.php">Módulo de Cajero</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../../controllers/logout.php">Cerrar Sesión</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>  

    <!-- Sidebar fija para escritorio -->
    <div class="sidebar d-none d-lg-block">
        <ul class="nav flex-column pt-3">
            <li class="nav-item">
                <a class="nav-link active" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="usuarios.php">
                    <i class="fas fa-users"></i>Usuarios
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="productos.php">
                    <i class="fas fa-mug-hot"></i>Productos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="categorias.php">
                    <i class="fas fa-mug-hot"></i>Categorias
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="gestion_mesas.php">
                    <i class="fas fa-chair"></i>Gestión Mesas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="pedidos.php">
                    <i class="fas fa-receipt"></i>Ventas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="graficas.php">
                    <i class="fas fa-chart-bar"></i>Gráficas
                </a>
            </li>
        </ul>
    </div>

    <!-- Sidebar offcanvas para móvil -->
    <div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="sidebarOffcanvas">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Menú</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            <ul class="nav flex-column pt-3">
                <li class="nav-item">   
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="usuarios.php">
                        <i class="fas fa-users me-2"></i>Usuarios
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="productos.php">
                        <i class="fas fa-mug-hot me-2"></i>Productos
                    </a>
                </li>
                <li class="nav-item">
                <a class="nav-link" href="categorias.php">
                    <i class="fas fa-mug-hot"></i>Categorias
                </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="gestion_mesas.php">
                        <i class="fas fa-chair me-2"></i>Gestión Mesas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="pedidos.php">
                        <i class="fas fa-receipt me-2"></i>Ventas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="graficas.php">
                        <i class="fas fa-chart-bar me-2"></i>Gráficas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../cocina.php">
                        <i class="fas fa-utensils me-2"></i>Módulo de Cocina
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../cajero.php">
                        <i class="fas fa-cash-register me-2"></i>Módulo de Cajero
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../../controllers/logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0" style="color: var(--primary-coffee); font-weight: 700;">Dashboard de Administración</h2>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary btn-sm btn-modern" onclick="location.reload()">
                    <i class="fas fa-sync-alt me-1"></i>Actualizar
                </button>
            </div>
        </div>

        <!-- Métricas principales mejoradas (manteniendo la funcionalidad original) -->
        <div class="row mb-4">
            <div class="col-md-4 mb-4">
                <div class="metric-card primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3>0</h3>
                            <small>Total Pedidos</small>
                        </div>
                        <i class="fas fa-receipt"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="metric-card success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3>$0.00</h3>
                            <small>Ingresos del Mes</small>
                        </div>
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="metric-card warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3>0</h3>
                            <small>Nuevos Usuarios</small>
                        </div>
                        <i class="fas fa-user-plus"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alertas de Stock mejoradas (manteniendo funcionalidad original) -->
        <div class="row mb-4" id="alertasStock">
            <div class="col-12">
                <div class="section-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>Estado del Inventario
                        </h5>
                    </div>
                    <div class="card-body" id="contenidoAlertasStock">
                        <div class="text-center">
                            <div class="loading-coffee"></div>
                            <p class="mt-2 text-muted">Cargando alertas de stock...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 mb-4">
                <!-- Gráfico de ventas mejorado (manteniendo funcionalidad original) -->
                <div class="section-card">
                    <div class="card-header">
                        <i class="fas fa-chart-area me-2"></i>Ventas Diarias
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="ventasDiariasChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 mb-4">
                <!-- Últimos pedidos mejorados (manteniendo funcionalidad original) -->
                <div class="section-card">
                    <div class="card-header">
                        <i class="fas fa-list me-2"></i>Últimos Pedidos
                    </div>
                    <div class="card-body">
                        <ul class="list-group" id="ultimosPedidosList">
                            <li class="list-group-item text-center">
                                <div class="loading-coffee"></div>
                                <p class="mt-2 mb-0 text-muted">Cargando pedidos...</p>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts originales mantenidos -->
    <script src="../../assets/jsBootstrap/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../../assets/js/appDashboard.js"></script>
    <script src="../../assets/js/notificacionesStock.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>