<?php
require_once '../../config/admin_controller_auth.php';
require_once '../../models/consultas.php';
require_once '../../models/consultasCategoria.php';

// Verificar que el usuario sea administrador
verificarAdminController();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$consultasGenerales = new ConsultasMesero();
$consultas = new consultasCategoria();

try {
    switch ($action) {
        case 'getAllCategorias':
            $categorias = $consultasGenerales->traerCategorias();
            echo json_encode(['success' => true, 'data' => $categorias]);
            break;
        case 'crear_categoria':
            $nombre_categoria = $_POST['nombre_categoria'] ?? '';
            if (empty($nombre_categoria)) {
                echo json_encode(['success' => false, 'message' => 'Nombre de categoría es requerido']);
                break;
            }
            $resultado = $consultas->crearCategoria($nombre_categoria);
            if ($resultado) {
                echo json_encode(['success' => true, 'message' => 'Categoría creada exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al crear la categoría']);
            }
            break;
        case 'editar_categoria':
            $categoriaId = $_POST['categoriaId'];
            $nombre_categoria = $_POST['nombre_categoria'];
            if(empty($categoriaId) || empty($nombre_categoria))
            {
                echo json_encode(['success'=> false, 'message'=> "Faltan datos"]);
            }
            else
            {
                $editar = $consultas->actualizarCategoria($nombre_categoria,$categoriaId);
                if ($editar) {
                    echo json_encode(['success'=> true, 'message'=> 'Usuario editado correctamente']);
                } else {
                    echo json_encode(['success'=> false, 'message'=> 'Error al editar el usuario']);
                }
            }
            break;
        case 'inhabilitar_categoria':
            $id = $_POST["categoriaId"];
            if (empty($id)) {
                $response = ['success' => false, 'message' => 'No hay ID'];
            }
             else {
                $eliminar = $consultas->inhabilitarCategoria($id);
                if ($eliminar) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Usuario eliminado correctamente'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Fallo en eliminar usuario'
                    ]);
                }
            }
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    } 
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
