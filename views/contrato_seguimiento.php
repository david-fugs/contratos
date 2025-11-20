<?php
$pageTitle = 'Seguimiento de Contratos';
require_once __DIR__ . '/../includes/header.php';

$usuarioActual = obtenerUsuarioActual();
$tipoUsuario = $usuarioActual['tipo_usuario'];

// Construir consulta según el tipo de usuario
if ($tipoUsuario === 'usuario') {
    // Los usuarios solo ven sus propios contratos
    $whereClause = "WHERE c.usuario_creacion = " . $usuarioActual['id'];
} elseif ($tipoUsuario === 'abogado') {
    // Los abogados ven contratos asignados a ellos
    $whereClause = "WHERE (c.abogado_asignado = " . $usuarioActual['id'] . " 
                    OR EXISTS (
                        SELECT 1 FROM asignaciones_workflow aw 
                        WHERE aw.contrato_id = c.id 
                        AND aw.usuario_asignado = " . $usuarioActual['id'] . "
                    ))";
} else {
    // Otros roles ven todos los contratos
    $whereClause = "WHERE 1=1";
}

// Obtener contratos con información de workflow y documentos
$query = "SELECT c.*, 
          u.nombre as nombre_usuario_creacion,
          a.nombre as nombre_abogado_asignado,
          (SELECT COUNT(*) FROM documentos WHERE contrato_id = c.id) as total_documentos,
          (SELECT COUNT(*) FROM documentos WHERE contrato_id = c.id AND estado_revision = 'aprobado') as documentos_aprobados,
          (SELECT COUNT(*) FROM documentos WHERE contrato_id = c.id AND estado_revision = 'rechazado') as documentos_rechazados,
          (SELECT COUNT(*) FROM documentos WHERE contrato_id = c.id AND (estado_revision = 'pendiente' OR estado_revision IS NULL)) as documentos_pendientes,
          c.estado_workflow,
          c.fecha_cambio_estado,
          c.puede_editar,
          (SELECT u2.nombre FROM asignaciones_workflow aw
           JOIN usuarios u2 ON aw.usuario_asignado = u2.id
           WHERE aw.contrato_id = c.id AND aw.estado IN ('pendiente', 'en_proceso')
           ORDER BY aw.fecha_asignacion DESC LIMIT 1) as usuario_asignado_actual,
          (SELECT etapa FROM asignaciones_workflow 
           WHERE contrato_id = c.id AND estado IN ('pendiente', 'en_proceso')
           ORDER BY fecha_asignacion DESC LIMIT 1) as etapa_actual,
          (SELECT DATEDIFF(NOW(), fecha_asignacion) FROM asignaciones_workflow 
           WHERE contrato_id = c.id AND estado IN ('pendiente', 'en_proceso')
           ORDER BY fecha_asignacion DESC LIMIT 1) as dias_etapa_actual,
          cdp.id as cdp_id,
          cdp.numero_proceso as cdp_numero_proceso,
          cdp.valor as cdp_valor,
          cdp.estado_aprobacion as cdp_estado,
          dt.numero_contrato,
          dt.numero_secop,
          dt.supervisor
          FROM contratos c
          LEFT JOIN usuarios u ON c.usuario_creacion = u.id
          LEFT JOIN usuarios a ON c.abogado_asignado = a.id
          LEFT JOIN cdp ON cdp.contrato_id = c.id
          LEFT JOIN datos_tecnicos dt ON dt.contrato_id = c.id
          $whereClause AND c.estado = 'activo'
          ORDER BY c.fecha_creacion DESC";

$result = $mysqli->query($query);
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-chart-line"></i> Seguimiento de Contratos</h3>
        <div style="display: flex; gap: 10px; align-items: center;">
            <span style="color: var(--gray-600); font-size: 14px;">
                <?php 
                if ($tipoUsuario === 'usuario') {
                    echo 'Mis contratos';
                } elseif ($tipoUsuario === 'abogado') {
                    echo 'Contratos asignados';
                } else {
                    echo 'Todos los contratos';
                }
                ?>
            </span>
        </div>
    </div>
    <div class="card-body">
        <!-- Filtros -->
        <div style="margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 250px;">
                <input type="text" 
                       id="filtroTabla" 
                       class="form-control" 
                       placeholder="🔍 Buscar por nombre, documento...">
            </div>
            <div>
                <select id="filtroEstado" class="form-control form-select" style="min-width: 200px;">
                    <option value="">Todos los estados</option>
                    <option value="en_creacion">En Creación</option>
                    <option value="revision_documentos">Revisión Documentos</option>
                    <option value="revision_abogado">Revisión Abogado</option>
                    <option value="administracion_tecnica">Administración Técnica</option>
                    <option value="en_elaboracion">En Elaboración</option>
                    <option value="para_firmas">Para Firmas</option>
                    <option value="publicado_aprobado">Publicado Aprobado</option>
                    <option value="publicado_rechazado">Publicado Rechazado</option>
                    <option value="publicado_corregido">Publicado Corregido</option>
                </select>
            </div>
        </div>

        <!-- Leyenda de Estados -->
        <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <h5 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 600;">📊 Leyenda de Estados</h5>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; font-size: 13px;">
                <div><span class="badge badge-secondary">En Creación</span> - Contrato recién creado</div>
                <div><span class="badge badge-primary">Revisión Documentos</span> - Documentos en revisión</div>
                <div><span class="badge badge-warning">Revisión Abogado</span> - Abogado revisando</div>
                <div><span class="badge badge-info">Admin Técnica</span> - Administración técnica</div>
                <div><span class="badge badge-success">Publicado Aprobado</span> - Finalizado exitosamente</div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="table-responsive">
            <table class="table" id="tablaSeguimiento">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Contrato</th>
                        <th>Estado Actual</th>
                        <th>Asignado A</th>
                        <th>Documentos</th>
                        <th>Progreso</th>
                        <th>CDP</th>
                        <th>Datos Técnicos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($contrato = $result->fetch_assoc()): 
                            // Determinar color del estado
                            $estados_colores = [
                                'en_creacion' => 'secondary',
                                'revision_documentos' => 'primary',
                                'administracion_tecnica' => 'info',
                                'revision_abogado' => 'warning',
                                'en_elaboracion' => 'info',
                                'para_firmas' => 'success',
                                'publicado_aprobado' => 'success',
                                'publicado_rechazado' => 'danger',
                                'publicado_corregido' => 'warning'
                            ];
                            $badge_class = $estados_colores[$contrato['estado_workflow']] ?? 'secondary';
                            
                            // Calcular progreso general
                            $pasos_completados = 0;
                            $pasos_totales = 8;
                            
                            if ($contrato['estado_workflow'] != 'en_creacion') $pasos_completados++;
                            if ($contrato['estado_workflow'] == 'revision_documentos' || in_array($contrato['estado_workflow'], ['revision_abogado', 'administracion_tecnica', 'en_elaboracion', 'para_firmas', 'publicado_aprobado'])) $pasos_completados++;
                            if ($contrato['estado_workflow'] == 'revision_abogado' || in_array($contrato['estado_workflow'], ['administracion_tecnica', 'en_elaboracion', 'para_firmas', 'publicado_aprobado'])) $pasos_completados++;
                            if ($contrato['estado_workflow'] == 'administracion_tecnica' || in_array($contrato['estado_workflow'], ['en_elaboracion', 'para_firmas', 'publicado_aprobado'])) $pasos_completados++;
                            if ($contrato['cdp_id']) $pasos_completados++;
                            if ($contrato['cdp_estado'] == 'aprobado') $pasos_completados++;
                            if ($contrato['numero_contrato']) $pasos_completados++;
                            if (in_array($contrato['estado_workflow'], ['publicado_aprobado', 'publicado_rechazado', 'publicado_corregido'])) $pasos_completados++;
                            
                            $porcentaje_progreso = round(($pasos_completados / $pasos_totales) * 100);
                        ?>
                        <tr data-estado="<?php echo $contrato['estado_workflow']; ?>">
                            <td><?php echo $contrato['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($contrato['nombre_completo']); ?></strong>
                                <br>
                                <small style="color: var(--gray-600);">
                                    Doc: <?php echo $contrato['numero_documento']; ?>
                                </small>
                                <br>
                                <small style="color: var(--gray-500);">
                                    Creado: <?php echo formatearFecha($contrato['fecha_creacion']); ?>
                                </small>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $badge_class; ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $contrato['estado_workflow'] ?? 'en_creacion')); ?>
                                </span>
                                <?php if ($contrato['fecha_cambio_estado']): ?>
                                <br>
                                <small style="color: var(--gray-500); font-size: 11px;">
                                    <?php echo formatearFecha($contrato['fecha_cambio_estado']); ?>
                                </small>
                                <?php endif; ?>
                                <?php if ($contrato['dias_etapa_actual']): ?>
                                <br>
                                <small style="color: var(--warning-color); font-weight: 600;">
                                    <i class="fas fa-clock"></i> <?php echo $contrato['dias_etapa_actual']; ?> día(s)
                                </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($contrato['usuario_asignado_actual']): ?>
                                    <span class="badge badge-info" style="font-size: 12px;">
                                        <?php echo htmlspecialchars($contrato['usuario_asignado_actual']); ?>
                                    </span>
                                    <br>
                                    <small style="color: var(--gray-600);">
                                        <?php echo ucwords(str_replace('_', ' ', $contrato['etapa_actual'] ?? '')); ?>
                                    </small>
                                <?php elseif ($contrato['nombre_abogado_asignado']): ?>
                                    <span class="badge badge-secondary" style="font-size: 12px;">
                                        <?php echo htmlspecialchars($contrato['nombre_abogado_asignado']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--gray-400);">Sin asignar</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-size: 12px;">
                                    <div style="display: flex; gap: 5px; align-items: center; margin-bottom: 3px;">
                                        <i class="fas fa-file" style="color: var(--gray-500);"></i>
                                        <span><strong><?php echo $contrato['total_documentos']; ?></strong> total</span>
                                    </div>
                                    <?php if ($contrato['total_documentos'] > 0): ?>
                                    <div style="display: flex; gap: 5px; align-items: center; margin-bottom: 3px;">
                                        <i class="fas fa-check-circle" style="color: #10b981;"></i>
                                        <span><?php echo $contrato['documentos_aprobados']; ?> aprobados</span>
                                    </div>
                                    <?php if ($contrato['documentos_rechazados'] > 0): ?>
                                    <div style="display: flex; gap: 5px; align-items: center; margin-bottom: 3px;">
                                        <i class="fas fa-times-circle" style="color: #ef4444;"></i>
                                        <span><?php echo $contrato['documentos_rechazados']; ?> rechazados</span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($contrato['documentos_pendientes'] > 0): ?>
                                    <div style="display: flex; gap: 5px; align-items: center;">
                                        <i class="fas fa-clock" style="color: #f59e0b;"></i>
                                        <span><?php echo $contrato['documentos_pendientes']; ?> pendientes</span>
                                    </div>
                                    <?php endif; ?>
                                    <?php else: ?>
                                    <div style="color: var(--gray-400); font-size: 11px;">Sin documentos</div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div style="min-width: 120px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                        <span style="font-size: 12px; font-weight: 600;"><?php echo $porcentaje_progreso; ?>%</span>
                                        <span style="font-size: 11px; color: var(--gray-500);"><?php echo $pasos_completados; ?>/<?php echo $pasos_totales; ?></span>
                                    </div>
                                    <div style="background: #e5e7eb; height: 8px; border-radius: 4px; overflow: hidden;">
                                        <div style="background: <?php echo $porcentaje_progreso == 100 ? '#10b981' : '#3b82f6'; ?>; width: <?php echo $porcentaje_progreso; ?>%; height: 100%; transition: width 0.3s;"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($contrato['cdp_id']): ?>
                                    <span class="badge badge-<?php echo $contrato['cdp_estado'] == 'aprobado' ? 'success' : 'warning'; ?>" style="font-size: 11px;">
                                        <?php echo $contrato['cdp_estado'] == 'aprobado' ? 'Aprobado' : 'Pendiente'; ?>
                                    </span>
                                    <?php if ($contrato['cdp_numero_proceso']): ?>
                                    <br>
                                    <small style="color: var(--gray-600); font-size: 11px;">
                                        #<?php echo $contrato['cdp_numero_proceso']; ?>
                                    </small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: var(--gray-400); font-size: 12px;">Sin CDP</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($contrato['numero_contrato']): ?>
                                    <span class="badge badge-success" style="font-size: 11px;">Completo</span>
                                    <br>
                                    <small style="color: var(--gray-600); font-size: 11px;">
                                        #<?php echo $contrato['numero_contrato']; ?>
                                    </small>
                                <?php else: ?>
                                    <span style="color: var(--gray-400); font-size: 12px;">Pendiente</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button onclick="verTimeline(<?php echo $contrato['id']; ?>)" 
                                        class="btn btn-sm btn-primary"
                                        title="Ver Timeline">
                                    <i class="fas fa-stream"></i>
                                </button>
                                <button onclick="verDetalles(<?php echo $contrato['id']; ?>)" 
                                        class="btn btn-sm btn-info"
                                        title="Ver Detalles">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if ($contrato['total_documentos'] > 0): ?>
                                <button onclick="verDocumentos(<?php echo $contrato['id']; ?>)" 
                                        class="btn btn-sm btn-secondary"
                                        title="Ver Documentos">
                                    <i class="fas fa-folder-open"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px; color: var(--gray-500);">
                                <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                                No hay contratos para mostrar
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Timeline -->
<div id="modalTimeline" class="modal">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h3><i class="fas fa-stream"></i> Timeline del Contrato</h3>
            <button class="modal-close" onclick="ocultarModal('modalTimeline')">&times;</button>
        </div>
        <div class="modal-body" id="contenidoTimeline">
            <!-- Contenido dinámico -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-light" onclick="ocultarModal('modalTimeline')">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal Documentos -->
<div id="modalDocumentos" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3><i class="fas fa-folder-open"></i> Documentos del Contrato</h3>
            <button class="modal-close" onclick="ocultarModal('modalDocumentos')">&times;</button>
        </div>
        <div class="modal-body" id="contenidoDocumentos">
            <!-- Contenido dinámico -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-light" onclick="ocultarModal('modalDocumentos')">Cerrar</button>
        </div>
    </div>
</div>

<style>
.file-item {
    display: flex;
    flex-direction: column;
    padding: 15px;
    background-color: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: 6px;
    margin-bottom: 10px;
}

.file-item-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 10px;
}

.timeline-item {
    padding: 15px;
    border-left: 3px solid var(--primary-color);
    margin-bottom: 15px;
    background: #f8fafc;
    border-radius: 0 8px 8px 0;
}

.timeline-item.completed {
    border-left-color: #10b981;
}

.timeline-item.pending {
    border-left-color: #f59e0b;
}

.timeline-item.rejected {
    border-left-color: #ef4444;
}
</style>

<script>
// Filtros
document.getElementById('filtroTabla')?.addEventListener('keyup', filtrarTabla);
document.getElementById('filtroEstado')?.addEventListener('change', filtrarTabla);

function filtrarTabla() {
    const filtro = document.getElementById('filtroTabla')?.value.toLowerCase() || '';
    const filtroEstado = document.getElementById('filtroEstado')?.value || '';
    const filas = document.querySelectorAll('#tablaSeguimiento tbody tr');

    filas.forEach(fila => {
        const texto = fila.textContent.toLowerCase();
        const estado = fila.getAttribute('data-estado');

        let mostrar = true;

        if (filtro && !texto.includes(filtro)) {
            mostrar = false;
        }

        if (filtroEstado && estado !== filtroEstado) {
            mostrar = false;
        }

        fila.style.display = mostrar ? '' : 'none';
    });
}

function verTimeline(contratoId) {
    fetch(`../controllers/workflow_controller.php?action=obtener_timeline&contrato_id=${contratoId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '<div style="max-height: 500px; overflow-y: auto;">';
                
                if (data.data.length > 0) {
                    data.data.forEach(evento => {
                        const estadoClass = evento.estado === 'completado' ? 'completed' : 
                                          evento.estado === 'rechazado' ? 'rejected' : 'pending';
                        
                        html += `
                            <div class="timeline-item ${estadoClass}">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <strong style="font-size: 14px;">${evento.etapa_nombre}</strong>
                                    <span class="badge badge-${estadoClass === 'completed' ? 'success' : estadoClass === 'rejected' ? 'danger' : 'warning'}" style="font-size: 11px;">
                                        ${evento.estado}
                                    </span>
                                </div>
                                ${evento.usuario_nombre ? `
                                    <div style="color: var(--gray-600); font-size: 13px; margin-bottom: 5px;">
                                        <i class="fas fa-user"></i> ${evento.usuario_nombre}
                                    </div>
                                ` : ''}
                                <div style="color: var(--gray-500); font-size: 12px;">
                                    <i class="fas fa-calendar"></i> ${formatearFecha(evento.fecha)}
                                </div>
                                ${evento.comentarios ? `
                                    <div style="margin-top: 8px; padding: 8px; background: white; border-radius: 4px; font-size: 13px;">
                                        <i class="fas fa-comment"></i> ${evento.comentarios}
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    });
                } else {
                    html += '<p style="text-align: center; color: var(--gray-500); padding: 40px;">No hay eventos registrados</p>';
                }
                
                html += '</div>';
                document.getElementById('contenidoTimeline').innerHTML = html;
                mostrarModal('modalTimeline');
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            Swal.fire('Error', 'No se pudo cargar el timeline', 'error');
        });
}

function verDetalles(contratoId) {
    window.location.href = `workflow_timeline.php?id=${contratoId}`;
}

function verDocumentos(contratoId) {
    fetch(`../controllers/contrato_controller.php?action=documentos&contrato_id=${contratoId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '';
                
                if (data.data.length > 0) {
                    data.data.forEach(doc => {
                        const estadoRevision = doc.estado_revision || 'pendiente';
                        const tipoNombre = doc.tipo_documento.replace(/_/g, ' ').split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
                        const estadoBadgeClass = estadoRevision === 'aprobado' ? 'badge-success' : 
                                               estadoRevision === 'rechazado' ? 'badge-danger' : 'badge-warning';
                        const estadoTexto = estadoRevision === 'aprobado' ? 'Aprobado' : 
                                           estadoRevision === 'rechazado' ? 'Rechazado' : 'Pendiente';
                        
                        html += `
                            <div class="file-item">
                                <div class="file-item-header">
                                    <div>
                                        <h5 style="margin: 0 0 5px 0; font-size: 14px;">${tipoNombre}</h5>
                                        <small style="color: var(--gray-500);">Subido: ${formatearFecha(doc.fecha_carga || doc.fecha_subida)}</small>
                                    </div>
                                    <span class="badge ${estadoBadgeClass}">${estadoTexto}</span>
                                </div>
                                
                                ${doc.comentarios_revision ? `
                                    <div style="background-color: var(--gray-50); padding: 10px; border-radius: 4px; margin: 10px 0;">
                                        <strong style="font-size: 12px; color: var(--gray-600);">Comentario de Revisión:</strong>
                                        <p style="margin: 5px 0 0 0; font-size: 13px;">${doc.comentarios_revision}</p>
                                        ${doc.fecha_revision ? `<small style="color: var(--gray-500);">Revisado: ${formatearFecha(doc.fecha_revision)}</small>` : ''}
                                    </div>
                                ` : ''}
                                
                                <div style="margin-top: 10px;">
                                    <a href="../uploads/documentos/${doc.nombre_archivo}" 
                                       target="_blank" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-download"></i> Ver/Descargar
                                    </a>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    html = '<p style="text-align: center; color: var(--gray-500); padding: 20px;">No hay documentos asociados</p>';
                }
                
                document.getElementById('contenidoDocumentos').innerHTML = html;
                mostrarModal('modalDocumentos');
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            Swal.fire('Error', 'No se pudo cargar los documentos', 'error');
        });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
