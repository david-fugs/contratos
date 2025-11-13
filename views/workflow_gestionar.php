<?php
$pageTitle = 'Gestión de Workflow';
require_once __DIR__ . '/../includes/header.php';

// Obtener ID del contrato
$contrato_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$contrato_id) {
    echo '<div class="alert alert-danger">ID de contrato no proporcionado</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Obtener información completa del contrato
$query = "SELECT c.*, u.nombre as nombre_usuario_creacion,
          (SELECT COUNT(*) FROM documentos WHERE contrato_id = c.id) as total_documentos,
          (SELECT COUNT(*) FROM documentos WHERE contrato_id = c.id AND estado_revision = 'aprobado') as documentos_aprobados,
          (SELECT COUNT(*) FROM documentos WHERE contrato_id = c.id AND estado_revision = 'pendiente') as documentos_pendientes,
          (SELECT etapa FROM asignaciones_workflow 
           WHERE contrato_id = c.id AND estado IN ('pendiente', 'en_proceso')
           ORDER BY fecha_asignacion DESC LIMIT 1) as etapa_actual,
          (SELECT usuario_asignado FROM asignaciones_workflow 
           WHERE contrato_id = c.id AND estado IN ('pendiente', 'en_proceso')
           ORDER BY fecha_asignacion DESC LIMIT 1) as usuario_asignado_actual,
          (SELECT COUNT(*) FROM cdp WHERE contrato_id = c.id AND estado = 'activo') as tiene_cdp,
          (SELECT COUNT(*) FROM datos_tecnicos_contrato WHERE contrato_id = c.id) as tiene_datos_tecnicos
          FROM contratos c
          LEFT JOIN usuarios u ON c.usuario_creacion = u.id
          WHERE c.id = ? AND c.estado = 'activo'";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $contrato_id);
$stmt->execute();
$contrato = $stmt->get_result()->fetch_assoc();

if (!$contrato) {
    echo '<div class="alert alert-danger">Contrato no encontrado</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Obtener información del CDP si existe
$cdp_info = null;
$cdp_aprobado = false;
if ($contrato['tiene_cdp']) {
    $stmt_cdp = $mysqli->prepare("SELECT * FROM cdp WHERE contrato_id = ? AND estado = 'activo' ORDER BY fecha_creacion DESC LIMIT 1");
    $stmt_cdp->bind_param("i", $contrato_id);
    $stmt_cdp->execute();
    $cdp_info = $stmt_cdp->get_result()->fetch_assoc();
    $cdp_aprobado = ($cdp_info && $cdp_info['estado_aprobacion'] == 'aprobado');
}

// Determinar acciones disponibles según rol y estado
$puede_aprobar_inicial = esAbogado() && $contrato['estado_workflow'] == 'en_creacion';
$puede_revisar_docs = esRevisorDocumentos() && $contrato['estado_workflow'] == 'revision_documentos';
$puede_aprobar_revision = esAbogado() && $contrato['estado_workflow'] == 'revision_abogado' && !$contrato['tiene_cdp'];
$puede_gestionar_cdp = esAdministradorTecnico() && $contrato['estado_workflow'] == 'administracion_tecnica' && !$contrato['tiene_cdp'];
$puede_aprobar_cdp = esAbogado() && $contrato['estado_workflow'] == 'administracion_tecnica' && $contrato['tiene_cdp'] && !$cdp_aprobado;
$puede_datos_tecnicos = esAdministradorTecnico() && $contrato['estado_workflow'] == 'administracion_tecnica' && $cdp_aprobado;
$puede_cambiar_estado_final = esAbogado() && $contrato['estado_workflow'] == 'administracion_tecnica' && $contrato['tiene_datos_tecnicos'];

$usuario_id = $_SESSION['usuario_id'];
$es_asignado = ($contrato['usuario_asignado_actual'] == $usuario_id);
?>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3><i class="fas fa-stream"></i> Gestión de Workflow - <?php echo htmlspecialchars($contrato['nombre_completo']); ?></h3>
        <a href="contrato_listar.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Listado
        </a>
    </div>
    <div class="card-body">
        <!-- Información del Contrato -->
        <div class="info-section" style="background: var(--gray-50); padding: 20px; border-radius: 8px; margin-bottom: 30px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div>
                    <strong style="color: var(--gray-600);">Documento:</strong>
                    <p style="margin: 5px 0 0 0;"><?php echo $contrato['numero_documento']; ?></p>
                </div>
                <div>
                    <strong style="color: var(--gray-600);">Creado por:</strong>
                    <p style="margin: 5px 0 0 0;"><?php echo htmlspecialchars($contrato['nombre_usuario_creacion']); ?></p>
                </div>
                <div>
                    <strong style="color: var(--gray-600);">Estado Actual:</strong>
                    <p style="margin: 5px 0 0 0;">
                        <span class="badge badge-primary">
                            <?php echo ucwords(str_replace('_', ' ', $contrato['estado_workflow'])); ?>
                        </span>
                    </p>
                </div>
                <div>
                    <strong style="color: var(--gray-600);">Documentos:</strong>
                    <p style="margin: 5px 0 0 0;">
                        <?php echo $contrato['documentos_aprobados']; ?> / <?php echo $contrato['total_documentos']; ?> aprobados
                    </p>
                </div>
            </div>
        </div>

        <!-- ETAPA 1: Aprobación Inicial por Abogado -->
        <?php if ($puede_aprobar_inicial): ?>
        <div class="workflow-step" style="border: 2px solid var(--primary-color); border-radius: 8px; padding: 20px; margin-bottom: 20px;">
            <h4 style="margin-top: 0; color: var(--primary-color);">
                <i class="fas fa-check-circle"></i> Aprobación Inicial del Contrato
            </h4>
            <p>Como abogado, debes revisar y aprobar el contrato para que avance a revisión de documentos.</p>
            <button onclick="aprobarInicial(<?php echo $contrato_id; ?>)" class="btn btn-success">
                <i class="fas fa-thumbs-up"></i> Aprobar y Enviar a Revisión de Documentos
            </button>
        </div>
        <?php endif; ?>

        <!-- ETAPA 2: Revisión de Documentos -->
        <?php if ($puede_revisar_docs): ?>
        <div class="workflow-step" style="border: 2px solid var(--info-color); border-radius: 8px; padding: 20px; margin-bottom: 20px;">
            <h4 style="margin-top: 0; color: var(--info-color);">
                <i class="fas fa-file-check"></i> Revisión de Documentos
            </h4>
            <p>Revisa y aprueba/rechaza todos los documentos del contrato.</p>
            <?php if ($contrato['documentos_pendientes'] > 0): ?>
            <div class="alert alert-warning">
                Quedan <?php echo $contrato['documentos_pendientes']; ?> documento(s) pendiente(s) de revisar.
            </div>
            <a href="contrato_listar.php" class="btn btn-primary">
                <i class="fas fa-eye"></i> Ver y Revisar Documentos
            </a>
            <?php else: ?>
            <div class="alert alert-success">
                ✅ Todos los documentos han sido revisados.
            </div>
            <p>Ya puedes asignar un abogado desde el modal de documentos en el listado.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ETAPA 3: Revisión por Abogado (después de documentos) -->
        <?php if ($puede_aprobar_revision && $es_asignado): ?>
        <div class="workflow-step" style="border: 2px solid var(--warning-color); border-radius: 8px; padding: 20px; margin-bottom: 20px;">
            <h4 style="margin-top: 0; color: var(--warning-color);">
                <i class="fas fa-gavel"></i> Revisión de Documentos Aprobados
            </h4>
            <p>Los documentos han sido aprobados. Revísalos y envía el contrato a Administración Técnica para agregar el CDP.</p>
            <button onclick="aprobarYEnviarAdminTecnica(<?php echo $contrato_id; ?>)" class="btn btn-success">
                <i class="fas fa-forward"></i> Aprobar y Enviar a Administración Técnica
            </button>
        </div>
        <?php endif; ?>

        <!-- ETAPA 4: Gestión de CDP por Admin Técnico -->
        <?php if ($puede_gestionar_cdp): ?>
        <div class="workflow-step" style="border: 2px solid var(--secondary-color); border-radius: 8px; padding: 20px; margin-bottom: 20px;">
            <h4 style="margin-top: 0; color: var(--secondary-color);">
                <i class="fas fa-file-invoice-dollar"></i> Gestión de CDP
            </h4>
            <p>Agrega la información del Certificado de Disponibilidad Presupuestal.</p>
            <a href="cdp_gestionar.php?contrato_id=<?php echo $contrato_id; ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Agregar CDP
            </a>
        </div>
        <?php endif; ?>

        <!-- ETAPA 5: Aprobación de CDP por Abogado -->
        <?php if ($puede_aprobar_cdp && $es_asignado): ?>
        <div class="workflow-step" style="border: 2px solid var(--success-color); border-radius: 8px; padding: 20px; margin-bottom: 20px;">
            <h4 style="margin-top: 0; color: var(--success-color);">
                <i class="fas fa-check-double"></i> Aprobación de CDP
            </h4>
            <p>El CDP ha sido agregado. Revísalo y apruébalo para habilitar los datos técnicos.</p>
            
            <?php if ($cdp_info): ?>
            <div class="alert alert-info" style="margin: 15px 0;">
                <strong>CDP Registrado:</strong><br>
                <strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($cdp_info['fecha_cdp'])); ?><br>
                <strong>Rubro:</strong> <?php echo htmlspecialchars($cdp_info['rubro']); ?> - <?php echo htmlspecialchars($cdp_info['nombre_rubro']); ?><br>
                <strong>Valor:</strong> $<?php echo number_format($cdp_info['valor'], 2); ?><br>
                <strong>Dependencia:</strong> <?php echo ucfirst($cdp_info['dependencia']); ?><br>
                <button onclick="verDetallesCDP(<?php echo $cdp_info['id']; ?>)" class="btn btn-sm btn-info" style="margin-top: 10px;">
                    <i class="fas fa-eye"></i> Ver Detalles Completos
                </button>
            </div>
            <?php endif; ?>
            
            <button onclick="aprobarCDP(<?php echo $contrato_id; ?>, <?php echo $cdp_info['id']; ?>)" class="btn btn-success">
                <i class="fas fa-check"></i> Aprobar CDP y Habilitar Datos Técnicos
            </button>
        </div>
        <?php endif; ?>

        <!-- ETAPA 6: Datos Técnicos Post-Aprobación -->
        <?php if ($puede_datos_tecnicos): ?>
        <div class="workflow-step" style="border: 2px solid var(--info-color); border-radius: 8px; padding: 20px; margin-bottom: 20px;">
            <h4 style="margin-top: 0; color: var(--info-color);">
                <i class="fas fa-cogs"></i> Datos Técnicos Post-Aprobación
            </h4>
            <?php if ($cdp_aprobado): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> CDP aprobado. Puedes completar los datos técnicos.
                </div>
                <p>Completa los datos técnicos del contrato (número interno, SECOP, supervisor, etc.)</p>
                <a href="cdp_gestionar.php?contrato_id=<?php echo $contrato_id; ?>&tab=datos_tecnicos" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Completar Datos Técnicos
                </a>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> El CDP debe ser aprobado por un abogado antes de completar los datos técnicos.
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ETAPA 7: Cambio de Estado Final por Abogado -->
        <?php if ($puede_cambiar_estado_final && $es_asignado): ?>
        <div class="workflow-step" style="border: 2px solid var(--danger-color); border-radius: 8px; padding: 20px; margin-bottom: 20px;">
            <h4 style="margin-top: 0; color: var(--danger-color);">
                <i class="fas fa-flag-checkered"></i> Estado Final del Contrato
            </h4>
            <p>Cambia el estado final del contrato según corresponda:</p>
            <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px;">
                <button onclick="cambiarEstadoFinal(<?php echo $contrato_id; ?>, 'en_elaboracion')" class="btn btn-info">
                    <i class="fas fa-pencil-alt"></i> En Elaboración
                </button>
                <button onclick="cambiarEstadoFinal(<?php echo $contrato_id; ?>, 'para_firmas')" class="btn btn-warning">
                    <i class="fas fa-signature"></i> Para Firmas
                </button>
                <button onclick="cambiarEstadoFinal(<?php echo $contrato_id; ?>, 'publicado_aprobado')" class="btn btn-success">
                    <i class="fas fa-check-circle"></i> Publicado Aprobado
                </button>
                <button onclick="cambiarEstadoFinal(<?php echo $contrato_id; ?>, 'publicado_rechazado')" class="btn btn-danger">
                    <i class="fas fa-times-circle"></i> Publicado Rechazado
                </button>
                <button onclick="cambiarEstadoFinal(<?php echo $contrato_id; ?>, 'publicado_corregido')" class="btn btn-secondary">
                    <i class="fas fa-edit"></i> Publicado Corregido
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Mensaje cuando no hay acciones disponibles -->
        <?php if (!$puede_aprobar_inicial && !$puede_revisar_docs && !$puede_aprobar_revision && 
                  !$puede_gestionar_cdp && !$puede_aprobar_cdp && !$puede_datos_tecnicos && !$puede_cambiar_estado_final): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            No hay acciones disponibles para tu rol en esta etapa del contrato.
            <?php if (!$es_asignado && $contrato['etapa_actual']): ?>
            <br>Este contrato está asignado a otro usuario.
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Timeline de Historial -->
        <div style="margin-top: 40px;">
            <h4><i class="fas fa-history"></i> Historial del Contrato</h4>
            <div id="timelineHistorial"></div>
        </div>
    </div>
</div>

<script>
const CONTRATO_ID = <?php echo $contrato_id; ?>;

// Aprobar contrato inicial
function aprobarInicial(contratoId) {
    Swal.fire({
        title: '¿Aprobar contrato?',
        text: 'El contrato pasará a revisión de documentos',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, aprobar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'cambiar_estado');
            formData.append('contrato_id', contratoId);
            formData.append('nuevo_estado', 'revision_documentos');

            fetch('../controllers/workflow_controller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('¡Aprobado!', 'El contrato ha sido enviado a revisión de documentos', 'success')
                        .then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
}

// Aprobar y enviar a admin técnica
function aprobarYEnviarAdminTecnica(contratoId) {
    Swal.fire({
        title: '¿Enviar a Administración Técnica?',
        text: 'El contrato pasará a la etapa de gestión de CDP',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, enviar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'cambiar_estado');
            formData.append('contrato_id', contratoId);
            formData.append('nuevo_estado', 'administracion_tecnica');

            fetch('../controllers/workflow_controller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('¡Enviado!', 'El contrato ha sido enviado a Administración Técnica', 'success')
                        .then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
}

// Aprobar CDP
function aprobarCDP(contratoId, cdpId) {
    Swal.fire({
        title: '¿Aprobar CDP?',
        text: 'Se habilitarán los datos técnicos para el administrador técnico',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, aprobar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'aprobar_cdp');
            formData.append('cdp_id', cdpId);

            fetch('../controllers/cdp_controller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('¡Aprobado!', 'El CDP ha sido aprobado. El administrador técnico puede completar los datos técnicos.', 'success')
                        .then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message || 'No se pudo aprobar el CDP', 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error', 'Error de conexión: ' + error, 'error');
            });
        }
    });
}

// Cambiar estado final
function cambiarEstadoFinal(contratoId, nuevoEstado) {
    const estados = {
        'en_elaboracion': 'En Elaboración',
        'para_firmas': 'Para Firmas',
        'publicado_aprobado': 'Publicado Aprobado',
        'publicado_rechazado': 'Publicado Rechazado',
        'publicado_corregido': 'Publicado Corregido'
    };

    Swal.fire({
        title: `¿Cambiar estado a "${estados[nuevoEstado]}"?`,
        text: 'Este es el estado final del contrato',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, cambiar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'cambiar_estado');
            formData.append('contrato_id', contratoId);
            formData.append('nuevo_estado', nuevoEstado);

            fetch('../controllers/workflow_controller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('¡Actualizado!', `El contrato ahora está en estado: ${estados[nuevoEstado]}`, 'success')
                        .then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
}

// Ver detalles completos del CDP en modal
function verDetallesCDP(cdpId) {
    fetch(`../controllers/cdp_controller.php?action=obtener_cdp&cdp_id=${cdpId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const cdp = data.data;
                Swal.fire({
                    title: '<strong>Detalles del CDP</strong>',
                    html: `
                        <div style="text-align: left; padding: 20px;">
                            <div style="margin-bottom: 15px;">
                                <strong>Fecha CDP:</strong><br>
                                ${cdp.fecha_cdp}
                            </div>
                            <div style="margin-bottom: 15px;">
                                <strong>Rubro:</strong><br>
                                ${cdp.rubro}
                            </div>
                            <div style="margin-bottom: 15px;">
                                <strong>Nombre del Rubro:</strong><br>
                                ${cdp.nombre_rubro}
                            </div>
                            <div style="margin-bottom: 15px;">
                                <strong>Valor:</strong><br>
                                $${parseFloat(cdp.valor).toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                            </div>
                            <div style="margin-bottom: 15px;">
                                <strong>Objeto:</strong><br>
                                ${cdp.objeto || 'No especificado'}
                            </div>
                            <div style="margin-bottom: 15px;">
                                <strong>Número de Proceso:</strong><br>
                                ${cdp.numero_proceso || 'No especificado'}
                            </div>
                            <div style="margin-bottom: 15px;">
                                <strong>Dependencia:</strong><br>
                                ${cdp.dependencia ? cdp.dependencia.charAt(0).toUpperCase() + cdp.dependencia.slice(1) : 'No especificado'}
                            </div>
                            ${cdp.archivo_pdf ? `
                            <div style="margin-bottom: 15px;">
                                <strong>Archivo PDF:</strong><br>
                                <a href="../uploads/cdp/${cdp.archivo_pdf}" target="_blank" class="btn btn-sm btn-info">
                                    <i class="fas fa-file-pdf"></i> Ver PDF
                                </a>
                            </div>
                            ` : ''}
                        </div>
                    `,
                    width: '600px',
                    confirmButtonText: 'Cerrar',
                    confirmButtonColor: '#3b82f6'
                });
            } else {
                Swal.fire('Error', 'No se pudieron cargar los detalles del CDP', 'error');
            }
        })
        .catch(error => {
            Swal.fire('Error', 'Error de conexión: ' + error, 'error');
        });
}

// Cargar historial al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    cargarHistorial();
});

function cargarHistorial() {
    fetch(`../controllers/workflow_controller.php?action=obtener_timeline&contrato_id=${CONTRATO_ID}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                mostrarTimeline(data.data);
            }
        });
}

function mostrarTimeline(eventos) {
    const container = document.getElementById('timelineHistorial');
    if (eventos.length === 0) {
        container.innerHTML = '<p style="color: var(--gray-500);">No hay eventos registrados aún.</p>';
        return;
    }

    let html = '<div class="timeline">';
    eventos.forEach(evento => {
        html += `
            <div class="timeline-item">
                <div class="timeline-marker"></div>
                <div class="timeline-content">
                    <div style="font-weight: 600; color: var(--primary-color);">
                        ${evento.etapa || evento.estado}
                    </div>
                    <div style="font-size: 13px; color: var(--gray-600); margin-top: 5px;">
                        ${evento.fecha} ${evento.usuario ? '- ' + evento.usuario : ''}
                    </div>
                    ${evento.comentarios ? `<div style="margin-top: 8px; font-size: 13px;">${evento.comentarios}</div>` : ''}
                </div>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
}
</script>

<style>
.workflow-step {
    transition: transform 0.2s;
}

.workflow-step:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--gray-300);
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
    padding-left: 30px;
}

.timeline-marker {
    position: absolute;
    left: -24px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--primary-color);
    border: 2px solid white;
    box-shadow: 0 0 0 2px var(--primary-color);
}

.timeline-content {
    background: var(--gray-50);
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid var(--primary-color);
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
