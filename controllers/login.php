<?php
require_once '../config/config.php';
require_once '../models/consultasLogin.php';

// Configurar headers para JSON
header('Content-Type: application/json');

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido.'
    ]);
    exit;
}

// Obtener datos del formulario
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validar que los campos no estén vacíos
if (empty($email) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Por favor, completa todos los campos.'
    ]);
    exit;
}

try {
    // Iniciar sesión
    config::iniciarSesion();
    
    // Crear instancia de consultas de login
    $consultasLogin = new ConsultasLogin();
    
    // Intentar autenticar usuario
    $usuario = $consultasLogin->autenticarUsuario($email, $password);
    
    if ($usuario) {
        // Login exitoso - crear sesión
        $_SESSION['usuario_id'] = $usuario['idusuarios'];
        $_SESSION['usuario_nombre'] = $usuario['nombre_usuario'];
        $_SESSION['usuario_email'] = $usuario['email_usuario'];
        $_SESSION['usuario_rol'] = $usuario['nombre_rol'];

        // Bloquear acceso a Mesero desde este login
        

        // Determinar la página de destino según el rol
        $redirect = '../views/admin/dashboard.php'; // Por defecto
        switch ($usuario['nombre_rol']) {
            case 'Administrador':
                $redirect = '../views/admin/dashboard.php';
                break;
            case 'Cajero':
                $redirect = '../views/cajero.php';
                break;
            case 'Cocina':
                $redirect = '../views/cocina.php';
                break;
            case 'Mesero':
                $redirect = '../views/mesero.php';
                break;
            // No incluir Mesero aquí
        }

        echo json_encode([
            'success' => true,
            'redirect' => $redirect,
            'message' => 'Login exitoso'
        ]);
        
    } else {
        // Credenciales incorrectas
        echo json_encode([
            'success' => false,
            'message' => 'Credenciales incorrectas o usuario inactivo.'
        ]);
    }
    
} catch (Exception $e) {
    // Error del sistema
    error_log("Error en login: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error en el sistema. Por favor, intenta nuevamente.'
    ]);
}
?>
