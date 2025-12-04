<?php
$pageTitle = 'Panel Administrador Técnico';
require_once __DIR__ . '/../includes/header.php';

// Verificar que sea administrador técnico o admin
if (!esAdministradorTecnico() && !esAdministrador()) {
    header('Location: dashboard.php');
    exit;
}

// Obtener contratos en administración técnica
$query = "SELECT c.*, 
          u.nombre as nombre_usuario_creacion,
          a.nombre as nombre_abogado_asignado,
          (SELECT COUNT(*) FROM documentos WHERE contrato_id = c.id) as total_documentos,
          (SELECT COUNT(*) FROM documentos WHERE contrato_id = c.id AND estado_revision = 'aprobado') as documentos_aprobados,
          c.estado_workflow,
          c.fecha_cambio_estado,
          (SELECT DATEDIFF(NOW(), fecha_cambio_estado) 
           FROM contratos WHERE id = c.id AND estado_workflow = 'administracion_tecnica') as dias_en_admin_tecnica,
          cdp.id as cdp_id,
          cdp.fecha_cdp,
          cdp.rubro_presupuestal,
          cdp.valor as cdp_valor,
          cdp.numero_proceso,
          cdp.dependencia,
          cdp.estado_aprobacion as cdp_estado,
          cdp.fecha_aprobacion as cdp_fecha_aprobacion,
          (SELECT u2.nombre FROM usuarios u2 WHERE u2.id = cdp.abogado_asignado_aprobacion) as cdp_abogado_aprobacion,
          dt.id as datos_tecnicos_id,
          dt.numero_contrato,
          dt.numero_secop,
          dt.supervisor,
          dt.dias_ejecucion,
          dt.valor_total,
          dt.fecha_inicio,
          dt.fecha_finalizacion,
          (SELECT u3.nombre FROM usuarios u3 WHERE u3.id = dt.abogado_asignado) as dt_abogado_asignado
          FROM contratos c
          LEFT JOIN usuarios u ON c.usuario_creacion = u.id
          LEFT JOIN usuarios a ON c.abogado_asignado = a.id
          LEFT JOIN cdp ON cdp.contrato_id = c.id
          LEFT JOIN datos_tecnicos dt ON dt.contrato_id = c.id
          WHERE c.estado = 'activo'
          AND c.estado_workflow IN ('administracion_tecnica', 'en_elaboracion', 'para_firmas', 'publicado_aprobado', 'publicado_rechazado', 'publicado_corregido')
          ORDER BY 
              CASE 
                  WHEN c.estado_workflow = 'administracion_tecnica' AND cdp.id IS NULL THEN 1
                  WHEN c.estado_workflow = 'administracion_tecnica' AND cdp.estado_aprobacion = 'pendiente' THEN 2
                  WHEN c.estado_workflow = 'administracion_tecnica' AND cdp.estado_aprobacion = 'aprobado' AND dt.id IS NULL THEN 3
                  WHEN c.estado_workflow = 'administracion_tecnica' THEN 4
                  ELSE 5
              END,
              c.fecha_creacion DESC";

$result = $mysqli->query($query);

// Estadísticas
$query_stats = "SELECT 
    COUNT(CASE WHEN c.estado_workflow = 'administracion_tecnica' AND cdp.id IS NULL THEN 1 END) as sin_cdp,
    COUNT(CASE WHEN c.estado_workflow = 'administracion_tecnica' AND cdp.estado_aprobacion = 'pendiente' THEN 1 END) as cdp_pendiente_aprobacion,
    COUNT(CASE WHEN c.estado_workflow = 'administracion_tecnica' AND cdp.estado_aprobacion = 'aprobado' AND dt.id IS NULL THEN 1 END) as sin_datos_tecnicos,
    COUNT(CASE WHEN c.estado_workflow = 'administracion_tecnica' AND dt.id IS NOT NULL THEN 1 END) as datos_completos,
    COUNT(CASE WHEN c.estado_workflow IN ('en_elaboracion', 'para_firmas', 'publicado_aprobado') THEN 1 END) as finalizados
    FROM contratos c
    LEFT JOIN cdp ON cdp.contrato_id = c.id
    LEFT JOIN datos_tecnicos dt ON dt.contrato_id = c.id
    WHERE c.estado = 'activo'
    AND c.estado_workflow IN ('administracion_tecnica', 'en_elaboracion', 'para_firmas', 'publicado_aprobado', 'publicado_rechazado', 'publicado_corregido')";
$stats_result = $mysqli->query($query_stats);
$stats = $stats_result->fetch_assoc();
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-cogs"></i> Panel Administrador Técnico</h3>
    </div>
    <div class="card-body">
        <!-- Estadísticas -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 15px; margin-bottom: 25px;">
            <div style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); padding: 18px; border-radius: 8px; color: white;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-file-medical" style="font-size: 28px; opacity: 0.9;"></i>
                    <div>
                        <div style="font-size: 26px; font-weight: bold;"><?php echo $stats['sin_cdp']; ?></div>
                        <div style="font-size: 12px; opacity: 0.9;">Sin CDP</div>
                    </div>
                </div>
            </div>
            <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 18px; border-radius: 8px; color: white;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-hourglass-half" style="font-size: 28px; opacity: 0.9;"></i>
                    <div>
                        <div style="font-size: 26px; font-weight: bold;"><?php echo $stats['cdp_pendiente_aprobacion']; ?></div>
                        <div style="font-size: 12px; opacity: 0.9;">CDP Pendientes</div>
                    </div>
                </div>
            </div>
            <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); padding: 18px; border-radius: 8px; color: white;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-clipboard-list" style="font-size: 28px; opacity: 0.9;"></i>
                    <div>
                        <div style="font-size: 26px; font-weight: bold;"><?php echo $stats['sin_datos_tecnicos']; ?></div>
                        <div style="font-size: 12px; opacity: 0.9;">Sin Datos Técnicos</div>
                    </div>
                </div>
            </div>
            <div style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); padding: 18px; border-radius: 8px; color: white;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-check-circle" style="font-size: 28px; opacity: 0.9;"></i>
                    <div>
                        <div style="font-size: 26px; font-weight: bold;"><?php echo $stats['datos_completos']; ?></div>
                        <div style="font-size: 12px; opacity: 0.9;">Datos Completos</div>
                    </div>
                </div>
            </div>
            <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 18px; border-radius: 8px; color: white;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-flag-checkered" style="font-size: 28px; opacity: 0.9;"></i>
                    <div>
                        <div style="font-size: 26px; font-weight: bold;"><?php echo $stats['finalizados']; ?></div>
                        <div style="font-size: 12px; opacity: 0.9;">Finalizados</div>
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
                    <option value="">Todos</option>
                    <option value="sin_cdp" selected>🔴 Sin CDP (Urgente)</option>
                    <option value="cdp_pendiente">🟡 CDP Pendiente Aprobación</option>
                    <option value="sin_datos">🔵 Sin Datos Técnicos</option>
                    <option value="completo">🟣 Datos Completos</option>
                    <option value="finalizado">🟢 Finalizados</option>
                </select>
            </div>
        </div>

        <!-- Tabla -->
        <div class="table-responsive">
            <table class="table" id="tablaAdminTecnico">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Contrato</th>
                        <th>Usuario</th>
                        <th>Estado</th>
                        <th>CDP</th>
                        <th>Datos Técnicos</th>
                        <th>Días en Admin</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($contrato = $result->fetch_assoc()): 
                            // Determinar prioridad y filtro
                            $enAdminTecnica = $contrato['estado_workflow'] === 'administracion_tecnica';
                            $sinCDP = $enAdminTecnica && !$contrato['cdp_id'];
                            $cdpPendiente = $enAdminTecnica && $contrato['cdp_estado'] === 'pendiente';
                            $sinDatos = $enAdminTecnica && $contrato['cdp_estado'] === 'aprobado' && !$contrato['datos_tecnicos_id'];
                            $datosCompletos = $enAdminTecnica && $contrato['datos_tecnicos_id'];
                            $finalizado = !$enAdminTecnica;
                            
                            $estadoFiltro = '';
                            $bgColor = 'transparent';
                            $prioridad = '';
                            
                            if ($sinCDP) {
                                $estadoFiltro = 'sin_cdp';
                                $bgColor = '#fee2e2';
                                $prioridad = '🔴 URGENTE';
                            } elseif ($cdpPendiente) {
                                $estadoFiltro = 'cdp_pendiente';
                                $bgColor = '#fef3c7';
                                $prioridad = '🟡 Pendiente';
                            } elseif ($sinDatos) {
                                $estadoFiltro = 'sin_datos';
                                $bgColor = '#dbeafe';
                                $prioridad = '🔵 Acción requerida';
                            } elseif ($datosCompletos) {
                                $estadoFiltro = 'completo';
                                $prioridad = '🟣 Completo';
                            } elseif ($finalizado) {
                                $estadoFiltro = 'finalizado';
                                $prioridad = '🟢 Finalizado';
                            }
                        ?>
                        <tr data-estado="<?php echo $estadoFiltro; ?>" style="background-color: <?php echo $bgColor; ?>;">
                            <td><?php echo $contrato['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($contrato['nombre_completo']); ?></strong>
                                <?php if ($prioridad): ?>
                                <span style="font-size: 11px; margin-left: 5px; font-weight: 600;">
                                    <?php echo $prioridad; ?>
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
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $enAdminTecnica ? 'info' : 'success'; ?>" style="font-size: 12px;">
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
                                <?php if ($contrato['cdp_id']): ?>
                                    <div style="font-size: 12px;">
                                        <span class="badge badge-<?php echo $contrato['cdp_estado'] == 'aprobado' ? 'success' : 'warning'; ?>" style="font-size: 11px;">
                                            <?php echo $contrato['cdp_estado'] == 'aprobado' ? '✓ Aprobado' : '⏳ Pendiente'; ?>
                                        </span>
                                        <?php if ($contrato['numero_proceso']): ?>
                                        <br>
                                        <strong style="color: var(--gray-700);">#<?php echo $contrato['numero_proceso']; ?></strong>
                                        <?php endif; ?>
                                        <?php if ($contrato['cdp_valor']): ?>
                                        <br>
                                        <small style="color: var(--gray-600);">
                                            $<?php echo number_format($contrato['cdp_valor'], 0, ',', '.'); ?>
                                        </small>
                                        <?php endif; ?>
                                        <?php if ($contrato['cdp_estado'] == 'aprobado' && $contrato['cdp_fecha_aprobacion']): ?>
                                        <br>
                                        <small style="color: #10b981; font-size: 10px;">
                                            Aprobado: <?php echo formatearFecha($contrato['cdp_fecha_aprobacion']); ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="badge badge-danger" style="font-size: 11px;">
                                        ❌ Sin CDP
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($contrato['datos_tecnicos_id']): ?>
                                    <div style="font-size: 12px;">
                                        <span class="badge badge-success" style="font-size: 11px;">
                                            ✓ Completo
                                        </span>
                                        <?php if ($contrato['numero_contrato']): ?>
                                        <br>
                                        <strong style="color: var(--gray-700);">Contrato: <?php echo $contrato['numero_contrato']; ?></strong>
                                        <?php endif; ?>
                                        <?php if ($contrato['numero_secop']): ?>
                                        <br>
                                        <small style="color: var(--gray-600);">SECOP: <?php echo $contrato['numero_secop']; ?></small>
                                        <?php endif; ?>
                                        <?php if ($contrato['supervisor']): ?>
                                        <br>
                                        <small style="color: var(--gray-600);">Sup: <?php echo htmlspecialchars($contrato['supervisor']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif ($contrato['cdp_estado'] == 'aprobado'): ?>
                                    <span class="badge badge-warning" style="font-size: 11px;">
                                        ⚠ Pendiente
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--gray-400); font-size: 11px;">
                                        Esperando CDP
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($contrato['dias_en_admin_tecnica'] !== null && $enAdminTecnica): ?>
                                    <div style="font-weight: 600; color: <?php echo $contrato['dias_en_admin_tecnica'] > 7 ? '#ef4444' : ($contrato['dias_en_admin_tecnica'] > 3 ? '#f59e0b' : '#10b981'); ?>;">
                                        <?php echo $contrato['dias_en_admin_tecnica']; ?> día(s)
                                    </div>
                                <?php elseif ($finalizado): ?>
                                    <span style="color: var(--gray-400); font-size: 12px;">Finalizado</span>
                                <?php else: ?>
                                    <span style="color: var(--gray-400); font-size: 12px;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($enAdminTecnica): ?>
                                    <button onclick="gestionarContrato(<?php echo $contrato['id']; ?>)" 
                                            class="btn btn-sm btn-<?php echo $sinCDP || $sinDatos ? 'danger' : ($cdpPendiente ? 'warning' : 'primary'); ?>"
                                            title="Gestionar">
                                        <i class="fas fa-cog"></i>
                                        <?php 
                                        if ($sinCDP) echo 'Agregar CDP';
                                        elseif ($sinDatos) echo 'Agregar Datos';
                                        else echo 'Gestionar';
                                        ?>
                                    </button>
                                <?php endif; ?>
                                <button onclick="verDetalles(<?php echo $contrato['id']; ?>)" 
                                        class="btn btn-sm btn-info"
                                        title="Ver Detalles">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: var(--gray-500);">
                                <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                                No hay contratos en administración técnica en este momento
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
    const filas = document.querySelectorAll('#tablaAdminTecnico tbody tr');

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

// Inicializar con filtro de urgentes
window.addEventListener('DOMContentLoaded', function() {
    filtrarTabla();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
