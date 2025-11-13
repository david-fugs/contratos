<?php
/**
 * Controlador CDP - Certificado de Disponibilidad Presupuestal
 * Solo accesible por administrador_tecnico
 */

require_once __DIR__ . '/../config/config.php';
verificarSesion();

// Verificar que solo administrador técnico, abogado o administrador puedan acceder
if (!esAdministradorTecnico() && !esAbogado() && !esAdministrador()) {
    generarRespuestaJSON(false, 'No tiene permisos para gestionar CDP');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'crear':
            crearCDP();
            break;
        case 'editar':
            editarCDP();
            break;
        case 'eliminar':
            eliminarCDP();
            break;
        case 'aprobar_cdp':
            aprobarCDP();
            break;
        default:
            generarRespuestaJSON(false, 'Acción no válida');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'obtener':
            obtenerCDP();
            break;
        case 'listar':
            listarCDP();
            break;
        case 'obtener_cdp':
            obtenerCDPDetalle();
            break;
        default:
            generarRespuestaJSON(false, 'Acción no válida');
    }
}

function crearCDP() {
    global $mysqli;
    
    // Validar campos requeridos
    $campos_requeridos = ['contrato_id', 'fecha_cdp', 'rubro', 'nombre_rubro', 'valor', 'dependencia'];
    foreach ($campos_requeridos as $campo) {
        if (empty($_POST[$campo])) {
            generarRespuestaJSON(false, "El campo $campo es requerido");
            return;
        }
    }
    
    $contrato_id = intval($_POST['contrato_id']);
    
    // Verificar que el contrato existe y que el usuario tiene acceso
    if (!puedeVerContrato($contrato_id)) {
        generarRespuestaJSON(false, 'No tiene permisos para agregar CDP a este contrato');
        return;
    }
    
    $fecha_cdp = $_POST['fecha_cdp'];
    $rubro = $mysqli->real_escape_string($_POST['rubro']);
    $nombre_rubro = $mysqli->real_escape_string($_POST['nombre_rubro']);
    $valor = floatval(str_replace([',', '$'], '', $_POST['valor']));
    $objeto = !empty($_POST['objeto']) ? $mysqli->real_escape_string($_POST['objeto']) : null;
    $numero_proceso = !empty($_POST['numero_proceso']) ? $mysqli->real_escape_string($_POST['numero_proceso']) : null;
    $dependencia = $mysqli->real_escape_string($_POST['dependencia']);
    $usuario_id = $_SESSION['usuario_id'];
    
    // Procesar archivo PDF si se cargó
    $archivo_pdf = null;
    if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/cdp/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $extension = strtolower(pathinfo($_FILES['archivo_pdf']['name'], PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            generarRespuestaJSON(false, 'Solo se permiten archivos PDF');
            return;
        }
        
        $nombre_archivo = 'CDP_' . $contrato_id . '_' . time() . '.pdf';
        $ruta_completa = $upload_dir . $nombre_archivo;
        
        if (move_uploaded_file($_FILES['archivo_pdf']['tmp_name'], $ruta_completa)) {
            $archivo_pdf = $nombre_archivo;
        } else {
            generarRespuestaJSON(false, 'Error al subir el archivo PDF');
            return;
        }
    }
    
    // Insertar CDP
    $stmt = $mysqli->prepare("
        INSERT INTO cdp 
        (contrato_id, fecha_cdp, rubro, nombre_rubro, valor, objeto, archivo_pdf, numero_proceso, dependencia, usuario_creacion)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param(
        "isssdssssi",
        $contrato_id,
        $fecha_cdp,
        $rubro,
        $nombre_rubro,
        $valor,
        $objeto,
        $archivo_pdf,
        $numero_proceso,
        $dependencia,
        $usuario_id
    );
    
    if ($stmt->execute()) {
        $cdp_id = $mysqli->insert_id;
        
        // Registrar en el log de actividades (si existe tabla)
        registrarActividad($contrato_id, 'cdp_creado', "CDP creado con ID: $cdp_id");
        
        generarRespuestaJSON(true, 'CDP creado exitosamente', ['id' => $cdp_id]);
    } else {
        generarRespuestaJSON(false, 'Error al crear CDP: ' . $mysqli->error);
    }
}

function editarCDP() {
    global $mysqli;
    
    if (empty($_POST['id'])) {
        generarRespuestaJSON(false, 'ID de CDP requerido');
        return;
    }
    
    $cdp_id = intval($_POST['id']);
    
    // Verificar que el CDP existe
    $stmt = $mysqli->prepare("SELECT contrato_id FROM cdp WHERE id = ?");
    $stmt->bind_param("i", $cdp_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        generarRespuestaJSON(false, 'CDP no encontrado');
        return;
    }
    
    $cdp = $result->fetch_assoc();
    
    // Verificar permisos sobre el contrato
    if (!puedeVerContrato($cdp['contrato_id'])) {
        generarRespuestaJSON(false, 'No tiene permisos para editar este CDP');
        return;
    }
    
    $fecha_cdp = $_POST['fecha_cdp'];
    $rubro = $mysqli->real_escape_string($_POST['rubro']);
    $nombre_rubro = $mysqli->real_escape_string($_POST['nombre_rubro']);
    $valor = floatval(str_replace([',', '$'], '', $_POST['valor']));
    $objeto = !empty($_POST['objeto']) ? $mysqli->real_escape_string($_POST['objeto']) : null;
    $numero_proceso = !empty($_POST['numero_proceso']) ? $mysqli->real_escape_string($_POST['numero_proceso']) : null;
    $dependencia = $mysqli->real_escape_string($_POST['dependencia']);
    
    // Construir query de actualización
    $query = "UPDATE cdp SET 
        fecha_cdp = ?, 
        rubro = ?, 
        nombre_rubro = ?, 
        valor = ?, 
        objeto = ?, 
        numero_proceso = ?, 
        dependencia = ?";
    
    $params = [$fecha_cdp, $rubro, $nombre_rubro, $valor, $objeto, $numero_proceso, $dependencia];
    $types = "sssdsss";
    
    // Procesar nuevo archivo si se cargó
    if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/cdp/';
        $extension = strtolower(pathinfo($_FILES['archivo_pdf']['name'], PATHINFO_EXTENSION));
        
        if ($extension !== 'pdf') {
            generarRespuestaJSON(false, 'Solo se permiten archivos PDF');
            return;
        }
        
        $nombre_archivo = 'CDP_' . $cdp['contrato_id'] . '_' . time() . '.pdf';
        $ruta_completa = $upload_dir . $nombre_archivo;
        
        if (move_uploaded_file($_FILES['archivo_pdf']['tmp_name'], $ruta_completa)) {
            $query .= ", archivo_pdf = ?";
            $params[] = $nombre_archivo;
            $types .= "s";
        }
    }
    
    $query .= " WHERE id = ?";
    $params[] = $cdp_id;
    $types .= "i";
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        registrarActividad($cdp['contrato_id'], 'cdp_editado', "CDP editado con ID: $cdp_id");
        generarRespuestaJSON(true, 'CDP actualizado exitosamente');
    } else {
        generarRespuestaJSON(false, 'Error al actualizar CDP: ' . $mysqli->error);
    }
}

function eliminarCDP() {
    global $mysqli;
    
    if (empty($_POST['id'])) {
        generarRespuestaJSON(false, 'ID de CDP requerido');
        return;
    }
    
    $cdp_id = intval($_POST['id']);
    
    // Verificar que el CDP existe
    $stmt = $mysqli->prepare("SELECT contrato_id, archivo_pdf FROM cdp WHERE id = ?");
    $stmt->bind_param("i", $cdp_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        generarRespuestaJSON(false, 'CDP no encontrado');
        return;
    }
    
    $cdp = $result->fetch_assoc();
    
    // Verificar permisos
    if (!esAdministrador()) {
        generarRespuestaJSON(false, 'Solo administradores pueden eliminar CDP');
        return;
    }
    
    // Eliminar archivo si existe
    if ($cdp['archivo_pdf']) {
        $ruta_archivo = __DIR__ . '/../uploads/cdp/' . $cdp['archivo_pdf'];
        if (file_exists($ruta_archivo)) {
            unlink($ruta_archivo);
        }
    }
    
    // Marcar como inactivo en lugar de eliminar
    $stmt = $mysqli->prepare("UPDATE cdp SET estado = 'inactivo' WHERE id = ?");
    $stmt->bind_param("i", $cdp_id);
    
    if ($stmt->execute()) {
        registrarActividad($cdp['contrato_id'], 'cdp_eliminado', "CDP eliminado con ID: $cdp_id");
        generarRespuestaJSON(true, 'CDP eliminado exitosamente');
    } else {
        generarRespuestaJSON(false, 'Error al eliminar CDP: ' . $mysqli->error);
    }
}

function obtenerCDP() {
    global $mysqli;
    
    if (empty($_GET['id'])) {
        generarRespuestaJSON(false, 'ID de CDP requerido');
        return;
    }
    
    $cdp_id = intval($_GET['id']);
    
    $stmt = $mysqli->prepare("
        SELECT cdp.*, u.nombre as nombre_usuario_creacion
        FROM cdp
        LEFT JOIN usuarios u ON cdp.usuario_creacion = u.id
        WHERE cdp.id = ? AND cdp.estado = 'activo'
    ");
    
    $stmt->bind_param("i", $cdp_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        generarRespuestaJSON(false, 'CDP no encontrado');
        return;
    }
    
    $cdp = $result->fetch_assoc();
    
    // Verificar permisos
    if (!puedeVerContrato($cdp['contrato_id'])) {
        generarRespuestaJSON(false, 'No tiene permisos para ver este CDP');
        return;
    }
    
    generarRespuestaJSON(true, 'CDP obtenido exitosamente', $cdp);
}

function listarCDP() {
    global $mysqli;
    
    if (empty($_GET['contrato_id'])) {
        generarRespuestaJSON(false, 'ID de contrato requerido');
        return;
    }
    
    $contrato_id = intval($_GET['contrato_id']);
    
    // Verificar permisos
    if (!puedeVerContrato($contrato_id)) {
        generarRespuestaJSON(false, 'No tiene permisos para ver los CDP de este contrato');
        return;
    }
    
    $stmt = $mysqli->prepare("
        SELECT cdp.*, u.nombre as nombre_usuario_creacion
        FROM cdp
        LEFT JOIN usuarios u ON cdp.usuario_creacion = u.id
        WHERE cdp.contrato_id = ? AND cdp.estado = 'activo'
        ORDER BY cdp.fecha_creacion DESC
    ");
    
    $stmt->bind_param("i", $contrato_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cdps = [];
    while ($row = $result->fetch_assoc()) {
        $cdps[] = $row;
    }
    
    generarRespuestaJSON(true, 'CDP listados exitosamente', $cdps);
}

function registrarActividad($contrato_id, $tipo, $descripcion) {
    global $mysqli;
    
    // Verificar si existe tabla de actividades
    $result = $mysqli->query("SHOW TABLES LIKE 'actividades_contrato'");
    if ($result->num_rows === 0) return;
    
    $usuario_id = $_SESSION['usuario_id'];
    $stmt = $mysqli->prepare("
        INSERT INTO actividades_contrato (contrato_id, usuario_id, tipo_actividad, descripcion)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("iiss", $contrato_id, $usuario_id, $tipo, $descripcion);
    $stmt->execute();
}

function aprobarCDP() {
    global $mysqli;
    
    // Verificar que sea abogado
    if (!esAbogado() && !esAdministrador()) {
        generarRespuestaJSON(false, 'Solo los abogados pueden aprobar CDP');
        return;
    }
    
    $cdp_id = intval($_POST['cdp_id'] ?? 0);
    
    if (!$cdp_id) {
        generarRespuestaJSON(false, 'ID de CDP no proporcionado');
        return;
    }
    
    $usuario_id = $_SESSION['usuario_id'];
    
    $stmt = $mysqli->prepare("
        UPDATE cdp 
        SET estado_aprobacion = 'aprobado',
            aprobado_por = ?,
            fecha_aprobacion = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("ii", $usuario_id, $cdp_id);
    
    if ($stmt->execute()) {
        generarRespuestaJSON(true, 'CDP aprobado exitosamente');
    } else {
        generarRespuestaJSON(false, 'Error al aprobar CDP: ' . $mysqli->error);
    }
}

function obtenerCDPDetalle() {
    global $mysqli;
    
    $cdp_id = intval($_GET['cdp_id'] ?? 0);
    
    if (!$cdp_id) {
        generarRespuestaJSON(false, 'ID de CDP no proporcionado');
        return;
    }
    
    $stmt = $mysqli->prepare("
        SELECT c.*, u.nombre as nombre_usuario_creacion,
               a.nombre as nombre_aprobador
        FROM cdp c
        LEFT JOIN usuarios u ON c.usuario_creacion = u.id
        LEFT JOIN usuarios a ON c.aprobado_por = a.id
        WHERE c.id = ?
    ");
    $stmt->bind_param("i", $cdp_id);
    $stmt->execute();
    $cdp = $stmt->get_result()->fetch_assoc();
    
    if ($cdp) {
        generarRespuestaJSON(true, 'CDP obtenido exitosamente', $cdp);
    } else {
        generarRespuestaJSON(false, 'CDP no encontrado');
    }
}
