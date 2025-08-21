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
    <title>Gestión de Usuarios - Sistema de Café</title>
    <link rel="stylesheet" href="../../assets/cssBootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/usuarios.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
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
                <a class="nav-link active" href="usuarios.php">
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
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="usuarios.php">
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
                    <a class="nav-link" href="pedidos.php">
                        <i class="fas fa-receipt me-2"></i>Ventas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="pedidos.php">
                        <i class="fa-solid fa-file-pdf me-2"></i>Balance
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
            <i class="fas fa-users me-3"></i>Gestión de Usuarios
        </h2>

        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-table me-2"></i>Lista de Usuarios
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap btn-group-actions">
                    <button id="btnCrearUsuario" class="btn btn-primary">
                        <i class="fa-solid fa-plus me-2"></i>Crear Usuario
                    </button>
                    <button class="btn btn-success" id="generarReporteBtn">
                        <i class="fa-solid fa-file-pdf me-2"></i>Generar Reporte PDF
                    </button>
                    <button class="btn btn-info" id="verHistorialBtn">
                        <i class="fa-solid fa-history me-2"></i>Ver Historial
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tablaUsuarios">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <!-- Los datos se cargarán via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal mejorado - manteniendo funcionalidad original -->
    <div class="modal fade" id="modalUsuario" tabindex="-1" aria-labelledby="modalUsuarioTitle" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="modalUsuarioTitle">
                        <i class="fas fa-user me-2"></i>Gestión de Usuario
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="frmUsuario">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nombre_usuario" class="form-label">
                                <i class="fas fa-user me-2"></i>Nombre del usuario
                            </label>
                            <input type="text" class="form-control" id="nombre_usuario" placeholder="Ingrese el nombre completo">
                        </div>
                        <div class="mb-3">
                            <label for="email_usuario" class="form-label">
                                <i class="fas fa-envelope me-2"></i>Email
                            </label>
                            <input type="email" class="form-control" id="email_usuario" placeholder="usuario@ejemplo.com">
                        </div>
                        <div class="mb-3">
                            <label for="contrasena_usuario" id="lblContrasena" class="form-label">
                                <i class="fas fa-lock me-2"></i>Contraseña
                            </label>
                            <input type="password" class="form-control" id="contrasena_usuario" placeholder="Ingrese la contraseña">
                        </div>
                        <div class="mb-3">
                            <label for="rolUsuario" class="form-label">
                                <i class="fas fa-user-tag me-2"></i>Rol
                            </label>
                            <select id="rolUsuario" class="form-select select-rol">
                                <!-- Las opciones se cargarán via JavaScript -->
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="reset" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

    <!-- Scripts originales mantenidos -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../assets/jsBootstrap/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/appUsuario.js"></script>

   <!--  <script>
        // Mejoras adicionales de UX (opcional)
        document.addEventListener('DOMContentLoaded', function() {
            // Añadir efectos de loading en botones
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(btn => {
                btn.addEventListener('click', function() {
                    if (!this.disabled) {
                        const originalHTML = this.innerHTML;
                        this.innerHTML = '<div class="loading-coffee"></div> ' + originalHTML.split('</i>')[1] || originalHTML;
                        setTimeout(() => {
                            this.innerHTML = originalHTML;
                        }, 1000);
                    }
                });
            });

            // Mejorar la experiencia del DataTable
            $('#tablaUsuarios').on('init.dt', function() {
                $('.dataTables_length select').addClass('form-select form-select-sm');
                $('.dataTables_filter input').addClass('form-control form-control-sm');
            });
        });
    </script> -->
</body>
</html>