<?php
$pageTitle = 'Panel Revisor de Documentos';
require_once __DIR__ . '/../includes/header.php';

// Verificar que sea revisor de documentos o admin
if (!esRevisorDocumentos() && !esAdministrador()) {
    header('Location: dashboard.php');
    exit;
}

// Obtener contratos que necesitan revisión de documentos o que ya fueron revisados
$query = "SELECT c.*, 
          u.nombre as nombre_usuario_creacion,
          a.nombre as nombre_abogado_asignado,
          (SELECT COUNT(*) FROM documentos WHERE contrato_id = c.id) as total_documentos,
          (SELECT COUNT(*) FROM documentos WHERE contrato_id = c.id AND estado_revision = 'aprobado') as documentos_aprobados,
          (SELECT COUNT(*) FROM documentos WHERE contrato_id = c.id AND estado_revision = 'rechazado') as documentos_rechazados,
          (SELECT COUNT(*) FROM documentos WHERE contrato_id = c.id AND (estado_revision = 'pendiente' OR estado_revision IS NULL)) as documentos_pendientes,
          c.estado_workflow,
          c.fecha_cambio_estado,
          (SELECT fecha_asignacion FROM asignaciones_workflow 
           WHERE contrato_id = c.id AND etapa = 'revision_documentos'
           ORDER BY fecha_asignacion DESC LIMIT 1) as fecha_asignacion_revision,
          (SELECT DATEDIFF(NOW(), fecha_asignacion) FROM asignaciones_workflow 
           WHERE contrato_id = c.id AND etapa = 'revision_documentos'
           ORDER BY fecha_asignacion DESC LIMIT 1) as dias_en_revision
          FROM contratos c
          LEFT JOIN usuarios u ON c.usuario_creacion = u.id
          LEFT JOIN usuarios a ON c.abogado_asignado = a.id
          WHERE c.estado = 'activo'
          AND c.estado_workflow IN ('revision_documentos', 'revision_abogado', 'administracion_tecnica', 'en_elaboracion', 'para_firmas', 'publicado_aprobado', 'publicado_rechazado', 'publicado_corregido')
          ORDER BY 
              CASE 
                  WHEN c.estado_workflow = 'revision_documentos' THEN 1
                  ELSE 2
              END,
              c.fecha_creacion DESC";

$result = $mysqli->query($query);

// Contar contratos por estado
$query_stats = "SELECT 
    COUNT(CASE WHEN estado_workflow = 'revision_documentos' THEN 1 END) as pendientes,
    COUNT(CASE WHEN estado_workflow IN ('revision_abogado', 'administracion_tecnica', 'en_elaboracion', 'para_firmas', 'publicado_aprobado', 'publicado_rechazado', 'publicado_corregido') THEN 1 END) as completados
    FROM contratos 
    WHERE estado = 'activo'
    AND estado_workflow IN ('revision_documentos', 'revision_abogado', 'administracion_tecnica', 'en_elaboracion', 'para_firmas', 'publicado_aprobado', 'publicado_rechazado', 'publicado_corregido')";
$stats_result = $mysqli->query($query_stats);
$stats = $stats_result->fetch_assoc();
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-clipboard-check"></i> Panel de Revisión de Documentos</h3>
    </div>
    <div class="card-body">
        <!-- Estadísticas -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
            <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 20px; border-radius: 8px; color: white;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <i class="fas fa-clock" style="font-size: 36px; opacity: 0.9;"></i>
                    <div>
                        <div style="font-size: 32px; font-weight: bold;"><?php echo $stats['pendientes']; ?></div>
                        <div style="font-size: 14px; opacity: 0.9;">Pendientes de Revisar</div>
                    </div>
                </div>
            </div>
            <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 20px; border-radius: 8px; color: white;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <i class="fas fa-check-circle" style="font-size: 36px; opacity: 0.9;"></i>
                    <div>
                        <div style="font-size: 32px; font-weight: bold;"><?php echo $stats['completados']; ?></div>
                        <div style="font-size: 14px; opacity: 0.9;">Ya Revisados</div>
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
                <select id="filtroEstado" class="form-control form-select" style="min-width: 200px;">
                    <option value="">Todos los estados</option>
                    <option value="revision_documentos" selected>⏳ Pendientes</option>
                    <option value="revisado">✅ Revisados</option>
                </select>
            </div>
            <div>
                <select id="filtroDocumentos" class="form-control form-select" style="min-width: 200px;">
                    <option value="">Todos</option>
                    <option value="pendientes">Con docs pendientes</option>
                    <option value="rechazados">Con docs rechazados</option>
                    <option value="aprobados">Todos aprobados</option>
                </select>
            </div>
        </div>

        <!-- Tabla -->
        <div class="table-responsive">
            <table class="table" id="tablaRevisor">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Contrato</th>
                        <th>Usuario</th>
                        <th>Estado Actual</th>
                        <th>Documentos</th>
                        <th>Tiempo en Revisión</th>
                        <th>Progreso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($contrato = $result->fetch_assoc()): 
                            $enRevision = $contrato['estado_workflow'] === 'revision_documentos';
                            $yaRevisado = !$enRevision;
                            
                            // Determinar si todos los docs están revisados
                            $todosRevisados = $contrato['documentos_pendientes'] == 0 && $contrato['total_documentos'] > 0;
                            
                            // Clase de estado para filtrado
                            $estadoFiltro = $enRevision ? 'revision_documentos' : 'revisado';
                            
                            // Clase de documentos para filtrado
                            $docsFiltro = '';
                            if ($contrato['documentos_pendientes'] > 0) $docsFiltro = 'pendientes';
                            elseif ($contrato['documentos_rechazados'] > 0) $docsFiltro = 'rechazados';
                            elseif ($contrato['documentos_aprobados'] == $contrato['total_documentos'] && $contrato['total_documentos'] > 0) $docsFiltro = 'aprobados';
                        ?>
                        <tr data-estado="<?php echo $estadoFiltro; ?>" data-docs="<?php echo $docsFiltro; ?>">
                            <td><?php echo $contrato['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($contrato['nombre_completo']); ?></strong>
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
                                <?php if ($enRevision): ?>
                                    <span class="badge badge-warning" style="font-size: 12px;">
                                        ⏳ Pendiente Revisión
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-success" style="font-size: 12px;">
                                        ✅ Revisado
                                    </span>
                                    <br>
                                    <small style="color: var(--gray-600); font-size: 11px;">
                                        Ahora: <?php echo ucwords(str_replace('_', ' ', $contrato['estado_workflow'])); ?>
                                    </small>
                                <?php endif; ?>
                                <?php if ($contrato['fecha_cambio_estado']): ?>
                                <br>
                                <small style="color: var(--gray-500); font-size: 10px;">
                                    <?php echo formatearFecha($contrato['fecha_cambio_estado']); ?>
                                </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-size: 12px;">
                                    <div style="margin-bottom: 5px;">
                                        <strong><?php echo $contrato['total_documentos']; ?></strong> documentos
                                    </div>
                                    <?php if ($contrato['total_documentos'] > 0): ?>
                                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
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
                                        <?php if ($contrato['documentos_pendientes'] > 0): ?>
                                        <span class="badge badge-warning" style="font-size: 10px;">
                                            ⏳ <?php echo $contrato['documentos_pendientes']; ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($contrato['dias_en_revision'] !== null && $enRevision): ?>
                                    <div style="font-weight: 600; color: <?php echo $contrato['dias_en_revision'] > 5 ? '#ef4444' : ($contrato['dias_en_revision'] > 2 ? '#f59e0b' : '#10b981'); ?>;">
                                        <?php echo $contrato['dias_en_revision']; ?> día(s)
                                    </div>
                                    <?php if ($contrato['fecha_asignacion_revision']): ?>
                                    <small style="color: var(--gray-500); font-size: 10px;">
                                        Desde: <?php echo formatearFecha($contrato['fecha_asignacion_revision']); ?>
                                    </small>
                                    <?php endif; ?>
                                <?php elseif ($yaRevisado): ?>
                                    <span style="color: var(--gray-400); font-size: 12px;">Completado</span>
                                <?php else: ?>
                                    <span style="color: var(--gray-400); font-size: 12px;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($contrato['total_documentos'] > 0): 
                                    $porcentaje = round(($contrato['documentos_aprobados'] / $contrato['total_documentos']) * 100);
                                ?>
                                <div style="min-width: 100px;">
                                    <div style="font-size: 12px; font-weight: 600; margin-bottom: 3px;">
                                        <?php echo $porcentaje; ?>%
                                    </div>
                                    <div style="background: #e5e7eb; height: 6px; border-radius: 3px; overflow: hidden;">
                                        <div style="background: <?php echo $porcentaje == 100 ? '#10b981' : '#3b82f6'; ?>; width: <?php echo $porcentaje; ?>%; height: 100%;"></div>
                                    </div>
                                </div>
                                <?php else: ?>
                                <span style="color: var(--gray-400); font-size: 12px;">Sin docs</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($enRevision): ?>
                                    <button onclick="revisarDocumentos(<?php echo $contrato['id']; ?>, '<?php echo htmlspecialchars($contrato['nombre_completo']); ?>')" 
                                            class="btn btn-sm btn-primary"
                                            title="Revisar Documentos">
                                        <i class="fas fa-clipboard-check"></i> Revisar
                                    </button>
                                <?php endif; ?>
                                <button onclick="verDetalles(<?php echo $contrato['id']; ?>)" 
                                        class="btn btn-sm btn-info"
                                        title="Ver Detalles">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="verTimeline(<?php echo $contrato['id']; ?>)" 
                                        class="btn btn-sm btn-secondary"
                                        title="Ver Timeline">
                                    <i class="fas fa-stream"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: var(--gray-500);">
                                <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                                No hay contratos para revisar en este momento
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
document.getElementById('filtroDocumentos')?.addEventListener('change', filtrarTabla);

function filtrarTabla() {
    const filtro = document.getElementById('filtroTabla')?.value.toLowerCase() || '';
    const filtroEstado = document.getElementById('filtroEstado')?.value || '';
    const filtroDocs = document.getElementById('filtroDocumentos')?.value || '';
    const filas = document.querySelectorAll('#tablaRevisor tbody tr');

    filas.forEach(fila => {
        const texto = fila.textContent.toLowerCase();
        const estado = fila.getAttribute('data-estado');
        const docs = fila.getAttribute('data-docs');

        let mostrar = true;

        if (filtro && !texto.includes(filtro)) {
            mostrar = false;
        }

        if (filtroEstado && estado !== filtroEstado) {
            mostrar = false;
        }

        if (filtroDocs && docs !== filtroDocs) {
            mostrar = false;
        }

        fila.style.display = mostrar ? '' : 'none';
    });
}

function revisarDocumentos(contratoId, nombreCompleto) {
    // Redirigir a la vista de gestión de workflow o abrir modal
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
