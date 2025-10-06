<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Gestión de Contratos</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-container {
            background-color: var(--white);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 420px;
            padding: 40px;
            animation: slideDown 0.5s;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-header .logo i {
            font-size: 40px;
            color: var(--white);
        }

        .login-header h1 {
            font-size: 28px;
            color: var(--gray-800);
            margin-bottom: 5px;
        }

        .login-header p {
            color: var(--gray-500);
            font-size: 14px;
        }

        .form-group {
            position: relative;
        }

        .form-group i {
            position: absolute;
            left: 15px;
            top: 42px;
            color: var(--gray-400);
            font-size: 16px;
        }

        .form-control.with-icon {
            padding-left: 45px;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 42px;
            cursor: pointer;
            color: var(--gray-400);
            font-size: 16px;
        }

        .password-toggle:hover {
            color: var(--gray-600);
        }

        .login-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--gray-200);
        }

        .login-footer p {
            color: var(--gray-500);
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-file-contract"></i>
            </div>
            <h1>Bienvenido</h1>
            <p>Sistema de Gestión de Contratos</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span>
                    <?php
                        if ($_GET['error'] == 'invalid') {
                    
                        } elseif ($_GET['error'] == 'empty') {
                            echo 'Por favor complete todos los campos';
                        } elseif ($_GET['error'] == 'session') {
                            echo 'Su sesión ha expirado. Por favor inicie sesión nuevamente';
                        } elseif ($_GET['error'] == 'inactive') {
                            echo 'El usuario está inactivo. Contacte al administrador';
                        } else {
                            echo 'Error al iniciar sesión';
                        }
                    ?>
                </span>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success']) && $_GET['success'] == 'logout'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>Sesión cerrada correctamente</span>
            </div>
        <?php endif; ?>

        <form action="controllers/auth_controller.php" method="POST" id="loginForm">
            <input type="hidden" name="action" value="login">
            
            <div class="form-group">
                <label for="usuario" class="form-label required">Usuario</label>
                <i class="fas fa-user"></i>
                <input type="text" 
                       id="usuario" 
                       name="usuario" 
                       class="form-control with-icon" 
                       placeholder="Ingrese su usuario"
                       required
                       autocomplete="username"
                       autofocus>
            </div>

            <div class="form-group">
                <label for="contrasena" class="form-label required">Contraseña</label>
                <i class="fas fa-lock"></i>
                <input type="password" 
                       id="contrasena" 
                       name="contrasena" 
                       class="form-control with-icon" 
                       placeholder="Ingrese su contraseña"
                       required
                       autocomplete="current-password">
                <i class="fas fa-eye password-toggle" id="togglePassword"></i>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" data-original-text="Iniciar Sesión">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </button>
        </form>

        <div class="login-footer">
            <p>&copy; 2025 Sistema de Gestión de Contratos. Todos los derechos reservados.</p>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        // Toggle mostrar/ocultar contraseña
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordField = document.getElementById('contrasena');
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            
            // Cambiar icono
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Validación antes de enviar
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const usuario = document.getElementById('usuario').value.trim();
            const contrasena = document.getElementById('contrasena').value.trim();

            if (!usuario || !contrasena) {
                e.preventDefault();
                mostrarAlerta('Por favor complete todos los campos', 'danger');
            }
        });
    </script>
</body>
</html>
