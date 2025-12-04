<?php
$pageTitle = 'Panel de Abogado';
require_once __DIR__ . '/../includes/header.php';

// Verificar que sea abogado o admin
if (!esAbogado() && !esAdministrador()) {
    header('Location: dashboard.php');
    exit;
}

$usuarioActual = obtenerUsuarioActual();
$abogadoId = $usuarioActual['id'];

// Obtener contratos asignados al abogado
$query = "SELECT c.*, 
          u.nombre as nombre_usuario_creacion,
          (SELECT COUNT(*) FROM documentos WHERE contrato_id = c.id) as total_documentos,
          (SELECT COUNT(*) FROM documentos WHERE contrato_id = c.id AND estado_revision = 'aprobado') as documentos_aprobados,
          (SELECT COUNT(*) FROM documentos WHERE contrato_id = c.id AND estado_revision = 'rechazado') as documentos_rechazados,
          c.estado_workflow,
          c.fecha_cambio_estado,
          (SELECT etapa FROM asignaciones_workflow 
           WHERE contrato_id = c.id AND usuario_asignado = $abogadoId
           AND estado IN ('pendiente', 'en_proceso')
           ORDER BY fecha_asignacion DESC LIMIT 1) as etapa_asignada,
          (SELECT fecha_asignacion FROM asignaciones_workflow 
           WHERE contrato_id = c.id AND usuario_asignado = $abogadoId
           ORDER BY fecha_asignacion DESC LIMIT 1) as fecha_mi_asignacion,
          (SELECT DATEDIFF(NOW(), fecha_asignacion) FROM asignaciones_workflow 
           WHERE contrato_id = c.id AND usuario_asignado = $abogadoId
           AND estado IN ('pendiente', 'en_proceso')
           ORDER BY fecha_asignacion DESC LIMIT 1) as dias_asignado,
          cdp.id as cdp_id,
          cdp.estado_aprobacion as cdp_estado,
          cdp.numero_proceso as cdp_numero,
          dt.numero_contrato
          FROM contratos c
          LEFT JOIN usuarios u ON c.usuario_creacion = u.id
          LEFT JOIN cdp ON cdp.contrato_id = c.id
          LEFT JOIN datos_tecnicos dt ON dt.contrato_id = c.id
          WHERE c.estado = 'activo'
          AND (c.abogado_asignado = $abogadoId 
               OR EXISTS (
                   SELECT 1 FROM asignaciones_workflow aw 
                   WHERE aw.contrato_id = c.id 
                   AND aw.usuario_asignado = $abogadoId
               ))
          ORDER BY 
              CASE 
                  WHEN c.estado_workflow IN ('en_creacion', 'revision_abogado') 
                       AND EXISTS (SELECT 1 FROM asignaciones_workflow aw2 
                                  WHERE aw2.contrato_id = c.id 
                                  AND aw2.usuario_asignado = $abogadoId
                                  AND aw2.estado IN ('pendiente', 'en_proceso')) THEN 1
                  ELSE 2
              END,
              c.fecha_creacion DESC";

$result = $mysqli->query($query);

// Estadísticas
$query_stats = "SELECT 
    COUNT(CASE WHEN c.estado_workflow IN ('en_creacion', 'revision_abogado') 
                    AND EXISTS (SELECT 1 FROM asignaciones_workflow aw 
                               WHERE aw.contrato_id = c.id 
                               AND aw.usuario_asignado = $abogadoId
                               AND aw.estado IN ('pendiente', 'en_proceso')) THEN 1 END) as pendientes,
    COUNT(CASE WHEN c.estado_workflow = 'administracion_tecnica' 
                    AND cdp.estado_aprobacion = 'pendiente' THEN 1 END) as pendientes_cdp,
    COUNT(CASE WHEN c.estado_workflow IN ('publicado_aprobado', 'publicado_rechazado', 'publicado_corregido') THEN 1 END) as finalizados,
    COUNT(*) as total
    FROM contratos c
    LEFT JOIN cdp ON cdp.contrato_id = c.id
    WHERE c.estado = 'activo'
    AND (c.abogado_asignado = $abogadoId 
         OR EXISTS (
             SELECT 1 FROM asignaciones_workflow aw 
             WHERE aw.contrato_id = c.id 
             AND aw.usuario_asignado = $abogadoId
         ))";
$stats_result = $mysqli->query($query_stats);
$stats = $stats_result->fetch_assoc();
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-balance-scale"></i> Panel de Abogado</h3>
        <div style="color: var(--gray-600); font-size: 14px;">
            Contratos asignados a <?php echo htmlspecialchars($usuarioActual['nombre']); ?>
        </div>
    </div>
    <div class="card-body">
        <!-- Estadísticas -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 25px;">
            <div style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); padding: 20px; border-radius: 8px; color: white;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-exclamation-circle" style="font-size: 32px; opacity: 0.9;"></i>
                    <div>
                        <div style="font-size: 28px; font-weight: bold;"><?php echo $stats['pendientes']; ?></div>
                        <div style="font-size: 13px; opacity: 0.9;">Pendientes Acción</div>
                    </div>
                </div>
            </div>
            <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 20px; border-radius: 8px; color: white;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-file-invoice-dollar" style="font-size: 32px; opacity: 0.9;"></i>
                    <div>
                        <div style="font-size: 28px; font-weight: bold;"><?php echo $stats['pendientes_cdp']; ?></div>
                        <div style="font-size: 13px; opacity: 0.9;">CDP Pendientes</div>
                    </div>
                </div>
            </div>
            <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 20px; border-radius: 8px; color: white;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-check-double" style="font-size: 32px; opacity: 0.9;"></i>
                    <div>
                        <div style="font-size: 28px; font-weight: bold;"><?php echo $stats['finalizados']; ?></div>
                        <div style="font-size: 13px; opacity: 0.9;">Finalizados</div>
                    </div>
                </div>
            </div>
            <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); padding: 20px; border-radius: 8px; color: white;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-briefcase" style="font-size: 32px; opacity: 0.9;"></i>
                    <div>
                        <div style="font-size: 28px; font-weight: bold;"><?php echo $stats['total']; ?></div>
                        <div style="font-size: 13px; opacity: 0.9;">Total Asignados</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div style="margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 250px;">
                <input type="text" 
                       id="filtroTabla" 
                       class="form-control" 
                       placeholder="🔍 Buscar por nombre, documento...">
            </div>
            <div>
                <select id="filtroEstado" class="form-control form-select" style="min-width: 220px;">
                    <option value="">Todos los estados</option>
                    <option value="pendiente_accion" selected>🔴 Requieren mi acción</option>
                    <option value="en_creacion">En Creación</option>
                    <option value="revision_documentos">Revisión Documentos</option>
                    <option value="revision_abogado">Revisión Abogado</option>
                    <option value="administracion_tecnica">Administración Técnica</option>
                    <option value="en_elaboracion">En Elaboración</option>
                    <option value="para_firmas">Para Firmas</option>
                    <option value="publicado_aprobado">Publicado Aprobado</option>
                    <option value="publicado_rechazado">Publicado Rechazado</option>
                </select>
            </div>
        </div>

        <!-- Tabla -->
        <div class="table-responsive">
            <table class="table" id="tablaAbogado">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Contrato</th>
                        <th>Usuario</th>
                        <th>Estado Actual</th>
                        <th>Mi Etapa</th>
                        <th>Documentos</th>
                        <th>CDP/Datos</th>
                        <th>Días Asignado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($contrato = $result->fetch_assoc()): 
                            // Determinar si requiere acción del abogado
                            $requiereAccion = !empty($contrato['etapa_asignada']) && 
                                            in_array($contrato['estado_workflow'], ['en_creacion', 'revision_abogado']) ||
                                            ($contrato['estado_workflow'] == 'administracion_tecnica' && 
                                             $contrato['cdp_estado'] == 'pendiente' && $contrato['cdp_id']);
                            
                            $estadoFiltro = $requiereAccion ? 'pendiente_accion' : $contrato['estado_workflow'];
                            
                            // Color de fondo según urgencia
                            $bgColor = 'transparent';
                            if ($requiereAccion && $contrato['dias_asignado'] > 5) {
                                $bgColor = '#fee2e2';
                            } elseif ($requiereAccion && $contrato['dias_asignado'] > 2) {
                                $bgColor = '#fef3c7';
                            }
                            
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
                        ?>
                        <tr data-estado="<?php echo $estadoFiltro; ?>" style="background-color: <?php echo $bgColor; ?>;">
                            <td><?php echo $contrato['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($contrato['nombre_completo']); ?></strong>
                                <?php if ($requiereAccion): ?>
                                <span class="badge badge-danger" style="font-size: 10px; margin-left: 5px;">
                                    ¡ACCIÓN!
                                </span>
                                <?php endif; ?>
                                <br>
                                <small style="color: var(--gray-600);">
                                    Doc: <?php echo $contrato['numero_documento']; ?>
                                </small>
                                <br>
                                <small style="color: var(--gray-500); font-size: 11px;">
                                    Creado: <?php echo formatearFecha($contrato['fecha_creacion']); ?>
                                </small>
                            </td>
                            <td>
                                <div style="font-size: 13px;">
                                    <?php echo htmlspecialchars($contrato['nombre_usuario_creacion']); ?>
                                </div>
                                <?php if ($contrato['correo_electronico']): ?>
                                <small style="color: var(--gray-500); font-size: 11px;">
                                    <?php echo htmlspecialchars($contrato['correo_electronico']); ?>
                                </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $badge_class; ?>" style="font-size: 12px;">
                                    <?php echo ucwords(str_replace('_', ' ', $contrato['estado_workflow'])); ?>
                                </span>
                                <?php if ($contrato['fecha_cambio_estado']): ?>
                                <br>
                                <small style="color: var(--gray-500); font-size: 10px;">
                                    <?php echo formatearFecha($contrato['fecha_cambio_estado']); ?>
                                </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($contrato['etapa_asignada']): ?>
                                    <span class="badge badge-info" style="font-size: 11px;">
                                        <?php echo ucwords(str_replace('_', ' ', $contrato['etapa_asignada'])); ?>
                                    </span>
                                    <?php if ($requiereAccion): ?>
                                    <br>
                                    <small style="color: #ef4444; font-weight: 600; font-size: 11px;">
                                        Requiere acción
                                    </small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: var(--gray-400); font-size: 12px;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-size: 12px;">
                                    <?php if ($contrato['total_documentos'] > 0): ?>
                                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                            <span class="badge badge-secondary" style="font-size: 10px;">
                                                <?php echo $contrato['total_documentos']; ?> total
                                            </span>
                                            <?php if ($contrato['documentos_aprobados'] > 0): ?>
                                            <span class="badge badge-success" style="font-size: 10px;">
                                                ✓ <?php echo $contrato['documentos_aprobados']; ?>
                                            </span>
                                            <?php endif; ?>
                                            <?php if ($contrato['documentos_rechazados'] > 0): ?>
                                            <span class="badge badge-danger" style="font-size: 10px;">
                                                ✗ <?php echo $contrato['documentos_rechazados']; ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: var(--gray-400); font-size: 11px;">Sin docs</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 11px;">
                                    <?php if ($contrato['cdp_id']): ?>
                                        <span class="badge badge-<?php echo $contrato['cdp_estado'] == 'aprobado' ? 'success' : 'warning'; ?>" style="font-size: 10px;">
                                            CDP: <?php echo $contrato['cdp_estado'] == 'aprobado' ? 'Aprobado' : 'Pendiente'; ?>
                                        </span>
                                        <?php if ($contrato['cdp_numero']): ?>
                                        <br>
                                        <small style="color: var(--gray-600);">#<?php echo $contrato['cdp_numero']; ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: var(--gray-400);">Sin CDP</span>
                                    <?php endif; ?>
                                    <br>
                                    <?php if ($contrato['numero_contrato']): ?>
                                        <span class="badge badge-success" style="font-size: 10px; margin-top: 3px;">
                                            Contrato: <?php echo $contrato['numero_contrato']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--gray-400);">Sin N° Contrato</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($contrato['dias_asignado'] !== null): ?>
                                    <div style="font-weight: 600; color: <?php echo $contrato['dias_asignado'] > 5 ? '#ef4444' : ($contrato['dias_asignado'] > 2 ? '#f59e0b' : '#10b981'); ?>;">
                                        <?php echo $contrato['dias_asignado']; ?> día(s)
                                    </div>
                                    <?php if ($contrato['fecha_mi_asignacion']): ?>
                                    <small style="color: var(--gray-500); font-size: 10px;">
                                        <?php echo formatearFecha($contrato['fecha_mi_asignacion']); ?>
                                    </small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: var(--gray-400); font-size: 12px;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($requiereAccion): ?>
                                    <button onclick="gestionarContrato(<?php echo $contrato['id']; ?>)" 
                                            class="btn btn-sm btn-danger"
                                            title="Gestionar">
                                        <i class="fas fa-tasks"></i> Gestionar
                                    </button>
                                <?php else: ?>
                                    <button onclick="verDetalles(<?php echo $contrato['id']; ?>)" 
                                            class="btn btn-sm btn-info"
                                            title="Ver Detalles">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                <?php endif; ?>
                                <button onclick="verTimeline(<?php echo $contrato['id']; ?>)" 
                                        class="btn btn-sm btn-secondary"
                                        title="Timeline">
                                    <i class="fas fa-stream"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px; color: var(--gray-500);">
                                <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                                No tienes contratos asignados en este momento
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Filtros
document.getElementById('filtroTabla')?.addEventListener('keyup', filtrarTabla);
document.getElementById('filtroEstado')?.addEventListener('change', filtrarTabla);

function filtrarTabla() {
    const filtro = document.getElementById('filtroTabla')?.value.toLowerCase() || '';
    const filtroEstado = document.getElementById('filtroEstado')?.value || '';
    const filas = document.querySelectorAll('#tablaAbogado tbody tr');

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

function gestionarContrato(contratoId) {
    window.location.href = `workflow_gestionar.php?id=${contratoId}`;
}

function verDetalles(contratoId) {
    window.location.href = `workflow_timeline.php?id=${contratoId}`;
}

function verTimeline(contratoId) {
    window.location.href = `workflow_timeline.php?id=${contratoId}`;
}

// Inicializar con filtro de pendientes
window.addEventListener('DOMContentLoaded', function() {
    filtrarTabla();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
