<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido de Mesa</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="../assets/css/estiloMesero.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-coffee">
    <div class="d-flex flex-column justify-content-center align-items-center min-vh-100 w-100 px-4 px-lg-5">
        <div class="d-flex flex-column flex-lg-row justify-content-center align-items-center w-100 gap-2 mx-auto" style="max-width: 900px;">
            <header class="text-center text-lg-end mb-4 mb-lg-0 col-12 col-lg-auto">
                <h1 class="display-4 text-light fw-bold">
                    <i class="fas fa-mug-hot me-2"></i>Tienda de Café
                </h1>
                <h4 class="text-light opacity-75">Pedido de Cliente en Mesa</h4>
            </header>
            <div class="col-12 col-lg-4 col-md-6 mx-auto py-4" id="tokenPanel" style="max-width: 400px;">
                <div class="card shadow-lg border-0 rounded-4 bg-light">
                    <div class="card-body p-4">
                        <div id="tokenSection">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-key me-2"></i>Validar Token
                            </h5>
                            <input type="number" id="tokenInput" maxlength="4" class="form-control form-control-lg mb-3 text-center" placeholder="Token de 4 dígitos">
                            <button class="btn btn-warning w-100" onclick="validarToken()">
                                <i class="fas fa-check me-2"></i>Validar
                            </button>
                        </div>
                        <div id="expiracionTokenInfo" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel de Productos y Pedido -->
        <div class="row g-4 justify-content-center w-100">
            <div class="col-lg-8" id="pedidoPanel">
                <div id="pedidoSection" style="display: none;">
                    <!-- Panel de Productos -->
                    <div class="card shadow-lg border-0 rounded-4 bg-light mb-4">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-4">
                                <i class="fas fa-coffee me-2"></i>Productos Disponibles
                            </h5>
                            <div class="mb-3">
                                <label for="categoriaSelect" class="form-label">Categoría:</label>
                                <select class="form-select form-select-lg rounded-3" id="categoriaSelect">
                                    <option value="">Seleccione una categoría</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <input type="text" id="buscadorProductos" class="form-control" placeholder="Buscar producto...">
                            </div>
                            <div class="row g-3" id="productosContainer">
                                <!-- Los productos se cargarán dinámicamente -->
                            </div>
                        </div>
                    </div>

                    <!-- Historial de Pedidos -->
                    <div class="card shadow-lg border-0 rounded-4 bg-light mb-4" id="historialPedidos" style="display: none;">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-history me-2"></i>Historial de Pedidos
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <div id="contenidoHistorial">
                                <!-- El historial se cargará dinámicamente -->
                            </div>
                        </div>
                    </div>

                    <!-- Pedido Actual -->
                    <div class="card shadow-lg border-0 rounded-4 bg-light">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-4">
                                <i class="fas fa-shopping-cart me-2"></i>Pedido Actual
                            </h5>
                            <ul class="list-group mb-3" id="productosPedido">
                                <!-- Los productos del pedido se cargarán dinámicamente como <li> -->
                            </ul>
                            <div class="text-end fw-bold mb-3">
                                Total: <span id="totalPedido">$0.00</span>
                            </div>
                            <button class="btn btn-success w-100" id="btnConfirmarPedido" onclick="confirmarPedido()">
                                <i class="fas fa-check me-2"></i>Confirmar Pedido
                            </button>
                        </div>
                    </div>
                </div>
                <div id="pedidoActual"></div>
            </div>
        </div>
    </div>

    <!-- Modal de Observaciones -->
    <div class="modal fade" id="modalObservaciones" tabindex="-1" aria-labelledby="modalObservacionesLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalObservacionesLabel">Agregar Observaciones</h5>
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
                        <label for="observaciones" class="form-label">Observaciones:</label>
                        <textarea class="form-control" id="observaciones" rows="3" placeholder="Ingrese observaciones del producto..."></textarea>
                    </div>
                    <input type="hidden" id="productoId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="agregarProductoAlPedido()">Agregar al Pedido</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/appUsuarioMesa.js"></script>
</body>
</html> 