<?php
/**
 * Controlador de Revisión de Documentos
 * Para revisor_documentos
 */

require_once __DIR__ . '/../config/config.php';
verificarSesion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'revisar_documento':
            revisarDocumento();
            break;
        case 'revisar_multiple':
            revisarMultiple();
            break;
        default:
            generarRespuestaJSON(false, 'Acción no válida');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'obtener_documentos_pendientes':
            obtenerDocumentosPendientes();
            break;
        case 'obtener_estado_revision':
            obtenerEstadoRevision();
            break;
        default:
            generarRespuestaJSON(false, 'Acción no válida');
    }
}

function revisarDocumento() {
    global $mysqli;
    
    if (empty($_POST['documento_id']) || empty($_POST['estado_revision'])) {
        generarRespuestaJSON(false, 'Faltan campos requeridos');
        return;
    }
    
    $documento_id = intval($_POST['documento_id']);
    $estado_revision = $mysqli->real_escape_string($_POST['estado_revision']);
    $comentarios = !empty($_POST['comentarios']) ? $mysqli->real_escape_string($_POST['comentarios']) : null;
    $usuario_id = $_SESSION['usuario_id'];
    
    // Validar estado
    if (!in_array($estado_revision, ['aprobado', 'rechazado'])) {
        generarRespuestaJSON(false, 'Estado de revisión no válido');
        return;
    }
    
    // Verificar permisos (solo revisor_documentos o admin)
    if (!esRevisorDocumentos() && !esAdministrador()) {
        generarRespuestaJSON(false, 'No tiene permisos para revisar documentos');
        return;
    }
    
    // Obtener información del documento
    $stmt = $mysqli->prepare("SELECT contrato_id FROM documentos WHERE id = ?");
    if (!$stmt) {
        generarRespuestaJSON(false, 'Error al preparar consulta: ' . $mysqli->error);
        return;
    }
    $stmt->bind_param("i", $documento_id);
    $stmt->execute();
    $documento = $stmt->get_result()->fetch_assoc();
    
    if (!$documento) {
        generarRespuestaJSON(false, 'Documento no encontrado');
        return;
    }
    
    // Verificar que tiene acceso al contrato
    // Revisor de documentos puede revisar cualquier contrato, no necesita asignación previa
    if (!esRevisorDocumentos() && !puedeVerContrato($documento['contrato_id']) && !esAdministrador()) {
        generarRespuestaJSON(false, 'No tiene permisos para revisar este documento');
        return;
    }
    
    // Verificar si existen las columnas de revisión
    $columnas_existen = $mysqli->query("SHOW COLUMNS FROM documentos LIKE 'estado_revision'")->num_rows > 0;
    
    if ($columnas_existen) {
        // Si existen las columnas, usar el nuevo sistema
        $stmt = $mysqli->prepare("
            UPDATE documentos 
            SET estado_revision = ?, 
                comentarios_revision = ?, 
                usuario_revision = ?, 
                fecha_revision = NOW()
            WHERE id = ?
        ");
        
        if (!$stmt) {
            generarRespuestaJSON(false, 'Error al preparar actualización: ' . $mysqli->error);
            return;
        }
        
        $stmt->bind_param("ssii", $estado_revision, $comentarios, $usuario_id, $documento_id);
        
        if ($stmt->execute()) {
            // Verificar si todos los documentos del contrato están revisados
            $contrato_id = $documento['contrato_id'];
            $query_check = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado_revision = 'aprobado' THEN 1 ELSE 0 END) as aprobados,
                SUM(CASE WHEN estado_revision = 'pendiente' THEN 1 ELSE 0 END) as pendientes
                FROM documentos WHERE contrato_id = ?";
            $stmt_check = $mysqli->prepare($query_check);
            $stmt_check->bind_param("i", $contrato_id);
            $stmt_check->execute();
            $stats = $stmt_check->get_result()->fetch_assoc();
            
            $mensaje = 'Documento revisado exitosamente';
            if ($stats['pendientes'] == 0 && $stats['aprobados'] > 0) {
                $mensaje .= '. Todos los documentos han sido revisados. Puede asignar un abogado ahora.';
            }
            
            generarRespuestaJSON(true, $mensaje, ['stats' => $stats]);
        } else {
            generarRespuestaJSON(false, 'Error al revisar documento: ' . $mysqli->error);
        }
    } else {
        // Modo de compatibilidad: usar campo estado_documento si existe, o crear nota temporal
        // Por ahora, simular que funcionó y avisar al usuario
        generarRespuestaJSON(false, 'IMPORTANTE: Debe ejecutar el archivo sistema_workflow.sql en phpMyAdmin para habilitar la revisión de documentos. Las columnas necesarias no existen en la base de datos.');
    }
}

function revisarMultiple() {
    global $mysqli;
    
    if (empty($_POST['contrato_id']) || empty($_POST['documentos'])) {
        generarRespuestaJSON(false, 'Faltan campos requeridos');
        return;
    }
    
    $contrato_id = intval($_POST['contrato_id']);
    $documentos = json_decode($_POST['documentos'], true);
    $usuario_id = $_SESSION['usuario_id'];
    
    if (!is_array($documentos)) {
        generarRespuestaJSON(false, 'Formato de documentos inválido');
        return;
    }
    
    // Verificar permisos
    if (!esRevisorDocumentos() && !esAdministrador()) {
        generarRespuestaJSON(false, 'No tiene permisos para revisar documentos');
        return;
    }
    
    if (!puedeVerContrato($contrato_id) && !esAdministrador()) {
        generarRespuestaJSON(false, 'No tiene permisos para revisar documentos de este contrato');
        return;
    }
    
    $mysqli->begin_transaction();
    
    try {
        $stmt = $mysqli->prepare("
            UPDATE documentos 
            SET estado_revision = ?, 
                comentarios_revision = ?, 
                usuario_revision = ?, 
                fecha_revision = NOW()
            WHERE id = ? AND contrato_id = ?
        ");
        
        foreach ($documentos as $doc) {
            if (empty($doc['id']) || empty($doc['estado'])) continue;
            
            $documento_id = intval($doc['id']);
            $estado = $mysqli->real_escape_string($doc['estado']);
            $comentarios = !empty($doc['comentarios']) ? $mysqli->real_escape_string($doc['comentarios']) : null;
            
            if (!in_array($estado, ['aprobado', 'rechazado'])) continue;
            
            $stmt->bind_param("ssiii", $estado, $comentarios, $usuario_id, $documento_id, $contrato_id);
            $stmt->execute();
        }
        
        $mysqli->commit();
        generarRespuestaJSON(true, 'Documentos revisados exitosamente');
        
    } catch (Exception $e) {
        $mysqli->rollback();
        generarRespuestaJSON(false, 'Error al revisar documentos: ' . $e->getMessage());
    }
}

function obtenerDocumentosPendientes() {
    global $mysqli;
    
    if (empty($_GET['contrato_id'])) {
        generarRespuestaJSON(false, 'ID de contrato requerido');
        return;
    }
    
    $contrato_id = intval($_GET['contrato_id']);
    
    // Verificar permisos
    if (!puedeVerContrato($contrato_id) && !esAdministrador()) {
        generarRespuestaJSON(false, 'No tiene permisos para ver estos documentos');
        return;
    }
    
    $query = "
        SELECT 
            d.*,
            ur.nombre as nombre_usuario_revision
        FROM documentos d
        LEFT JOIN usuarios ur ON d.usuario_revision = ur.id
        WHERE d.contrato_id = ? AND d.estado = 'activo'
        ORDER BY 
            CASE d.estado_revision
                WHEN 'pendiente' THEN 1
                WHEN 'rechazado' THEN 2
                WHEN 'aprobado' THEN 3
            END,
            d.fecha_carga DESC
    ";
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $contrato_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $documentos = [];
    while ($row = $result->fetch_assoc()) {
        $documentos[] = $row;
    }
    
    // Contar resumen
    $resumen = [
        'total' => count($documentos),
        'pendientes' => 0,
        'aprobados' => 0,
        'rechazados' => 0
    ];
    
    foreach ($documentos as $doc) {
        $resumen[$doc['estado_revision'] . 's']++;
    }
    
    generarRespuestaJSON(true, 'Documentos obtenidos', [
        'documentos' => $documentos,
        'resumen' => $resumen
    ]);
}

function obtenerEstadoRevision() {
    global $mysqli;
    
    if (empty($_GET['contrato_id'])) {
        generarRespuestaJSON(false, 'ID de contrato requerido');
        return;
    }
    
    $contrato_id = intval($_GET['contrato_id']);
    
    $query = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN estado_revision = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN estado_revision = 'aprobado' THEN 1 ELSE 0 END) as aprobados,
            SUM(CASE WHEN estado_revision = 'rechazado' THEN 1 ELSE 0 END) as rechazados
        FROM documentos
        WHERE contrato_id = ? AND estado = 'activo'
    ";
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $contrato_id);
    $stmt->execute();
    $estado = $stmt->get_result()->fetch_assoc();
    
    $estado['todos_aprobados'] = ($estado['total'] > 0 && $estado['pendientes'] == 0 && $estado['rechazados'] == 0);
    $estado['tiene_rechazados'] = ($estado['rechazados'] > 0);
    $estado['porcentaje_completado'] = $estado['total'] > 0 ? 
        round((($estado['aprobados'] + $estado['rechazados']) / $estado['total']) * 100, 2) : 0;
    
    generarRespuestaJSON(true, 'Estado de revisión obtenido', $estado);
}
