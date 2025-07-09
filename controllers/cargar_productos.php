<?php
require_once '../models/consultas.php';

if (isset($_POST['idcategoria']) && !empty($_POST['idcategoria'])) {
    $categoria = $_POST['idcategoria'];

    $consultas = new consultas();
    $productos = $consultas->traer_productos_por_categoria($categoria);

    if (count($productos) > 0) {
        foreach ($productos as $producto) {
            echo '
            <div class="col-md-4 mb-3">
              <div class="card card-cafe p-3">
                <h5 class = "text-center">' . htmlspecialchars($producto['nombre_producto']) . '</h5>
                <p class="text-center">Precio: $' . number_format($producto['precio_producto'], 2) . '</p>
                <input type="number" class="form-control text-center" min="0" id="inputCantidad" placeholder="Cantidad">
                <button 
                class="btn btn-primary mt-2" 
                onclick="abrirModal(this, ' . $producto['idproductos'] . ', \'' . addslashes($producto['nombre_producto']) . '\')">
                Agregar
                </button>
              </div>
            </div>';
        }
    } else {
        echo "<p>No hay productos disponibles para esta categoría.</p>";
    }
} else {
    echo "<p>Categoría no válida.</p>";
}
?>

