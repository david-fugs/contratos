<?php
/**
 * Controlador de Workflow
 * Manejo de asignaciones, estados y devoluciones
 */

require_once __DIR__ . '/../config/config.php';
verificarSesion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'asignar_usuario':
            asignarUsuarioEtapa();
            break;
        case 'cambiar_estado':
            cambiarEstadoContrato();
            break;
        case 'devolver_contrato':
            devolverContrato();
            break;
        case 'atender_devolucion':
            atenderDevolucion();
            break;
        case 'iniciar_trabajo':
            iniciarTrabajo();
            break;
        case 'finalizar_trabajo':
            finalizarTrabajo();
            break;
        default:
            generarRespuestaJSON(false, 'Acción no válida');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'obtener_timeline':
            obtenerTimeline();
            break;
        case 'obtener_asignaciones':
            obtenerAsignaciones();
            break;
        case 'obtener_devoluciones':
            obtenerDevoluciones();
            break;
        case 'obtener_contratos_asignados':
            obtenerContratosAsignados();
            break;
        default:
            generarRespuestaJSON(false, 'Acción no válida');
    }
}

function asignarUsuarioEtapa() {
    global $mysqli;
    
    // Validar campos requeridos
    if (empty($_POST['contrato_id']) || empty($_POST['etapa']) || empty($_POST['usuario_asignado'])) {
        generarRespuestaJSON(false, 'Faltan campos requeridos');
        return;
    }
    
    $contrato_id = intval($_POST['contrato_id']);
    $etapa = $mysqli->real_escape_string($_POST['etapa']);
    $usuario_asignado = intval($_POST['usuario_asignado']);
    $comentarios = !empty($_POST['comentarios']) ? $mysqli->real_escape_string($_POST['comentarios']) : null;
    $usuario_asigno = $_SESSION['usuario_id'];
    
    // Verificar permisos (solo admin, revisor_documentos o admin_tecnico pueden asignar)
    $tipo_usuario = $_SESSION['tipo_usuario'];
    if (!in_array($tipo_usuario, ['administrador', 'revisor_documentos', 'administrador_tecnico'])) {
        generarRespuestaJSON(false, 'No tiene permisos para asignar usuarios');
        return;
    }
    
    // Verificar que el usuario asignado existe y tiene el rol correcto
    $stmt = $mysqli->prepare("SELECT tipo_usuario FROM usuarios WHERE id = ? AND estado = 'activo'");
    $stmt->bind_param("i", $usuario_asignado);
    $stmt->execute();
    $usuario_result = $stmt->get_result();
    
    if ($usuario_result->num_rows === 0) {
        generarRespuestaJSON(false, 'Usuario asignado no existe o está inactivo');
        return;
    }
    
    $usuario_data = $usuario_result->fetch_assoc();
    
    // Validar que el usuario tiene el rol correcto para la etapa
    $roles_etapa = [
        'revision_documentos' => 'revisor_documentos',
        'administracion_tecnica' => 'administrador_tecnico',
        'revision_abogado' => 'abogado'
    ];
    
    if (isset($roles_etapa[$etapa]) && $usuario_data['tipo_usuario'] !== $roles_etapa[$etapa]) {
        generarRespuestaJSON(false, "El usuario debe tener el rol {$roles_etapa[$etapa]} para esta etapa");
        return;
    }
    
    // Asignar usuario usando SQL directo (sin procedimiento almacenado)
    // Iniciar transacción
    $mysqli->begin_transaction();
    
    try {
        // 1. Finalizar asignaciones anteriores
        $stmt = $mysqli->prepare("UPDATE asignaciones_workflow 
                                  SET estado = 'completado', fecha_finalizacion = NOW()
                                  WHERE contrato_id = ? 
                                  AND etapa = ? 
                                  AND estado IN ('pendiente', 'en_proceso')");
        $stmt->bind_param("is", $contrato_id, $etapa);
        $stmt->execute();
        
        // 2. Crear nueva asignación
        $stmt = $mysqli->prepare("INSERT INTO asignaciones_workflow 
                                 (contrato_id, etapa, usuario_asignado, usuario_asigno, comentarios, estado, fecha_asignacion)
                                 VALUES (?, ?, ?, ?, ?, 'pendiente', NOW())");
        $stmt->bind_param("isiis", $contrato_id, $etapa, $usuario_asignado, $usuario_asigno, $comentarios);
        $stmt->execute();
        
        // 3. Actualizar estado del contrato
        $stmt = $mysqli->prepare("UPDATE contratos 
                                  SET estado_workflow = ?, fecha_cambio_estado = NOW()
                                  WHERE id = ?");
        $stmt->bind_param("si", $etapa, $contrato_id);
        $stmt->execute();
        
        // 4. Cerrar tiempo en etapa actual
        $stmt = $mysqli->prepare("UPDATE tiempo_etapas 
                                  SET fecha_salida = NOW(), activo = 0
                                  WHERE contrato_id = ? AND activo = 1");
        $stmt->bind_param("i", $contrato_id);
        $stmt->execute();
        
        // 5. Crear nuevo registro de tiempo
        $stmt = $mysqli->prepare("INSERT INTO tiempo_etapas (contrato_id, etapa, fecha_entrada, activo)
                                  VALUES (?, ?, NOW(), 1)");
        $stmt->bind_param("is", $contrato_id, $etapa);
        $stmt->execute();
        
        // Confirmar transacción
        $mysqli->commit();
        generarRespuestaJSON(true, 'Usuario asignado exitosamente');
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $mysqli->rollback();
        generarRespuestaJSON(false, 'Error al asignar usuario: ' . $e->getMessage());
    }
}

function cambiarEstadoContrato() {
    global $mysqli;
    
    if (empty($_POST['contrato_id']) || empty($_POST['nuevo_estado'])) {
        generarRespuestaJSON(false, 'Faltan campos requeridos');
        return;
    }
    
    $contrato_id = intval($_POST['contrato_id']);
    $nuevo_estado = $mysqli->real_escape_string($_POST['nuevo_estado']);
    $usuario_id = $_SESSION['usuario_id'];
    
    // Verificar permisos
    if (!esAbogado() && !esAdministrador()) {
        generarRespuestaJSON(false, 'Solo abogados pueden cambiar el estado del contrato');
        return;
    }
    
    // Verificar que el abogado tiene acceso al contrato
    if (esAbogado() && !puedeVerContrato($contrato_id)) {
        generarRespuestaJSON(false, 'No tiene permisos para modificar este contrato');
        return;
    }
    
    // Validar que el nuevo estado existe
    $stmt = $mysqli->prepare("SELECT nombre FROM estados_contrato WHERE nombre = ? AND activo = 1");
    $stmt->bind_param("s", $nuevo_estado);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        generarRespuestaJSON(false, 'Estado no válido');
        return;
    }
    
    // Si el estado es "en_elaboracion", marcar como no editable
    $puede_editar = in_array($nuevo_estado, ['en_elaboracion', 'para_firmas', 'publicado_aprobado', 'publicado_rechazado', 'publicado_corregido']) ? 0 : 1;
    
    // Cambiar estado usando SQL directo (sin procedimiento almacenado)
    $mysqli->begin_transaction();
    
    try {
        // Obtener estado actual
        $stmt = $mysqli->prepare("SELECT estado_workflow FROM contratos WHERE id = ?");
        $stmt->bind_param("i", $contrato_id);
        $stmt->execute();
        $estado_actual = $stmt->get_result()->fetch_assoc()['estado_workflow'];
        
        // Actualizar contrato
        $stmt = $mysqli->prepare("UPDATE contratos
                                  SET estado_workflow = ?,
                                      fecha_cambio_estado = NOW(),
                                      puede_editar = ?
                                  WHERE id = ?");
        $stmt->bind_param("sii", $nuevo_estado, $puede_editar, $contrato_id);
        $stmt->execute();
        
        // Cerrar tiempo en etapa actual
        $stmt = $mysqli->prepare("UPDATE tiempo_etapas
                                  SET fecha_salida = NOW(), activo = 0
                                  WHERE contrato_id = ? AND activo = 1");
        $stmt->bind_param("i", $contrato_id);
        $stmt->execute();
        
        // Crear nuevo registro de tiempo
        $stmt = $mysqli->prepare("INSERT INTO tiempo_etapas (contrato_id, etapa, fecha_entrada, activo)
                                  VALUES (?, ?, NOW(), 1)");
        $stmt->bind_param("is", $contrato_id, $nuevo_estado);
        $stmt->execute();
        
        // Si el estado es 'administracion_tecnica', asignar a un administrador técnico
        if ($nuevo_estado === 'administracion_tecnica') {
            // Obtener el primer administrador técnico activo
            $stmt_admin = $mysqli->prepare("SELECT id FROM usuarios WHERE tipo_usuario = 'administrador_tecnico' AND estado = 'activo' LIMIT 1");
            $stmt_admin->execute();
            $admin_tecnico = $stmt_admin->get_result()->fetch_assoc();
            
            if ($admin_tecnico) {
                // Crear asignación
                $stmt_asig = $mysqli->prepare("
                    INSERT INTO asignaciones_workflow (contrato_id, etapa, usuario_asignado, usuario_asigna, estado, fecha_asignacion)
                    VALUES (?, ?, ?, ?, 'pendiente', NOW())
                ");
                $stmt_asig->bind_param("isii", $contrato_id, $nuevo_estado, $admin_tecnico['id'], $usuario_id);
                $stmt_asig->execute();
            }
        }
        
        $mysqli->commit();
        generarRespuestaJSON(true, 'Estado actualizado exitosamente');
        
    } catch (Exception $e) {
        $mysqli->rollback();
        generarRespuestaJSON(false, 'Error al cambiar estado: ' . $e->getMessage());
    }
}

function devolverContrato() {
    global $mysqli;
    
    // Validar campos
    if (empty($_POST['contrato_id']) || empty($_POST['etapa_destino']) || empty($_POST['motivo'])) {
        generarRespuestaJSON(false, 'Faltan campos requeridos');
        return;
    }
    
    $contrato_id = intval($_POST['contrato_id']);
    $etapa_destino = $mysqli->real_escape_string($_POST['etapa_destino']);
    $motivo = $mysqli->real_escape_string($_POST['motivo']);
    $comentarios = !empty($_POST['comentarios']) ? $mysqli->real_escape_string($_POST['comentarios']) : null;
    $usuario_devuelve = $_SESSION['usuario_id'];
    
    // Solo abogados pueden devolver contratos
    if (!esAbogado() && !esAdministrador()) {
        generarRespuestaJSON(false, 'Solo abogados pueden devolver contratos');
        return;
    }
    
    // Obtener etapa actual
    $stmt = $mysqli->prepare("SELECT estado_workflow FROM contratos WHERE id = ?");
    $stmt->bind_param("i", $contrato_id);
    $stmt->execute();
    $contrato = $stmt->get_result()->fetch_assoc();
    
    if (!$contrato) {
        generarRespuestaJSON(false, 'Contrato no encontrado');
        return;
    }
    
    $etapa_origen = $contrato['estado_workflow'];
    
    // Insertar devolución
    $stmt = $mysqli->prepare("
        INSERT INTO devoluciones_contrato 
        (contrato_id, etapa_origen, etapa_destino, usuario_devuelve, motivo_devolucion, comentarios)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("ississ", $contrato_id, $etapa_origen, $etapa_destino, $usuario_devuelve, $motivo, $comentarios);
    
    if (!$stmt->execute()) {
        generarRespuestaJSON(false, 'Error al registrar devolución: ' . $mysqli->error);
        return;
    }
    
    // Cambiar estado del contrato usando SQL directo
    $mysqli->begin_transaction();
    
    try {
        // Actualizar contrato
        $stmt = $mysqli->prepare("UPDATE contratos
                                  SET estado_workflow = ?,
                                      fecha_cambio_estado = NOW(),
                                      puede_editar = 1
                                  WHERE id = ?");
        $stmt->bind_param("si", $etapa_destino, $contrato_id);
        $stmt->execute();
        
        // Cerrar tiempo en etapa actual
        $stmt = $mysqli->prepare("UPDATE tiempo_etapas
                                  SET fecha_salida = NOW(), activo = 0
                                  WHERE contrato_id = ? AND activo = 1");
        $stmt->bind_param("i", $contrato_id);
        $stmt->execute();
        
        // Crear nuevo registro de tiempo
        $stmt = $mysqli->prepare("INSERT INTO tiempo_etapas (contrato_id, etapa, fecha_entrada, activo)
                                  VALUES (?, ?, NOW(), 1)");
        $stmt->bind_param("is", $contrato_id, $etapa_destino);
        $stmt->execute();
        
        $mysqli->commit();
        generarRespuestaJSON(true, 'Contrato devuelto exitosamente');
        
    } catch (Exception $e) {
        $mysqli->rollback();
        generarRespuestaJSON(false, 'Error al devolver contrato: ' . $e->getMessage());
    }
}

function atenderDevolucion() {
    global $mysqli;
    
    if (empty($_POST['devolucion_id'])) {
        generarRespuestaJSON(false, 'ID de devolución requerido');
        return;
    }
    
    $devolucion_id = intval($_POST['devolucion_id']);
    $usuario_atiende = $_SESSION['usuario_id'];
    
    $stmt = $mysqli->prepare("
        UPDATE devoluciones_contrato 
        SET estado = 'atendido', fecha_atencion = NOW(), usuario_atiende = ?
        WHERE id = ?
    ");
    
    $stmt->bind_param("ii", $usuario_atiende, $devolucion_id);
    
    if ($stmt->execute()) {
        generarRespuestaJSON(true, 'Devolución atendida');
    } else {
        generarRespuestaJSON(false, 'Error al atender devolución: ' . $mysqli->error);
    }
}

function iniciarTrabajo() {
    global $mysqli;
    
    if (empty($_POST['asignacion_id'])) {
        generarRespuestaJSON(false, 'ID de asignación requerido');
        return;
    }
    
    $asignacion_id = intval($_POST['asignacion_id']);
    $usuario_id = $_SESSION['usuario_id'];
    
    // Verificar que la asignación pertenece al usuario
    $stmt = $mysqli->prepare("SELECT usuario_asignado FROM asignaciones_workflow WHERE id = ?");
    $stmt->bind_param("i", $asignacion_id);
    $stmt->execute();
    $asignacion = $stmt->get_result()->fetch_assoc();
    
    if (!$asignacion || $asignacion['usuario_asignado'] != $usuario_id) {
        generarRespuestaJSON(false, 'No tiene permisos para esta asignación');
        return;
    }
    
    $stmt = $mysqli->prepare("
        UPDATE asignaciones_workflow 
        SET estado = 'en_proceso', fecha_inicio = NOW()
        WHERE id = ?
    ");
    
    $stmt->bind_param("i", $asignacion_id);
    
    if ($stmt->execute()) {
        generarRespuestaJSON(true, 'Trabajo iniciado');
    } else {
        generarRespuestaJSON(false, 'Error al iniciar trabajo: ' . $mysqli->error);
    }
}

function finalizarTrabajo() {
    global $mysqli;
    
    if (empty($_POST['asignacion_id'])) {
        generarRespuestaJSON(false, 'ID de asignación requerido');
        return;
    }
    
    $asignacion_id = intval($_POST['asignacion_id']);
    $usuario_id = $_SESSION['usuario_id'];
    $comentarios = !empty($_POST['comentarios']) ? $mysqli->real_escape_string($_POST['comentarios']) : null;
    
    $stmt = $mysqli->prepare("
        UPDATE asignaciones_workflow 
        SET estado = 'completado', fecha_finalizacion = NOW(), comentarios = ?
        WHERE id = ? AND usuario_asignado = ?
    ");
    
    $stmt->bind_param("sii", $comentarios, $asignacion_id, $usuario_id);
    
    if ($stmt->execute()) {
        generarRespuestaJSON(true, 'Trabajo finalizado');
    } else {
        generarRespuestaJSON(false, 'Error al finalizar trabajo: ' . $mysqli->error);
    }
}

function obtenerTimeline() {
    global $mysqli;
    
    if (empty($_GET['contrato_id'])) {
        generarRespuestaJSON(false, 'ID de contrato requerido');
        return;
    }
    
    $contrato_id = intval($_GET['contrato_id']);
    
    // Verificar permisos
    if (!puedeVerContrato($contrato_id) && !esAdministrador()) {
        generarRespuestaJSON(false, 'No tiene permisos para ver este timeline');
        return;
    }
    
    $query = "
        SELECT 
            te.etapa,
            te.fecha_entrada,
            te.fecha_salida,
            te.dias_en_etapa,
            te.activo,
            ec.descripcion as descripcion_etapa,
            ec.color
        FROM tiempo_etapas te
        LEFT JOIN estados_contrato ec ON te.etapa = ec.nombre
        WHERE te.contrato_id = ?
        ORDER BY te.fecha_entrada ASC
    ";
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $contrato_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $timeline = [];
    while ($row = $result->fetch_assoc()) {
        $timeline[] = $row;
    }
    
    generarRespuestaJSON(true, 'Timeline obtenido', $timeline);
}

function obtenerAsignaciones() {
    global $mysqli;
    
    if (empty($_GET['contrato_id'])) {
        generarRespuestaJSON(false, 'ID de contrato requerido');
        return;
    }
    
    $contrato_id = intval($_GET['contrato_id']);
    
    $query = "
        SELECT 
            aw.*,
            u.nombre as nombre_usuario_asignado,
            u.tipo_usuario as tipo_usuario_asignado,
            ua.nombre as nombre_usuario_asigno
        FROM asignaciones_workflow aw
        LEFT JOIN usuarios u ON aw.usuario_asignado = u.id
        LEFT JOIN usuarios ua ON aw.usuario_asigno = ua.id
        WHERE aw.contrato_id = ?
        ORDER BY aw.fecha_asignacion DESC
    ";
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $contrato_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $asignaciones = [];
    while ($row = $result->fetch_assoc()) {
        $asignaciones[] = $row;
    }
    
    generarRespuestaJSON(true, 'Asignaciones obtenidas', $asignaciones);
}

function obtenerDevoluciones() {
    global $mysqli;
    
    if (empty($_GET['contrato_id'])) {
        generarRespuestaJSON(false, 'ID de contrato requerido');
        return;
    }
    
    $contrato_id = intval($_GET['contrato_id']);
    
    $query = "
        SELECT 
            dc.*,
            ud.nombre as nombre_usuario_devuelve,
            ua.nombre as nombre_usuario_atiende
        FROM devoluciones_contrato dc
        LEFT JOIN usuarios ud ON dc.usuario_devuelve = ud.id
        LEFT JOIN usuarios ua ON dc.usuario_atiende = ua.id
        WHERE dc.contrato_id = ?
        ORDER BY dc.fecha_devolucion DESC
    ";
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $contrato_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $devoluciones = [];
    while ($row = $result->fetch_assoc()) {
        $devoluciones[] = $row;
    }
    
    generarRespuestaJSON(true, 'Devoluciones obtenidas', $devoluciones);
}

function obtenerContratosAsignados() {
    global $mysqli;
    
    $usuario_id = $_SESSION['usuario_id'];
    $tipo_usuario = $_SESSION['tipo_usuario'];
    
    // Administradores ven todo
    if ($tipo_usuario === 'administrador') {
        $query = "SELECT * FROM vista_contratos_workflow ORDER BY fecha_creacion DESC";
        $stmt = $mysqli->prepare($query);
    } 
    // Usuarios ven los que crearon
    elseif ($tipo_usuario === 'usuario') {
        $query = "SELECT * FROM vista_contratos_workflow WHERE usuario_creador = ? ORDER BY fecha_creacion DESC";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("s", $_SESSION['nombre']);
    }
    // Otros ven los asignados a ellos
    else {
        $query = "
            SELECT DISTINCT vcw.*
            FROM vista_contratos_workflow vcw
            WHERE vcw.usuario_actual_asignado = ?
            OR vcw.abogado_asignado = ?
            ORDER BY vcw.fecha_creacion DESC
        ";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("ii", $usuario_id, $usuario_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $contratos = [];
    while ($row = $result->fetch_assoc()) {
        $contratos[] = $row;
    }
    
    generarRespuestaJSON(true, 'Contratos obtenidos', $contratos);
}
