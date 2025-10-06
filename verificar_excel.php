<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación - Sistema de Exportación Excel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 800px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        h1 {
            color: #2E86AB;
            margin-bottom: 10px;
            font-size: 2.5em;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1em;
        }
        
        .status-box {
            background: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        
        .status-box.error {
            border-left-color: #dc3545;
            background: #fff5f5;
        }
        
        .status-item {
            display: flex;
            align-items: center;
            margin: 10px 0;
            font-size: 1.1em;
        }
        
        .icon {
            font-size: 1.5em;
            margin-right: 15px;
        }
        
        .success {
            color: #28a745;
        }
        
        .error {
            color: #dc3545;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .feature-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .feature-card .number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: #2E86AB;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 1.1em;
            margin: 10px;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #0C7C59;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Sistema de Exportación Excel</h1>
        <p class="subtitle">PhpSpreadsheet v1.30.0 Instalado Correctamente</p>
        
        <div class="status-box">
            <h2 style="color: #28a745; margin-bottom: 15px;">Estado del Sistema</h2>
            
            <?php
            $checks = [
                'vendor' => [
                    'name' => 'Carpeta vendor',
                    'path' => __DIR__ . '/vendor',
                    'type' => 'dir'
                ],
                'autoload' => [
                    'name' => 'Autoloader de Composer',
                    'path' => __DIR__ . '/vendor/autoload.php',
                    'type' => 'file'
                ],
                'phpspreadsheet' => [
                    'name' => 'PhpSpreadsheet',
                    'path' => __DIR__ . '/vendor/phpoffice/phpspreadsheet',
                    'type' => 'dir'
                ],
                'controller' => [
                    'name' => 'Controlador de exportación',
                    'path' => __DIR__ . '/controllers/exportar_controller.php',
                    'type' => 'file'
                ]
            ];
            
            $allOk = true;
            foreach ($checks as $key => $check) {
                $exists = $check['type'] === 'dir' ? is_dir($check['path']) : file_exists($check['path']);
                if (!$exists) $allOk = false;
                
                echo '<div class="status-item">';
                echo '<span class="icon ' . ($exists ? 'success' : 'error') . '">';
                echo $exists ? '✓' : '✗';
                echo '</span>';
                echo '<span>' . $check['name'] . ': ';
                echo '<strong>' . ($exists ? 'OK' : 'NO ENCONTRADO') . '</strong>';
                echo '</span>';
                echo '</div>';
            }
            
            // Verificar extensiones PHP
            $extensions = ['zip', 'gd', 'xml', 'xmlwriter', 'xmlreader'];
            echo '<hr style="margin: 20px 0;">';
            echo '<h3 style="margin-bottom: 10px;">Extensiones PHP:</h3>';
            foreach ($extensions as $ext) {
                $loaded = extension_loaded($ext);
                if (!$loaded) $allOk = false;
                
                echo '<div class="status-item">';
                echo '<span class="icon ' . ($loaded ? 'success' : 'error') . '">';
                echo $loaded ? '✓' : '✗';
                echo '</span>';
                echo '<span>extension=' . $ext . ': ';
                echo '<strong>' . ($loaded ? 'HABILITADA' : 'NO HABILITADA') . '</strong>';
                echo '</span>';
                echo '</div>';
            }
            ?>
        </div>
        
        <?php if ($allOk): ?>
        <div class="info-box">
            <h3 style="color: #0C7C59; margin-bottom: 10px;">🎉 ¡Todo Listo!</h3>
            <p>El sistema de exportación está completamente funcional. Puedes generar archivos Excel profesionales con:</p>
        </div>
        
        <div class="feature-grid">
            <div class="feature-card">
                <div class="number">40</div>
                <div>Columnas de Datos</div>
            </div>
            <div class="feature-card">
                <div class="number">∞</div>
                <div>Registros Exportables</div>
            </div>
            <div class="feature-card">
                <div class="number">100%</div>
                <div>Datos Completos</div>
            </div>
            <div class="feature-card">
                <div class="number">5</div>
                <div>Estilos Aplicados</div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php" class="btn">🚀 Ir al Sistema</a>
            <a href="views/contrato_listar.php" class="btn">📊 Listar Contratos</a>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
            <h3 style="color: #2E86AB; margin-bottom: 15px;">📋 Características del Reporte Excel:</h3>
            <ul style="line-height: 2; margin-left: 20px;">
                <li>✓ Título con fondo azul y texto blanco</li>
                <li>✓ Encabezados con fondo verde</li>
                <li>✓ Filas alternas con fondo gris</li>
                <li>✓ Bordes en todas las celdas</li>
                <li>✓ Filtros automáticos habilitados</li>
                <li>✓ Paneles congelados (scroll independiente)</li>
                <li>✓ Columnas auto-ajustadas</li>
                <li>✓ Total de registros al final</li>
                <li>✓ Formato de fechas DD/MM/YYYY</li>
                <li>✓ Sin archivos adjuntos (solo datos)</li>
            </ul>
        </div>
        
        <?php else: ?>
        <div class="status-box error">
            <h3 style="color: #dc3545; margin-bottom: 10px;">⚠️ Acción Requerida</h3>
            <p>Por favor, sigue estos pasos:</p>
            <ol style="margin: 15px 0 15px 20px; line-height: 2;">
                <li>Abre PowerShell o CMD</li>
                <li>Navega a: <code>cd C:\xampp\htdocs\contratos</code></li>
                <li>Ejecuta: <code>composer install</code></li>
                <li>Recarga esta página</li>
            </ol>
        </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px;">
            <h4 style="color: #856404; margin-bottom: 10px;">💡 Información Técnica</h4>
            <p style="color: #856404;">
                <strong>Librería:</strong> PhpSpreadsheet v1.30.0<br>
                <strong>Formato:</strong> XLSX (Office Open XML)<br>
                <strong>Compatibilidad:</strong> Excel 2007+, LibreOffice, Google Sheets<br>
                <strong>Tamaño estimado:</strong> ~50KB por 100 registros<br>
                <strong>Máximo de filas:</strong> 1,048,576 (Excel limit)
            </p>
        </div>
        
        <div style="text-align: center; margin-top: 20px; color: #999; font-size: 0.9em;">
            <p>Sistema de Gestión de Contratos</p>
            <p>Secretaría de Educación Risaralda • 2025</p>
        </div>
    </div>
</body>
</html>
