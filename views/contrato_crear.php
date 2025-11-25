<?php
$pageTitle = 'Nuevo Contrato';
require_once __DIR__ . '/../includes/header.php';
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
</style>

<form id="formContrato" enctype="multipart/form-data">
    <input type="hidden" name="action" value="crear">
    
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
                       required>
            </div>

            <div class="form-group">
                <label for="correo_electronico" class="form-label required">Correo Electrónico</label>
                <input type="email" 
                       id="correo_electronico" 
                       name="correo_electronico" 
                       class="form-control"
                       placeholder="ejemplo@correo.com"
                       required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="tipo_documento" class="form-label required">Tipo de Documento</label>
                <select id="tipo_documento" name="tipo_documento" class="form-control form-select" required>
                    <option value="">Seleccione...</option>
                    <option value="cedula_ciudadania">Cédula de Ciudadanía</option>
                    <option value="tarjeta_identidad">Tarjeta de Identidad</option>
                    <option value="cedula_extranjeria">Cédula de Extranjería</option>
                </select>
            </div>

            <div class="form-group">
                <label for="numero_documento" class="form-label required">Número de Documento</label>
                <input type="text" 
                       id="numero_documento" 
                       name="numero_documento" 
                       class="form-control"
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
                       required>
            </div>

            <div class="form-group">
                <label for="nombre_completo" class="form-label required">Nombre Completo</label>
                <input type="text" 
                       id="nombre_completo" 
                       name="nombre_completo" 
                       class="form-control"
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
                       required>
            </div>

            <div class="form-group">
                <label for="identidad_genero" class="form-label required">Identidad de Género</label>
                <select id="identidad_genero" name="identidad_genero" class="form-control form-select" required>
                    <option value="">Seleccione...</option>
                    <option value="hombre">Hombre</option>
                    <option value="mujer">Mujer</option>
                    <option value="prefiero_no_decirlo">Prefiero no decirlo</option>
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
                    <option value="afrocolombiano">Afrocolombiano</option>
                    <option value="desplazado">Desplazado</option>
                    <option value="discapacitado">Discapacitado</option>
                    <option value="indigena">Indígena</option>
                    <option value="mestizo">Mestizo</option>
                    <option value="victima_conflicto">Víctima del Conflicto Armado</option>
                    <option value="no_aplica">No Aplica</option>
                </select>
            </div>

            <div class="form-group">
                <label for="posee_discapacidad" class="form-label required">¿Posee alguna Discapacidad?</label>
                <select id="posee_discapacidad" name="posee_discapacidad" class="form-control form-select" required>
                    <option value="">Seleccione...</option>
                    <option value="si">Sí</option>
                    <option value="no">No</option>
                </select>
            </div>
        </div>

        <div class="form-group conditional-field" id="campo_especifique_discapacidad">
            <label for="especifique_discapacidad" class="form-label required">Especifique cuál Discapacidad</label>
            <textarea id="especifique_discapacidad" 
                      name="especifique_discapacidad" 
                      class="form-control"
                      rows="3"></textarea>
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
                       placeholder="3001234567"
                       required>
            </div>

            <div class="form-group">
                <label for="estado_civil" class="form-label required">Estado Civil</label>
                <select id="estado_civil" name="estado_civil" class="form-control form-select" required>
                    <option value="">Seleccione...</option>
                    <option value="casado">Casado(a)</option>
                    <option value="separado_divorciado">Separado/Divorciado</option>
                    <option value="soltero">Soltero(a)</option>
                    <option value="union_libre">Unión Libre</option>
                    <option value="viudo">Viudo(a)</option>
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
                       value="0"
                       required>
            </div>

            <div class="form-group">
                <label for="tiene_hijos_menores" class="form-label required">¿Tiene Hijos Menores de Edad?</label>
                <select id="tiene_hijos_menores" name="tiene_hijos_menores" class="form-control form-select" required>
                    <option value="">Seleccione...</option>
                    <option value="si">Sí</option>
                    <option value="no">No</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group conditional-field" id="campo_cuantos_hijos_menores">
                <label for="cuantos_hijos_menores" class="form-label required">¿Cuántos Hijos Menores de Edad Tiene?</label>
                <input type="number" 
                       id="cuantos_hijos_menores" 
                       name="cuantos_hijos_menores" 
                       class="form-control"
                       min="1">
            </div>

            <div class="form-group">
                <label for="padre_madre_soltero" class="form-label required">¿Padre o Madre Soltero(a)?</label>
                <select id="padre_madre_soltero" name="padre_madre_soltero" class="form-control form-select" required>
                    <option value="">Seleccione...</option>
                    <option value="si">Sí</option>
                    <option value="no">No</option>
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
                       required>
            </div>

            <div class="form-group">
                <label for="barrio" class="form-label required">Barrio</label>
                <input type="text" 
                       id="barrio" 
                       name="barrio" 
                       class="form-control"
                       required>
            </div>
        </div>

        <div class="form-group">
            <label for="municipio_residencia" class="form-label required">Municipio de Residencia</label>
            <select id="municipio_residencia" name="municipio_residencia" class="form-control form-select" required>
                <option value="">Seleccione...</option>
                <option value="apia">Apía</option>
                <option value="balboa">Balboa</option>
                <option value="belen_de_umbria">Belén de Umbría</option>
                <option value="dosquebradas">Dosquebradas</option>
                <option value="guatica">Guática</option>
                <option value="la_celia">La Celia</option>
                <option value="la_virginia">La Virginia</option>
                <option value="marsella">Marsella</option>
                <option value="mistrato">Mistrató</option>
                <option value="pereira">Pereira</option>
                <option value="pueblo_rico">Pueblo Rico</option>
                <option value="quinchia">Quinchía</option>
                <option value="santa_rosa_de_cabal">Santa Rosa de Cabal</option>
                <option value="santuario">Santuario</option>
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
                <option value="bachiller">Bachiller</option>
                <option value="tecnico">Técnico</option>
                <option value="tecnologo">Tecnólogo</option>
                <option value="profesional">Profesional</option>
                <option value="posgrado">Posgrado</option>
            </select>
        </div>

        <div class="form-group conditional-field" id="campo_formacion_tecnica">
            <label for="formacion_tecnica" class="form-label required">Escriba su Formación Técnica</label>
            <input type="text" 
                   id="formacion_tecnica" 
                   name="formacion_tecnica" 
                   class="form-control">
        </div>

        <div class="form-group conditional-field" id="campo_formacion_tecnologica">
            <label for="formacion_tecnologica" class="form-label required">Escriba su Formación Tecnológica</label>
            <input type="text" 
                   id="formacion_tecnologica" 
                   name="formacion_tecnologica" 
                   class="form-control">
        </div>

        <div class="form-group conditional-field" id="campo_formacion_pregrado">
            <label for="formacion_pregrado" class="form-label required">Escriba su Formación de Pregrado Universitario</label>
            <input type="text" 
                   id="formacion_pregrado" 
                   name="formacion_pregrado" 
                   class="form-control">
        </div>

        <div id="campos_posgrado" class="conditional-field">
            <div class="form-group">
                <label for="datos_posgrado" class="form-label required">Datos de Posgrado</label>
                <select id="datos_posgrado" name="datos_posgrado" class="form-control form-select">
                    <option value="">Seleccione...</option>
                    <option value="especializacion">Especialización</option>
                    <option value="maestria">Maestría</option>
                    <option value="doctorado">Doctorado</option>
                    <option value="no_aplica">No Aplica</option>
                </select>
            </div>

            <div class="form-group">
                <label for="formacion_posgrado" class="form-label">Escriba Formación Posgrado</label>
                <input type="text" 
                       id="formacion_posgrado" 
                       name="formacion_posgrado" 
                       class="form-control">
            </div>

            <div class="form-group conditional-field" id="campo_maestria">
                <label for="maestria" class="form-label required">Ingrese su Maestría</label>
                <input type="text" 
                       id="maestria" 
                       name="maestria" 
                       class="form-control">
            </div>

            <div class="form-group conditional-field" id="campo_posee_doctorado">
                <label for="posee_doctorado" class="form-label required">¿Posee Doctorado?</label>
                <select id="posee_doctorado" name="posee_doctorado" class="form-control form-select">
                    <option value="">Seleccione...</option>
                    <option value="si">Sí</option>
                    <option value="no">No</option>
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
                   required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="fondo_pension" class="form-label required">¿A qué Fondo de Pensión se Encuentra Afiliado?</label>
                <select id="fondo_pension" name="fondo_pension" class="form-control form-select" required>
                    <option value="">Seleccione...</option>
                    <option value="colfondos">Colfondos</option>
                    <option value="colpensiones">Colpensiones</option>
                    <option value="old_mutual">Old Mutual</option>
                    <option value="porvenir">Porvenir</option>
                    <option value="proteccion">Protección</option>
                </select>
            </div>

            <div class="form-group">
                <label for="arl" class="form-label required">¿Cuál es su ARL?</label>
                <input type="text" 
                       id="arl" 
                       name="arl" 
                       class="form-control"
                       required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label required">Su Trabajo lo Realiza en Cuál Municipio (Puede seleccionar múltiples opciones)</label>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-top: 10px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="apia"> Apía
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="balboa"> Balboa
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="belen_de_umbria"> Belén de Umbría
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="dosquebradas"> Dosquebradas
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="guatica"> Guática
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="la_celia"> La Celia
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="la_virginia"> La Virginia
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="marsella"> Marsella
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="mistrato"> Mistrató
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="pereira"> Pereira
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="pueblo_rico"> Pueblo Rico
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="quinchia"> Quinchía
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="santa_rosa_de_cabal"> Santa Rosa de Cabal
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="santuario"> Santuario
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="trabajo_municipio[]" value="todos_municipios"> En los 12 Municipios
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
                // Obtener abogados
                $query_abogados = "SELECT id, nombre FROM usuarios WHERE tipo_usuario = 'abogado' AND estado = 'activo' ORDER BY nombre";
                $result_abogados = $mysqli->query($query_abogados);
                while($abogado = $result_abogados->fetch_assoc()):
                ?>
                    <option value="<?php echo $abogado['id']; ?>"><?php echo htmlspecialchars($abogado['nombre']); ?></option>
                <?php endwhile; ?>
            </select>
            <small class="form-text">Si asigna un abogado, se comenzará a contar el tiempo desde el momento de asignación.</small>
        </div>
    </div>

    <!-- SECCIÓN 7: TRATAMIENTO DE DATOS -->
    <div class="form-section">
        <div class="form-section-title">
            <i class="fas fa-shield-alt"></i> Tratamiento de Datos Personales
        </div>

        <div class="checkmark-box">
            <label>
                <input type="checkbox" id="aceptacion_datos" name="aceptacion_datos" value="si" required>
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

    <!-- SECCIÓN 8: CARGA DE DOCUMENTOS -->
    <div class="form-section">
        <div class="form-section-title">
            <i class="fas fa-file-upload"></i> Carga de Documentos
        </div>

        <p style="color: var(--gray-600); margin-bottom: 20px;">
            <i class="fas fa-info-circle"></i> 
            Todos los documentos deben estar en formato PDF, JPG, PNG, DOC o DOCX. Tamaño máximo: 10MB por archivo.
        </p>

        <div id="documentos-container"></div>

        <button type="button" class="btn btn-secondary" onclick="agregarDocumento()">
            <i class="fas fa-plus"></i> Agregar Documento
        </button>
    </div>

    <!-- Botones de Acción -->
    <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px;">
        <a href="contrato_listar.php" class="btn btn-light btn-lg">
            <i class="fas fa-times"></i> Cancelar
        </a>
        <button type="submit" class="btn btn-primary btn-lg" data-original-text="Guardar Contrato">
            <i class="fas fa-save"></i> Guardar Contrato
        </button>
    </div>
</form>

<script>
// Establecer fecha actual
document.getElementById('fecha_diligenciamiento').valueAsDate = new Date();

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
    
    // Limpiar valores
    document.getElementById('formacion_tecnica').value = '';
    document.getElementById('formacion_tecnologica').value = '';
    document.getElementById('formacion_pregrado').value = '';
    document.getElementById('formacion_posgrado').value = '';
    document.getElementById('datos_posgrado').value = '';
    document.getElementById('maestria').value = '';
    document.getElementById('posee_doctorado').value = '';
    
    // Remover required
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
let contadorDocumentos = 0;

function agregarDocumento() {
    contadorDocumentos++;
    const container = document.getElementById('documentos-container');
    
    const docDiv = document.createElement('div');
    docDiv.className = 'file-item';
    docDiv.id = `documento-${contadorDocumentos}`;
    docDiv.innerHTML = `
        <div style="flex: 1;">
            <select name="tipo_documento_${contadorDocumentos}" class="form-control form-select" style="margin-bottom: 10px;" required>
                <option value="">Seleccione tipo de documento...</option>
                <option value="autorizacion_tratamiento_datos">Autorización de Tratamiento de Datos</option>
                <option value="autorizacion_consulta_delitos">Autorización Consulta Delitos Sexuales</option>
                <option value="aportes_novedades">Aportes de Novedades</option>
                <option value="declaracion_proactiva">Declaración Proactiva</option>
                <option value="prestacion_servicios">Prestación de Servicios</option>
                <option value="creacion_usuario">Creación Usuario</option>
                <option value="propuesta_economica">Propuesta Económica</option>
            </select>
            <input type="file" name="archivo_${contadorDocumentos}" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
        </div>
        <button type="button" class="btn btn-sm btn-danger" onclick="eliminarDocumento(${contadorDocumentos})">
            <i class="fas fa-trash"></i>
        </button>
    `;
    
    container.appendChild(docDiv);
}

function eliminarDocumento(id) {
    document.getElementById(`documento-${id}`).remove();
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
        
        // Obtener el texto de la respuesta primero
        const responseText = await response.text();
        console.log('Respuesta del servidor:', responseText);
        
        // Intentar parsear como JSON
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('Error al parsear JSON:', parseError);
            console.error('Respuesta recibida:', responseText);
            ocultarLoading();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error en la respuesta del servidor. Por favor, revise la consola para más detalles.'
            });
            return;
        }
        
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
        console.error('Error completo:', error);
        ocultarLoading();
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al procesar la solicitud: ' + error.message
        });
    }
});

// Agregar un documento por defecto al cargar
agregarDocumento();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
