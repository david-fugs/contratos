<?php
$pageTitle = 'Gestión CDP - Contrato';
require_once __DIR__ . '/../includes/header.php';

// Verificar permisos
if (!esAdministradorTecnico() && !esAdministrador()) {
    redirigir('views/dashboard.php');
}

// Obtener ID del contrato
$contrato_id = isset($_GET['contrato_id']) ? intval($_GET['contrato_id']) : 0;

if (!$contrato_id || !puedeVerContrato($contrato_id)) {
    echo '<div class="alert alert-danger">No tiene permisos para acceder a este contrato</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

// Obtener información del contrato
$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM cdp WHERE contrato_id = c.id AND estado = 'activo') as tiene_cdp,
          (SELECT COUNT(*) FROM datos_tecnicos_contrato WHERE contrato_id = c.id) as tiene_datos_tecnicos
          FROM contratos c
          WHERE c.id = ? AND c.estado = 'activo'";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $contrato_id);
$stmt->execute();
$contrato = $stmt->get_result()->fetch_assoc();

if (!$contrato) {
    echo '<div class="alert alert-danger">Contrato no encontrado</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

// Calcular días en etapa actual
$contrato['dias_etapa_actual'] = 0;
if ($contrato['fecha_cambio_estado']) {
    $fecha_cambio = new DateTime($contrato['fecha_cambio_estado']);
    $hoy = new DateTime();
    $contrato['dias_etapa_actual'] = $fecha_cambio->diff($hoy)->days;
}
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-file-invoice-dollar"></i> Gestión CDP - <?php echo htmlspecialchars($contrato['nombre_completo']); ?></h3>
        <button onclick="window.history.back()" class="btn btn-light">
            <i class="fas fa-arrow-left"></i> Volver
        </button>
    </div>
    <div class="card-body">
        <!-- Información del contrato -->
        <div style="background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                <div>
                    <strong>Documento:</strong><br>
                    <?php echo htmlspecialchars($contrato['numero_documento']); ?>
                </div>
                <div>
                    <strong>Estado:</strong><br>
                    <span class="badge badge-info">
                        <?php echo ucwords(str_replace('_', ' ', $contrato['estado_workflow'] ?? 'Desconocido')); ?>
                    </span>
                </div>
                <div>
                    <strong>Días en etapa actual:</strong><br>
                    <?php echo $contrato['dias_etapa_actual'] ?? 0; ?> días
                </div>
                <div>
                    <strong>CDP Registrados:</strong><br>
                    <span id="contadorCDP"><?php echo $contrato['tiene_cdp'] ?? 0; ?></span>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-button active" onclick="cambiarTab(event, 'tabCDP')">
                <i class="fas fa-file-invoice"></i> CDP
            </button>
            <button class="tab-button" onclick="cambiarTab(event, 'tabDatosTecnicos')">
                <i class="fas fa-clipboard-list"></i> Datos Técnicos
            </button>
            <button class="tab-button" onclick="cambiarTab(event, 'tabAsignar')">
                <i class="fas fa-user-tag"></i> Asignar Abogado
            </button>
        </div>

        <!-- Tab CDP -->
        <div id="tabCDP" class="tab-content active">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h4>Certificados de Disponibilidad Presupuestal</h4>
                <button onclick="mostrarFormularioCDP()" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Agregar CDP
                </button>
            </div>

            <div id="listaCDP" style="margin-top: 20px;">
                <!-- Se carga dinámicamente -->
            </div>
        </div>

        <!-- Tab Datos Técnicos -->
        <div id="tabDatosTecnicos" class="tab-content">
            <h4>Datos Técnicos del Contrato</h4>
            <p class="text-muted">Estos datos se habilitan después de la aprobación del contrato</p>
            
            <form id="formDatosTecnicos" style="margin-top: 20px;">
                <input type="hidden" name="action" value="actualizar">
                <input type="hidden" name="contrato_id" value="<?php echo $contrato_id; ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label for="numero_contrato_interno">Número Contrato Interno</label>
                        <input type="text" id="numero_contrato_interno" name="numero_contrato_interno" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="numero_contrato_secop">Número Contrato SECOP</label>
                        <input type="text" id="numero_contrato_secop" name="numero_contrato_secop" class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_firma_contrato">Fecha de Firma de Contrato</label>
                        <input type="date" id="fecha_firma_contrato" name="fecha_firma_contrato" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="plazo_inicial_dias">Plazo Inicial (Días)</label>
                        <input type="number" id="plazo_inicial_dias" name="plazo_inicial_dias" class="form-control" min="1">
                    </div>
                </div>

                <div class="form-group">
                    <label for="enlace_secop_ii">Enlace SECOP II</label>
                    <input type="url" id="enlace_secop_ii" name="enlace_secop_ii" class="form-control" placeholder="https://...">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="solicitud_arl">Solicitud ARL</label>
                        <select id="solicitud_arl" name="solicitud_arl" class="form-control form-select">
                            <option value="">Seleccione...</option>
                            <option value="si">Sí</option>
                            <option value="no">No</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="fecha_aprobacion_arl">Fecha Aprobación ARL</label>
                        <input type="date" id="fecha_aprobacion_arl" name="fecha_aprobacion_arl" class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="solicitud_rp">Solicitud RP</label>
                        <select id="solicitud_rp" name="solicitud_rp" class="form-control form-select">
                            <option value="">Seleccione...</option>
                            <option value="si">Sí</option>
                            <option value="no">No</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="numero_rp">Número RP</label>
                        <input type="text" id="numero_rp" name="numero_rp" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="fecha_rp">Fecha RP</label>
                        <input type="date" id="fecha_rp" name="fecha_rp" class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre_supervisor">Nombre Supervisor</label>
                        <input type="text" id="nombre_supervisor" name="nombre_supervisor" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="notificacion_supervision">Notificación Supervisión</label>
                        <select id="notificacion_supervision" name="notificacion_supervision" class="form-control form-select">
                            <option value="">Seleccione...</option>
                            <option value="si">Sí</option>
                            <option value="no">No</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="acta_inicio_entregada">Acta de Inicio Entregada</label>
                        <select id="acta_inicio_entregada" name="acta_inicio_entregada" class="form-control form-select">
                            <option value="">Seleccione...</option>
                            <option value="si">Sí</option>
                            <option value="no">No</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="estado_secop">Estado en el SECOP</label>
                        <select id="estado_secop" name="estado_secop" class="form-control form-select">
                            <option value="">Seleccione...</option>
                            <option value="en_ejecucion">En Ejecución</option>
                            <option value="firmado">Firmado</option>
                            <option value="terminado">Terminado</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Datos Técnicos
                    </button>
                </div>
            </form>
        </div>

        <!-- Tab Asignar Abogado -->
        <div id="tabAsignar" class="tab-content">
            <h4>Asignar Abogado para Revisión</h4>
            <p class="text-muted">Asigne un abogado para la revisión legal del contrato</p>
            
            <form id="formAsignarAbogado" style="margin-top: 20px;">
                <input type="hidden" name="action" value="asignar_usuario">
                <input type="hidden" name="contrato_id" value="<?php echo $contrato_id; ?>">
                <input type="hidden" name="etapa" value="<?php echo $contrato['estado_workflow'] ?? 'administracion_tecnica'; ?>">

                <div class="form-group">
                    <label for="abogado_asignado">Seleccionar Abogado</label>
                    <select id="abogado_asignado" name="usuario_asignado" class="form-control form-select" required>
                        <option value="">Cargando abogados...</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="comentarios_asignacion">Comentarios (opcional)</label>
                    <textarea id="comentarios_asignacion" name="comentarios" class="form-control" rows="3" 
                              placeholder="Instrucciones o comentarios para el abogado..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-user-check"></i> Asignar Abogado
                </button>
            </form>

            <!-- Historial de asignaciones -->
            <div id="historialAsignaciones" style="margin-top: 30px;">
                <h5>Historial de Asignaciones</h5>
                <div id="listaAsignaciones"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal CDP -->
<div id="modalCDP" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h3 id="modalCDPTitulo">Agregar CDP</h3>
            <button class="modal-close" onclick="cerrarModalCDP()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formCDP" enctype="multipart/form-data">
                <input type="hidden" name="action" value="crear" id="cdp_action">
                <input type="hidden" name="id" id="cdp_id">
                <input type="hidden" name="contrato_id" value="<?php echo $contrato_id; ?>">

                <div class="form-group">
                    <label for="fecha_cdp" class="required">Fecha CDP</label>
                    <input type="date" id="fecha_cdp" name="fecha_cdp" class="form-control" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="rubro" class="required">Rubro (Alfanumérico)</label>
                        <input type="text" id="rubro" name="rubro" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="valor" class="required">Valor</label>
                        <input type="text" id="valor" name="valor" class="form-control" required 
                               placeholder="$ 0.00" onkeyup="formatearMoneda(this)">
                    </div>
                </div>

                <div class="form-group">
                    <label for="nombre_rubro" class="required">Nombre Rubro</label>
                    <input type="text" id="nombre_rubro" name="nombre_rubro" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="objeto">Objeto</label>
                    <textarea id="objeto" name="objeto" class="form-control" rows="3"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="numero_proceso">Número del Proceso (Veeduría)</label>
                        <input type="text" id="numero_proceso" name="numero_proceso" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="dependencia" class="required">Dependencia</label>
                        <select id="dependencia" name="dependencia" class="form-control form-select" required>
                            <option value="">Seleccione...</option>
                            <option value="despacho">Despacho</option>
                            <option value="administrativa">Administrativa</option>
                            <option value="cobertura">Cobertura</option>
                            <option value="calidad">Calidad</option>
                            <option value="planeacion">Planeación</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="archivo_pdf">Cargar PDF</label>
                    <input type="file" id="archivo_pdf" name="archivo_pdf" class="form-control" accept=".pdf">
                    <small class="form-text text-muted">Solo archivos PDF</small>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn btn-light" onclick="cerrarModalCDP()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar CDP
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../assets/js/cdp_gestion.js"></script>

<style>
.tabs {
    display: flex;
    border-bottom: 2px solid #e5e7eb;
    margin-bottom: 20px;
}

.tab-button {
    padding: 12px 24px;
    background: transparent;
    border: none;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    color: #6b7280;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all 0.3s;
}

.tab-button:hover {
    color: var(--primary-color);
}

.tab-button.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}

.tab-content {
    display: none;
    padding: 20px 0;
}

.tab-content.active {
    display: block;
}

.cdp-item {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    transition: box-shadow 0.3s;
}

.cdp-item:hover {
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
}
</style>

<script>
function cambiarTab(event, tabId) {
    // Ocultar todos los tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remover active de todos los botones
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Mostrar tab seleccionado
    document.getElementById(tabId).classList.add('active');
    event.currentTarget.classList.add('active');
    
    // Cargar datos si es necesario
    if (tabId === 'tabCDP') {
        cargarCDPs();
    } else if (tabId === 'tabDatosTecnicos') {
        cargarDatosTecnicos();
    } else if (tabId === 'tabAsignar') {
        cargarAbogados();
        cargarHistorialAsignaciones();
    }
}

function formatearMoneda(input) {
    let valor = input.value.replace(/[^0-9]/g, '');
    if (valor) {
        valor = parseFloat(valor);
        input.value = '$ ' + valor.toLocaleString('es-CO');
    }
}

// Cargar al iniciar
document.addEventListener('DOMContentLoaded', function() {
    cargarCDPs();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
