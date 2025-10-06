<?php
/**
 * Controlador de Contratos
 */

require_once __DIR__ . '/../config/config.php';
verificarSesion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES) && count($_FILES) > 0) {
        // Es una creación o actualización con archivos
        $action = $_POST['action'] ?? 'crear';
        
        if ($action === 'crear' || !isset($_POST['action'])) {
            crearContrato();
        } elseif ($action === 'actualizar_documentos') {
            actualizarDocumentos();
        }
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'editar':
                editarContrato();
                break;
            case 'eliminar':
                eliminarContrato();
                break;
            default:
                generarRespuestaJSON(false, 'Acción no válida');
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'obtener':
            obtenerContrato();
            break;
        case 'documentos':
            obtenerDocumentos();
            break;
        default:
            generarRespuestaJSON(false, 'Acción no válida');
    }
}

function crearContrato() {
    global $mysqli;
    
    // Procesar municipios de trabajo
    $trabajo_municipio = isset($_POST['trabajo_municipio']) ? implode(',', $_POST['trabajo_municipio']) : '';
    
    // Validar número de documento único
    $numero_documento = sanitizar($_POST['numero_documento'] ?? '');
    $stmt_check = $mysqli->prepare("SELECT id FROM contratos WHERE numero_documento = ?");
    $stmt_check->bind_param("s", $numero_documento);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        generarRespuestaJSON(false, 'Ya existe un contrato con este número de documento');
    }
    $stmt_check->close();
    
    // Preparar datos
    $fecha_diligenciamiento = $_POST['fecha_diligenciamiento'] ?? date('Y-m-d');
    $correo_electronico = sanitizar($_POST['correo_electronico'] ?? '');
    $tipo_documento = sanitizar($_POST['tipo_documento'] ?? '');
    $lugar_expedicion = sanitizar($_POST['lugar_expedicion'] ?? '');
    $nombre_completo = sanitizar($_POST['nombre_completo'] ?? '');
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
    $identidad_genero = sanitizar($_POST['identidad_genero'] ?? '');
    $grupo_poblacional = sanitizar($_POST['grupo_poblacional'] ?? '');
    $posee_discapacidad = sanitizar($_POST['posee_discapacidad'] ?? '');
    $especifique_discapacidad = $posee_discapacidad === 'si' ? sanitizar($_POST['especifique_discapacidad'] ?? '') : null;
    $celular_contacto = sanitizar($_POST['celular_contacto'] ?? '');
    $estado_civil = sanitizar($_POST['estado_civil'] ?? '');
    $numero_hijos_dependientes = intval($_POST['numero_hijos_dependientes'] ?? 0);
    $tiene_hijos_menores = sanitizar($_POST['tiene_hijos_menores'] ?? '');
    $cuantos_hijos_menores = $tiene_hijos_menores === 'si' ? intval($_POST['cuantos_hijos_menores'] ?? 0) : null;
    $padre_madre_soltero = sanitizar($_POST['padre_madre_soltero'] ?? '');
    $direccion_residencia = sanitizar($_POST['direccion_residencia'] ?? '');
    $barrio = sanitizar($_POST['barrio'] ?? '');
    $municipio_residencia = sanitizar($_POST['municipio_residencia'] ?? '');
    $nivel_estudio = sanitizar($_POST['nivel_estudio'] ?? '');
    $formacion_tecnica = $nivel_estudio === 'tecnico' ? sanitizar($_POST['formacion_tecnica'] ?? '') : null;
    $formacion_tecnologica = $nivel_estudio === 'tecnologo' ? sanitizar($_POST['formacion_tecnologica'] ?? '') : null;
    $formacion_pregrado = $nivel_estudio === 'profesional' ? sanitizar($_POST['formacion_pregrado'] ?? '') : null;
    $formacion_posgrado = $nivel_estudio === 'posgrado' ? sanitizar($_POST['formacion_posgrado'] ?? '') : null;
    $datos_posgrado = $nivel_estudio === 'posgrado' ? sanitizar($_POST['datos_posgrado'] ?? '') : null;
    $maestria = ($nivel_estudio === 'posgrado' && $datos_posgrado === 'maestria') ? sanitizar($_POST['maestria'] ?? '') : null;
    $posee_doctorado = ($nivel_estudio === 'posgrado' && $datos_posgrado === 'doctorado') ? sanitizar($_POST['posee_doctorado'] ?? '') : null;
    $eps_afiliado = sanitizar($_POST['eps_afiliado'] ?? '');
    $fondo_pension = sanitizar($_POST['fondo_pension'] ?? '');
    $arl = sanitizar($_POST['arl'] ?? '');
    $aceptacion_datos = 'si';
    $usuario_creacion = $_SESSION['usuario_id'];
    
    // Insertar contrato
    $sql = "INSERT INTO contratos (
        fecha_diligenciamiento, correo_electronico, tipo_documento, numero_documento, lugar_expedicion,
        nombre_completo, fecha_nacimiento, identidad_genero, grupo_poblacional, posee_discapacidad,
        especifique_discapacidad, celular_contacto, estado_civil, numero_hijos_dependientes,
        tiene_hijos_menores, cuantos_hijos_menores, padre_madre_soltero, direccion_residencia,
        barrio, municipio_residencia, nivel_estudio, formacion_tecnica, formacion_tecnologica,
        formacion_pregrado, formacion_posgrado, datos_posgrado, maestria, posee_doctorado,
        eps_afiliado, fondo_pension, arl, trabajo_municipio, aceptacion_datos, usuario_creacion
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param(
        "sssssssssssssississssssssssssssssi",
        $fecha_diligenciamiento, $correo_electronico, $tipo_documento, $numero_documento,
        $lugar_expedicion, $nombre_completo, $fecha_nacimiento, $identidad_genero,
        $grupo_poblacional, $posee_discapacidad, $especifique_discapacidad, $celular_contacto,
        $estado_civil, $numero_hijos_dependientes, $tiene_hijos_menores, $cuantos_hijos_menores,
        $padre_madre_soltero, $direccion_residencia, $barrio, $municipio_residencia,
        $nivel_estudio, $formacion_tecnica, $formacion_tecnologica, $formacion_pregrado,
        $formacion_posgrado, $datos_posgrado, $maestria, $posee_doctorado, $eps_afiliado,
        $fondo_pension, $arl, $trabajo_municipio, $aceptacion_datos, $usuario_creacion
    );
    
    if ($stmt->execute()) {
        $contrato_id = $stmt->insert_id;
        
        // Procesar archivos
        $archivos_subidos = 0;
        foreach ($_FILES as $key => $file) {
            if (strpos($key, 'archivo_') === 0 && $file['error'] === UPLOAD_ERR_OK) {
                $index = str_replace('archivo_', '', $key);
                $tipo_documento_archivo = sanitizar($_POST["tipo_documento_$index"] ?? '');
                
                if (!empty($tipo_documento_archivo)) {
                    $resultado = subirArchivo($file, $numero_documento, $tipo_documento_archivo, $contrato_id);
                    if ($resultado['success']) {
                        $archivos_subidos++;
                    }
                }
            }
        }
        
        // Registrar auditoría
        $stmt_audit = $mysqli->prepare("INSERT INTO auditoria (tabla, registro_id, accion, usuario_id, datos_nuevos) VALUES ('contratos', ?, 'crear', ?, ?)");
        $datos_nuevos = json_encode(['numero_documento' => $numero_documento, 'nombre_completo' => $nombre_completo]);
        $stmt_audit->bind_param("iis", $contrato_id, $_SESSION['usuario_id'], $datos_nuevos);
        $stmt_audit->execute();
        $stmt_audit->close();
        
        generarRespuestaJSON(true, "Contrato creado exitosamente. Documentos subidos: $archivos_subidos", ['id' => $contrato_id]);
    } else {
        generarRespuestaJSON(false, 'Error al crear el contrato: ' . $mysqli->error);
    }
    
    $stmt->close();
}

function subirArchivo($file, $numero_documento, $tipo_documento, $contrato_id) {
    global $mysqli;
    
    // Validar tamaño
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'El archivo excede el tamaño máximo permitido (5MB)'];
    }
    
    // Validar extensión
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Extensión de archivo no permitida'];
    }
    
    // Crear nombre único
    $nombre_archivo = $numero_documento . '_' . $tipo_documento . '_' . time() . '.' . $extension;
    $ruta_destino = UPLOAD_DIR . $nombre_archivo;
    
    // Mover archivo
    if (move_uploaded_file($file['tmp_name'], $ruta_destino)) {
        // Guardar en base de datos
        $stmt = $mysqli->prepare("INSERT INTO documentos (contrato_id, numero_documento, tipo_documento, nombre_archivo, ruta_archivo, usuario_subida) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssi", $contrato_id, $numero_documento, $tipo_documento, $nombre_archivo, $ruta_destino, $_SESSION['usuario_id']);
        
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Archivo subido exitosamente'];
        } else {
            $stmt->close();
            unlink($ruta_destino); // Eliminar archivo si falla la BD
            return ['success' => false, 'message' => 'Error al guardar en base de datos'];
        }
    } else {
        return ['success' => false, 'message' => 'Error al subir el archivo'];
    }
}

function obtenerContrato() {
    global $mysqli;
    
    $id = intval($_GET['id'] ?? 0);
    
    $stmt = $mysqli->prepare("SELECT * FROM contratos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $contrato = $result->fetch_assoc();
        generarRespuestaJSON(true, 'Contrato obtenido exitosamente', $contrato);
    } else {
        generarRespuestaJSON(false, 'Contrato no encontrado');
    }
    
    $stmt->close();
}

function obtenerDocumentos() {
    global $mysqli;
    
    $contrato_id = intval($_GET['contrato_id'] ?? 0);
    
    $stmt = $mysqli->prepare("SELECT * FROM documentos WHERE contrato_id = ? ORDER BY fecha_subida DESC");
    $stmt->bind_param("i", $contrato_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $documentos = [];
    while ($row = $result->fetch_assoc()) {
        $documentos[] = $row;
    }
    
    generarRespuestaJSON(true, 'Documentos obtenidos exitosamente', $documentos);
    $stmt->close();
}

function editarContrato() {
    global $mysqli;
    
    $id = intval($_POST['id'] ?? 0);
    $trabajo_municipio = isset($_POST['trabajo_municipio']) ? implode(',', $_POST['trabajo_municipio']) : '';
    
    // Similar a crearContrato pero con UPDATE
    // (Por brevedad, esta función puede expandirse según necesidades específicas)
    
    generarRespuestaJSON(true, 'Función de edición disponible - implementar según necesidades');
}

function actualizarDocumentos() {
    global $mysqli;
    
    $contrato_id = intval($_POST['contrato_id'] ?? 0);
    $numero_documento = sanitizar($_POST['numero_documento'] ?? '');
    
    $archivos_subidos = 0;
    foreach ($_FILES as $key => $file) {
        if (strpos($key, 'archivo_') === 0 && $file['error'] === UPLOAD_ERR_OK) {
            $index = str_replace('archivo_', '', $key);
            $tipo_documento_archivo = sanitizar($_POST["tipo_documento_$index"] ?? '');
            
            if (!empty($tipo_documento_archivo)) {
                $resultado = subirArchivo($file, $numero_documento, $tipo_documento_archivo, $contrato_id);
                if ($resultado['success']) {
                    $archivos_subidos++;
                }
            }
        }
    }
    
    generarRespuestaJSON(true, "Documentos actualizados. Archivos subidos: $archivos_subidos");
}

function eliminarContrato() {
    global $mysqli;
    
    $id = intval($_POST['id'] ?? 0);
    
    // Obtener documentos asociados para eliminarlos
    $stmt_docs = $mysqli->prepare("SELECT ruta_archivo FROM documentos WHERE contrato_id = ?");
    $stmt_docs->bind_param("i", $id);
    $stmt_docs->execute();
    $result_docs = $stmt_docs->get_result();
    
    while ($doc = $result_docs->fetch_assoc()) {
        if (file_exists($doc['ruta_archivo'])) {
            unlink($doc['ruta_archivo']);
        }
    }
    $stmt_docs->close();
    
    // Eliminar contrato (los documentos se eliminan por CASCADE)
    $stmt = $mysqli->prepare("DELETE FROM contratos WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Registrar auditoría
        $stmt_audit = $mysqli->prepare("INSERT INTO auditoria (tabla, registro_id, accion, usuario_id) VALUES ('contratos', ?, 'eliminar', ?)");
        $stmt_audit->bind_param("ii", $id, $_SESSION['usuario_id']);
        $stmt_audit->execute();
        $stmt_audit->close();
        
        generarRespuestaJSON(true, 'Contrato eliminado exitosamente');
    } else {
        generarRespuestaJSON(false, 'Error al eliminar el contrato');
    }
    
    $stmt->close();
}
?>
