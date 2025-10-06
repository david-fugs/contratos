<?php
/**
 * Script para generar contraseña correcta del administrador
 * Ejecutar este archivo una sola vez después de importar la base de datos
 */

// Incluir conexión
require_once 'conexion.php';

// Contraseña deseada
$contrasena = 'admin123';

// Generar hash
$hash = password_hash($contrasena, PASSWORD_DEFAULT);

echo "<h2>Generador de Contraseña Admin</h2>";
echo "<p><strong>Contraseña:</strong> $contrasena</p>";
echo "<p><strong>Hash generado:</strong> $hash</p>";

// Actualizar en la base de datos
$sql = "UPDATE usuarios SET contrasena = ? WHERE usuario = 'admin'";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $hash);

if ($stmt->execute()) {
    echo "<p style='color: green; font-weight: bold;'>✅ Contraseña actualizada correctamente en la base de datos</p>";
    echo "<p>Ahora puedes iniciar sesión con:</p>";
    echo "<ul>";
    echo "<li><strong>Usuario:</strong> admin</li>";
    echo "<li><strong>Contraseña:</strong> admin123</li>";
    echo "</ul>";
    echo "<p><a href='index.php' style='padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 5px;'>Ir al Login</a></p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ Error al actualizar: " . $mysqli->error . "</p>";
}

$stmt->close();
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar Contraseña Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h2 {
            color: #2563eb;
        }
        p {
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <hr>
    <p style="color: #666; font-size: 14px; margin-top: 30px;">
        <strong>Nota:</strong> Este archivo solo debe ejecutarse una vez. Puedes eliminarlo después de usarlo por seguridad.
    </p>
</body>
</html>
