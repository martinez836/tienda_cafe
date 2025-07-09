<?php

require_once __DIR__ . '/../../config/admin_controller_auth.php';
require_once __DIR__ . '/../../models/consultasUsuarios.php';

// Verificar que el usuario sea administrador
verificarAdminController();

header('Content-Type: application/json');

$consultas = new ConsultasUsuarios();

$action = $_GET['action'] ?? '';

$response = ['success' => false, 'message' => 'Invalid action'];

try {
    switch ($action) {
        case 'get_all_users':
            $usuarios = $consultas->getAllUsuarios();
            $response = [
                'success' => true,
                'data' => $usuarios
            ];
            break;
        case 'traer_roles':
            $roles = $consultas->traerRoles();
            $response = [
                'success' => true,
                'data' => $roles
            ];
            break;
        case 'traer_estados':
            $estados = $consultas->traerEstados();
            $response = [
                'success' => true,
                'data' => $estados
            ];
            break;
        case 'crear_usuario':
            $nombre_usuario = $_POST['nombre_usuario'] ?? '';
            $contrasena_usuario = $_POST['contrasena_usuario'] ?? '';
            $email_usuario = $_POST['email_usuario'] ?? '';
            $rol_idrol = $_POST['rol_idrol'] ?? 0;
            $contrasenaEncriptada = password_hash($contrasena_usuario, PASSWORD_BCRYPT);

            $debug = [];
            $debug[] = "[DEBUG] Insertar usuario: nombre=$nombre_usuario, email=$email_usuario, rol=$rol_idrol";

            if (empty($nombre_usuario) || empty($contrasena_usuario) || empty($email_usuario) || $rol_idrol <= 0) {
                $response = ['success' => false, 'message' => 'Datos invalidos', 'debug' => $debug];
            } else {
                $insertar = $consultas->insertarUsuarios($nombre_usuario, $contrasenaEncriptada, $email_usuario, $rol_idrol);
                $debug[] = "[DEBUG] Resultado insertarUsuarios: " . ($insertar ? 'true' : 'false');
                if ($insertar) {
                    $response = ['success' => true, 'message' => 'Usuario creado exitosamente', 'debug' => $debug];
                } else {
                    $response = ['success' => false, 'message' => 'Fallo en crear el usuario', 'debug' => $debug];
                }
            }
            break;
        case 'eliminar':
            $id = $_POST['id'] ?? 0;
            if(empty($id)) {
                $response = ['success'=> false, 'message'=> 'No hay id'];
            } else{
                $eliminar = $consultas->eliminarUsuario( $id );
                if($eliminar){
                    $response = ['success'=> true, 'message'=> 'Usuario Eliminado Correctamente'];
                }else{
                    $response = ['success'=> false, 'message'=> 'Fallo en eliminar usuario'];
                }
            }
            break;
        case 'editar':
            $idusuario = $_POST['idusuario'];
            $nombre = $_POST['nombre_usuario'];
            $email = $_POST['email_usuario'];
            $rol = $_POST['rol_idrol'];
            if(empty($idusuario) || empty($nombre) || empty($email) || empty($rol))
            {
                $response = ['success'=> false, 'message'=> "faltan datos"];
            }
            else{
                $editar = $consultas->editarUsuario($idusuario, $nombre, $email, $rol);
                if($editar)
                {
                    $response = ['success'=> true, 'message'=> 'Usuario editado correctamente']; 
                }
                else
                {
                    $response = ['success'=> false, 'message'=> 'Error al editar el usuario'];
                }
            }
            break;
        // Puedes añadir más casos aquí para agregar, editar, eliminar usuarios, etc.

        default:
            $response = ['success' => false, 'message' => 'Invalid action provided.'];
            break;
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];
    error_log("Usuarios Controller Error: " . $e->getMessage());
}

echo json_encode($response);

?> 