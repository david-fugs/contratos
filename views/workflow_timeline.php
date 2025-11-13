<?php
$pageTitle = 'Timeline Workflow';
require_once __DIR__ . '/../includes/header.php';

// Obtener ID del contrato si viene en la URL
$contrato_id = isset($_GET['contrato_id']) ? intval($_GET['contrato_id']) : 0;
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-project-diagram"></i> Timeline de Workflow</h3>
        <?php if ($contrato_id > 0): ?>
        <button onclick="window.history.back()" class="btn btn-light">
            <i class="fas fa-arrow-left"></i> Volver
        </button>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if ($contrato_id > 0): ?>
            <!-- Vista de un contrato específico -->
            <div id="contratoInfo" style="margin-bottom: 30px;">
                <!-- Se carga dinámicamente -->
            </div>
            
            <!-- Timeline -->
            <div id="timelineContainer">
                <!-- Se carga dinámicamente -->
            </div>
            
            <!-- Asignaciones -->
            <div style="margin-top: 40px;">
                <h4><i class="fas fa-users"></i> Historial de Asignaciones</h4>
                <div id="asignacionesContainer"></div>
            </div>
            
            <!-- Devoluciones -->
            <div style="margin-top: 40px;">
                <h4><i class="fas fa-undo"></i> Devoluciones</h4>
                <div id="devolucionesContainer"></div>
            </div>
        <?php else: ?>
            <!-- Vista general de todos los contratos -->
            <div style="margin-bottom: 20px;">
                <input type="text" 
                       id="filtroContratos" 
                       class="form-control" 
                       placeholder="🔍 Buscar contrato por nombre, documento...">
            </div>
            
            <div id="listaContratos">
                <!-- Se carga dinámicamente -->
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding: 20px 0;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 30px;
    top: 0;
    bottom: 0;
    width: 3px;
    background: #e5e7eb;
}

.timeline-item {
    position: relative;
    padding: 0 0 30px 70px;
    min-height: 80px;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: 19px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    z-index: 1;
}

.timeline-marker.active {
    width: 30px;
    height: 30px;
    left: 16px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(59, 130, 246, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);
    }
}

.timeline-content {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.timeline-content:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.timeline-title {
    font-weight: 600;
    font-size: 16px;
    color: #1f2937;
}

.timeline-date {
    font-size: 14px;
    color: #6b7280;
}

.timeline-description {
    color: #4b5563;
    font-size: 14px;
    margin-bottom: 10px;
}

.timeline-duration {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    background: #f3f4f6;
    border-radius: 20px;
    font-size: 13px;
    color: #6b7280;
}

.contrato-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    cursor: pointer;
    transition: all 0.3s;
}

.contrato-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.asignacion-item, .devolucion-item {
    background: #f9fafb;
    border-left: 4px solid #3b82f6;
    padding: 15px;
    margin-bottom: 10px;
    border-radius: 4px;
}

.devolucion-item {
    border-left-color: #ef4444;
}
</style>

<script>
const contratoId = <?php echo $contrato_id; ?>;

document.addEventListener('DOMContentLoaded', function() {
    if (contratoId > 0) {
        cargarTimelineContrato(contratoId);
        cargarAsignaciones(contratoId);
        cargarDevoluciones(contratoId);
    } else {
        cargarListaContratos();
    }
});

async function cargarTimelineContrato(id) {
    try {
        // Cargar info del contrato
        const respContrato = await fetch(`../controllers/workflow_controller.php?action=obtener_contratos_asignados`);
        const dataContratos = await respContrato.json();
        
        if (dataContratos.success) {
            const contrato = dataContratos.data.find(c => c.id == id);
            if (contrato) {
                mostrarInfoContrato(contrato);
            }
        }
        
        // Cargar timeline
        const respTimeline = await fetch(`../controllers/workflow_controller.php?action=obtener_timeline&contrato_id=${id}`);
        const dataTimeline = await respTimeline.json();
        
        if (dataTimeline.success) {
            mostrarTimeline(dataTimeline.data);
        } else {
            document.getElementById('timelineContainer').innerHTML = 
                '<p class="text-muted">No hay información de timeline disponible</p>';
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('timelineContainer').innerHTML = 
            '<p class="text-danger">Error al cargar el timeline</p>';
    }
}

function mostrarInfoContrato(contrato) {
    const html = `
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 10px;">
            <h4 style="margin: 0 0 15px 0; color: white;">
                <i class="fas fa-file-contract"></i> ${contrato.nombre_completo}
            </h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <small style="opacity: 0.8;">Documento</small><br>
                    <strong>${contrato.numero_documento}</strong>
                </div>
                <div>
                    <small style="opacity: 0.8;">Estado Actual</small><br>
                    <strong>${formatearTexto(contrato.estado_workflow || 'en_creacion')}</strong>
                </div>
                <div>
                    <small style="opacity: 0.8;">Días en Etapa Actual</small><br>
                    <strong>${contrato.dias_etapa_actual || 0} días</strong>
                </div>
                <div>
                    <small style="opacity: 0.8;">Creado</small><br>
                    <strong>${formatearFecha(contrato.fecha_creacion)}</strong>
                </div>
            </div>
        </div>
    `;
    document.getElementById('contratoInfo').innerHTML = html;
}

function mostrarTimeline(timeline) {
    if (!timeline || timeline.length === 0) {
        document.getElementById('timelineContainer').innerHTML = 
            '<p class="text-muted">No hay etapas registradas</p>';
        return;
    }
    
    let html = '<div class="timeline">';
    
    timeline.forEach((etapa, index) => {
        const isActive = etapa.activo == 1;
        const color = etapa.color || '#6b7280';
        const diasText = etapa.dias_en_etapa === 1 ? '1 día' : `${etapa.dias_en_etapa} días`;
        
        html += `
            <div class="timeline-item">
                <div class="timeline-marker ${isActive ? 'active' : ''}" 
                     style="background-color: ${color}"></div>
                <div class="timeline-content">
                    <div class="timeline-header">
                        <div class="timeline-title">
                            ${formatearTexto(etapa.etapa)}
                            ${isActive ? '<span class="badge badge-primary" style="margin-left: 10px;">Actual</span>' : ''}
                        </div>
                        <div class="timeline-date">
                            ${formatearFecha(etapa.fecha_entrada)}
                        </div>
                    </div>
                    ${etapa.descripcion_etapa ? `
                        <div class="timeline-description">
                            ${etapa.descripcion_etapa}
                        </div>
                    ` : ''}
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <span class="timeline-duration">
                            <i class="fas fa-clock"></i>
                            ${diasText}
                        </span>
                        ${etapa.fecha_salida ? `
                            <span style="font-size: 13px; color: #6b7280;">
                                Salida: ${formatearFecha(etapa.fecha_salida)}
                            </span>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    document.getElementById('timelineContainer').innerHTML = html;
}

async function cargarAsignaciones(id) {
    try {
        const response = await fetch(`../controllers/workflow_controller.php?action=obtener_asignaciones&contrato_id=${id}`);
        const data = await response.json();
        
        if (data.success && data.data.length > 0) {
            mostrarAsignaciones(data.data);
        } else {
            document.getElementById('asignacionesContainer').innerHTML = 
                '<p class="text-muted">No hay asignaciones registradas</p>';
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function mostrarAsignaciones(asignaciones) {
    let html = '';
    
    asignaciones.forEach(asig => {
        const estadoBadge = {
            'pendiente': 'badge-warning',
            'en_proceso': 'badge-info',
            'completado': 'badge-success',
            'devuelto': 'badge-danger'
        }[asig.estado] || 'badge-secondary';
        
        html += `
            <div class="asignacion-item">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <strong>${formatearTexto(asig.etapa)}</strong><br>
                        <span style="color: #6b7280; font-size: 14px;">
                            Usuario: ${asig.nombre_usuario_asignado}
                            ${asig.nombre_usuario_asigno ? ` • Asignado por: ${asig.nombre_usuario_asigno}` : ''}
                        </span>
                    </div>
                    <span class="badge ${estadoBadge}">${formatearTexto(asig.estado)}</span>
                </div>
                <div style="margin-top: 10px; font-size: 13px; color: #6b7280;">
                    <i class="fas fa-calendar"></i> ${formatearFechaHora(asig.fecha_asignacion)}
                    ${asig.dias_transcurridos !== null ? ` • <i class="fas fa-clock"></i> ${asig.dias_transcurridos} días` : ''}
                </div>
                ${asig.comentarios ? `
                    <div style="margin-top: 10px; padding: 10px; background: white; border-radius: 4px; font-size: 13px;">
                        <i class="fas fa-comment"></i> ${asig.comentarios}
                    </div>
                ` : ''}
            </div>
        `;
    });
    
    document.getElementById('asignacionesContainer').innerHTML = html;
}

async function cargarDevoluciones(id) {
    try {
        const response = await fetch(`../controllers/workflow_controller.php?action=obtener_devoluciones&contrato_id=${id}`);
        const data = await response.json();
        
        if (data.success && data.data.length > 0) {
            mostrarDevoluciones(data.data);
        } else {
            document.getElementById('devolucionesContainer').innerHTML = 
                '<p class="text-muted">No hay devoluciones registradas</p>';
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function mostrarDevoluciones(devoluciones) {
    let html = '';
    
    devoluciones.forEach(dev => {
        const estadoBadge = dev.estado === 'atendido' ? 'badge-success' : 'badge-warning';
        
        html += `
            <div class="devolucion-item">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <strong style="color: #ef4444;">
                            <i class="fas fa-undo"></i> 
                            ${formatearTexto(dev.etapa_origen)} → ${formatearTexto(dev.etapa_destino)}
                        </strong><br>
                        <span style="color: #6b7280; font-size: 14px;">
                            Devuelto por: ${dev.nombre_usuario_devuelve}
                        </span>
                    </div>
                    <span class="badge ${estadoBadge}">${formatearTexto(dev.estado)}</span>
                </div>
                <div style="margin-top: 10px; padding: 10px; background: white; border-radius: 4px;">
                    <strong style="font-size: 13px;">Motivo:</strong><br>
                    ${dev.motivo_devolucion}
                    ${dev.comentarios ? `<br><br><strong>Comentarios:</strong> ${dev.comentarios}` : ''}
                </div>
                <div style="margin-top: 10px; font-size: 13px; color: #6b7280;">
                    <i class="fas fa-calendar"></i> ${formatearFechaHora(dev.fecha_devolucion)}
                    ${dev.fecha_atencion ? ` • Atendido: ${formatearFechaHora(dev.fecha_atencion)} por ${dev.nombre_usuario_atiende}` : ''}
                </div>
            </div>
        `;
    });
    
    document.getElementById('devolucionesContainer').innerHTML = html;
}

async function cargarListaContratos() {
    try {
        const response = await fetch('../controllers/workflow_controller.php?action=obtener_contratos_asignados');
        const data = await response.json();
        
        if (data.success) {
            mostrarListaContratos(data.data);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function mostrarListaContratos(contratos) {
    const container = document.getElementById('listaContratos');
    
    if (contratos.length === 0) {
        container.innerHTML = '<p class="text-muted">No hay contratos disponibles</p>';
        return;
    }
    
    let html = '';
    contratos.forEach(contrato => {
        const color = contrato.color_estado || '#6b7280';
        
        html += `
            <div class="contrato-card" onclick="window.location.href='workflow_timeline.php?contrato_id=${contrato.id}'">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div style="flex: 1;">
                        <h5 style="margin: 0 0 10px 0;">${contrato.nombre_completo}</h5>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; font-size: 14px; color: #6b7280;">
                            <div>
                                <i class="fas fa-id-card"></i> ${contrato.numero_documento}
                            </div>
                            <div>
                                <i class="fas fa-envelope"></i> ${contrato.correo_electronico}
                            </div>
                            <div>
                                <i class="fas fa-clock"></i> ${contrato.dias_etapa_actual || 0} días en etapa
                            </div>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span class="badge" style="background-color: ${color}; font-size: 13px;">
                            ${formatearTexto(contrato.estado_workflow || 'en_creacion')}
                        </span>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    
    // Filtro
    document.getElementById('filtroContratos').addEventListener('input', function(e) {
        const filtro = e.target.value.toLowerCase();
        const cards = document.querySelectorAll('.contrato-card');
        
        cards.forEach(card => {
            const texto = card.textContent.toLowerCase();
            card.style.display = texto.includes(filtro) ? 'block' : 'none';
        });
    });
}

// Funciones auxiliares
function formatearFecha(fecha) {
    if (!fecha) return '';
    const d = new Date(fecha + 'T00:00:00');
    return d.toLocaleDateString('es-CO');
}

function formatearFechaHora(fecha) {
    if (!fecha) return '';
    const d = new Date(fecha);
    return d.toLocaleString('es-CO');
}

function formatearTexto(texto) {
    if (!texto) return '';
    return texto.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
