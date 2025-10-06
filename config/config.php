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

// Constantes del sistema
define('SITE_NAME', 'Sistema de Gestión de Contratos');
define('SITE_URL', 'http://localhost/contratos/');
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
