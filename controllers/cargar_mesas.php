<?php
require_once '../models/consultas.php';

$consultas = new consultas();
$mesas = $consultas->traer_mesas();
header('Content-Type: application/json');
echo json_encode($mesas);
?>