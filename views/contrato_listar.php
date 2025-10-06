<?php
$pageTitle = 'Listar Contratos';
require_once __DIR__ . '/../includes/header.php';

// Obtener contratos
$query = "SELECT c.*, u.nombre as nombre_usuario_creacion, 
          (SELECT COUNT(*) FROM documentos WHERE contrato_id = c.id) as total_documentos
          FROM contratos c
          LEFT JOIN usuarios u ON c.usuario_creacion = u.id
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
                        <th>Documentos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($contrato = $result->fetch_assoc()): ?>
                    <tr data-municipio="<?php echo $contrato['municipio_residencia']; ?>">
                        <td><?php echo $contrato['id']; ?></td>
                        <td><?php echo htmlspecialchars($contrato['nombre_completo']); ?></td>
                        <td><?php echo $contrato['numero_documento']; ?></td>
                        <td><?php echo htmlspecialchars($contrato['correo_electronico']); ?></td>
                        <td><?php echo ucwords(str_replace('_', ' ', $contrato['municipio_residencia'])); ?></td>
                        <td><?php echo formatearFecha($contrato['fecha_diligenciamiento']); ?></td>
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
                            <button onclick="verDocumentos(<?php echo $contrato['id']; ?>, '<?php echo htmlspecialchars($contrato['nombre_completo']); ?>')" 
                                    class="btn btn-sm btn-secondary"
                                    title="Ver Documentos">
                                <i class="fas fa-folder-open"></i>
                            </button>
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
    <div class="modal-content" style="max-width: 800px;">
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

<script>
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
                        html += `
                            <div class="file-item">
                                <div>
                                    <h5 style="margin: 0 0 5px 0; font-size: 14px;">${tipoNombre}</h5>
                                    <small style="color: var(--gray-500);">Subido: ${formatearFecha(doc.fecha_subida)}</small>
                                </div>
                                <a href="../uploads/documentos/${doc.nombre_archivo}" 
                                   target="_blank" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-download"></i> Ver/Descargar
                                </a>
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

            fetch('../controllers/contrato_controller.php', {
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
