<?php
$pageTitle = 'Editar Contrato';
require_once __DIR__ . '/../includes/header.php';

// Obtener ID del contrato
$contrato_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($contrato_id === 0) {
    echo "<script>window.location.href = 'contrato_listar.php';</script>";
    exit;
}

// Obtener datos del contrato
$query = "SELECT * FROM contratos WHERE id = $contrato_id AND estado = 'activo'";
$result = $mysqli->query($query);

if (!$result) {
    die("Error en la consulta: " . $mysqli->error);
}

if ($result->num_rows === 0) {
    echo "<script>
        Swal.fire('Error', 'Contrato no encontrado', 'error').then(() => {
            window.location.href = 'contrato_listar.php';
        });
    </script>";
    exit;
}

$contrato = $result->fetch_assoc();

// Obtener municipios de trabajo - primero intentar desde trabajo_municipio (campo de texto)
$municipios_seleccionados = [];
if (!empty($contrato['trabajo_municipio'])) {
    $municipios_seleccionados = explode(',', $contrato['trabajo_municipio']);
}

// Si existe la tabla trabajo_municipios, también obtener de ahí
$query_municipios = "SELECT municipio FROM trabajo_municipios WHERE contrato_id = $contrato_id";
$result_municipios = $mysqli->query($query_municipios);
if ($result_municipios && $result_municipios->num_rows > 0) {
    $municipios_seleccionados = [];
    while ($row = $result_municipios->fetch_assoc()) {
        $municipios_seleccionados[] = $row['municipio'];
    }
}

// Obtener documentos existentes
$query_docs = "SELECT * FROM documentos WHERE contrato_id = $contrato_id ORDER BY fecha_subida DESC";
$result_docs = $mysqli->query($query_docs);
$documentos_existentes = [];
if ($result_docs) {
    while ($doc = $result_docs->fetch_assoc()) {
        $documentos_existentes[] = $doc;
    }
}

// Obtener abogados para el select
$query_abogados = "SELECT id, nombre FROM usuarios WHERE tipo_usuario = 'abogado' AND estado = 'activo' ORDER BY nombre";
$result_abogados = $mysqli->query($query_abogados);
?>

<style>
.form-section {
    background-color: var(--white);
    border-left: 4px solid var(--primary-color);
    padding: 20px;
    margin-bottom: 25px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
}

.form-section-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--primary-color);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.conditional-field {
    display: none;
}

.conditional-field.show {
    display: block;
}

.file-upload-section {
    border: 2px dashed var(--gray-300);
    padding: 20px;
    border-radius: var(--border-radius);
    background-color: var(--gray-50);
    margin-top: 15px;
}

.file-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background-color: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: 6px;
    margin-top: 10px;
}

.checkmark-box {
    border: 2px solid var(--gray-300);
    padding: 15px;
    border-radius: var(--border-radius);
    background-color: var(--gray-50);
    margin-top: 10px;
}

.checkmark-box label {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    cursor: pointer;
}

.checkmark-box input[type="checkbox"] {
    margin-top: 5px;
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.documento-existente {
    background-color: #e8f5e9;
    border-left: 3px solid #4caf50;
}

.documento-existente .badge {
    background-color: #4caf50;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
}
</style>

<form id="formContrato" enctype="multipart/form-data">
    <input type="hidden" name="action" value="editar">
    <input type="hidden" name="contrato_id" value="<?php echo $contrato_id; ?>">
    
    <!-- SECCIÓN 1: INFORMACIÓN BÁSICA -->
    <div class="form-section">
        <div class="form-section-title">
            <i class="fas fa-user"></i> Información Personal Básica
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="fecha_diligenciamiento" class="form-label required">Fecha de Diligenciamiento</label>
                <input type="date" 
                       id="fecha_diligenciamiento" 
                       name="fecha_diligenciamiento" 
                       class="form-control"
                       value="<?php echo $contrato['fecha_diligenciamiento']; ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="correo_electronico" class="form-label required">Correo Electrónico</label>
                <input type="email" 
                       id="correo_electronico" 
                       name="correo_electronico" 
                       class="form-control"
                       value="<?php echo htmlspecialchars($contrato['correo_electronico']); ?>"
                       placeholder="ejemplo@correo.com"
                       required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="tipo_documento" class="form-label required">Tipo de Documento</label>
                <select id="tipo_documento" name="tipo_documento" class="form-control form-select" required>
                    <option value="">Seleccione...</option>
                    <option value="cedula_ciudadania" <?php echo ($contrato['tipo_documento'] == 'cedula_ciudadania') ? 'selected' : ''; ?>>Cédula de Ciudadanía</option>
                    <option value="tarjeta_identidad" <?php echo ($contrato['tipo_documento'] == 'tarjeta_identidad') ? 'selected' : ''; ?>>Tarjeta de Identidad</option>
                    <option value="cedula_extranjeria" <?php echo ($contrato['tipo_documento'] == 'cedula_extranjeria') ? 'selected' : ''; ?>>Cédula de Extranjería</option>
                </select>
            </div>

            <div class="form-group">
                <label for="numero_documento" class="form-label required">Número de Documento</label>
                <input type="text" 
                       id="numero_documento" 
                       name="numero_documento" 
                       class="form-control"
                       value="<?php echo htmlspecialchars($contrato['numero_documento']); ?>"
                       placeholder="Sin puntos ni espacios"
                       required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="lugar_expedicion" class="form-label required">Lugar de Expedición del Documento</label>
                <input type="text" 
                       id="lugar_expedicion" 
                       name="lugar_expedicion" 
                       class="form-control"
                       value="<?php echo htmlspecialchars($contrato['lugar_expedicion']); ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="nombre_completo" class="form-label required">Nombre Completo</label>
                <input type="text" 
                       id="nombre_completo" 
                       name="nombre_completo" 
                       class="form-control"
                       value="<?php echo htmlspecialchars($contrato['nombre_completo']); ?>"
                       required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="fecha_nacimiento" class="form-label required">Fecha de Nacimiento</label>
                <input type="date" 
                       id="fecha_nacimiento" 
                       name="fecha_nacimiento" 
                       class="form-control"
                       value="<?php echo $contrato['fecha_nacimiento']; ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="identidad_genero" class="form-label required">Identidad de Género</label>
                <select id="identidad_genero" name="identidad_genero" class="form-control form-select" required>
                    <option value="">Seleccione...</option>
                    <option value="hombre" <?php echo ($contrato['identidad_genero'] == 'hombre') ? 'selected' : ''; ?>>Hombre</option>
                    <option value="mujer" <?php echo ($contrato['identidad_genero'] == 'mujer') ? 'selected' : ''; ?>>Mujer</option>
                    <option value="prefiero_no_decirlo" <?php echo ($contrato['identidad_genero'] == 'prefiero_no_decirlo') ? 'selected' : ''; ?>>Prefiero no decirlo</option>
                </select>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 2: INFORMACIÓN SOCIAL -->
    <div class="form-section">
        <div class="form-section-title">
            <i class="fas fa-users"></i> Información Social y Poblacional
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="grupo_poblacional" class="form-label required">Grupo Poblacional al que Pertenece</label>
                <select id="grupo_poblacional" name="grupo_poblacional" class="form-control form-select" required>
                    <option value="">Seleccione...</option>
                    <option value="afrocolombiano" <?php echo ($contrato['grupo_poblacional'] == 'afrocolombiano') ? 'selected' : ''; ?>>Afrocolombiano</option>
                    <option value="desplazado" <?php echo ($contrato['grupo_poblacional'] == 'desplazado') ? 'selected' : ''; ?>>Desplazado</option>
                    <option value="discapacitado" <?php echo ($contrato['grupo_poblacional'] == 'discapacitado') ? 'selected' : ''; ?>>Discapacitado</option>
                    <option value="indigena" <?php echo ($contrato['grupo_poblacional'] == 'indigena') ? 'selected' : ''; ?>>Indígena</option>
                    <option value="mestizo" <?php echo ($contrato['grupo_poblacional'] == 'mestizo') ? 'selected' : ''; ?>>Mestizo</option>
                    <option value="victima_conflicto" <?php echo ($contrato['grupo_poblacional'] == 'victima_conflicto') ? 'selected' : ''; ?>>Víctima del Conflicto Armado</option>
                    <option value="no_aplica" <?php echo ($contrato['grupo_poblacional'] == 'no_aplica') ? 'selected' : ''; ?>>No Aplica</option>
                </select>
            </div>

            <div class="form-group">
                <label for="posee_discapacidad" class="form-label required">¿Posee alguna Discapacidad?</label>
                <select id="posee_discapacidad" name="posee_discapacidad" class="form-control form-select" required>
                    <option value="">Seleccione...</option>
                    <option value="si" <?php echo ($contrato['posee_discapacidad'] == 'si') ? 'selected' : ''; ?>>Sí</option>
                    <option value="no" <?php echo ($contrato['posee_discapacidad'] == 'no') ? 'selected' : ''; ?>>No</option>
                </select>
            </div>
        </div>

        <div class="form-group conditional-field <?php echo ($contrato['posee_discapacidad'] == 'si') ? 'show' : ''; ?>" id="campo_especifique_discapacidad">
            <label for="especifique_discapacidad" class="form-label required">Especifique cuál Discapacidad</label>
            <textarea id="especifique_discapacidad" 
                      name="especifique_discapacidad" 
                      class="form-control"
                      rows="3"><?php echo htmlspecialchars($contrato['especifique_discapacidad'] ?? ''); ?></textarea>
        </div>
    </div>

    <!-- SECCIÓN 3: INFORMACIÓN DE CONTACTO -->
    <div class="form-section">
        <div class="form-section-title">
            <i class="fas fa-phone"></i> Información de Contacto y Residencia
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="celular_contacto" class="form-label required">Celular de Contacto</label>
                <input type="tel" 
                       id="celular_contacto" 
                       name="celular_contacto" 
                       class="form-control"
                       value="<?php echo htmlspecialchars($contrato['celular_contacto']); ?>"
                       placeholder="3001234567"
                       required>
            </div>

            <div class="form-group">
                <label for="estado_civil" class="form-label required">Estado Civil</label>
                <select id="estado_civil" name="estado_civil" class="form-control form-select" required>
                    <option value="">Seleccione...</option>
                    <option value="casado" <?php echo ($contrato['estado_civil'] == 'casado') ? 'selected' : ''; ?>>Casado(a)</option>
                    <option value="separado_divorciado" <?php echo ($contrato['estado_civil'] == 'separado_divorciado') ? 'selected' : ''; ?>>Separado/Divorciado</option>
                    <option value="soltero" <?php echo ($contrato['estado_civil'] == 'soltero') ? 'selected' : ''; ?>>Soltero(a)</option>
                    <option value="union_libre" <?php echo ($contrato['estado_civil'] == 'union_libre') ? 'selected' : ''; ?>>Unión Libre</option>
                    <option value="viudo" <?php echo ($contrato['estado_civil'] == 'viudo') ? 'selected' : ''; ?>>Viudo(a)</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="numero_hijos_dependientes" class="form-label required">Número de Hijos que Dependen Económicamente de Usted</label>
                <input type="number" 
                       id="numero_hijos_dependientes" 
                       name="numero_hijos_dependientes" 
                       class="form-control"
                       min="0"
                       value="<?php echo $contrato['numero_hijos_dependientes']; ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="tiene_hijos_menores" class="form-label required">¿Tiene Hijos Menores de Edad?</label>
                <select id="tiene_hijos_menores" name="tiene_hijos_menores" class="form-control form-select" required>
                    <option value="">Seleccione...</option>
                    <option value="si" <?php echo ($contrato['tiene_hijos_menores'] == 'si') ? 'selected' : ''; ?>>Sí</option>
                    <option value="no" <?php echo ($contrato['tiene_hijos_menores'] == 'no') ? 'selected' : ''; ?>>No</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group conditional-field <?php echo ($contrato['tiene_hijos_menores'] == 'si') ? 'show' : ''; ?>" id="campo_cuantos_hijos_menores">
                <label for="cuantos_hijos_menores" class="form-label required">¿Cuántos Hijos Menores de Edad Tiene?</label>
                <input type="number" 
                       id="cuantos_hijos_menores" 
                       name="cuantos_hijos_menores" 
                       class="form-control"
                       min="1"
                       value="<?php echo $contrato['cuantos_hijos_menores'] ?? ''; ?>">
            </div>

            <div class="form-group">
                <label for="padre_madre_soltero" class="form-label required">¿Padre o Madre Soltero(a)?</label>
                <select id="padre_madre_soltero" name="padre_madre_soltero" class="form-control form-select" required>
                    <option value="">Seleccione...</option>
                    <option value="si" <?php echo ($contrato['padre_madre_soltero'] == 'si') ? 'selected' : ''; ?>>Sí</option>
                    <option value="no" <?php echo ($contrato['padre_madre_soltero'] == 'no') ? 'selected' : ''; ?>>No</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="direccion_residencia" class="form-label required">Dirección de Residencia</label>
                <input type="text" 
                       id="direccion_residencia" 
                       name="direccion_residencia" 
                       class="form-control"
                       value="<?php echo htmlspecialchars($contrato['direccion_residencia']); ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="barrio" class="form-label required">Barrio</label>
                <input type="text" 
                       id="barrio" 
                       name="barrio" 
                       class="form-control"
                       value="<?php echo htmlspecialchars($contrato['barrio']); ?>"
                       required>
            </div>
        </div>

        <div class="form-group">
            <label for="municipio_residencia" class="form-label required">Municipio de Residencia</label>
            <select id="municipio_residencia" name="municipio_residencia" class="form-control form-select" required>
                <option value="">Seleccione...</option>
                <option value="apia" <?php echo ($contrato['municipio_residencia'] == 'apia') ? 'selected' : ''; ?>>Apía</option>
                <option value="balboa" <?php echo ($contrato['municipio_residencia'] == 'balboa') ? 'selected' : ''; ?>>Balboa</option>
                <option value="belen_de_umbria" <?php echo ($contrato['municipio_residencia'] == 'belen_de_umbria') ? 'selected' : ''; ?>>Belén de Umbría</option>
                <option value="dosquebradas" <?php echo ($contrato['municipio_residencia'] == 'dosquebradas') ? 'selected' : ''; ?>>Dosquebradas</option>
                <option value="guatica" <?php echo ($contrato['municipio_residencia'] == 'guatica') ? 'selected' : ''; ?>>Guática</option>
                <option value="la_celia" <?php echo ($contrato['municipio_residencia'] == 'la_celia') ? 'selected' : ''; ?>>La Celia</option>
                <option value="la_virginia" <?php echo ($contrato['municipio_residencia'] == 'la_virginia') ? 'selected' : ''; ?>>La Virginia</option>
                <option value="marsella" <?php echo ($contrato['municipio_residencia'] == 'marsella') ? 'selected' : ''; ?>>Marsella</option>
                <option value="mistrato" <?php echo ($contrato['municipio_residencia'] == 'mistrato') ? 'selected' : ''; ?>>Mistrató</option>
                <option value="pereira" <?php echo ($contrato['municipio_residencia'] == 'pereira') ? 'selected' : ''; ?>>Pereira</option>
                <option value="pueblo_rico" <?php echo ($contrato['municipio_residencia'] == 'pueblo_rico') ? 'selected' : ''; ?>>Pueblo Rico</option>
                <option value="quinchia" <?php echo ($contrato['municipio_residencia'] == 'quinchia') ? 'selected' : ''; ?>>Quinchía</option>
                <option value="santa_rosa_de_cabal" <?php echo ($contrato['municipio_residencia'] == 'santa_rosa_de_cabal') ? 'selected' : ''; ?>>Santa Rosa de Cabal</option>
                <option value="santuario" <?php echo ($contrato['municipio_residencia'] == 'santuario') ? 'selected' : ''; ?>>Santuario</option>
            </select>
        </div>
    </div>

    <!-- SECCIÓN 4: INFORMACIÓN EDUCATIVA -->
    <div class="form-section">
        <div class="form-section-title">
            <i class="fas fa-graduation-cap"></i> Información Educativa
        </div>

        <div class="form-group">
            <label for="nivel_estudio" class="form-label required">Nivel de Estudio</label>
            <select id="nivel_estudio" name="nivel_estudio" class="form-control form-select" required>
                <option value="">Seleccione...</option>
                <option value="bachiller" <?php echo ($contrato['nivel_estudio'] == 'bachiller') ? 'selected' : ''; ?>>Bachiller</option>
                <option value="tecnico" <?php echo ($contrato['nivel_estudio'] == 'tecnico') ? 'selected' : ''; ?>>Técnico</option>
                <option value="tecnologo" <?php echo ($contrato['nivel_estudio'] == 'tecnologo') ? 'selected' : ''; ?>>Tecnólogo</option>
                <option value="profesional" <?php echo ($contrato['nivel_estudio'] == 'profesional') ? 'selected' : ''; ?>>Profesional</option>
                <option value="posgrado" <?php echo ($contrato['nivel_estudio'] == 'posgrado') ? 'selected' : ''; ?>>Posgrado</option>
            </select>
        </div>

        <div class="form-group conditional-field <?php echo ($contrato['nivel_estudio'] == 'tecnico') ? 'show' : ''; ?>" id="campo_formacion_tecnica">
            <label for="formacion_tecnica" class="form-label required">Escriba su Formación Técnica</label>
            <input type="text" 
                   id="formacion_tecnica" 
                   name="formacion_tecnica" 
                   class="form-control"
                   value="<?php echo htmlspecialchars($contrato['formacion_tecnica'] ?? ''); ?>">
        </div>

        <div class="form-group conditional-field <?php echo ($contrato['nivel_estudio'] == 'tecnologo') ? 'show' : ''; ?>" id="campo_formacion_tecnologica">
            <label for="formacion_tecnologica" class="form-label required">Escriba su Formación Tecnológica</label>
            <input type="text" 
                   id="formacion_tecnologica" 
                   name="formacion_tecnologica" 
                   class="form-control"
                   value="<?php echo htmlspecialchars($contrato['formacion_tecnologica'] ?? ''); ?>">
        </div>

        <div class="form-group conditional-field <?php echo ($contrato['nivel_estudio'] == 'profesional') ? 'show' : ''; ?>" id="campo_formacion_pregrado">
            <label for="formacion_pregrado" class="form-label required">Escriba su Formación de Pregrado Universitario</label>
            <input type="text" 
                   id="formacion_pregrado" 
                   name="formacion_pregrado" 
                   class="form-control"
                   value="<?php echo htmlspecialchars($contrato['formacion_pregrado'] ?? ''); ?>">
        </div>

        <div id="campos_posgrado" class="conditional-field <?php echo ($contrato['nivel_estudio'] == 'posgrado') ? 'show' : ''; ?>">
            <div class="form-group">
                <label for="datos_posgrado" class="form-label required">Datos de Posgrado</label>
                <select id="datos_posgrado" name="datos_posgrado" class="form-control form-select">
                    <option value="">Seleccione...</option>
                    <option value="especializacion" <?php echo ($contrato['datos_posgrado'] == 'especializacion') ? 'selected' : ''; ?>>Especialización</option>
                    <option value="maestria" <?php echo ($contrato['datos_posgrado'] == 'maestria') ? 'selected' : ''; ?>>Maestría</option>
                    <option value="doctorado" <?php echo ($contrato['datos_posgrado'] == 'doctorado') ? 'selected' : ''; ?>>Doctorado</option>
                    <option value="no_aplica" <?php echo ($contrato['datos_posgrado'] == 'no_aplica') ? 'selected' : ''; ?>>No Aplica</option>
                </select>
            </div>

            <div class="form-group">
                <label for="formacion_posgrado" class="form-label">Escriba Formación Posgrado</label>
                <input type="text" 
                       id="formacion_posgrado" 
                       name="formacion_posgrado" 
                       class="form-control"
                       value="<?php echo htmlspecialchars($contrato['formacion_posgrado'] ?? ''); ?>">
            </div>

            <div class="form-group conditional-field <?php echo ($contrato['datos_posgrado'] == 'maestria') ? 'show' : ''; ?>" id="campo_maestria">
                <label for="maestria" class="form-label required">Ingrese su Maestría</label>
                <input type="text" 
                       id="maestria" 
                       name="maestria" 
                       class="form-control"
                       value="<?php echo htmlspecialchars($contrato['maestria'] ?? ''); ?>">
            </div>

            <div class="form-group conditional-field <?php echo ($contrato['datos_posgrado'] == 'doctorado') ? 'show' : ''; ?>" id="campo_posee_doctorado">
                <label for="posee_doctorado" class="form-label required">¿Posee Doctorado?</label>
                <select id="posee_doctorado" name="posee_doctorado" class="form-control form-select">
                    <option value="">Seleccione...</option>
                    <option value="si" <?php echo ($contrato['posee_doctorado'] == 'si') ? 'selected' : ''; ?>>Sí</option>
                    <option value="no" <?php echo ($contrato['posee_doctorado'] == 'no') ? 'selected' : ''; ?>>No</option>
                </select>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 5: INFORMACIÓN LABORAL Y SEGURIDAD SOCIAL -->
    <div class="form-section">
        <div class="form-section-title">
            <i class="fas fa-briefcase"></i> Información Laboral y Seguridad Social
        </div>

        <div class="form-group">
            <label for="eps_afiliado" class="form-label required">¿A qué EPS se Encuentra Afiliado?</label>
            <input type="text" 
                   id="eps_afiliado" 
                   name="eps_afiliado" 
                   class="form-control"
                   value="<?php echo htmlspecialchars($contrato['eps_afiliado']); ?>"
                   required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="fondo_pension" class="form-label required">¿A qué Fondo de Pensión se Encuentra Afiliado?</label>
                <select id="fondo_pension" name="fondo_pension" class="form-control form-select" required>
                    <option value="">Seleccione...</option>
                    <option value="colfondos" <?php echo ($contrato['fondo_pension'] == 'colfondos') ? 'selected' : ''; ?>>Colfondos</option>
                    <option value="colpensiones" <?php echo ($contrato['fondo_pension'] == 'colpensiones') ? 'selected' : ''; ?>>Colpensiones</option>
                    <option value="old_mutual" <?php echo ($contrato['fondo_pension'] == 'old_mutual') ? 'selected' : ''; ?>>Old Mutual</option>
                    <option value="porvenir" <?php echo ($contrato['fondo_pension'] == 'porvenir') ? 'selected' : ''; ?>>Porvenir</option>
                    <option value="proteccion" <?php echo ($contrato['fondo_pension'] == 'proteccion') ? 'selected' : ''; ?>>Protección</option>
                </select>
            </div>

            <div class="form-group">
                <label for="arl" class="form-label required">¿Cuál es su ARL?</label>
                <input type="text" 
                       id="arl" 
                       name="arl" 
                       class="form-control"
                       value="<?php echo htmlspecialchars($contrato['arl']); ?>"
                       required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label required">Su Trabajo lo Realiza en Cuál Municipio (Puede seleccionar múltiples opciones)</label>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-top: 10px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="apia" <?php echo in_array('apia', $municipios_seleccionados) ? 'checked' : ''; ?>> Apía
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="balboa" <?php echo in_array('balboa', $municipios_seleccionados) ? 'checked' : ''; ?>> Balboa
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="belen_de_umbria" <?php echo in_array('belen_de_umbria', $municipios_seleccionados) ? 'checked' : ''; ?>> Belén de Umbría
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="dosquebradas" <?php echo in_array('dosquebradas', $municipios_seleccionados) ? 'checked' : ''; ?>> Dosquebradas
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="guatica" <?php echo in_array('guatica', $municipios_seleccionados) ? 'checked' : ''; ?>> Guática
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="la_celia" <?php echo in_array('la_celia', $municipios_seleccionados) ? 'checked' : ''; ?>> La Celia
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="la_virginia" <?php echo in_array('la_virginia', $municipios_seleccionados) ? 'checked' : ''; ?>> La Virginia
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="marsella" <?php echo in_array('marsella', $municipios_seleccionados) ? 'checked' : ''; ?>> Marsella
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="mistrato" <?php echo in_array('mistrato', $municipios_seleccionados) ? 'checked' : ''; ?>> Mistrató
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="pereira" <?php echo in_array('pereira', $municipios_seleccionados) ? 'checked' : ''; ?>> Pereira
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="pueblo_rico" <?php echo in_array('pueblo_rico', $municipios_seleccionados) ? 'checked' : ''; ?>> Pueblo Rico
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="quinchia" <?php echo in_array('quinchia', $municipios_seleccionados) ? 'checked' : ''; ?>> Quinchía
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="santa_rosa_de_cabal" <?php echo in_array('santa_rosa_de_cabal', $municipios_seleccionados) ? 'checked' : ''; ?>> Santa Rosa de Cabal
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="santuario" <?php echo in_array('santuario', $municipios_seleccionados) ? 'checked' : ''; ?>> Santuario
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="todos_municipios" <?php echo in_array('todos_municipios', $municipios_seleccionados) ? 'checked' : ''; ?>> En los 12 Municipios
                </label>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 6: ASIGNACIÓN DE ABOGADO -->
    <div class="form-section">
        <div class="form-section-title">
            <i class="fas fa-user-tie"></i> Asignación de Abogado
        </div>

        <div class="form-group">
            <label for="abogado_asignado" class="form-label">Abogado Asignado (Opcional)</label>
            <select id="abogado_asignado" name="abogado_asignado" class="form-control form-select">
                <option value="">Sin asignar</option>
                <?php
                $result_abogados->data_seek(0); // Reset el puntero del resultado
                while($abogado = $result_abogados->fetch_assoc()):
                ?>
                    <option value="<?php echo $abogado['id']; ?>" <?php echo ($contrato['abogado_asignado'] == $abogado['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($abogado['nombre']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <small class="form-text">Si cambia el abogado, se actualizará la fecha de asignación.</small>
        </div>
    </div>

    <!-- SECCIÓN 7: TRATAMIENTO DE DATOS -->
    <div class="form-section">
        <div class="form-section-title">
            <i class="fas fa-shield-alt"></i> Tratamiento de Datos Personales
        </div>

        <div class="checkmark-box">
            <label>
                <input type="checkbox" 
                       id="aceptacion_datos" 
                       name="aceptacion_datos" 
                       value="si" 
                       <?php echo ($contrato['aceptacion_datos'] == 'si') ? 'checked' : ''; ?>
                       required>
                <div>
                    <strong>Aceptación de términos y condiciones de manejo de datos personales (Habeas Data)</strong>
                    <p style="margin-top: 10px; font-size: 14px; color: var(--gray-600); line-height: 1.6;">
                        En cumplimiento de la Ley 1581 de 2012 y el Decreto 1377 de 2013 sobre protección de datos personales, 
                        autorizo de manera voluntaria, previa, explícita, informada e inequívoca a la oficina de contratación 
                        de la secretaría de educación del Departamento de Risaralda para tratar mis datos personales de acuerdo 
                        con su política de privacidad. Entiendo que estos datos serán utilizados para fines administrativos y 
                        de gestión interna. Declaro que conozco y puedo ejercer mis derechos a conocer, actualizar, rectificar 
                        y suprimir mis datos personales, así como mi derecho a revocar el consentimiento otorgado.
                    </p>
                </div>
            </label>
        </div>
    </div>

    <!-- SECCIÓN 8: DOCUMENTOS -->
    <div class="form-section">
        <div class="form-section-title">
            <i class="fas fa-file-upload"></i> Documentos del Contrato
        </div>

        <?php if (count($documentos_existentes) > 0): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            <strong>Documentos actuales:</strong> Se muestran los documentos ya cargados. Puede agregar nuevos documentos si lo necesita.
        </div>
        
        <div id="documentos-existentes" style="margin-bottom: 20px;">
            <?php foreach ($documentos_existentes as $doc): ?>
            <div class="file-item documento-existente">
                <div>
                    <i class="fas fa-file-pdf"></i>
                    <strong><?php echo htmlspecialchars($doc['nombre_documento'] ?? $doc['tipo_documento']); ?></strong>
                    <span class="badge">Cargado</span>
                    <br>
                    <small style="color: var(--gray-600);">
                        Fecha: <?php echo formatearFecha($doc['fecha_subida']); ?>
                    </small>
                </div>
                <a href="../uploads/documentos/<?php echo $doc['ruta_archivo']; ?>" 
                   target="_blank" 
                   class="btn btn-sm btn-info">
                    <i class="fas fa-eye"></i> Ver
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> 
            No hay documentos cargados aún. Puede agregar nuevos documentos usando el botón de abajo.
        </div>
        <?php endif; ?>

        <p style="color: var(--gray-600); margin-bottom: 20px;">
            <i class="fas fa-info-circle"></i> 
            Todos los documentos deben estar en formato PDF, JPG, PNG, DOC o DOCX. Tamaño máximo: 5MB por archivo.
        </p>

        <div id="documentos-container"></div>

        <button type="button" class="btn btn-secondary" onclick="agregarDocumento()">
            <i class="fas fa-plus"></i> Agregar Nuevo Documento
        </button>
    </div>

    <!-- Botones de Acción -->
    <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px;">
        <a href="contrato_listar.php" class="btn btn-light btn-lg">
            <i class="fas fa-times"></i> Cancelar
        </a>
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save"></i> Guardar Cambios
        </button>
    </div>
</form>
        <button type="button" class="btn btn-light" onclick="window.location.href='contrato_listar.php'">
            <i class="fas fa-times"></i> Cancelar
        </button>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Guardar Cambios
        </button>
    </div>
</form>

<script>
let contadorDocumentos = <?php echo count($documentos_existentes); ?>;

// Lógica condicional: Discapacidad
document.getElementById('posee_discapacidad').addEventListener('change', function() {
    const campo = document.getElementById('campo_especifique_discapacidad');
    const input = document.getElementById('especifique_discapacidad');
    
    if (this.value === 'si') {
        campo.classList.add('show');
        input.required = true;
    } else {
        campo.classList.remove('show');
        input.required = false;
        input.value = '';
    }
});

// Lógica condicional: Hijos menores
document.getElementById('tiene_hijos_menores').addEventListener('change', function() {
    const campo = document.getElementById('campo_cuantos_hijos_menores');
    const input = document.getElementById('cuantos_hijos_menores');
    
    if (this.value === 'si') {
        campo.classList.add('show');
        input.required = true;
    } else {
        campo.classList.remove('show');
        input.required = false;
        input.value = '';
    }
});

// Lógica condicional: Nivel de estudio
document.getElementById('nivel_estudio').addEventListener('change', function() {
    // Ocultar todos los campos condicionales
    document.getElementById('campo_formacion_tecnica').classList.remove('show');
    document.getElementById('campo_formacion_tecnologica').classList.remove('show');
    document.getElementById('campo_formacion_pregrado').classList.remove('show');
    document.getElementById('campos_posgrado').classList.remove('show');
    
    // Limpiar required
    document.getElementById('formacion_tecnica').required = false;
    document.getElementById('formacion_tecnologica').required = false;
    document.getElementById('formacion_pregrado').required = false;
    
    // Mostrar según selección
    if (this.value === 'tecnico') {
        document.getElementById('campo_formacion_tecnica').classList.add('show');
        document.getElementById('formacion_tecnica').required = true;
    } else if (this.value === 'tecnologo') {
        document.getElementById('campo_formacion_tecnologica').classList.add('show');
        document.getElementById('formacion_tecnologica').required = true;
    } else if (this.value === 'profesional') {
        document.getElementById('campo_formacion_pregrado').classList.add('show');
        document.getElementById('formacion_pregrado').required = true;
    } else if (this.value === 'posgrado') {
        document.getElementById('campos_posgrado').classList.add('show');
        document.getElementById('datos_posgrado').required = true;
    }
});

// Lógica condicional: Datos de posgrado
document.getElementById('datos_posgrado').addEventListener('change', function() {
    document.getElementById('campo_maestria').classList.remove('show');
    document.getElementById('campo_posee_doctorado').classList.remove('show');
    document.getElementById('maestria').required = false;
    document.getElementById('posee_doctorado').required = false;
    
    if (this.value === 'maestria') {
        document.getElementById('campo_maestria').classList.add('show');
        document.getElementById('maestria').required = true;
    } else if (this.value === 'doctorado') {
        document.getElementById('campo_posee_doctorado').classList.add('show');
        document.getElementById('posee_doctorado').required = true;
    }
});

// Sistema de carga de documentos
function agregarDocumento() {
    contadorDocumentos++;
    const container = document.getElementById('documentos-container');
    
    const docDiv = document.createElement('div');
    docDiv.className = 'file-upload-section';
    docDiv.id = `documento-nuevo-${contadorDocumentos}`;
    docDiv.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h4 style="margin: 0;">Nuevo Documento #${contadorDocumentos}</h4>
            <button type="button" class="btn btn-sm btn-danger" onclick="eliminarDocumento(${contadorDocumentos})">
                <i class="fas fa-trash"></i> Eliminar
            </button>
        </div>
        
        <div class="form-group">
            <label class="form-label required">Tipo de Documento</label>
            <select name="tipo_documento_${contadorDocumentos}" class="form-control form-select" required>
                <option value="">Seleccione tipo de documento...</option>
                <option value="autorizacion_tratamiento_datos">Autorización de Tratamiento de Datos</option>
                <option value="autorizacion_consulta_delitos">Autorización Consulta Delitos Sexuales</option>
                <option value="aportes_novedades">Aportes de Novedades</option>
                <option value="declaracion_proactiva">Declaración Proactiva</option>
                <option value="prestacion_servicios">Prestación de Servicios</option>
                <option value="creacion_usuario">Creación Usuario</option>
                <option value="propuesta_economica">Propuesta Económica</option>
                <option value="otro">Otro</option>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label required">Archivo (PDF, JPG, PNG, DOC o DOCX)</label>
            <input type="file" 
                   name="archivo_${contadorDocumentos}" 
                   class="form-control" 
                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                   required>
            <small class="form-text">Tamaño máximo: 5MB</small>
        </div>
    `;
    
    container.appendChild(docDiv);
}

function eliminarDocumento(id) {
    const elemento = document.getElementById(`documento-nuevo-${id}`);
    if (elemento) {
        elemento.remove();
    }
}

// Enviar formulario
document.getElementById('formContrato').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Validar aceptación de datos
    if (!document.getElementById('aceptacion_datos').checked) {
        Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: 'Debe aceptar el tratamiento de datos personales para continuar'
        });
        return;
    }
    
    // Validar al menos un municipio de trabajo
    const municipiosTrabajo = document.querySelectorAll('input[name="trabajo_municipio[]"]:checked');
    if (municipiosTrabajo.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: 'Debe seleccionar al menos un municipio donde realiza su trabajo'
        });
        return;
    }
    
    mostrarLoading();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('../controllers/contrato_controller.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        ocultarLoading();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: data.message,
                confirmButtonText: 'Aceptar'
            }).then(() => {
                window.location.href = 'contrato_listar.php';
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message
            });
        }
    } catch (error) {
        ocultarLoading();
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al procesar la solicitud'
        });
        console.error('Error:', error);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
