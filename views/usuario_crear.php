<?php
$pageTitle = 'Crear Usuario';
require_once __DIR__ . '/../includes/header.php';
verificarAdmin();
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-user-plus"></i> Crear Nuevo Usuario</h3>
    </div>
    <div class="card-body">
        <form id="formCrearUsuario" method="POST" action="../controllers/usuario_controller.php">
            <input type="hidden" name="action" value="crear">

            <div class="form-row">
                <div class="form-group">
                    <label for="nombre" class="form-label required">Nombre Completo</label>
                    <input type="text" 
                           id="nombre" 
                           name="nombre" 
                           class="form-control" 
                           placeholder="Ej: Juan Pérez"
                           required>
                </div>

                <div class="form-group">
                    <label for="cedula" class="form-label required">Cédula</label>
                    <input type="text" 
                           id="cedula" 
                           name="cedula" 
                           class="form-control" 
                           placeholder="Ej: 1234567890"
                           required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="usuario" class="form-label required">Usuario</label>
                    <input type="text" 
                           id="usuario" 
                           name="usuario" 
                           class="form-control" 
                           placeholder="Ej: jperez"
                           required>
                </div>

                <div class="form-group">
                    <label for="tipo_usuario" class="form-label required">Tipo de Usuario</label>
                    <select id="tipo_usuario" name="tipo_usuario" class="form-control form-select" required>
                        <option value="">Seleccione...</option>
                        <option value="administrador">Administrador</option>
                        <option value="usuario">Usuario</option>
                        <option value="abogado">Abogado</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="contrasena" class="form-label required">Contraseña</label>
                    <input type="password" 
                           id="contrasena" 
                           name="contrasena" 
                           class="form-control" 
                           placeholder="Mínimo 6 caracteres"
                           required
                           minlength="6">
                </div>

                <div class="form-group">
                    <label for="confirmar_contrasena" class="form-label required">Confirmar Contraseña</label>
                    <input type="password" 
                           id="confirmar_contrasena" 
                           name="confirmar_contrasena" 
                           class="form-control" 
                           placeholder="Repita la contraseña"
                           required
                           minlength="6">
                </div>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 30px;">
                <a href="usuario_listar.php" class="btn btn-light">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary" data-original-text="Crear Usuario">
                    <i class="fas fa-save"></i> Crear Usuario
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('formCrearUsuario').addEventListener('submit', function(e) {
    e.preventDefault();

    const contrasena = document.getElementById('contrasena').value;
    const confirmar = document.getElementById('confirmar_contrasena').value;

    // Validar que las contraseñas coincidan
    if (contrasena !== confirmar) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Las contraseñas no coinciden'
        });
        return;
    }

    // Validar longitud mínima
    if (contrasena.length < 6) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'La contraseña debe tener al menos 6 caracteres'
        });
        return;
    }

    // Enviar formulario
    const formData = new FormData(this);
    
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
                text: data.message,
                confirmButtonText: 'Aceptar'
            }).then(() => {
                window.location.href = 'usuario_listar.php';
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al procesar la solicitud'
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
