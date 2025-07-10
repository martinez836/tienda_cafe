<?php
require_once '../config/config.php';
require_once '../config/security.php';
require_once '../models/consultas.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Sanitizar parámetros GET
$correo = '';
$codigo = '';
$token_valido = false;

if (isset($_GET['correo']) && isset($_GET['codigo'])) {
    try {
        $correo = SecurityUtils::sanitizeEmail($_GET['correo']);
        $codigo = SecurityUtils::sanitizeRecoveryCode($_GET['codigo']);
        
        // Usar la clase de consultas para validar el token
        $pdo = config::conectar();
        $consultas = new ConsultasMesero();
        $token_data = $consultas->validarTokenRecuperacion($pdo, $correo, $codigo);
        $token_valido = $token_data !== false;
    } catch (Exception $e) {
        $token_valido = false;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restaurar Contraseña - Tienda de Café</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/estiloMesero.css">
</head>
<body class="bg-coffee">
    <div class="d-flex flex-column justify-content-center align-items-center min-vh-100 w-100 px-4 px-lg-5">
        <div class="d-flex flex-column flex-lg-row justify-content-center align-items-center w-100 gap-2 mx-auto" style="max-width: 900px;">
            <header class="text-center text-lg-end mb-4 mb-lg-0 col-12 col-lg-auto">
                <h1 class="display-4 text-light fw-bold">
                    <i class="fas fa-mug-hot me-2"></i>Tienda de Café
                </h1>
                <h4 class="text-light opacity-75">Restaurar Contraseña</h4>
            </header>
            <div class="col-12 col-lg-4 col-md-6 mx-auto" style="max-width: 400px;">
                <div class="card shadow-lg border-0 rounded-4 bg-light">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-key me-2"></i>Restaurar Contraseña
                        </h5>
                        <?php if ($token_valido): ?>
                        <form id="formNuevaContrasena">
                            <input type="hidden" name="correo" value="<?php echo SecurityUtils::escapeHtml($correo); ?>">
                            <input type="hidden" name="codigo" value="<?php echo SecurityUtils::escapeHtml($codigo); ?>">
                            <div class="mb-4">
                                <label for="nueva_contrasena" class="form-label fw-semibold">Nueva Contraseña:</label>
                                <div class="input-group">
                                    <input type="password" name="nueva_contrasena" id="nueva_contrasena" class="form-control" required minlength="5" maxlength="255">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('nueva_contrasena', this)" title="Mostrar/Ocultar contraseña">👁</button>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="confirmar_contrasena" class="form-label fw-semibold">Confirmar Contraseña:</label>
                                <div class="input-group">
                                    <input type="password" name="confirmar_contrasena" id="confirmar_contrasena" class="form-control" required minlength="5" maxlength="255">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirmar_contrasena', this)" title="Mostrar/Ocultar contraseña">👁</button>
                                </div>
                            </div>
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-lg w-100 btn-primary">
                                    Actualizar Contraseña
                                </button>
                            </div>
                        </form>
                        <?php else: ?>
                            <div class="alert alert-danger text-center">El enlace de recuperación ha expirado o es inválido.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/appNuevaContrasena.js"></script>
</body>
</html> 