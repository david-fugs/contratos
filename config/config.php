<?php
/**
 * Configuración General del Sistema
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir conexión
require_once __DIR__ . '/../conexion.php';

// Detectar URL base del sitio automáticamente
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$scriptPath = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'])));
$baseUrl = $protocol . $host . $scriptPath;
// Asegurar que termine con /
if (substr($baseUrl, -1) !== '/') {
    $baseUrl .= '/';
}

// Constantes del sistema
define('SITE_NAME', 'Sistema de Gestión de Contratos');
define('SITE_URL', $baseUrl);
define('UPLOAD_DIR', __DIR__ . '/../uploads/documentos/');
define('UPLOAD_URL', SITE_URL . 'uploads/documentos/');

// Tipos de documentos permitidos
define('TIPOS_DOCUMENTOS', [
    'autorizacion_tratamiento_datos' => 'Autorización de Tratamiento de Datos',
    'autorizacion_consulta_delitos' => 'Autorización Consulta Delitos Sexuales',
    'aportes_novedades' => 'Aportes de Novedades',
    'declaracion_proactiva' => 'Declaración Proactiva',
    'prestacion_servicios' => 'Prestación de Servicios',
    'creacion_usuario' => 'Creación Usuario',
    'propuesta_economica' => 'Propuesta Económica'
]);

// Extensiones de archivo permitidas
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Funciones de utilidad
function estaLogueado() {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

function esAdministrador() {
    return isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'administrador';
}

function esAbogado() {
    return isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'abogado';
}

function esAdministradorTecnico() {
    return isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'administrador_tecnico';
}

function esRevisorDocumentos() {
    return isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'revisor_documentos';
}

function tienePermiso($permiso) {
    global $mysqli;
    if (!isset($_SESSION['tipo_usuario'])) return false;
    
    $tipo_usuario = $_SESSION['tipo_usuario'];
    
    // Verificar si la tabla permisos_rol existe
    $tabla_existe = $mysqli->query("SHOW TABLES LIKE 'permisos_rol'")->num_rows > 0;
    
    if (!$tabla_existe) {
        // Si no existe la tabla, usar permisos básicos por rol
        $permisos_basicos = [
            'administrador' => ['crear_usuarios', 'editar_usuarios', 'eliminar_usuarios', 'crear_contratos', 'editar_contratos', 'eliminar_contratos', 'ver_todos_contratos', 'aprobar_contratos', 'asignar_usuarios'],
            'usuario' => ['crear_contratos', 'editar_propios_contratos', 'ver_propios_contratos'],
            'revisor_documentos' => ['ver_contratos_asignados', 'revisar_documentos', 'comentar_documentos', 'asignar_abogado'],
            'administrador_tecnico' => ['ver_contratos_asignados', 'crear_cdp', 'editar_cdp', 'agregar_datos_tecnicos', 'asignar_abogado', 'ver_documentos'],
            'abogado' => ['ver_contratos_asignados', 'aprobar_contratos', 'rechazar_contratos', 'devolver_contratos', 'cambiar_estado_contrato', 'ver_documentos']
        ];
        return isset($permisos_basicos[$tipo_usuario]) && in_array($permiso, $permisos_basicos[$tipo_usuario]);
    }
    
    $stmt = $mysqli->prepare("SELECT COUNT(*) as tiene FROM permisos_rol WHERE tipo_usuario = ? AND permiso = ? AND activo = 1");
    if (!$stmt) {
        error_log("Error en prepare tienePermiso: " . $mysqli->error);
        return false;
    }
    $stmt->bind_param("ss", $tipo_usuario, $permiso);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['tiene'] > 0;
}

function puedeVerContrato($contrato_id) {
    global $mysqli;
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo_usuario'])) return false;
    
    $usuario_id = $_SESSION['usuario_id'];
    $tipo_usuario = $_SESSION['tipo_usuario'];
    
    // Administradores ven todo
    if ($tipo_usuario === 'administrador') return true;
    
    // Usuarios ven solo los que crearon
    if ($tipo_usuario === 'usuario') {
        $stmt = $mysqli->prepare("SELECT COUNT(*) as puede FROM contratos WHERE id = ? AND usuario_creacion = ?");
        if (!$stmt) {
            error_log("Error en prepare puedeVerContrato usuario: " . $mysqli->error);
            return false;
        }
        $stmt->bind_param("ii", $contrato_id, $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['puede'] > 0;
    }
    
    // Abogados ven los asignados en el campo abogado_asignado
    if ($tipo_usuario === 'abogado') {
        $stmt = $mysqli->prepare("SELECT COUNT(*) as puede FROM contratos WHERE id = ? AND abogado_asignado = ?");
        if (!$stmt) {
            error_log("Error en prepare puedeVerContrato abogado: " . $mysqli->error);
            return false;
        }
        $stmt->bind_param("ii", $contrato_id, $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['puede'] > 0;
    }
    
    // Administrador técnico puede ver contratos en administracion_tecnica
    if ($tipo_usuario === 'administrador_tecnico') {
        $stmt = $mysqli->prepare("SELECT COUNT(*) as puede FROM contratos WHERE id = ? AND estado_workflow = 'administracion_tecnica'");
        if (!$stmt) {
            error_log("Error en prepare puedeVerContrato admin_tecnico: " . $mysqli->error);
            return false;
        }
        $stmt->bind_param("i", $contrato_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        if ($result['puede'] > 0) return true;
    }
    
    // Revisor de documentos y administrador técnico
    // Verificar si la tabla asignaciones_workflow existe
    $tabla_existe = $mysqli->query("SHOW TABLES LIKE 'asignaciones_workflow'")->num_rows > 0;
    
    if ($tabla_existe) {
        // Si existe la tabla, verificar asignaciones
        $stmt = $mysqli->prepare("
            SELECT COUNT(*) as puede 
            FROM asignaciones_workflow 
            WHERE contrato_id = ? AND usuario_asignado = ? AND estado IN ('pendiente', 'en_proceso')
        ");
        if (!$stmt) {
            error_log("Error en prepare puedeVerContrato workflow: " . $mysqli->error);
            return false;
        }
        $stmt->bind_param("ii", $contrato_id, $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['puede'] > 0;
    } else {
        // Si no existe la tabla, permitir ver todos los contratos (modo de transición)
        return true;
    }
}

function obtenerUsuarioActual() {
    return [
        'id' => $_SESSION['usuario_id'] ?? null,
        'nombre' => $_SESSION['nombre'] ?? '',
        'usuario' => $_SESSION['usuario'] ?? '',
        'tipo_usuario' => $_SESSION['tipo_usuario'] ?? ''
    ];
}

function redirigir($url) {
    header("Location: " . SITE_URL . $url);
    exit();
}

function verificarSesion() {
    if (!estaLogueado()) {
        redirigir('index.php');
    }
}

function verificarAdmin() {
    verificarSesion();
    if (!esAdministrador()) {
        redirigir('views/dashboard.php');
    }
}

function sanitizar($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitizar($value);
        }
        return $data;
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function formatearFecha($fecha) {
    if (empty($fecha)) return '';
    return date('d/m/Y', strtotime($fecha));
}

function formatearFechaHora($fecha) {
    if (empty($fecha)) return '';
    return date('d/m/Y H:i:s', strtotime($fecha));
}

function formatearTexto($texto) {
    if (empty($texto)) return '';
    return ucwords(str_replace('_', ' ', strtolower($texto)));
}

function generarRespuestaJSON($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// Crear directorio de uploads si no existe
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}
?>
