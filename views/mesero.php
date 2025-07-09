<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('cafe_session');
    session_start();
}
require_once '../config/config.php';

// Verificar si está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../views/login.php');
    exit();
}

// Permitir acceso a Mesero y Administrador
if ($_SESSION['usuario_rol'] !== 'Mesero' && $_SESSION['usuario_rol'] !== 'Administrador') {
    header('Location: ../views/login.php');
    exit();
}

require_once '../models/consultas.php';
$consultas = new consultas();
$mesas = $consultas->traer_mesas();
$categorias = $consultas->traer_categorias();
$productos = $consultas->traer_productos_por_categoria('');
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tienda de Café</title>
  <link href="/Cafe/assets/cssBootstrap/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <link rel="stylesheet" href="/Cafe/assets/css/estiloMesero.css">
</head>

<body class="bg-coffee">
  <div class="container py-4">
    <header class="text-center mb-5">
      <h1 class="display-4 text-light fw-bold">
        <i class="fas fa-mug-hot me-2"></i>Tienda de Café
      </h1>
      <p class="text-light opacity-75">Sistema de Gestión de Pedidos</p>
    </header>

    <div class="row g-4">
      <!-- Panel de Selección -->
      <div class="col-lg-4">
        <div class="card shadow-lg border-0 rounded-4 bg-light">
          <div class="card-body p-4">
            <!-- Selección de Mesa -->
            <div class="mb-4">
              <h5 class="card-title mb-3">
                <i class="fas fa-chair me-2"></i>Mesa Actual
              </h5>
              <select id="mesaSelect" class="form-select form-select-lg rounded-3">
                <option value="">Seleccione una mesa</option>
                <?php
                if ($mesas) {
                  foreach ($mesas as $mesa) {
                ?>
                    <option value="<?php echo $mesa['nombre']; ?>"><?php echo $mesa['nombre']; ?></option>
                <?php
                  }
                }
                ?>
              </select>
            </div>

            <!-- Categorías -->
            <div class="mb-4">
              <h5 class="card-title mb-3">
                <i class="fas fa-tags me-2"></i>Categoría
              </h5>
              <select id="categoriaSelect" class="form-select form-select-lg rounded-3">
                <option value="">Seleccione una categoría</option>
                <?php
                if ($categorias) {
                  foreach ($categorias as $categoria) {
                ?>
                    <option value="<?php echo $categoria['idcategorias']; ?>"><?php echo $categoria['nombre_categoria']; ?></option>
                <?php
                  }
                }
                ?>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Panel de Productos -->
      <div class="col-lg-8">
        <div class="card shadow-lg border-0 rounded-4 bg-light">
          <div class="card-body p-4">
            <h5 class="card-title mb-4">
              <i class="fas fa-coffee me-2"></i>Productos Disponibles
            </h5>
            <div class="mb-3">
              <input type="text" id="buscadorProductos" class="form-control" placeholder="Buscar producto...">
            </div>
            <div class="row g-3" id="productosContainer">
              <!-- Los productos se cargarán dinámicamente aquí -->
            </div>
          </div>
        </div>
      </div>

      <!-- Panel de Pedido Actual -->
      <div class="col-12">
        <div class="card shadow-lg border-0 rounded-4 bg-light">
          <div class="card-body p-4">
            <h5 class="card-title mb-4">
              <i class="fas fa-clipboard-list me-2"></i>Pedido Actual
            </h5>
            <ul class="list-group list-group-flush mb-4" id="pedidoLista"></ul>
            <button class="btn btn-success btn-lg w-100 rounded-3" onclick="confirmarPedido()">
              <i class="fas fa-check me-2"></i>Confirmar Pedido
            </button>
          </div>
        </div>
      </div>

      <!-- Panel de Pedidos Activos -->
      <div class="col-12">
        <div class="card shadow-lg border-0 rounded-4 bg-light">
          <div class="card-body p-4">
            <h5 class="card-title mb-4">
              <i class="fas fa-clock me-2"></i>Pedidos Activos
            </h5>
            <ul class="list-group list-group-flush" id="pedidosActivos"></ul>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal de Observaciones -->
  <div class="modal fade" id="observacionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header border-0">
          <h5 class="modal-title">
            <i class="fas fa-comment-alt me-2"></i>Observaciones
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="productoSeleccionado" />
          <div class="mb-3">
            <label class="form-label">Comentario adicional:</label>
            <textarea id="comentarioInput" class="form-control" rows="3" placeholder="Escribe aquí las observaciones, sabores u otros"></textarea>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button class="btn btn-primary btn-lg px-4" onclick="agregarAlPedido()">
            <i class="fas fa-plus me-2"></i>Agregar
          </button>
        </div>
      </div>
    </div>
  </div>

  <script src="/Cafe/assets/jsBootstrap/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="/Cafe/assets/js/appMesero.js"></script>
</body>

</html>