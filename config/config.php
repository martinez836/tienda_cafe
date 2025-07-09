<?php
// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'bd_cafe');
define('DB_USER', 'root');  
define('DB_PASS', '');   

// Configuración de seguridad
define('SESSION_NAME', 'cafe_session');
define('SESSION_LIFETIME', 3600); // 1 hora
define('SECURE_COOKIES', false); // Cambiar a true en producción con HTTPS

// Configuración de la aplicación
define('APP_NAME', 'Sistema de Gestión de Café');
define('APP_VERSION', '1.0.0');

class config {
    public static function conectar() {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            throw new PDOException('Error de conexión: ' . $e->getMessage());
        }
    }

    public static function iniciarSesion() {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path' => '/',
                'secure' => SECURE_COOKIES,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            session_start();
        }
    }

    public static function verificarAutenticacion() {
        self::iniciarSesion();
        if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_rol'])) {
            header('Location: ../views/login.php');
            exit();
        }
    }

    public static function verificarRol($rolesPermitidos) {
        self::verificarAutenticacion();
        if (!in_array($_SESSION['usuario_rol'], $rolesPermitidos)) {
            header('Location: error.php?msg=acceso_denegado');
            exit();
        }
    }

    public static function cerrarSesion() {
        self::iniciarSesion();
        session_destroy();
        setcookie(SESSION_NAME, '', time() - 3600, '/');
    }
}
?>
