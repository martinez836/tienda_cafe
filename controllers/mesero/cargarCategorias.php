<?php
require_once '../../models/mesero/consultas_mesero.php';

$consultasMesero = new consultas_mesero();
$categorias = $consultasMesero->traerCategorias();

$option = "";
if (empty($categorias)) {
    $option = "<option value=\"\">No hay categorías disponibles</option>";
    echo $option;
    exit;
}
// Opción por defecto
$option .= "<option value=\"\">Seleccione categoría</option>"; // el .= concatena!!!
foreach ($categorias as $categoria) {
    $option .= "<option value=\"{$categoria['idcategorias']}\">{$categoria['nombre_categoria']}</option>";
}
echo $option;

