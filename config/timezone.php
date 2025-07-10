<?php
// Configuración de zona horaria para Colombia
date_default_timezone_set('America/Bogota');

// Función para obtener la fecha y hora actual en formato local
function getFechaHoraLocal($formato = 'd/m/Y H:i:s') {
    return date($formato);
}

// Función para obtener solo la fecha
function getFechaLocal($formato = 'd/m/Y') {
    return date($formato);
}

// Función para obtener solo la hora
function getHoraLocal($formato = 'H:i:s') {
    return date($formato);
}

// Función para formatear una fecha específica
function formatearFecha($fecha, $formato = 'd/m/Y H:i:s') {
    return date($formato, strtotime($fecha));
}
?> 