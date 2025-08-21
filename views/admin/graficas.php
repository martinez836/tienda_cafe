<?php
require_once '../../config/admin_auth.php';

// Verificar que el usuario sea administrador
verificarAdmin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gráficas y Reportes - Sistema de Café</title>
    <link rel="stylesheet" href="../../assets/cssBootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/productos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Estilos adicionales específicos para gráficas */
        .chart-container {
            position: relative;
            height: 350px;
        }
        
        .chart-container canvas {
            max-height: 100%;
        }
        
        .stats-card {
            transition: all 0.3s ease;
            border: none;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-coffee);
        }
        
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .chart-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 300px;
            color: var(--primary-coffee);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
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
                    <a class="nav-link active" href="graficas.php">
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
                        <a class="nav-link active" href="graficas.php">
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
            <h2 class="page-title">
                <i class="fas fa-chart-bar me-3"></i>Gráficas y Reportes
            </h2>
            <!-- Sección de Gráficas Principales -->
            <div class="row graficas-principales">
                <!-- Recaudo Mensual -->
                <div class="col-lg-6 mb-4">
                    <div class="card grafica-barras">
                        <div class="card-header">
                            <i class="fas fa-chart-bar me-2"></i>Recaudo Mensual
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <div class="chart-loading" id="loading1">
                                    <div class="loading-coffee me-2"></div>
                                    Cargando datos...
                                </div>
                                <canvas id="ventasCategoriaChart" style="display: none;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recaudo por Mes -->
                <div class="col-lg-6 mb-4">
                    <div class="card grafica-barras">
                        <div class="card-header">
                            <i class="fas fa-chart-bar me-2"></i>Recaudo por Mes
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <div class="chart-loading" id="loading2">
                                    <div class="loading-coffee me-2"></div>
                                    Cargando datos...
                                </div>
                                <canvas id="recaudoMesChart" style="display: none;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ingresos por empleado -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-line me-2"></i>Ingresos por Empleado
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <div class="chart-loading" id="loading3">
                                    <div class="loading-coffee me-2"></div>
                                    Cargando datos...
                                </div>
                                <canvas id="ingresosEmpleadoChart" style="display: none;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mesas atendidas por empleado -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-line me-2"></i>Mesas Atendidas por Empleado
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <div class="chart-loading" id="loading4">
                                    <div class="loading-coffee me-2"></div>
                                    Cargando datos...
                                </div>
                                <canvas id="mesasEmpleadoChart" style="display: none;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Productos más Vendidos -->
                <div class="col-lg-6 mb-4">
                    <div class="card grafica-pastel">
                        <div class="card-header">
                            <i class="fas fa-chart-pie me-2"></i>Productos más Vendidos
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <div class="chart-loading" id="loading5">
                                    <div class="loading-coffee me-2"></div>
                                    Cargando datos...
                                </div>
                                <canvas id="productosVendidosChart" style="display: none;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../../assets/jsBootstrap/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../assets/js/appGraficas.js"></script>
    
    <script>
        // Script para manejar los estados de carga
        document.addEventListener('DOMContentLoaded', function() {
            // Simular carga de gráficas (esto debería integrarse con tu JS existente)
            const loadingElements = document.querySelectorAll('.chart-loading');
            const chartElements = document.querySelectorAll('canvas');
            
            setTimeout(() => {
                loadingElements.forEach(loading => loading.style.display = 'none');
                chartElements.forEach(chart => chart.style.display = 'block');
            }, 1500);
        });
    </script>
</body>
</html>