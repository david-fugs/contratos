<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación del Sistema</title>
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
        h1 {
            color: #2563eb;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 10px;
        }
        .check-item {
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #ccc;
            background: #f9f9f9;
        }
        .check-item.success {
            border-left-color: #10b981;
            background: #d1fae5;
        }
        .check-item.error {
            border-left-color: #ef4444;
            background: #fee2e2;
        }
        .check-item.warning {
            border-left-color: #f59e0b;
            background: #fef3c7;
        }
        .icon {
            font-weight: bold;
            font-size: 18px;
            margin-right: 10px;
        }
        .info {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #1e40af;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>🔍 Verificación del Sistema de Gestión de Contratos</h1>
        
        <?php
        // Verificar versión de PHP
        $php_version = phpversion();
        $php_ok = version_compare($php_version, '7.4.0', '>=');
        ?>
        
        <div class="check-item <?php echo $php_ok ? 'success' : 'error'; ?>">
            <span class="icon"><?php echo $php_ok ? '✅' : '❌'; ?></span>
            <strong>Versión de PHP:</strong> <?php echo $php_version; ?>
            <div class="info">
                <?php if ($php_ok): ?>
                    Versión compatible (se requiere PHP 7.4 o superior)
                <?php else: ?>
                    ⚠️ Se requiere PHP 7.4 o superior. Actualiza tu versión de PHP.
                <?php endif; ?>
            </div>
        </div>

        <?php
        // Verificar extensiones PHP requeridas
        $extensiones = [
            'mysqli' => 'Conexión a MySQL',
            'json' => 'Procesamiento JSON',
            'fileinfo' => 'Información de archivos',
            'mbstring' => 'Manejo de strings multibyte'
        ];
        
        foreach ($extensiones as $ext => $desc):
            $loaded = extension_loaded($ext);
        ?>
        <div class="check-item <?php echo $loaded ? 'success' : 'error'; ?>">
            <span class="icon"><?php echo $loaded ? '✅' : '❌'; ?></span>
            <strong>Extensión <?php echo $ext; ?>:</strong> <?php echo $loaded ? 'Instalada' : 'No instalada'; ?>
            <div class="info"><?php echo $desc; ?></div>
        </div>
        <?php endforeach; ?>

        <?php
        // Verificar conexión a la base de datos
        $db_ok = false;
        $db_message = '';
        try {
            $mysqli = new mysqli("localhost", "root", "", "contratos");
            if ($mysqli->connect_error) {
                $db_message = "Error: " . $mysqli->connect_error;
            } else {
                $db_ok = true;
                $db_message = "Conexión exitosa a la base de datos 'contratos'";
                $mysqli->close();
            }
        } catch (Exception $e) {
            $db_message = "Error: " . $e->getMessage();
        }
        ?>
        
        <div class="check-item <?php echo $db_ok ? 'success' : 'error'; ?>">
            <span class="icon"><?php echo $db_ok ? '✅' : '❌'; ?></span>
            <strong>Conexión a Base de Datos:</strong> <?php echo $db_ok ? 'Conectado' : 'Error de conexión'; ?>
            <div class="info"><?php echo $db_message; ?></div>
        </div>

        <?php
        // Verificar carpetas y permisos
        $carpetas = [
            'uploads/documentos' => 'Carpeta para documentos',
            'assets/css' => 'Carpeta de estilos',
            'assets/js' => 'Carpeta de JavaScript',
            'config' => 'Carpeta de configuración',
            'controllers' => 'Carpeta de controladores',
            'views' => 'Carpeta de vistas'
        ];
        
        foreach ($carpetas as $carpeta => $desc):
            $exists = is_dir($carpeta);
            $writable = $exists && is_writable($carpeta);
        ?>
        <div class="check-item <?php echo ($exists && $writable) ? 'success' : ($exists ? 'warning' : 'error'); ?>">
            <span class="icon">
                <?php 
                    if ($exists && $writable) echo '✅';
                    elseif ($exists) echo '⚠️';
                    else echo '❌';
                ?>
            </span>
            <strong>Carpeta <?php echo $carpeta; ?>:</strong> 
            <?php 
                if (!$exists) echo 'No existe';
                elseif (!$writable) echo 'Sin permisos de escritura';
                else echo 'OK';
            ?>
            <div class="info"><?php echo $desc; ?></div>
        </div>
        <?php endforeach; ?>

        <?php
        // Verificar archivos críticos
        $archivos = [
            'index.php' => 'Página de login',
            'conexion.php' => 'Configuración de BD',
            'config/config.php' => 'Configuración general',
            'database.sql' => 'Script de base de datos',
            'assets/css/styles.css' => 'Estilos principales',
            'assets/js/app.js' => 'JavaScript principal'
        ];
        
        foreach ($archivos as $archivo => $desc):
            $exists = file_exists($archivo);
        ?>
        <div class="check-item <?php echo $exists ? 'success' : 'error'; ?>">
            <span class="icon"><?php echo $exists ? '✅' : '❌'; ?></span>
            <strong>Archivo <?php echo $archivo; ?>:</strong> <?php echo $exists ? 'Encontrado' : 'No encontrado'; ?>
            <div class="info"><?php echo $desc; ?></div>
        </div>
        <?php endforeach; ?>

        <?php
        // Verificar configuración de upload
        $upload_max = ini_get('upload_max_filesize');
        $post_max = ini_get('post_max_size');
        $upload_ok = (intval($upload_max) >= 5 && intval($post_max) >= 5);
        ?>
        
        <div class="check-item <?php echo $upload_ok ? 'success' : 'warning'; ?>">
            <span class="icon"><?php echo $upload_ok ? '✅' : '⚠️'; ?></span>
            <strong>Configuración de Uploads:</strong>
            <div class="info">
                upload_max_filesize: <?php echo $upload_max; ?><br>
                post_max_size: <?php echo $post_max; ?><br>
                <?php if (!$upload_ok): ?>
                    ⚠️ Se recomienda al menos 5MB para ambos valores
                <?php endif; ?>
            </div>
        </div>

        <?php
        // Resumen
        $total_checks = 0;
        $passed_checks = 0;
        
        if ($php_ok) $passed_checks++;
        $total_checks++;
        
        foreach ($extensiones as $ext => $desc) {
            $total_checks++;
            if (extension_loaded($ext)) $passed_checks++;
        }
        
        $total_checks++;
        if ($db_ok) $passed_checks++;
        
        foreach ($carpetas as $carpeta => $desc) {
            $total_checks++;
            if (is_dir($carpeta) && is_writable($carpeta)) $passed_checks++;
        }
        
        foreach ($archivos as $archivo => $desc) {
            $total_checks++;
            if (file_exists($archivo)) $passed_checks++;
        }
        
        $percentage = round(($passed_checks / $total_checks) * 100);
        ?>

        <div style="margin-top: 30px; padding: 20px; background: <?php echo $percentage >= 90 ? '#d1fae5' : ($percentage >= 70 ? '#fef3c7' : '#fee2e2'); ?>; border-radius: 8px;">
            <h2 style="margin: 0 0 10px 0;">📊 Resumen de Verificación</h2>
            <p style="font-size: 24px; margin: 10px 0;">
                <strong><?php echo $passed_checks; ?></strong> de <strong><?php echo $total_checks; ?></strong> verificaciones pasaron
            </p>
            <div style="background: #e5e7eb; height: 30px; border-radius: 15px; overflow: hidden;">
                <div style="background: <?php echo $percentage >= 90 ? '#10b981' : ($percentage >= 70 ? '#f59e0b' : '#ef4444'); ?>; height: 100%; width: <?php echo $percentage; ?>%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                    <?php echo $percentage; ?>%
                </div>
            </div>
            <div style="margin-top: 15px;">
                <?php if ($percentage >= 90): ?>
                    <strong style="color: #059669;">✅ Sistema listo para usar</strong>
                    <p>Todos los componentes críticos están funcionando correctamente.</p>
                    <a href="index.php" class="btn">🚀 Ir al Sistema</a>
                <?php elseif ($percentage >= 70): ?>
                    <strong style="color: #d97706;">⚠️ Sistema funcional con advertencias</strong>
                    <p>El sistema puede funcionar pero hay algunas advertencias que deberías revisar.</p>
                    <a href="index.php" class="btn">Ir al Sistema de todos modos</a>
                <?php else: ?>
                    <strong style="color: #dc2626;">❌ Sistema no está listo</strong>
                    <p>Por favor corrige los errores antes de continuar.</p>
                <?php endif; ?>
            </div>
        </div>

        <div style="margin-top: 30px; padding: 20px; background: #f3f4f6; border-radius: 8px;">
            <h3>📚 Próximos Pasos:</h3>
            <ol>
                <li>Si no lo has hecho, importa el archivo <strong>database.sql</strong> en phpMyAdmin</li>
                <li>Verifica que la carpeta <strong>uploads/documentos</strong> tenga permisos de escritura</li>
                <li>Accede al sistema: <a href="index.php">http://localhost/contratos/</a></li>
                <li>Usuario por defecto: <strong>admin</strong> / Contraseña: <strong>admin123</strong></li>
                <li>Cambia la contraseña después del primer acceso</li>
            </ol>
        </div>
    </div>

    <div style="text-align: center; color: #666; margin-top: 30px;">
        <p>Sistema de Gestión de Contratos - Versión 1.0.0</p>
        <p>Departamento de Risaralda - Secretaría de Educación</p>
    </div>
</body>
</html>
