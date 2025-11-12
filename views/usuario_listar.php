<?php
$pageTitle = 'Listar Usuarios';
require_once __DIR__ . '/../includes/header.php';
verificarAdmin();

// Obtener usuario actual
$usuarioActual = obtenerUsuarioActual();

// Obtener usuarios
$query = "SELECT * FROM usuarios ORDER BY id DESC";
$result = $mysqli->query($query);
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-users"></i> Gestión de Usuarios</h3>
        <div style="display: flex; gap: 10px;">
            <a href="../controllers/documentacion_controller.php?action=descargar_plantilla&tipo=usuarios" 
               class="btn btn-success">
                <i class="fas fa-file-excel"></i> Descargar Formato Excel
            </a>
            <button onclick="mostrarModalSubirExcel()" class="btn btn-info">
                <i class="fas fa-file-upload"></i> Subir Archivo Excel
            </button>
            <a href="usuario_crear.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Usuario
            </a>
        </div>
    </div>
    <div class="card-body">
        <!-- Filtros -->
        <div style="margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 250px;">
                <input type="text" 
                       id="filtroTabla" 
                       class="form-control" 
                       placeholder="🔍 Buscar por nombre, usuario o tipo...">
            </div>
            <div>
                <select id="filtroTipo" class="form-control form-select" style="min-width: 180px;">
                    <option value="">Todos los tipos</option>
                    <option value="administrador">Administrador</option>
                    <option value="usuario">Usuario</option>
                </select>
            </div>
            <div>
                <select id="filtroEstado" class="form-control form-select" style="min-width: 150px;">
                    <option value="">Todos los estados</option>
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo</option>
                </select>
            </div>
        </div>

        <!-- Tabla -->
        <div class="table-responsive">
            <table class="table" id="tablaUsuarios">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Cédula</th>
                        <th>Usuario</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Fecha Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($usuario = $result->fetch_assoc()): ?>
                    <tr data-tipo="<?php echo $usuario['tipo_usuario']; ?>" data-estado="<?php echo $usuario['estado']; ?>">
                        <td><?php echo $usuario['id']; ?></td>
                        <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['cedula'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($usuario['usuario']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $usuario['tipo_usuario'] === 'administrador' ? 'primary' : 'info'; ?>">
                                <?php echo ucfirst($usuario['tipo_usuario']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $usuario['estado'] === 'activo' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($usuario['estado']); ?>
                            </span>
                        </td>
                        <td><?php echo formatearFechaHora($usuario['fecha_creacion']); ?></td>
                        <td>
                            <button onclick="editarUsuario(<?php echo $usuario['id']; ?>)" 
                                    class="btn btn-sm btn-info"
                                    title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($usuario['id'] != $usuarioActual['id']): ?>
                            <button onclick="eliminarUsuario(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nombre']); ?>')" 
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

<!-- Modal Editar Usuario -->
<div id="modalEditarUsuario" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Editar Usuario</h3>
            <button class="modal-close" onclick="ocultarModal('modalEditarUsuario')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formEditarUsuario">
                <input type="hidden" id="edit_id" name="id">
                <input type="hidden" name="action" value="editar">

                <div class="form-group">
                    <label for="edit_nombre" class="form-label required">Nombre Completo</label>
                    <input type="text" id="edit_nombre" name="nombre" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="edit_cedula" class="form-label required">Cédula</label>
                    <input type="text" id="edit_cedula" name="cedula" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="edit_usuario" class="form-label required">Usuario</label>
                    <input type="text" id="edit_usuario" name="usuario" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="edit_tipo_usuario" class="form-label required">Tipo de Usuario</label>
                    <select id="edit_tipo_usuario" name="tipo_usuario" class="form-control form-select" required>
                        <option value="administrador">Administrador</option>
                        <option value="usuario">Usuario</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_estado" class="form-label required">Estado</label>
                    <select id="edit_estado" name="estado" class="form-control form-select" required>
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_nueva_contrasena" class="form-label">Nueva Contraseña</label>
                    <input type="password" 
                           id="edit_nueva_contrasena" 
                           name="nueva_contrasena" 
                           class="form-control" 
                           placeholder="Dejar en blanco para no cambiar"
                           minlength="6">
                    <small style="color: var(--gray-500); font-size: 12px;">Solo complete este campo si desea cambiar la contraseña</small>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-light" onclick="ocultarModal('modalEditarUsuario')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarEdicion()">Guardar Cambios</button>
        </div>
    </div>
</div>

<script>
// Filtro de búsqueda general
document.getElementById('filtroTabla').addEventListener('keyup', function() {
    filtrarTabla();
});

// Filtro por tipo
document.getElementById('filtroTipo').addEventListener('change', function() {
    filtrarTabla();
});

// Filtro por estado
document.getElementById('filtroEstado').addEventListener('change', function() {
    filtrarTabla();
});

function filtrarTabla() {
    const filtro = document.getElementById('filtroTabla').value.toLowerCase();
    const filtroTipo = document.getElementById('filtroTipo').value;
    const filtroEstado = document.getElementById('filtroEstado').value;
    const filas = document.querySelectorAll('#tablaUsuarios tbody tr');

    filas.forEach(fila => {
        const texto = fila.textContent.toLowerCase();
        const tipo = fila.getAttribute('data-tipo');
        const estado = fila.getAttribute('data-estado');

        let mostrar = true;

        // Filtro de texto
        if (filtro && !texto.includes(filtro)) {
            mostrar = false;
        }

        // Filtro de tipo
        if (filtroTipo && tipo !== filtroTipo) {
            mostrar = false;
        }

        // Filtro de estado
        if (filtroEstado && estado !== filtroEstado) {
            mostrar = false;
        }

        fila.style.display = mostrar ? '' : 'none';
    });
}

function editarUsuario(id) {
    fetch(`../controllers/usuario_controller.php?action=obtener&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_id').value = data.data.id;
                document.getElementById('edit_nombre').value = data.data.nombre;
                document.getElementById('edit_cedula').value = data.data.cedula || '';
                document.getElementById('edit_usuario').value = data.data.usuario;
                document.getElementById('edit_tipo_usuario').value = data.data.tipo_usuario;
                document.getElementById('edit_estado').value = data.data.estado;
                document.getElementById('edit_nueva_contrasena').value = '';
                
                mostrarModal('modalEditarUsuario');
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
}

function guardarEdicion() {
    const formData = new FormData(document.getElementById('formEditarUsuario'));

    fetch('../controllers/usuario_controller.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: data.message
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    });
}

function eliminarUsuario(id, nombre) {
    Swal.fire({
        title: '¿Está seguro?',
        text: `¿Desea eliminar al usuario "${nombre}"? Esta acción no se puede deshacer.`,
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

            fetch('../controllers/usuario_controller.php', {
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

function mostrarModalSubirExcel() {
    Swal.fire({
        title: 'Subir Archivo Excel de Usuarios',
        html: `
            <div style="text-align: left; margin-bottom: 15px;">
                <p style="margin-bottom: 10px;">Seleccione el archivo Excel con los datos de los usuarios.</p>
                <p style="font-size: 13px; color: #6b7280;">
                    <strong>Columnas requeridas:</strong><br>
                    - NOMBRE COMPLETO<br>
                    - CEDULA<br>
                    - USUARIO<br>
                    - TIPO USUARIO (administrador o abogado)
                </p>
            </div>
            <input type="file" id="archivoExcel" accept=".xlsx,.xls" class="swal2-input" style="width: 90%;">
        `,
        showCancelButton: true,
        confirmButtonText: 'Subir',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            const archivo = document.getElementById('archivoExcel').files[0];
            if (!archivo) {
                Swal.showValidationMessage('Por favor seleccione un archivo');
                return false;
            }
            
            const formData = new FormData();
            formData.append('archivo', archivo);
            formData.append('action', 'importar_excel');
            
            return fetch('../controllers/usuario_controller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message);
                }
                return data;
            })
            .catch(error => {
                Swal.showValidationMessage(`Error: ${error.message}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                html: result.value.message,
                confirmButtonText: 'Aceptar'
            }).then(() => {
                location.reload();
            });
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
