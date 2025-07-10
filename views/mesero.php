<?php
session_start();

if (!isset($_SESSION['usuario'])) {
  header('Location: login.php');
  exit;
}

require_once '../models/consultas.php';
require_once '../config/config.php';

try {
    $consultas = new ConsultasMesero();
    $mesas = $consultas->traerMesas();
    $categorias = $consultas->traerCategorias();
} catch (Exception $e) {
    die("Error al cargar datos iniciales: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tienda de Café</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css" />
  <!-- Respaldo: -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <link rel="stylesheet" href="../assets/css/estiloMesero.css" />
</head>

<body class="bg-coffee">
  <div class="container py-4">
    <header class="text-center mb-5 d-flex justify-content-between align-items-center">
      <div>
        <h1 class="display-4 text-light fw-bold mb-0">
          <i class="fas fa-mug-hot me-2"></i>Tienda de Café
        </h1>
        <h4 class="text-light opacity-75 mb-0">Sistema de Gestión de Pedidos</h4>
      </div>
      <div>
        <button id="btnCerrarSesion" class="btn btn-danger btn-lg" title="Cerrar Sesión">
          <i class="fas fa-sign-out-alt"></i>
        </button>
      </div>
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
                if ($mesas && is_array($mesas)) {
                    foreach ($mesas as $mesa) {
                        $deshabilitar = ($mesa['tiene_pedido_confirmado'] > 0 || $mesa['tiene_pedido_entregado'] > 0 || $mesa['tiene_token_activo'] > 0);
                        $disabled = $deshabilitar ? 'disabled' : '';
                        $msg = $mesa['tiene_pedido_confirmado'] > 0 ? ' (Confirmado)' : ($mesa['tiene_pedido_entregado'] > 0 ? ' (Entregado)' : '');
                        $token = isset($mesa['token_activo']) && $mesa['token_activo'] ? ' | Token #' . htmlspecialchars($mesa['token_activo']) : '';
                        $tokenActivo = isset($mesa['token_activo']) && $mesa['token_activo'] ? '1' : '0';
                        echo '<option value="' . (int)$mesa['idmesas'] . '" data-token-activo="' . $tokenActivo . '" ' . $disabled . '>' . htmlspecialchars($mesa['nombre']) . $token . $msg . '</option>';
                    }
                }
                ?>
              </select>
              <button class="btn btn-warning mt-2 w-100" onclick="generarTokenMesa()">
                <i class="fas fa-key me-2"></i>Generar Token para la Mesa
              </button>
            </div>
            <!-- Categorías -->
            <div class="mb-4">
              <h5 class="card-title mb-3">
                <i class="fas fa-tags me-2"></i>Categoría
              </h5>
              <select id="categoriaSelect" class="form-select form-select-lg rounded-3">
                <option value="">Seleccione una categoría</option>
                <?php 
                if ($categorias && is_array($categorias)) {
                    foreach ($categorias as $categoria) {
                        echo '<option value="' . (int)$categoria['idcategorias'] . '">' . 
                             htmlspecialchars($categoria['nombre_categoria']) . '</option>';
                    }
                }
                ?>
              </select>
            </div>
          </div>
        </div>
        <!-- Tarjeta de mesas con token activo -->
        <?php
        $mesasConToken = array_filter($mesas, function($m) {
          return isset($m['token_activo']) && $m['token_activo'];
        });
        if (count($mesasConToken) > 0): ?>
          <div class="card shadow-lg border-0 rounded-4 bg-light mt-4">
            <div class="card-body p-4">
              <h5 class="card-title mb-3 text-danger">
                <i class="fas fa-key me-2"></i>Mesas con Token Activo
              </h5>
              <ul class="list-group">
                <?php foreach ($mesasConToken as $mesa): ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>
                      <strong><?php echo htmlspecialchars($mesa['nombre']); ?></strong>                      
                    </span>
                    <button class="btn btn-danger btn-sm" onclick="cancelarTokenMesa(<?php echo (int)$mesa['idmesas']; ?>, '<?php echo htmlspecialchars($mesa['nombre']); ?>')">
                      <i class="fas fa-ban me-1"></i>Cancelar Token
                    </button>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        <?php endif; ?>
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
              <!-- Los productos se cargarán dinámicamente -->
            </div>
          </div>
        </div>

        <!-- Pedido Actual -->
        <div class="card shadow-lg border-0 rounded-4 bg-light mt-4">
          <div class="card-body p-4">
            <h5 class="card-title mb-4">
              <i class="fas fa-shopping-cart me-2"></i>Pedido Actual
            </h5>
            <ul class="list-group mb-3" id="pedidoLista">
              <!-- Los items del pedido se cargarán dinámicamente -->
            </ul>
            <button class="btn btn-success w-100" onclick="confirmarPedido()">
              <i class="fas fa-check me-2"></i>Confirmar Pedido
            </button>
          </div>
        </div>

        <!-- Pedidos Activos de la Mesa -->
        <div class="card shadow-lg border-0 rounded-4 bg-light mt-4">
          <div class="card-body p-4">
            <h5 class="card-title mb-4">
              <i class="fas fa-list me-2"></i>Pedidos Activos de la Mesa
            </h5>
            <div id="pedidosActivosMesa"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal de Observación -->
  <div class="modal fade" id="observacionModal" tabindex="-1" aria-labelledby="observacionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="observacionModalLabel">Agregar Observaciones</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Producto:</label>
            <p id="productoNombreSeleccionado" class="form-control-static"></p>
          </div>
          <div class="mb-3">
            <label class="form-label">Cantidad:</label>
            <p id="productoCantidadSeleccionada" class="form-control-static"></p>
          </div>
          <div class="mb-3">
            <label class="form-label">Precio:</label>
            <p id="productoPrecioSeleccionado" class="form-control-static"></p>
          </div>
          <div class="mb-3">
            <label for="comentarioInput" class="form-label">Observaciones:</label>
            <textarea class="form-control" id="comentarioInput" rows="3" placeholder="Ingrese observaciones del producto..."></textarea>
          </div>
          <input type="hidden" id="productoSeleccionado">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" onclick="agregarAlPedido()">Agregar al Pedido</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/appMesero.js"></script>
  <script>
  // Mostrar el botón si la opción seleccionada al cargar ya tiene token activo
  window.addEventListener('DOMContentLoaded', function() {
    const selected = document.getElementById('mesaSelect').options[document.getElementById('mesaSelect').selectedIndex];
    if (selected && selected.getAttribute('data-token-activo') === '1') {
      document.getElementById('btnCancelarToken').style.display = '';
    } else {
      document.getElementById('btnCancelarToken').style.display = 'none';
    }
  });
  </script>
</body>
</html>
