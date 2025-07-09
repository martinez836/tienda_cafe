<?php
require_once '../../config/config.php';
config::iniciarSesion();

// Verificar si está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gráficas - Tienda de Café</title>
    <link href="/Cafe/assets/cssBootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet"  href="/Cafe/assets/css/graficas.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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
                        <a class="nav-link" href="#">Bienvenido, Admin</a>
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
                <a class="nav-link" href="dashboard.php">
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
                <a class="nav-link active" href="gestion_mesas.php">
                    <i class="fas fa-mug-hot me-2"></i>Gestión Mesas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="pedidos.php">
                    <i class="fas fa-receipt me-2"></i>Ventas
                </a>
            </li>
            <li class="nav-item">
                    <a class="nav-link" href="balanceGeneral.php">
                        <i class="fa-solid fa-file-pdf"></i>Balance
                    </a>
                </li>
            <li class="nav-item">
                <a class="nav-link active" href="graficas.php">
                    <i class="fas fa-chart-bar me-2"></i>Gráficas
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
                    <a class="nav-link" href="dashboard.php">
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
                    <a class="nav-link active" href="gestion_mesas.php">
                        <i class="fas fa-chair me-2"></i>Gestión Mesas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="pedidos.php">
                        <i class="fas fa-receipt me-2"></i>Ventas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="balanceGeneral.php">
                        <i class="fa-solid fa-file-pdf"></i>Balance
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="graficas.php">
                        <i class="fas fa-chart-bar me-2"></i>Gráficas
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="content">
        <h2 class="mb-4">Gráficas y Reportes</h2>

        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-bar me-2"></i>Recaudo Mensual
                    </div>
                    <div class="card-body">
                        <canvas id="ventasCategoriaChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-pie me-2"></i>Productos más Vendidos
                    </div>
                    <div class="card-body">
                        <canvas id="productosVendidosChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-line me-2"></i>Ingresos por empleado
                    </div>
                    <div class="card-body">
                        <canvas id="ingresosEmpleadoChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-line me-2"></i>Mesas atendidas por empleado
                    </div>
                    <div class="card-body">
                        <canvas id="mesasEmpleadoChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="/Cafe/assets/jsBootstrap/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="/Cafe/assets/js/appGraficas.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
