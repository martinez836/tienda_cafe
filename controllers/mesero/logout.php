<?php
require_once __DIR__ . '/../config/config.php';

// Cerrar sesión
config::cerrarSesion();

// Redirigir al login
header('Location: ../views/login.php');
exit();
?> 