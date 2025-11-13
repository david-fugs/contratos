/**
 * Gestión de CDP y Datos Técnicos
 */

// Variables globales
const contratoId = new URLSearchParams(window.location.search).get('contrato_id');

// Cargar CDPs del contrato
async function cargarCDPs() {
    try {
        const response = await fetch(`../controllers/cdp_controller.php?action=listar&contrato_id=${contratoId}`);
        const data = await response.json();
        
        if (data.success) {
            mostrarCDPs(data.data);
            document.getElementById('contadorCDP').textContent = data.data.length;
        } else {
            console.error('Error al cargar CDPs:', data.message);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Mostrar lista de CDPs
function mostrarCDPs(cdps) {
    const lista = document.getElementById('listaCDP');
    
    if (cdps.length === 0) {
        lista.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #6b7280;">
                <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px;"></i>
                <p>No hay CDP registrados para este contrato</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    cdps.forEach(cdp => {
        html += `
            <div class="cdp-item">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div style="flex: 1;">
                        <h5 style="margin: 0 0 10px 0; color: var(--primary-color);">
                            ${cdp.nombre_rubro}
                        </h5>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-bottom: 10px;">
                            <div>
                                <strong>Rubro:</strong> ${cdp.rubro}
                            </div>
                            <div>
                                <strong>Valor:</strong> $ ${parseFloat(cdp.valor).toLocaleString('es-CO', {minimumFractionDigits: 2})}
                            </div>
                            <div>
                                <strong>Fecha CDP:</strong> ${formatearFecha(cdp.fecha_cdp)}
                            </div>
                            <div>
                                <strong>Dependencia:</strong> ${formatearTexto(cdp.dependencia)}
                            </div>
                        </div>
                        ${cdp.numero_proceso ? `<div><strong>Nº Proceso:</strong> ${cdp.numero_proceso}</div>` : ''}
                        ${cdp.objeto ? `<div style="margin-top: 10px;"><strong>Objeto:</strong> ${cdp.objeto}</div>` : ''}
                        ${cdp.archivo_pdf ? `
                            <div style="margin-top: 10px;">
                                <a href="../uploads/cdp/${cdp.archivo_pdf}" target="_blank" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-file-pdf"></i> Ver PDF
                                </a>
                            </div>
                        ` : ''}
                        <div style="margin-top: 10px; font-size: 12px; color: #6b7280;">
                            Creado por: ${cdp.nombre_usuario_creacion} - ${formatearFechaHora(cdp.fecha_creacion)}
                        </div>
                    </div>
                    <div style="display: flex; gap: 5px;">
                        <button onclick="editarCDP(${cdp.id})" class="btn btn-sm btn-primary" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="eliminarCDP(${cdp.id})" class="btn btn-sm btn-danger" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    lista.innerHTML = html;
}

// Mostrar formulario CDP
function mostrarFormularioCDP() {
    document.getElementById('modalCDPTitulo').textContent = 'Agregar CDP';
    document.getElementById('formCDP').reset();
    document.getElementById('cdp_action').value = 'crear';
    document.getElementById('cdp_id').value = '';
    document.getElementById('fecha_cdp').value = new Date().toISOString().split('T')[0];
    mostrarModal('modalCDP');
}

// Editar CDP
async function editarCDP(cdpId) {
    try {
        const response = await fetch(`../controllers/cdp_controller.php?action=obtener&id=${cdpId}`);
        const data = await response.json();
        
        if (data.success) {
            const cdp = data.data;
            document.getElementById('modalCDPTitulo').textContent = 'Editar CDP';
            document.getElementById('cdp_action').value = 'editar';
            document.getElementById('cdp_id').value = cdp.id;
            document.getElementById('fecha_cdp').value = cdp.fecha_cdp;
            document.getElementById('rubro').value = cdp.rubro;
            document.getElementById('nombre_rubro').value = cdp.nombre_rubro;
            document.getElementById('valor').value = '$ ' + parseFloat(cdp.valor).toLocaleString('es-CO');
            document.getElementById('objeto').value = cdp.objeto || '';
            document.getElementById('numero_proceso').value = cdp.numero_proceso || '';
            document.getElementById('dependencia').value = cdp.dependencia;
            
            mostrarModal('modalCDP');
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('Error', 'No se pudo cargar el CDP', 'error');
    }
}

// Eliminar CDP
function eliminarCDP(cdpId) {
    Swal.fire({
        title: '¿Está seguro?',
        text: 'El CDP será marcado como inactivo',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const formData = new FormData();
                formData.append('action', 'eliminar');
                formData.append('id', cdpId);
                
                const response = await fetch('../controllers/cdp_controller.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire('Eliminado', data.message, 'success');
                    cargarCDPs();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'No se pudo eliminar el CDP', 'error');
            }
        }
    });
}

// Cerrar modal CDP
function cerrarModalCDP() {
    ocultarModal('modalCDP');
    document.getElementById('formCDP').reset();
}

// Enviar formulario CDP
document.addEventListener('DOMContentLoaded', function() {
    const formCDP = document.getElementById('formCDP');
    if (formCDP) {
        formCDP.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('../controllers/cdp_controller.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire('Éxito', data.message, 'success');
                    cerrarModalCDP();
                    cargarCDPs();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'No se pudo guardar el CDP', 'error');
            }
        });
    }
});

// Cargar datos técnicos
async function cargarDatosTecnicos() {
    try {
        const response = await fetch(`../controllers/datos_tecnicos_controller.php?action=obtener&contrato_id=${contratoId}`);
        const data = await response.json();
        
        if (data.success && data.data) {
            const dt = data.data;
            document.getElementById('numero_contrato_interno').value = dt.numero_contrato_interno || '';
            document.getElementById('numero_contrato_secop').value = dt.numero_contrato_secop || '';
            document.getElementById('fecha_firma_contrato').value = dt.fecha_firma_contrato || '';
            document.getElementById('plazo_inicial_dias').value = dt.plazo_inicial_dias || '';
            document.getElementById('enlace_secop_ii').value = dt.enlace_secop_ii || '';
            document.getElementById('solicitud_arl').value = dt.solicitud_arl || '';
            document.getElementById('fecha_aprobacion_arl').value = dt.fecha_aprobacion_arl || '';
            document.getElementById('solicitud_rp').value = dt.solicitud_rp || '';
            document.getElementById('numero_rp').value = dt.numero_rp || '';
            document.getElementById('fecha_rp').value = dt.fecha_rp || '';
            document.getElementById('nombre_supervisor').value = dt.nombre_supervisor || '';
            document.getElementById('notificacion_supervision').value = dt.notificacion_supervision || '';
            document.getElementById('acta_inicio_entregada').value = dt.acta_inicio_entregada || '';
            document.getElementById('estado_secop').value = dt.estado_secop || '';
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Guardar datos técnicos
document.addEventListener('DOMContentLoaded', function() {
    const formDatosTecnicos = document.getElementById('formDatosTecnicos');
    if (formDatosTecnicos) {
        formDatosTecnicos.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('../controllers/datos_tecnicos_controller.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire('Éxito', data.message, 'success');
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'No se pudieron guardar los datos técnicos', 'error');
            }
        });
    }
});

// Cargar abogados
async function cargarAbogados() {
    try {
        const response = await fetch('../controllers/usuario_controller.php?action=listar_abogados');
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('abogado_asignado');
            select.innerHTML = '<option value="">Seleccione un abogado...</option>';
            
            data.data.forEach(abogado => {
                select.innerHTML += `<option value="${abogado.id}">${abogado.nombre}</option>`;
            });
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Asignar abogado
document.addEventListener('DOMContentLoaded', function() {
    const formAsignar = document.getElementById('formAsignarAbogado');
    if (formAsignar) {
        formAsignar.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('../controllers/workflow_controller.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire('Éxito', data.message, 'success');
                    this.reset();
                    cargarHistorialAsignaciones();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'No se pudo asignar el abogado', 'error');
            }
        });
    }
});

// Cargar historial de asignaciones
async function cargarHistorialAsignaciones() {
    try {
        const response = await fetch(`../controllers/workflow_controller.php?action=obtener_asignaciones&contrato_id=${contratoId}`);
        const data = await response.json();
        
        if (data.success) {
            mostrarHistorialAsignaciones(data.data);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Mostrar historial de asignaciones
function mostrarHistorialAsignaciones(asignaciones) {
    const lista = document.getElementById('listaAsignaciones');
    
    if (asignaciones.length === 0) {
        lista.innerHTML = '<p class="text-muted">No hay asignaciones registradas</p>';
        return;
    }
    
    let html = '<div class="table-responsive"><table class="table"><thead><tr><th>Etapa</th><th>Usuario</th><th>Estado</th><th>Fecha Asignación</th><th>Días</th></tr></thead><tbody>';
    
    asignaciones.forEach(asig => {
        const estadoBadge = {
            'pendiente': 'badge-warning',
            'en_proceso': 'badge-info',
            'completado': 'badge-success',
            'devuelto': 'badge-danger'
        }[asig.estado] || 'badge-secondary';
        
        html += `
            <tr>
                <td>${formatearTexto(asig.etapa)}</td>
                <td>${asig.nombre_usuario_asignado}</td>
                <td><span class="badge ${estadoBadge}">${formatearTexto(asig.estado)}</span></td>
                <td>${formatearFechaHora(asig.fecha_asignacion)}</td>
                <td>${asig.dias_transcurridos || 0} días</td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    lista.innerHTML = html;
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

function mostrarModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

function ocultarModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}
