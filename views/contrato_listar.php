<?php
$pageTitle = 'Listar Contratos';
require_once __DIR__ . '/../includes/header.php';

// Obtener contratos
$query = "SELECT c.*, u.nombre as nombre_usuario_creacion,
          a.nombre as nombre_abogado_asignado,
          (SELECT COUNT(*) FROM documentos WHERE contrato_id = c.id) as total_documentos,
          CASE 
              WHEN c.fecha_asignacion IS NULL THEN NULL
              WHEN c.fecha_aprobacion IS NOT NULL THEN DATEDIFF(c.fecha_aprobacion, c.fecha_asignacion)
              ELSE DATEDIFF(NOW(), c.fecha_asignacion)
          END as dias_transcurridos
          FROM contratos c
          LEFT JOIN usuarios u ON c.usuario_creacion = u.id
          LEFT JOIN usuarios a ON c.abogado_asignado = a.id
          WHERE c.estado = 'activo'
          ORDER BY c.fecha_creacion DESC";
$result = $mysqli->query($query);
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-list-alt"></i> Listado de Contratos</h3>
        <a href="contrato_crear.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuevo Contrato
        </a>
    </div>
    <div class="card-body">
        <!-- Filtros -->
        <div style="margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 250px;">
                <input type="text" 
                       id="filtroTabla" 
                       class="form-control" 
                       placeholder="🔍 Buscar por nombre, documento, correo...">
            </div>
            <div>
                <select id="filtroMunicipio" class="form-control form-select" style="min-width: 180px;">
                    <option value="">Todos los municipios</option>
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
            <div>
                <a href="../controllers/exportar_controller.php" class="btn btn-secondary" style="display: flex; align-items: center; gap: 8px; text-decoration: none;">
                    <i class="fas fa-file-excel"></i> 
                    <span>Exportar a Excel</span>
                </a>
            </div>
        </div>

        <!-- Tabla -->
        <div class="table-responsive">
            <table class="table" id="tablaContratos">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre Completo</th>
                        <th>Documento</th>
                        <th>Correo</th>
                        <th>Municipio</th>
                        <th>Fecha Diligenciamiento</th>
                        <th>Abogado Asignado</th>
                        <th>Días Transcurridos</th>
                        <th>Estado</th>
                        <th>Documentos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($contrato = $result->fetch_assoc()): 
                        // Calcular color de fila según días transcurridos
                        $bgColor = 'transparent';
                        if ($contrato['dias_transcurridos'] !== null && $contrato['estado_aprobacion'] === 'pendiente') {
                            if ($contrato['dias_transcurridos'] <= 10) {
                                $bgColor = '#d1f2eb'; // Verde claro
                            } elseif ($contrato['dias_transcurridos'] <= 20) {
                                $bgColor = '#fff3cd'; // Amarillo claro
                            } else {
                                $bgColor = '#f8d7da'; // Rojo claro
                            }
                        }
                    ?>
                    <tr data-municipio="<?php echo $contrato['municipio_residencia']; ?>" style="background-color: <?php echo $bgColor; ?>;">
                        <td><?php echo $contrato['id']; ?></td>
                        <td><?php echo htmlspecialchars($contrato['nombre_completo']); ?></td>
                        <td><?php echo $contrato['numero_documento']; ?></td>
                        <td><?php echo htmlspecialchars($contrato['correo_electronico']); ?></td>
                        <td><?php echo ucwords(str_replace('_', ' ', $contrato['municipio_residencia'])); ?></td>
                        <td><?php echo formatearFecha($contrato['fecha_diligenciamiento']); ?></td>
                        <td>
                            <?php if ($contrato['abogado_asignado']): ?>
                                <span class="badge badge-info">
                                    <?php echo htmlspecialchars($contrato['nombre_abogado_asignado']); ?>
                                </span>
                                <br>
                                <small style="color: var(--gray-600);">
                                    Asignado: <?php echo formatearFecha($contrato['fecha_asignacion']); ?>
                                </small>
                            <?php else: ?>
                                <span class="badge badge-secondary">Sin asignar</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($contrato['dias_transcurridos'] !== null): ?>
                                <strong><?php echo $contrato['dias_transcurridos']; ?> días</strong>
                            <?php else: ?>
                                <span style="color: var(--gray-400);">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($contrato['estado_aprobacion'] === 'aprobado'): ?>
                                <span class="badge badge-success">Aprobado</span>
                                <br>
                                <small style="color: var(--gray-600);">
                                    <?php echo formatearFecha($contrato['fecha_aprobacion']); ?>
                                </small>
                            <?php else: ?>
                                <span class="badge badge-warning">Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?php echo $contrato['total_documentos'] > 0 ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo $contrato['total_documentos']; ?> archivos
                            </span>
                        </td>
                        <td>
                            <button onclick="verContrato(<?php echo $contrato['id']; ?>)" 
                                    class="btn btn-sm btn-info"
                                    title="Ver Detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="window.location.href='contrato_editar.php?id=<?php echo $contrato['id']; ?>'" 
                                    class="btn btn-sm btn-primary"
                                    title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="verDocumentos(<?php echo $contrato['id']; ?>, '<?php echo htmlspecialchars($contrato['nombre_completo']); ?>')" 
                                    class="btn btn-sm btn-secondary"
                                    title="Ver Documentos">
                                <i class="fas fa-folder-open"></i>
                            </button>
                            <?php if ($contrato['estado_aprobacion'] === 'pendiente' && (esAdministrador() || esAbogado())): ?>
                            <button onclick="aprobarContrato(<?php echo $contrato['id']; ?>, '<?php echo htmlspecialchars($contrato['nombre_completo']); ?>')" 
                                    class="btn btn-sm btn-success"
                                    title="Aprobar Contrato">
                                <i class="fas fa-check-circle"></i>
                            </button>
                            <?php endif; ?>
                            <?php if (esAdministrador()): ?>
                            <button onclick="eliminarContrato(<?php echo $contrato['id']; ?>, '<?php echo htmlspecialchars($contrato['nombre_completo']); ?>')" 
                                    class="btn btn-sm btn-danger"
                                    title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Ver Contrato -->
<div id="modalVerContrato" class="modal">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h3>Detalles del Contrato</h3>
            <button class="modal-close" onclick="ocultarModal('modalVerContrato')">&times;</button>
        </div>
        <div class="modal-body" id="detallesContrato">
            <!-- Contenido dinámico -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-light" onclick="ocultarModal('modalVerContrato')">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal Ver Documentos -->
<div id="modalVerDocumentos" class="modal">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h3>Documentos del Contrato</h3>
            <button class="modal-close" onclick="ocultarModal('modalVerDocumentos')">&times;</button>
        </div>
        <div class="modal-body">
            <h4 id="nombreContratoDocumentos" style="margin-bottom: 20px;"></h4>
            <div id="listaDocumentos">
                <!-- Contenido dinámico -->
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-light" onclick="ocultarModal('modalVerDocumentos')">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal Editar Contrato -->
<div id="modalEditarContrato" class="modal">
    <div class="modal-content" style="max-width: 1200px; max-height: 90vh; overflow-y: auto;">
        <div class="modal-header">
            <h3>Editar Contrato</h3>
            <button class="modal-close" onclick="ocultarModal('modalEditarContrato')">&times;</button>
        </div>
        <div class="modal-body" id="formularioEdicion">
            <!-- Contenido dinámico -->
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
    margin-top: 10px;
}

.file-item-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 10px;
}

.file-item-actions {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

.estado-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.estado-pendiente {
    background-color: #fff3cd;
    color: #856404;
}

.estado-aprobado {
    background-color: #d1f2eb;
    color: #0f5132;
}

.estado-rechazado {
    background-color: #f8d7da;
    color: #842029;
}

/* Asegurar que SweetAlert aparezca sobre los modales */
.swal-high-zindex {
    z-index: 10000 !important;
}
</style>

<script>
// URL base del proyecto
const BASE_URL = window.location.origin + '/contratos';

// Filtro de búsqueda general
document.getElementById('filtroTabla').addEventListener('keyup', function() {
    filtrarTablaContratos();
});

// Filtro por municipio
document.getElementById('filtroMunicipio').addEventListener('change', function() {
    filtrarTablaContratos();
});

function filtrarTablaContratos() {
    const filtro = document.getElementById('filtroTabla').value.toLowerCase();
    const filtroMunicipio = document.getElementById('filtroMunicipio').value;
    const filas = document.querySelectorAll('#tablaContratos tbody tr');

    filas.forEach(fila => {
        const texto = fila.textContent.toLowerCase();
        const municipio = fila.getAttribute('data-municipio');

        let mostrar = true;

        if (filtro && !texto.includes(filtro)) {
            mostrar = false;
        }

        if (filtroMunicipio && municipio !== filtroMunicipio) {
            mostrar = false;
        }

        fila.style.display = mostrar ? '' : 'none';
    });
}

function verContrato(id) {
    fetch(`../controllers/contrato_controller.php?action=obtener&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const c = data.data;
                let html = `
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <h4 style="color: var(--primary-color); margin-bottom: 15px;">Información Personal</h4>
                            <p><strong>Nombre:</strong> ${c.nombre_completo}</p>
                            <p><strong>Documento:</strong> ${c.tipo_documento.replace(/_/g, ' ')} - ${c.numero_documento}</p>
                            <p><strong>Lugar Expedición:</strong> ${c.lugar_expedicion}</p>
                            <p><strong>Fecha Nacimiento:</strong> ${formatearFecha(c.fecha_nacimiento)}</p>
                            <p><strong>Correo:</strong> ${c.correo_electronico}</p>
                            <p><strong>Celular:</strong> ${c.celular_contacto}</p>
                            <p><strong>Género:</strong> ${c.identidad_genero.replace(/_/g, ' ')}</p>
                            <p><strong>Estado Civil:</strong> ${c.estado_civil.replace(/_/g, ' ')}</p>
                        </div>
                        <div>
                            <h4 style="color: var(--primary-color); margin-bottom: 15px;">Información de Residencia</h4>
                            <p><strong>Dirección:</strong> ${c.direccion_residencia}</p>
                            <p><strong>Barrio:</strong> ${c.barrio}</p>
                            <p><strong>Municipio:</strong> ${c.municipio_residencia.replace(/_/g, ' ')}</p>
                            <p><strong>Grupo Poblacional:</strong> ${c.grupo_poblacional.replace(/_/g, ' ')}</p>
                            <p><strong>Discapacidad:</strong> ${c.posee_discapacidad}</p>
                            ${c.especifique_discapacidad ? `<p><strong>Especifique:</strong> ${c.especifique_discapacidad}</p>` : ''}
                        </div>
                    </div>
                    <hr style="margin: 20px 0;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <h4 style="color: var(--primary-color); margin-bottom: 15px;">Información Familiar</h4>
                            <p><strong>Hijos Dependientes:</strong> ${c.numero_hijos_dependientes}</p>
                            <p><strong>Hijos Menores:</strong> ${c.tiene_hijos_menores}</p>
                            ${c.cuantos_hijos_menores ? `<p><strong>Cantidad:</strong> ${c.cuantos_hijos_menores}</p>` : ''}
                            <p><strong>Padre/Madre Soltero:</strong> ${c.padre_madre_soltero}</p>
                        </div>
                        <div>
                            <h4 style="color: var(--primary-color); margin-bottom: 15px;">Educación</h4>
                            <p><strong>Nivel Estudio:</strong> ${c.nivel_estudio}</p>
                            ${c.formacion_tecnica ? `<p><strong>Técnica:</strong> ${c.formacion_tecnica}</p>` : ''}
                            ${c.formacion_tecnologica ? `<p><strong>Tecnológica:</strong> ${c.formacion_tecnologica}</p>` : ''}
                            ${c.formacion_pregrado ? `<p><strong>Pregrado:</strong> ${c.formacion_pregrado}</p>` : ''}
                            ${c.formacion_posgrado ? `<p><strong>Posgrado:</strong> ${c.formacion_posgrado}</p>` : ''}
                        </div>
                    </div>
                    <hr style="margin: 20px 0;">
                    <div>
                        <h4 style="color: var(--primary-color); margin-bottom: 15px;">Seguridad Social y Trabajo</h4>
                        <p><strong>EPS:</strong> ${c.eps_afiliado}</p>
                        <p><strong>Fondo Pensión:</strong> ${c.fondo_pension}</p>
                        <p><strong>ARL:</strong> ${c.arl}</p>
                        <p><strong>Municipios Trabajo:</strong> ${c.trabajo_municipio.replace(/,/g, ', ').replace(/_/g, ' ')}</p>
                    </div>
                `;
                
                document.getElementById('detallesContrato').innerHTML = html;
                mostrarModal('modalVerContrato');
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
}

function verDocumentos(contratoId, nombreCompleto) {
    document.getElementById('nombreContratoDocumentos').textContent = nombreCompleto;
    
    fetch(`../controllers/contrato_controller.php?action=documentos&contrato_id=${contratoId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '';
                
                if (data.data.length > 0) {
                    data.data.forEach(doc => {
                        const tipoNombre = doc.tipo_documento.replace(/_/g, ' ').split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
                        const estadoBadgeClass = doc.estado_documento === 'aprobado' ? 'estado-aprobado' : 
                                                 doc.estado_documento === 'rechazado' ? 'estado-rechazado' : 'estado-pendiente';
                        const estadoTexto = doc.estado_documento === 'aprobado' ? 'Aprobado' : 
                                           doc.estado_documento === 'rechazado' ? 'Rechazado' : 'Pendiente';
                        
                        html += `
                            <div class="file-item">
                                <div class="file-item-header">
                                    <div>
                                        <h5 style="margin: 0 0 5px 0; font-size: 14px;">${tipoNombre}</h5>
                                        <small style="color: var(--gray-500);">Subido: ${formatearFecha(doc.fecha_subida)}</small>
                                    </div>
                                    <span class="estado-badge ${estadoBadgeClass}">${estadoTexto}</span>
                                </div>
                                
                                ${doc.comentario_revision ? `
                                    <div style="background-color: var(--gray-50); padding: 10px; border-radius: 4px; margin: 10px 0;">
                                        <strong style="font-size: 12px; color: var(--gray-600);">Comentario:</strong>
                                        <p style="margin: 5px 0 0 0; font-size: 13px;">${doc.comentario_revision}</p>
                                        ${doc.fecha_revision ? `<small style="color: var(--gray-500);">Revisado: ${formatearFecha(doc.fecha_revision)}</small>` : ''}
                                    </div>
                                ` : ''}
                                
                                <div class="file-item-actions">
                                    <a href="../uploads/documentos/${doc.nombre_archivo}" 
                                       target="_blank" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-download"></i> Ver/Descargar
                                    </a>
                                    <?php if (esAdministrador() || esAbogado()): ?>
                                    <button onclick="revisarDocumento(${doc.id}, 'aprobado')" 
                                            class="btn btn-sm btn-success"
                                            ${doc.estado_documento === 'aprobado' ? 'disabled' : ''}>
                                        <i class="fas fa-check"></i> Aprobar
                                    </button>
                                    <button onclick="revisarDocumento(${doc.id}, 'rechazado')" 
                                            class="btn btn-sm btn-danger"
                                            ${doc.estado_documento === 'rechazado' ? 'disabled' : ''}>
                                        <i class="fas fa-times"></i> Rechazar
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    html = '<p style="text-align: center; color: var(--gray-500); padding: 20px;">No hay documentos asociados</p>';
                }
                
                document.getElementById('listaDocumentos').innerHTML = html;
                mostrarModal('modalVerDocumentos');
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
}

function revisarDocumento(documentoId, estado) {
    Swal.fire({
        title: estado === 'aprobado' ? '¿Aprobar documento?' : '¿Rechazar documento?',
        input: 'textarea',
        inputLabel: 'Comentario (opcional)',
        inputPlaceholder: 'Escriba un comentario sobre la decisión...',
        showCancelButton: true,
        confirmButtonColor: estado === 'aprobado' ? '#10b981' : '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: estado === 'aprobado' ? 'Aprobar' : 'Rechazar',
        cancelButtonText: 'Cancelar',
        customClass: {
            container: 'swal-high-zindex'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'actualizar_documento');
            formData.append('documento_id', documentoId);
            formData.append('estado', estado);
            formData.append('comentario', result.value || '');

            fetch(BASE_URL + '/controllers/contrato_controller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('¡Actualizado!', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
}

function editarContrato(id) {
    fetch(`../controllers/contrato_controller.php?action=obtener&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const c = data.data;
                
                // Obtener lista de abogados
                fetch(BASE_URL + '/controllers/usuario_controller.php?action=listar_abogados')
                    .then(response => response.json())
                    .then(abogadosData => {
                        let opcionesAbogados = '<option value="">Sin asignar</option>';
                        if (abogadosData.success && abogadosData.data) {
                            abogadosData.data.forEach(abogado => {
                                const selected = c.abogado_asignado == abogado.id ? 'selected' : '';
                                opcionesAbogados += `<option value="${abogado.id}" ${selected}>${abogado.nombre}</option>`;
                            });
                        }
                        
                        // Crear formulario de edición (versión simplificada)
                        const html = `
                            <form id="formEditarContrato">
                                <input type="hidden" name="id" value="${c.id}">
                                <input type="hidden" name="action" value="editar">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Nombre Completo</label>
                                        <input type="text" name="nombre_completo" class="form-control" value="${c.nombre_completo}" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Correo Electrónico</label>
                                        <input type="email" name="correo_electronico" class="form-control" value="${c.correo_electronico}" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Número de Documento</label>
                                        <input type="text" name="numero_documento" class="form-control" value="${c.numero_documento}" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Celular</label>
                                        <input type="tel" name="celular_contacto" class="form-control" value="${c.celular_contacto}" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Abogado Asignado</label>
                                    <select name="abogado_asignado" class="form-control form-select">
                                        ${opcionesAbogados}
                                    </select>
                                    <small class="form-text">Cambiar el abogado actualizará la fecha de asignación</small>
                                </div>
                                
                                <input type="hidden" name="fecha_diligenciamiento" value="${c.fecha_diligenciamiento}">
                                <input type="hidden" name="tipo_documento" value="${c.tipo_documento}">
                                <input type="hidden" name="lugar_expedicion" value="${c.lugar_expedicion}">
                                <input type="hidden" name="fecha_nacimiento" value="${c.fecha_nacimiento}">
                                <input type="hidden" name="identidad_genero" value="${c.identidad_genero}">
                                <input type="hidden" name="grupo_poblacional" value="${c.grupo_poblacional}">
                                <input type="hidden" name="posee_discapacidad" value="${c.posee_discapacidad}">
                                <input type="hidden" name="especifique_discapacidad" value="${c.especifique_discapacidad || ''}">
                                <input type="hidden" name="estado_civil" value="${c.estado_civil}">
                                <input type="hidden" name="numero_hijos_dependientes" value="${c.numero_hijos_dependientes}">
                                <input type="hidden" name="tiene_hijos_menores" value="${c.tiene_hijos_menores}">
                                <input type="hidden" name="cuantos_hijos_menores" value="${c.cuantos_hijos_menores || ''}">
                                <input type="hidden" name="padre_madre_soltero" value="${c.padre_madre_soltero}">
                                <input type="hidden" name="direccion_residencia" value="${c.direccion_residencia}">
                                <input type="hidden" name="barrio" value="${c.barrio}">
                                <input type="hidden" name="municipio_residencia" value="${c.municipio_residencia}">
                                <input type="hidden" name="nivel_estudio" value="${c.nivel_estudio}">
                                <input type="hidden" name="formacion_tecnica" value="${c.formacion_tecnica || ''}">
                                <input type="hidden" name="formacion_tecnologica" value="${c.formacion_tecnologica || ''}">
                                <input type="hidden" name="formacion_pregrado" value="${c.formacion_pregrado || ''}">
                                <input type="hidden" name="formacion_posgrado" value="${c.formacion_posgrado || ''}">
                                <input type="hidden" name="datos_posgrado" value="${c.datos_posgrado || ''}">
                                <input type="hidden" name="maestria" value="${c.maestria || ''}">
                                <input type="hidden" name="posee_doctorado" value="${c.posee_doctorado || ''}">
                                <input type="hidden" name="eps_afiliado" value="${c.eps_afiliado}">
                                <input type="hidden" name="fondo_pension" value="${c.fondo_pension}">
                                <input type="hidden" name="arl" value="${c.arl}">
                                <input type="hidden" name="trabajo_municipio" value="${c.trabajo_municipio}">
                                
                                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                                    <button type="button" class="btn btn-light" onclick="ocultarModal('modalEditarContrato')">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                </div>
                            </form>
                        `;
                        
                        document.getElementById('formularioEdicion').innerHTML = html;
                        
                        // Agregar evento al formulario
                        document.getElementById('formEditarContrato').addEventListener('submit', function(e) {
                            e.preventDefault();
                            
                            const formData = new FormData(this);
                            
                            fetch(BASE_URL + '/controllers/contrato_controller.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire('¡Actualizado!', data.message, 'success').then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire('Error', data.message, 'error');
                                }
                            });
                        });
                        
                        mostrarModal('modalEditarContrato');
                    });
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
}

function aprobarContrato(id, nombre) {
    Swal.fire({
        title: '¿Aprobar contrato?',
        text: `¿Está seguro de aprobar el contrato de "${nombre}"? Esta acción detendrá el contador de días.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, aprobar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'aprobar');
            formData.append('id', id);

            fetch(BASE_URL + '/controllers/contrato_controller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('¡Aprobado!', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
}

function eliminarContrato(id, nombre) {
    Swal.fire({
        title: '¿Está seguro?',
        text: `¿Desea eliminar el contrato de "${nombre}"? Esta acción no se puede deshacer y eliminará todos los documentos asociados.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'eliminar');
            formData.append('id', id);

            fetch(BASE_URL + '/controllers/contrato_controller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('¡Eliminado!', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
