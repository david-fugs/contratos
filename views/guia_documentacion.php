<?php
$pageTitle = 'Guía de Documentación';
require_once __DIR__ . '/../includes/header.php';

// Directorio de documentos
$directorioDocumentos = __DIR__ . '/../DOCUMENTOS/DOCUMENTOS INICIALES';

// Obtener todos los archivos del directorio
$documentos = [];
if (is_dir($directorioDocumentos)) {
    $archivos = scandir($directorioDocumentos);
    foreach ($archivos as $archivo) {
        if ($archivo != '.' && $archivo != '..' && is_file($directorioDocumentos . '/' . $archivo)) {
            $rutaCompleta = $directorioDocumentos . '/' . $archivo;
            $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
            $tamano = filesize($rutaCompleta);
            
            // Formatear tamaño
            $tamanoFormateado = '';
            if ($tamano < 1024) {
                $tamanoFormateado = $tamano . ' B';
            } elseif ($tamano < 1048576) {
                $tamanoFormateado = round($tamano / 1024, 2) . ' KB';
            } else {
                $tamanoFormateado = round($tamano / 1048576, 2) . ' MB';
            }
            
            // Determinar icono según extensión
            $icono = 'fa-file';
            $colorIcono = '#6b7280';
            
            switch ($extension) {
                case 'pdf':
                    $icono = 'fa-file-pdf';
                    $colorIcono = '#ef4444';
                    break;
                case 'doc':
                case 'docx':
                    $icono = 'fa-file-word';
                    $colorIcono = '#2563eb';
                    break;
                case 'xls':
                case 'xlsx':
                    $icono = 'fa-file-excel';
                    $colorIcono = '#10b981';
                    break;
                case 'ppt':
                case 'pptx':
                    $icono = 'fa-file-powerpoint';
                    $colorIcono = '#f59e0b';
                    break;
                case 'jpg':
                case 'jpeg':
                case 'png':
                case 'gif':
                    $icono = 'fa-file-image';
                    $colorIcono = '#8b5cf6';
                    break;
                case 'zip':
                case 'rar':
                case '7z':
                    $icono = 'fa-file-zipper';
                    $colorIcono = '#f59e0b';
                    break;
                case 'txt':
                    $icono = 'fa-file-lines';
                    $colorIcono = '#6b7280';
                    break;
            }
            
            $documentos[] = [
                'nombre' => $archivo,
                'ruta' => $archivo,
                'tamano' => $tamanoFormateado,
                'extension' => strtoupper($extension),
                'icono' => $icono,
                'color_icono' => $colorIcono,
                'fecha_modificacion' => date('d/m/Y H:i', filemtime($rutaCompleta))
            ];
        }
    }
    
    // Ordenar alfabéticamente
    usort($documentos, function($a, $b) {
        return strcmp($a['nombre'], $b['nombre']);
    });
}
?>

<!-- Sección de Instrucciones -->
<div class="card" style="margin-bottom: 25px;">
    <div class="card-header" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white;">
        <h3><i class="fas fa-lightbulb"></i> Instrucciones de Uso del Sistema</h3>
    </div>
    <div class="card-body">
        <div class="instrucciones-grid">
            <!-- Instrucción 1: Crear Contrato -->
            <div class="instruccion-card">
                <div class="instruccion-numero">1</div>
                <div class="instruccion-contenido">
                    <h4><i class="fas fa-file-signature"></i> Cómo Crear un Nuevo Contrato</h4>
                    <ol>
                        <li>Haga clic en el menú lateral en <strong>"Nuevo Contrato"</strong></li>
                        <li>Complete todos los campos del formulario de información básica</li>
                        <li>Adjunte los documentos requeridos en la sección de documentación</li>
                        <li>Revise que toda la información sea correcta</li>
                        <li>Haga clic en <strong>"Guardar Contrato"</strong> para finalizar</li>
                    </ol>
                    <p class="nota-importante">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Importante:</strong> Todos los campos marcados con * son obligatorios. Asegúrese de completarlos antes de guardar.
                    </p>
                </div>
            </div>

            <!-- Instrucción 2: Carga Masiva de Usuarios -->
            <div class="instruccion-card">
                <div class="instruccion-numero">2</div>
                <div class="instruccion-contenido">
                    <h4><i class="fas fa-users"></i> Cómo Crear Usuarios de Forma Masiva</h4>
                    <ol>
                        <li>Vaya al menú <strong>"Listar Usuarios"</strong></li>
                        <li>Descargue la plantilla Excel haciendo clic en <strong>"Descargar Formato Excel"</strong></li>
                        <li>Complete la plantilla con los datos de los usuarios:
                            <ul>
                                <li><strong>Nombre Completo:</strong> Nombre y apellidos del usuario</li>
                                <li><strong>Cédula:</strong> Número de cédula (será el usuario y contraseña inicial)</li>
                                <li><strong>Usuario:</strong> Nombre de usuario para iniciar sesión</li>
                                <li><strong>Tipo Usuario:</strong> Escriba "administrador" o "abogado"</li>
                            </ul>
                        </li>
                        <li>Guarde el archivo Excel</li>
                        <li>Haga clic en <strong>"Subir Archivo Excel"</strong> y seleccione su archivo</li>
                        <li>El sistema creará automáticamente todos los usuarios</li>
                    </ol>
                    <p class="nota-importante">
                        <i class="fas fa-key"></i>
                        <strong>Nota de Seguridad:</strong> El usuario y contraseña inicial serán la cédula del usuario. 
                        Los usuarios <strong>deben cambiar su contraseña</strong> al iniciar sesión por primera vez.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sección de Documentos -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-book"></i> Guía de Documentación</h3>
        <span class="badge badge-primary"><?php echo count($documentos); ?> documentos disponibles</span>
    </div>
    <div class="card-body">
        <?php if (empty($documentos)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                No hay documentos disponibles en este momento.
            </div>
        <?php else: ?>
            <!-- Buscador -->
            <div style="margin-bottom: 20px;">
                <input type="text" 
                       id="buscarDocumento" 
                       class="form-control" 
                       placeholder="🔍 Buscar documento por nombre...">
            </div>

            <!-- Lista de documentos -->
            <div class="documentos-grid" id="listaDocumentos">
                <?php foreach ($documentos as $doc): ?>
                    <div class="documento-card" data-nombre="<?php echo strtolower(htmlspecialchars($doc['nombre'])); ?>">
                        <div class="documento-icon">
                            <i class="fas <?php echo $doc['icono']; ?>" style="color: <?php echo $doc['color_icono']; ?>;"></i>
                        </div>
                        <div class="documento-info">
                            <h4 class="documento-nombre" title="<?php echo htmlspecialchars($doc['nombre']); ?>">
                                <?php echo htmlspecialchars($doc['nombre']); ?>
                            </h4>
                            <div class="documento-meta">
                                <span class="badge badge-secondary"><?php echo $doc['extension']; ?></span>
                                <span class="documento-tamano"><?php echo $doc['tamano']; ?></span>
                            </div>
                            <div class="documento-fecha">
                                <i class="fas fa-clock"></i> <?php echo $doc['fecha_modificacion']; ?>
                            </div>
                        </div>
                        <div class="documento-acciones">
                            <a href="../controllers/documentacion_controller.php?action=descargar&archivo=<?php echo urlencode($doc['ruta']); ?>" 
                               class="btn btn-primary btn-sm"
                               title="Descargar documento">
                                <i class="fas fa-download"></i> Descargar
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Estilos para la vista de documentos */
.documentos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.documento-card {
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: var(--border-radius);
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 15px;
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
}

.documento-card:hover {
    box-shadow: var(--shadow-md);
    border-color: var(--primary-color);
    transform: translateY(-2px);
}

.documento-icon {
    text-align: center;
}

.documento-icon i {
    font-size: 48px;
}

.documento-info {
    flex: 1;
}

.documento-nombre {
    font-size: 14px;
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 10px;
    word-break: break-word;
    line-height: 1.4;
}

.documento-meta {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 8px;
}

.documento-tamano {
    color: var(--gray-500);
    font-size: 13px;
}

.documento-fecha {
    color: var(--gray-500);
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.documento-acciones {
    display: flex;
    gap: 10px;
}

.documento-acciones .btn {
    flex: 1;
    justify-content: center;
}

/* Animación de entrada */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.documento-card {
    animation: fadeInUp 0.3s ease-out;
}

/* Responsive */
@media (max-width: 768px) {
    .documentos-grid {
        grid-template-columns: 1fr;
    }
}

/* Badge personalizado */
.badge {
    display: inline-block;
    padding: 4px 10px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 4px;
    text-transform: uppercase;
}

.badge-primary {
    background-color: var(--primary-color);
    color: white;
}

.badge-secondary {
    background-color: var(--gray-200);
    color: var(--gray-700);
}

/* Estilos para instrucciones */
.instrucciones-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 25px;
    margin-top: 20px;
}

.instruccion-card {
    background: white;
    border: 2px solid var(--primary-color);
    border-radius: var(--border-radius);
    padding: 25px;
    position: relative;
    box-shadow: var(--shadow-md);
    transition: var(--transition);
}

.instruccion-card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-3px);
}

.instruccion-numero {
    position: absolute;
    top: -15px;
    left: 20px;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: bold;
    box-shadow: var(--shadow-md);
}

.instruccion-contenido h4 {
    color: var(--primary-color);
    font-size: 18px;
    margin-bottom: 15px;
    padding-top: 10px;
}

.instruccion-contenido ol {
    margin-left: 20px;
    margin-bottom: 15px;
}

.instruccion-contenido ol li {
    margin-bottom: 10px;
    line-height: 1.6;
}

.instruccion-contenido ul {
    margin-left: 20px;
    margin-top: 8px;
}

.instruccion-contenido ul li {
    margin-bottom: 5px;
}

.nota-importante {
    background: #fff3cd;
    border-left: 4px solid var(--warning-color);
    padding: 12px 15px;
    border-radius: 4px;
    margin-top: 15px;
    font-size: 14px;
}

.nota-importante i {
    color: var(--warning-color);
    margin-right: 8px;
}

@media (max-width: 768px) {
    .instrucciones-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Filtro de búsqueda
document.getElementById('buscarDocumento').addEventListener('input', function() {
    const filtro = this.value.toLowerCase();
    const documentos = document.querySelectorAll('.documento-card');
    
    documentos.forEach(doc => {
        const nombre = doc.getAttribute('data-nombre');
        if (nombre.includes(filtro)) {
            doc.style.display = '';
        } else {
            doc.style.display = 'none';
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
