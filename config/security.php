<?php
/**
 * Clase de utilidades de seguridad para sanitización y validación de entradas
 */
class SecurityUtils
{
    /**
     * Sanitiza y valida un correo electrónico
     */
    public static function sanitizeEmail($email)
    {
        $email = trim($email);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Formato de correo electrónico inválido');
        }
        
        return $email;
    }
    
    /**
     * Sanitiza y valida una contraseña
     */
    public static function sanitizePassword($password)
    {
        $password = trim($password);
        
        if (empty($password)) {
            throw new Exception('La contraseña no puede estar vacía');
        }
        
        if (strlen($password) < 5) {
            throw new Exception('La contraseña debe tener al menos 5 caracteres');
        }
        
        // Limitar longitud máxima
        if (strlen($password) > 255) {
            throw new Exception('La contraseña es demasiado larga');
        }
        
        return $password;
    }
    
    /**
     * Sanitiza y valida un token
     */
    public static function sanitizeToken($token)
    {
        $token = trim($token);
        
        if (empty($token)) {
            throw new Exception('Token no proporcionado');
        }
        
        // Validar que solo contenga dígitos
        if (!preg_match('/^\d{4}$/', $token)) {
            throw new Exception('Formato de token inválido');
        }
        
        return $token;
    }
    
    /**
     * Sanitiza y valida un ID numérico
     */
    public static function sanitizeId($id, $fieldName = 'ID')
    {
        $id = trim($id);
        
        if (empty($id)) {
            throw new Exception($fieldName . ' no proporcionado');
        }
        
        $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
        
        if (!$id || $id <= 0) {
            throw new Exception($fieldName . ' inválido');
        }
        
        return (int)$id;
    }
    
    /**
     * Sanitiza y valida un comentario/observación
     */
    public static function sanitizeComment($comment)
    {
        $comment = trim($comment);
        
        // Limitar longitud
        if (strlen($comment) > 500) {
            throw new Exception('El comentario es demasiado largo (máximo 500 caracteres)');
        }
        
        // Remover caracteres peligrosos pero permitir algunos caracteres especiales
        $comment = htmlspecialchars($comment, ENT_QUOTES, 'UTF-8');
        
        return $comment;
    }
    
    /**
     * Sanitiza y valida una cantidad
     */
    public static function sanitizeQuantity($quantity)
    {
        $quantity = trim($quantity);
        
        if (empty($quantity)) {
            throw new Exception('Cantidad no proporcionada');
        }
        
        $quantity = filter_var($quantity, FILTER_SANITIZE_NUMBER_INT);
        
        if (!$quantity || $quantity <= 0) {
            throw new Exception('Cantidad inválida');
        }
        
        if ($quantity > 999) {
            throw new Exception('Cantidad demasiado alta');
        }
        
        return (int)$quantity;
    }
    
    /**
     * Sanitiza y valida un precio
     */
    public static function sanitizePrice($price)
    {
        $price = trim($price);
        
        if (empty($price)) {
            throw new Exception('Precio no proporcionado');
        }
        
        // Validar formato de precio
        if (!preg_match('/^\d+(\.\d{1,2})?$/', $price)) {
            throw new Exception('Formato de precio inválido');
        }
        
        $price = (float)$price;
        
        if ($price < 0 || $price > 999999.99) {
            throw new Exception('Precio fuera de rango válido');
        }
        
        return $price;
    }
    
    /**
     * Sanitiza y valida un código de recuperación
     */
    public static function sanitizeRecoveryCode($code)
    {
        $code = trim($code);
        
        if (empty($code)) {
            throw new Exception('Código de recuperación no proporcionado');
        }
        
        // Validar que solo contenga caracteres hexadecimales
        if (!preg_match('/^[a-f0-9]{10}$/', $code)) {
            throw new Exception('Formato de código de recuperación inválido');
        }
        
        return $code;
    }
    
    /**
     * Sanitiza datos JSON recibidos
     */
    public static function sanitizeJsonData($jsonData)
    {
        if (!is_array($jsonData)) {
            throw new Exception('Datos JSON inválidos');
        }
        
        return $jsonData;
    }
    
    /**
     * Valida que un array contenga las claves requeridas
     */
    public static function validateRequiredKeys($data, $requiredKeys)
    {
        foreach ($requiredKeys as $key) {
            if (!isset($data[$key]) || empty($data[$key])) {
                throw new Exception('Campo requerido faltante: ' . $key);
            }
        }
    }
    
    /**
     * Genera un token CSRF
     */
    public static function generateCSRFToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Valida un token CSRF
     */
    public static function validateCSRFToken($token)
    {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            throw new Exception('Token CSRF inválido');
        }
        return true;
    }
    
    /**
     * Escapa HTML para prevenir XSS
     */
    public static function escapeHtml($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Valida que una cadena solo contenga caracteres alfanuméricos y espacios
     */
    public static function sanitizeAlphanumeric($string, $fieldName = 'Campo')
    {
        $string = trim($string);
        
        if (empty($string)) {
            throw new Exception($fieldName . ' no puede estar vacío');
        }
        
        if (!preg_match('/^[a-zA-Z0-9\s]+$/', $string)) {
            throw new Exception($fieldName . ' contiene caracteres no permitidos');
        }
        
        return $string;
    }
}
?> 