<?php
/**
 * Controlador Datos Técnicos del Contrato
 * Solo accesible por administrador_tecnico después de la aprobación
 */

require_once __DIR__ . '/../config/config.php';
verificarSesion();

if (!esAdministradorTecnico() && !esAdministrador()) {
    generarRespuestaJSON(false, 'No tiene permisos para gestionar datos técnicos');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'crear':
        case 'actualizar':
            guardarDatosTecnicos();
            break;
        default:
            generarRespuestaJSON(false, 'Acción no válida');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'obtener':
            obtenerDatosTecnicos();
            break;
        default:
            generarRespuestaJSON(false, 'Acción no válida');
    }
}

function guardarDatosTecnicos() {
    global $mysqli;
    
    if (empty($_POST['contrato_id'])) {
        generarRespuestaJSON(false, 'ID de contrato requerido');
        return;
    }
    
    $contrato_id = intval($_POST['contrato_id']);
    
    // Verificar permisos
    if (!puedeVerContrato($contrato_id)) {
        generarRespuestaJSON(false, 'No tiene permisos para modificar este contrato');
        return;
    }
    
    // Verificar que el contrato esté aprobado o en estado adecuado
    $stmt = $mysqli->prepare("SELECT estado_aprobacion, estado_workflow FROM contratos WHERE id = ?");
    $stmt->bind_param("i", $contrato_id);
    $stmt->execute();
    $contrato = $stmt->get_result()->fetch_assoc();
    
    if (!$contrato) {
        generarRespuestaJSON(false, 'Contrato no encontrado');
        return;
    }
    
    $usuario_id = $_SESSION['usuario_id'];
    
    // Preparar datos
    $campos = [
        'numero_contrato_interno' => $_POST['numero_contrato_interno'] ?? null,
        'numero_contrato_secop' => $_POST['numero_contrato_secop'] ?? null,
        'fecha_firma_contrato' => $_POST['fecha_firma_contrato'] ?? null,
        'plazo_inicial_dias' => !empty($_POST['plazo_inicial_dias']) ? intval($_POST['plazo_inicial_dias']) : null,
        'enlace_secop_ii' => $_POST['enlace_secop_ii'] ?? null,
        'solicitud_arl' => $_POST['solicitud_arl'] ?? null,
        'fecha_aprobacion_arl' => $_POST['fecha_aprobacion_arl'] ?? null,
        'solicitud_rp' => $_POST['solicitud_rp'] ?? null,
        'numero_rp' => $_POST['numero_rp'] ?? null,
        'fecha_rp' => $_POST['fecha_rp'] ?? null,
        'nombre_supervisor' => $_POST['nombre_supervisor'] ?? null,
        'notificacion_supervision' => $_POST['notificacion_supervision'] ?? null,
        'acta_inicio_entregada' => $_POST['acta_inicio_entregada'] ?? null,
        'estado_secop' => $_POST['estado_secop'] ?? null
    ];
    
    // Sanitizar datos
    foreach ($campos as $key => $value) {
        if ($value !== null && !in_array($key, ['plazo_inicial_dias'])) {
            $campos[$key] = $mysqli->real_escape_string($value);
        }
    }
    
    // Verificar si ya existen datos técnicos
    $stmt = $mysqli->prepare("SELECT id FROM datos_tecnicos_contrato WHERE contrato_id = ?");
    $stmt->bind_param("i", $contrato_id);
    $stmt->execute();
    $existe = $stmt->get_result()->num_rows > 0;
    
    if ($existe) {
        // Actualizar
        $query = "UPDATE datos_tecnicos_contrato SET 
            numero_contrato_interno = ?,
            numero_contrato_secop = ?,
            fecha_firma_contrato = ?,
            plazo_inicial_dias = ?,
            enlace_secop_ii = ?,
            solicitud_arl = ?,
            fecha_aprobacion_arl = ?,
            solicitud_rp = ?,
            numero_rp = ?,
            fecha_rp = ?,
            nombre_supervisor = ?,
            notificacion_supervision = ?,
            acta_inicio_entregada = ?,
            estado_secop = ?
            WHERE contrato_id = ?";
        
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param(
            "sssississsssssi",
            $campos['numero_contrato_interno'],
            $campos['numero_contrato_secop'],
            $campos['fecha_firma_contrato'],
            $campos['plazo_inicial_dias'],
            $campos['enlace_secop_ii'],
            $campos['solicitud_arl'],
            $campos['fecha_aprobacion_arl'],
            $campos['solicitud_rp'],
            $campos['numero_rp'],
            $campos['fecha_rp'],
            $campos['nombre_supervisor'],
            $campos['notificacion_supervision'],
            $campos['acta_inicio_entregada'],
            $campos['estado_secop'],
            $contrato_id
        );
    } else {
        // Insertar
        $query = "INSERT INTO datos_tecnicos_contrato (
            contrato_id, numero_contrato_interno, numero_contrato_secop,
            fecha_firma_contrato, plazo_inicial_dias, enlace_secop_ii,
            solicitud_arl, fecha_aprobacion_arl, solicitud_rp,
            numero_rp, fecha_rp, nombre_supervisor,
            notificacion_supervision, acta_inicio_entregada, estado_secop, usuario_creacion
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param(
            "isssississsssssi",
            $contrato_id,
            $campos['numero_contrato_interno'],
            $campos['numero_contrato_secop'],
            $campos['fecha_firma_contrato'],
            $campos['plazo_inicial_dias'],
            $campos['enlace_secop_ii'],
            $campos['solicitud_arl'],
            $campos['fecha_aprobacion_arl'],
            $campos['solicitud_rp'],
            $campos['numero_rp'],
            $campos['fecha_rp'],
            $campos['nombre_supervisor'],
            $campos['notificacion_supervision'],
            $campos['acta_inicio_entregada'],
            $campos['estado_secop'],
            $usuario_id
        );
    }
    
    if ($stmt->execute()) {
        generarRespuestaJSON(true, 'Datos técnicos guardados exitosamente');
    } else {
        generarRespuestaJSON(false, 'Error al guardar datos técnicos: ' . $mysqli->error);
    }
}

function obtenerDatosTecnicos() {
    global $mysqli;
    
    if (empty($_GET['contrato_id'])) {
        generarRespuestaJSON(false, 'ID de contrato requerido');
        return;
    }
    
    $contrato_id = intval($_GET['contrato_id']);
    
    // Verificar permisos
    if (!puedeVerContrato($contrato_id)) {
        generarRespuestaJSON(false, 'No tiene permisos para ver este contrato');
        return;
    }
    
    $stmt = $mysqli->prepare("
        SELECT dt.*, u.nombre as nombre_usuario_creacion
        FROM datos_tecnicos_contrato dt
        LEFT JOIN usuarios u ON dt.usuario_creacion = u.id
        WHERE dt.contrato_id = ?
    ");
    
    $stmt->bind_param("i", $contrato_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        generarRespuestaJSON(true, 'No hay datos técnicos', null);
        return;
    }
    
    $datos = $result->fetch_assoc();
    generarRespuestaJSON(true, 'Datos técnicos obtenidos', $datos);
}
