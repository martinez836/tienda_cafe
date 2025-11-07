<?php
require_once "../../models/mesero/consultas_mesero.php";

$consultasMesero = new consultas_mesero();
$mesas = $consultasMesero->traerMesas();

$option = "";
if (empty($mesas)) {
    $option = "<option value=\"\">No hay mesas creadas</option>";
    echo $option;
    exit;
}
//opcion por defecto
$option .= "<option value=\"\">Seleccione mesa</option>"; // el .= concatena!!!
foreach ($mesas as $mesa) {
    $estadoTexto = "";
    $disabled = "";
    
    switch ($mesa['estado_mesa']) {
        case 'ocupada_pedido':
            $estadoTexto = " (Con pedido activo)";
            break;
        case 'ocupada_token':
            $estadoTexto = " (Con token activo)";
            break;
        // No mostrar nada para 'disponible'
    }
    
    // Solo deshabilitar si la mesa está inactiva en la base de datos
    if ($mesa['estados_idestados'] == 2) {
        $estadoTexto .= " [Inactiva]";
        $disabled = " disabled";
    }
    
    $option .= "<option value=\"{$mesa['idmesas']}\"{$disabled}>{$mesa['nombre']}{$estadoTexto}</option>";
}
echo $option;