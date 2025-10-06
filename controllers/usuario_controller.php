<?php
/**
 * Controlador de Usuarios
 */

require_once __DIR__ . '/../config/config.php';
verificarAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'crear':
            crearUsuario();
            break;
        case 'editar':
            editarUsuario();
            break;
        case 'eliminar':
            eliminarUsuario();
            break;
        default:
            generarRespuestaJSON(false, 'Acción no válida');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'obtener':
            obtenerUsuario();
            break;
        default:
            generarRespuestaJSON(false, 'Acción no válida');
    }
}

function crearUsuario() {
    global $mysqli;

    $nombre = sanitizar($_POST['nombre'] ?? '');
    $usuario = sanitizar($_POST['usuario'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $tipo_usuario = sanitizar($_POST['tipo_usuario'] ?? '');

    // Validaciones
    if (empty($nombre) || empty($usuario) || empty($contrasena) || empty($tipo_usuario)) {
        generarRespuestaJSON(false, 'Todos los campos son obligatorios');
    }

    if (strlen($contrasena) < 6) {
        generarRespuestaJSON(false, 'La contraseña debe tener al menos 6 caracteres');
    }

    // Verificar si el usuario ya existe
    $stmt = $mysqli->prepare("SELECT id FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        generarRespuestaJSON(false, 'El nombre de usuario ya está en uso');
    }
    $stmt->close();

    // Encriptar contraseña
    $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

    // Insertar usuario
    $stmt = $mysqli->prepare("INSERT INTO usuarios (nombre, usuario, contrasena, tipo_usuario) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nombre, $usuario, $contrasena_hash, $tipo_usuario);

    if ($stmt->execute()) {
        $usuario_id = $stmt->insert_id;

        // Registrar auditoría
        $datos_nuevos = json_encode([
            'nombre' => $nombre,
            'usuario' => $usuario,
            'tipo_usuario' => $tipo_usuario
        ]);

        $stmt_audit = $mysqli->prepare("INSERT INTO auditoria (tabla, registro_id, accion, usuario_id, datos_nuevos) VALUES ('usuarios', ?, 'crear', ?, ?)");
        $stmt_audit->bind_param("iis", $usuario_id, $_SESSION['usuario_id'], $datos_nuevos);
        $stmt_audit->execute();
        $stmt_audit->close();

        generarRespuestaJSON(true, 'Usuario creado exitosamente', ['id' => $usuario_id]);
    } else {
        generarRespuestaJSON(false, 'Error al crear el usuario: ' . $mysqli->error);
    }

    $stmt->close();
}

function editarUsuario() {
    global $mysqli;

    $id = intval($_POST['id'] ?? 0);
    $nombre = sanitizar($_POST['nombre'] ?? '');
    $usuario = sanitizar($_POST['usuario'] ?? '');
    $tipo_usuario = sanitizar($_POST['tipo_usuario'] ?? '');
    $estado = sanitizar($_POST['estado'] ?? '');
    $nueva_contrasena = $_POST['nueva_contrasena'] ?? '';

    // Validaciones
    if (empty($nombre) || empty($usuario) || empty($tipo_usuario) || empty($estado)) {
        generarRespuestaJSON(false, 'Todos los campos son obligatorios');
    }

    // Obtener datos anteriores para auditoría
    $stmt_old = $mysqli->prepare("SELECT nombre, usuario, tipo_usuario, estado FROM usuarios WHERE id = ?");
    $stmt_old->bind_param("i", $id);
    $stmt_old->execute();
    $result_old = $stmt_old->get_result();
    $datos_anteriores = json_encode($result_old->fetch_assoc());
    $stmt_old->close();

    // Verificar si el usuario ya existe (excepto el actual)
    $stmt = $mysqli->prepare("SELECT id FROM usuarios WHERE usuario = ? AND id != ?");
    $stmt->bind_param("si", $usuario, $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        generarRespuestaJSON(false, 'El nombre de usuario ya está en uso por otro usuario');
    }
    $stmt->close();

    // Actualizar usuario
    if (!empty($nueva_contrasena)) {
        if (strlen($nueva_contrasena) < 6) {
            generarRespuestaJSON(false, 'La contraseña debe tener al menos 6 caracteres');
        }

        $contrasena_hash = password_hash($nueva_contrasena, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("UPDATE usuarios SET nombre = ?, usuario = ?, contrasena = ?, tipo_usuario = ?, estado = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $nombre, $usuario, $contrasena_hash, $tipo_usuario, $estado, $id);
    } else {
        $stmt = $mysqli->prepare("UPDATE usuarios SET nombre = ?, usuario = ?, tipo_usuario = ?, estado = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $nombre, $usuario, $tipo_usuario, $estado, $id);
    }

    if ($stmt->execute()) {
        // Registrar auditoría
        $datos_nuevos = json_encode([
            'nombre' => $nombre,
            'usuario' => $usuario,
            'tipo_usuario' => $tipo_usuario,
            'estado' => $estado
        ]);

        $stmt_audit = $mysqli->prepare("INSERT INTO auditoria (tabla, registro_id, accion, usuario_id, datos_anteriores, datos_nuevos) VALUES ('usuarios', ?, 'editar', ?, ?, ?)");
        $stmt_audit->bind_param("iiss", $id, $_SESSION['usuario_id'], $datos_anteriores, $datos_nuevos);
        $stmt_audit->execute();
        $stmt_audit->close();

        generarRespuestaJSON(true, 'Usuario actualizado exitosamente');
    } else {
        generarRespuestaJSON(false, 'Error al actualizar el usuario: ' . $mysqli->error);
    }

    $stmt->close();
}

function eliminarUsuario() {
    global $mysqli;

    $id = intval($_POST['id'] ?? 0);

    // No permitir eliminar el propio usuario
    if ($id == $_SESSION['usuario_id']) {
        generarRespuestaJSON(false, 'No puede eliminar su propio usuario');
    }

    // Obtener datos para auditoría
    $stmt_old = $mysqli->prepare("SELECT nombre, usuario, tipo_usuario FROM usuarios WHERE id = ?");
    $stmt_old->bind_param("i", $id);
    $stmt_old->execute();
    $result_old = $stmt_old->get_result();
    $datos_anteriores = json_encode($result_old->fetch_assoc());
    $stmt_old->close();

    // Eliminar usuario
    $stmt = $mysqli->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Registrar auditoría
        $stmt_audit = $mysqli->prepare("INSERT INTO auditoria (tabla, registro_id, accion, usuario_id, datos_anteriores) VALUES ('usuarios', ?, 'eliminar', ?, ?)");
        $stmt_audit->bind_param("iis", $id, $_SESSION['usuario_id'], $datos_anteriores);
        $stmt_audit->execute();
        $stmt_audit->close();

        generarRespuestaJSON(true, 'Usuario eliminado exitosamente');
    } else {
        generarRespuestaJSON(false, 'Error al eliminar el usuario: ' . $mysqli->error);
    }

    $stmt->close();
}

function obtenerUsuario() {
    global $mysqli;

    $id = intval($_GET['id'] ?? 0);

    $stmt = $mysqli->prepare("SELECT id, nombre, usuario, tipo_usuario, estado FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        generarRespuestaJSON(true, 'Usuario obtenido exitosamente', $usuario);
    } else {
        generarRespuestaJSON(false, 'Usuario no encontrado');
    }

    $stmt->close();
}
?>
