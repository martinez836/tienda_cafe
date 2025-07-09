<?php
require_once '../../config/admin_auth.php';

// Verificar que el usuario sea administrador
verificarAdmin();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../assets/css/cssBalance.css">
    <link href="../../assets/cssBootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <title>Balance General</title>
</head>

<body>
    <div class="container">
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
                    <a class="nav-link " href="dashboard.php">
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
                    <a class="nav-link" href="gestion_mesas.php">
                        <i class="fas fa-chair me-2"></i>Gestión Mesas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="pedidos.php">
                        <i class="fas fa-receipt me-2"></i>Ventas
                    </a>
                </li>
                <li class="nav-item active">
                    <a class="nav-link" href="balanceGeneral.php">
                        <i class="fa-solid fa-file-pdf"></i>Balance
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="graficas.php">
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
                        <a class="nav-link active" href="balanceGeneral.php">
                            <i class="fa-solid fa-file-pdf"></i> Balance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="graficas.php">
                            <i class="fas fa-chart-bar me-2"></i>Gráficas
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <!-- Fin de la barra lateral y comienzo del main del balance -->
        <main class="content">
            <div class="container mt-5 pt-3">
                <h1>Balance General</h1>
                <div class="row">
                    <div class="col-6">
                        <label for="fechaInicio">Fecha de Inicio</label>
                        <input type="date" name="" id="fechaInicio" class="form-control" placeholder="Fecha de Inicio">
                    </div>
                    <div class="col-6">
                        <label for="fechaFin">Fecha de Fin</label>
                        <input type="date" name="" id="fechaFin" class="form-control" placeholder="Fecha de Fin">
                        <button type="button">Buscar</button>
                    </div>

                    <!-- Tabla del balance -->
                    <table class="table mt-4">
                        <thead>
                            <tr>
                                <th>Pedido #</th>
                                <th>Fecha</th>
                                <th>Mesero</th>
                                <th>Total</th>
                                <th>Productos</th>
                            </tr>
                        </thead>
                        <tbody id="TablaBalance">

                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>


    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="../../assets/js/appBalance.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../assets/jsBootstrap/bootstrap.bundle.min.js"></script>
</body>

</html>