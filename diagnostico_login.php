<?php
/**
 * DIAGNÓSTICO DE LOGIN
 * Este archivo te ayudará a identificar el problema
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 { color: #2563eb; }
        .success { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .warning { color: #f59e0b; font-weight: bold; }
        .test { padding: 15px; margin: 10px 0; background: #f9f9f9; border-left: 4px solid #ccc; }
        .test.ok { border-left-color: #10b981; background: #d1fae5; }
        .test.fail { border-left-color: #ef4444; background: #fee2e2; }
        code { background: #e5e7eb; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table th, table td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        table th { background: #f3f4f6; }
        .query-box { background: #1f2937; color: #10b981; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🔍 Diagnóstico del Sistema de Login</h1>
        
        <?php
        // Test 1: Conexión a la base de datos
        echo "<div class='test'>";
        echo "<h3>1️⃣ Conexión a Base de Datos</h3>";
        try {
            require_once 'conexion.php';
            if ($mysqli->connect_error) {
                echo "<p class='error'>❌ Error de conexión: " . $mysqli->connect_error . "</p>";
            } else {
                echo "<p class='success'>✅ Conexión exitosa a la base de datos 'contratos'</p>";
                echo "<p><strong>Host:</strong> " . $mysqli->host_info . "</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
        }
        echo "</div>";

        // Test 2: Verificar tabla usuarios
        echo "<div class='test'>";
        echo "<h3>2️⃣ Tabla de Usuarios</h3>";
        $result = $mysqli->query("SELECT COUNT(*) as total FROM usuarios");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "<p class='success'>✅ Tabla 'usuarios' existe</p>";
            echo "<p><strong>Total de usuarios:</strong> " . $row['total'] . "</p>";
        } else {
            echo "<p class='error'>❌ Error al consultar tabla usuarios: " . $mysqli->error . "</p>";
        }
        echo "</div>";

        // Test 3: Verificar usuario admin
        echo "<div class='test'>";
        echo "<h3>3️⃣ Usuario Administrador</h3>";
        $stmt = $mysqli->prepare("SELECT id, nombre, usuario, contrasena, tipo_usuario, estado FROM usuarios WHERE usuario = 'admin'");
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            echo "<p class='success'>✅ Usuario 'admin' encontrado</p>";
            echo "<table>";
            echo "<tr><th>Campo</th><th>Valor</th></tr>";
            echo "<tr><td>ID</td><td>" . $user['id'] . "</td></tr>";
            echo "<tr><td>Nombre</td><td>" . $user['nombre'] . "</td></tr>";
            echo "<tr><td>Usuario</td><td>" . $user['usuario'] . "</td></tr>";
            echo "<tr><td>Tipo</td><td>" . $user['tipo_usuario'] . "</td></tr>";
            echo "<tr><td>Estado</td><td><strong>" . $user['estado'] . "</strong></td></tr>";
            echo "<tr><td>Hash contraseña</td><td><code style='font-size: 10px;'>" . substr($user['contrasena'], 0, 30) . "...</code></td></tr>";
            echo "</table>";
            
            // Test 4: Verificar password_verify
            echo "<h3>4️⃣ Verificación de Contraseña</h3>";
            $password_test = 'admin123';
            if (password_verify($password_test, $user['contrasena'])) {
                echo "<p class='success'>✅ password_verify('admin123') funciona correctamente</p>";
                echo "<p>La contraseña está bien configurada en la base de datos.</p>";
            } else {
                echo "<p class='error'>❌ password_verify('admin123') FALLÓ</p>";
                echo "<p class='warning'>⚠️ El hash en la base de datos NO coincide con 'admin123'</p>";
                echo "<p><strong>SOLUCIÓN:</strong> Hacer click en el botón de abajo para corregir.</p>";
            }
            
        } else {
            echo "<p class='error'>❌ Usuario 'admin' NO encontrado en la base de datos</p>";
            echo "<p><strong>SOLUCIÓN:</strong> Necesitas crear el usuario admin manualmente.</p>";
        }
        echo "</div>";

        // Test 5: Verificar funciones PHP
        echo "<div class='test'>";
        echo "<h3>5️⃣ Funciones PHP Requeridas</h3>";
        $funciones = ['password_hash', 'password_verify', 'mysqli_connect'];
        $todas_ok = true;
        foreach ($funciones as $func) {
            if (function_exists($func)) {
                echo "<p class='success'>✅ $func() disponible</p>";
            } else {
                echo "<p class='error'>❌ $func() NO disponible</p>";
                $todas_ok = false;
            }
        }
        echo "</div>";

        // Test 6: Generar hash correcto
        echo "<div class='card' style='background: #fef3c7; border: 2px solid #f59e0b;'>";
        echo "<h2>🔧 SOLUCIÓN RÁPIDA</h2>";
        
        if (isset($_GET['corregir']) && $_GET['corregir'] == 'si') {
            $nuevo_hash = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt_update = $mysqli->prepare("UPDATE usuarios SET contrasena = ? WHERE usuario = 'admin'");
            $stmt_update->bind_param("s", $nuevo_hash);
            
            if ($stmt_update->execute()) {
                echo "<p class='success' style='font-size: 18px;'>✅ ¡CONTRASEÑA CORREGIDA!</p>";
                echo "<p>Hash actualizado en la base de datos.</p>";
                echo "<p><strong>Ahora puedes iniciar sesión con:</strong></p>";
                echo "<ul>";
                echo "<li>Usuario: <code>admin</code></li>";
                echo "<li>Contraseña: <code>admin123</code></li>";
                echo "</ul>";
                echo "<a href='index.php' class='btn'>🚀 Ir al Login</a>";
            } else {
                echo "<p class='error'>❌ Error al actualizar: " . $mysqli->error . "</p>";
            }
            $stmt_update->close();
        } else {
            echo "<p>Si password_verify falló, haz click aquí para corregir automáticamente:</p>";
            echo "<a href='diagnostico_login.php?corregir=si' class='btn' style='background: #10b981;'>✅ Corregir Contraseña Admin</a>";
        }
        echo "</div>";

        // Mostrar query de verificación
        echo "<div class='card'>";
        echo "<h2>📝 Query de Verificación Manual</h2>";
        echo "<p>Si quieres hacerlo manualmente en phpMyAdmin, ejecuta esta query:</p>";
        $hash_ejemplo = password_hash('admin123', PASSWORD_DEFAULT);
        echo "<div class='query-box'>";
        echo "<code>UPDATE usuarios SET contrasena = '$hash_ejemplo' WHERE usuario = 'admin';</code>";
        echo "</div>";
        echo "</div>";

        $stmt->close();
        $mysqli->close();
        ?>

        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php" class="btn">🏠 Volver al Login</a>
            <a href="verificar_sistema.php" class="btn" style="background: #6b7280;">🔍 Verificar Sistema Completo</a>
        </div>
    </div>

    <div style="text-align: center; color: #666; margin-top: 30px;">
        <p><small>Este archivo es solo para diagnóstico. Puedes eliminarlo después de resolver el problema.</small></p>
    </div>
</body>
</html>
