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
    <title>Gestión de Mesas - Sistema de Café</title>
    <link href="../../assets/cssBootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/estiloMesa.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="../../assets/css/notificaciones.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        
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
                    <a class="nav-link active" href="gestion_mesas.php">
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
            <h2 class="page-title">
                <i class="fas fa-chair me-3"></i>Gestión de Mesas
            </h2>

            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-list-alt me-2"></i>Listado de Mesas
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap btn-group-actions">
                        <button id="addMesasBtn" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Agregar Mesa
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm w-100 align-middle" id="tablaMesas">
                            <thead>
                                <tr>
                                    <th style="width: 8%; min-width: 60px;" class="text-center">ID</th>
                                    <th style="width: 50%;">Nombre Mesa</th>
                                    <th style="width: 20%;" class="text-center">Estado</th>
                                    <th style="width: 22%;" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="mesasTableBody">
                                <!-- Los datos se cargarán via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para Agregar/Editar Mesa -->
        <div class="modal fade" id="mesasModal" tabindex="-1" aria-labelledby="mesasModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="mesasModalLabel">
                            <i class="fas fa-chair me-2"></i>Gestión de Mesas
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="mesasForm">
                            <input type="hidden" id="mesaId">
                            <div class="mb-3">
                                <label for="nombreMesa" class="form-label">
                                    <i class="fas fa-table me-2"></i>Nombre Mesa
                                </label>
                                <input type="text" class="form-control" id="nombreMesa" placeholder="Ej: Mesa 1, Mesa VIP, Terraza..." required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="button" class="btn btn-primary" id="saveMesa">
                            <i class="fas fa-save me-2"></i>Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts originales mantenidos -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="../../assets/jsBootstrap/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/appGestion_mesas.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>