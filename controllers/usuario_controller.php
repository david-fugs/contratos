<?php
require_once __DIR__ . '/../config/config.php';
verificarSesion();

$accionesPublicas = ['listar_abogados'];
$action = $_REQUEST['action'] ?? '';

if (!in_array($action, $accionesPublicas)) {
    verificarAdmin();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    switch ($action) {
        case 'crear': crearUsuario(); break;
        case 'editar': editarUsuario(); break;
        case 'eliminar': eliminarUsuario(); break;
        default: generarRespuestaJSON(false, 'Acción no válida');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'obtener': obtenerUsuario(); break;
        case 'listar_abogados': listarAbogados(); break;
        default: generarRespuestaJSON(false, 'Acción no válida');
    }
}

function crearUsuario() {
    global $mysqli;
    if (empty($_POST['nombre']) || empty($_POST['usuario']) || empty($_POST['contrasena'])) {
        generarRespuestaJSON(false, 'Todos los campos son requeridos');
        return;
    }
    $usuario = $mysqli->real_escape_string($_POST['usuario']);
    $query = "SELECT id FROM usuarios WHERE usuario = '$usuario'";
    $result = $mysqli->query($query);
    if ($result->num_rows > 0) {
        generarRespuestaJSON(false, 'El nombre de usuario ya existe');
        return;
    }
    $nombre = $mysqli->real_escape_string($_POST['nombre']);
    $contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
    $tipo_usuario = $mysqli->real_escape_string($_POST['tipo_usuario'] ?? 'usuario');
    $correo = $mysqli->real_escape_string($_POST['correo'] ?? '');
    $query = "INSERT INTO usuarios (nombre, usuario, contrasena, tipo_usuario, correo, estado, fecha_creacion) VALUES ('$nombre', '$usuario', '$contrasena', '$tipo_usuario', '$correo', 'activo', NOW())";
    if ($mysqli->query($query)) {
        generarRespuestaJSON(true, 'Usuario creado exitosamente');
    } else {
        generarRespuestaJSON(false, 'Error al crear el usuario: ' . $mysqli->error);
    }
}

function editarUsuario() {
    global $mysqli;
    if (empty($_POST['id']) || empty($_POST['nombre']) || empty($_POST['usuario'])) {
        generarRespuestaJSON(false, 'Todos los campos son requeridos');
        return;
    }
    $id = intval($_POST['id']);
    $nombre = $mysqli->real_escape_string($_POST['nombre']);
    $usuario = $mysqli->real_escape_string($_POST['usuario']);
    $tipo_usuario = $mysqli->real_escape_string($_POST['tipo_usuario'] ?? 'usuario');
    $correo = $mysqli->real_escape_string($_POST['correo'] ?? '');
    $query = "SELECT id FROM usuarios WHERE usuario = '$usuario' AND id != $id";
    $result = $mysqli->query($query);
    if ($result->num_rows > 0) {
        generarRespuestaJSON(false, 'El nombre de usuario ya existe');
        return;
    }
    $query = "UPDATE usuarios SET nombre = '$nombre', usuario = '$usuario', tipo_usuario = '$tipo_usuario', correo = '$correo'";
    if (!empty($_POST['contrasena'])) {
        $contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
        $query .= ", contrasena = '$contrasena'";
    }
    $query .= " WHERE id = $id";
    if ($mysqli->query($query)) {
        generarRespuestaJSON(true, 'Usuario actualizado exitosamente');
    } else {
        generarRespuestaJSON(false, 'Error al actualizar el usuario: ' . $mysqli->error);
    }
}

function eliminarUsuario() {
    global $mysqli;
    if (empty($_POST['id'])) {
        generarRespuestaJSON(false, 'ID de usuario requerido');
        return;
    }
    $id = intval($_POST['id']);
    if ($id == $_SESSION['usuario_id']) {
        generarRespuestaJSON(false, 'No puedes eliminar tu propio usuario');
        return;
    }
    $query = "UPDATE usuarios SET estado = 'inactivo' WHERE id = $id";
    if ($mysqli->query($query)) {
        generarRespuestaJSON(true, 'Usuario eliminado exitosamente');
    } else {
        generarRespuestaJSON(false, 'Error al eliminar el usuario: ' . $mysqli->error);
    }
}

function obtenerUsuario() {
    global $mysqli;
    if (empty($_GET['id'])) {
        generarRespuestaJSON(false, 'ID de usuario requerido');
        return;
    }
    $id = intval($_GET['id']);
    $query = "SELECT id, nombre, usuario, tipo_usuario, correo, estado FROM usuarios WHERE id = $id";
    $result = $mysqli->query($query);
    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
        generarRespuestaJSON(true, 'Usuario obtenido exitosamente', $usuario);
    } else {
        generarRespuestaJSON(false, 'Usuario no encontrado');
    }
}

function listarAbogados() {
    global $mysqli;
    $query = "SELECT id, nombre, usuario FROM usuarios WHERE tipo_usuario = 'abogado' AND estado = 'activo' ORDER BY nombre";
    $result = $mysqli->query($query);
    $abogados = [];
    while ($row = $result->fetch_assoc()) {
        $abogados[] = $row;
    }
    generarRespuestaJSON(true, 'Abogados obtenidos exitosamente', $abogados);
}
?>
