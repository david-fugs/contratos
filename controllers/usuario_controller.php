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
        case 'importar_excel': importarExcel(); break;
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
    
    // Validar cédula si se proporciona
    $cedula = !empty($_POST['cedula']) ? $mysqli->real_escape_string($_POST['cedula']) : null;
    if ($cedula) {
        $query = "SELECT id FROM usuarios WHERE cedula = '$cedula'";
        $result = $mysqli->query($query);
        if ($result->num_rows > 0) {
            generarRespuestaJSON(false, 'La cédula ya existe');
            return;
        }
    }
    
    $nombre = $mysqli->real_escape_string($_POST['nombre']);
    $contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
    $tipo_usuario = $mysqli->real_escape_string($_POST['tipo_usuario'] ?? 'usuario');
    $correo = $mysqli->real_escape_string($_POST['correo'] ?? '');
    
    $query = "INSERT INTO usuarios (nombre, cedula, usuario, contrasena, tipo_usuario, correo, estado, fecha_creacion) VALUES ('$nombre', " . ($cedula ? "'$cedula'" : "NULL") . ", '$usuario', '$contrasena', '$tipo_usuario', '$correo', 'activo', NOW())";
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
    $cedula = !empty($_POST['cedula']) ? $mysqli->real_escape_string($_POST['cedula']) : null;
    
    $query = "SELECT id FROM usuarios WHERE usuario = '$usuario' AND id != $id";
    $result = $mysqli->query($query);
    if ($result->num_rows > 0) {
        generarRespuestaJSON(false, 'El nombre de usuario ya existe');
        return;
    }
    
    // Validar cédula si se proporciona
    if ($cedula) {
        $query = "SELECT id FROM usuarios WHERE cedula = '$cedula' AND id != $id";
        $result = $mysqli->query($query);
        if ($result->num_rows > 0) {
            generarRespuestaJSON(false, 'La cédula ya existe');
            return;
        }
    }
    
    $query = "UPDATE usuarios SET nombre = '$nombre', cedula = " . ($cedula ? "'$cedula'" : "NULL") . ", usuario = '$usuario', tipo_usuario = '$tipo_usuario', correo = '$correo'";
    if (!empty($_POST['contrasena'])) {
        $contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
        $query .= ", contrasena = '$contrasena'";
    }
    if (!empty($_POST['estado'])) {
        $estado = $mysqli->real_escape_string($_POST['estado']);
        $query .= ", estado = '$estado'";
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
    $query = "SELECT id, nombre, cedula, usuario, tipo_usuario, correo, estado FROM usuarios WHERE id = $id";
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

function importarExcel() {
    global $mysqli;
    
    // Verificar que se haya subido un archivo
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        generarRespuestaJSON(false, 'No se ha subido ningún archivo o hubo un error en la carga');
        return;
    }
    
    $archivo = $_FILES['archivo'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    
    // Validar extensión
    if (!in_array($extension, ['xlsx', 'xls'])) {
        generarRespuestaJSON(false, 'El archivo debe ser un archivo Excel (.xlsx o .xls)');
        return;
    }
    
    // Cargar PhpSpreadsheet
    require_once __DIR__ . '/../vendor/autoload.php';
    
    try {
        // Leer el archivo Excel
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($archivo['tmp_name']);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        
        $usuariosCreados = 0;
        $errores = [];
        
        // Comenzar desde la fila 2 (asumiendo que fila 1 es encabezado)
        for ($row = 2; $row <= $highestRow; $row++) {
            $nombreCompleto = trim($sheet->getCell('A' . $row)->getValue());
            $cedula = trim($sheet->getCell('B' . $row)->getValue());
            $usuario = trim($sheet->getCell('C' . $row)->getValue());
            $tipoUsuario = strtolower(trim($sheet->getCell('D' . $row)->getValue()));
            
            // Validar datos obligatorios
            if (empty($nombreCompleto) || empty($cedula)) {
                $errores[] = "Fila $row: Faltan datos obligatorios (nombre o cédula)";
                continue;
            }
            
            // Si no hay usuario, usar la cédula
            if (empty($usuario)) {
                $usuario = $cedula;
            }
            
            // Validar tipo de usuario
            if (!in_array($tipoUsuario, ['administrador', 'abogado', 'usuario'])) {
                $tipoUsuario = 'usuario';
            }
            
            // Verificar si el usuario ya existe
            $usuarioEsc = $mysqli->real_escape_string($usuario);
            $cedulaEsc = $mysqli->real_escape_string($cedula);
            
            $query = "SELECT id FROM usuarios WHERE usuario = '$usuarioEsc' OR cedula = '$cedulaEsc'";
            $result = $mysqli->query($query);
            
            if ($result->num_rows > 0) {
                $errores[] = "Fila $row: El usuario o cédula ya existe ($usuario)";
                continue;
            }
            
            // Crear usuario con contraseña = cédula
            $nombreEsc = $mysqli->real_escape_string($nombreCompleto);
            $tipoUsuarioEsc = $mysqli->real_escape_string($tipoUsuario);
            $contrasenaHash = password_hash($cedula, PASSWORD_DEFAULT);
            
            $query = "INSERT INTO usuarios (nombre, cedula, usuario, contrasena, tipo_usuario, estado, fecha_creacion) 
                     VALUES ('$nombreEsc', '$cedulaEsc', '$usuarioEsc', '$contrasenaHash', '$tipoUsuarioEsc', 'activo', NOW())";
            
            if ($mysqli->query($query)) {
                $usuariosCreados++;
            } else {
                $errores[] = "Fila $row: Error al crear usuario - " . $mysqli->error;
            }
        }
        
        // Generar mensaje de respuesta
        $mensaje = "Se crearon $usuariosCreados usuarios exitosamente.";
        if (count($errores) > 0) {
            $mensaje .= "<br><br><strong>Errores encontrados:</strong><br>";
            $mensaje .= implode('<br>', array_slice($errores, 0, 10)); // Mostrar máximo 10 errores
            if (count($errores) > 10) {
                $mensaje .= "<br>... y " . (count($errores) - 10) . " errores más.";
            }
        }
        
        generarRespuestaJSON(true, $mensaje);
        
    } catch (Exception $e) {
        generarRespuestaJSON(false, 'Error al procesar el archivo: ' . $e->getMessage());
    }
}
?>
