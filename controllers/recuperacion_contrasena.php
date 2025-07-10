<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

require_once '../config/security.php';

// Conectar a la base de datos
$mysqli = new mysqli('localhost', 'root', '', 'bd_cafe');
if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Conexión fallida']);
    exit;
}

// Requiere PHPMailer
require '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    // Recibe JSON
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validar que los datos JSON sean válidos
    $data = SecurityUtils::sanitizeJsonData($data);
    
    // Validar campo requerido
    SecurityUtils::validateRequiredKeys($data, ['correo']);
    
    // Sanitizar y validar el correo
    $correo = SecurityUtils::sanitizeEmail($data['correo']);

    // Verifica si el correo existe en la tabla usuarios
    $stmt = $mysqli->prepare("SELECT * FROM usuarios WHERE email_usuario = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        // Generar un código de recuperación aleatorio
        $codigo = bin2hex(random_bytes(5));

        // Guardar el código y el correo en la tabla de recuperación
        $stmt = $mysqli->prepare("INSERT INTO recuperacion (correo_recuperacion, codigo_recuperacion) VALUES (?, ?)");
        $stmt->bind_param("ss", $correo, $codigo);
        $stmt->execute();

        // Crear el enlace de recuperación (ajusta la ruta a tu sistema)
        $enlace = "http://localhost/tienda_cafe/views/nuevaContrasena.php?correo=" . urlencode($correo) . "&codigo=" . $codigo;

        // Crear el mensaje de recuperación
        $asunto = "Resturar contrasena";
        $mensaje = "Haz clic en este enlace para recuperar tu contraseña: <a href='$enlace'>Recuperar Contraseña</a>";

        // Usar PHPMailer para enviar el correo
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'recuperacionContrasenas03@gmail.com';
            $mail->Password = 'lnie usnk rnuo xueq';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->setFrom('recuperacionContrasenas03@gmail.com', 'Servicio de Recuperación');
            $mail->addAddress($correo);
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body    = $mensaje;
            $mail->send();
            echo json_encode(['success' => true, 'message' => 'Se ha enviado un correo de recuperación a ' . SecurityUtils::escapeHtml($correo)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al enviar el correo: ' . SecurityUtils::escapeHtml($mail->ErrorInfo)]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'El correo no está registrado.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
