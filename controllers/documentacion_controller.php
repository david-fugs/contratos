<?php
/**
 * Controlador para la Guía de Documentación
 * Maneja la descarga de documentos
 */

require_once __DIR__ . '/../config/config.php';

// Verificar que el usuario esté autenticado
verificarSesion();

// Directorio de documentos
$directorioDocumentos = __DIR__ . '/../DOCUMENTOS/DOCUMENTOS INICIALES';

// Procesar la acción
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    switch ($action) {
        case 'descargar_plantilla':
            if (!isset($_GET['tipo'])) {
                header('Location: ../views/guia_documentacion.php');
                exit();
            }
            
            $tipo = $_GET['tipo'];
            
            if ($tipo === 'usuarios') {
                $directorioPlantillas = __DIR__ . '/../DOCUMENTOS/PLANTILLAS';
                $nombreArchivo = 'usuarios.xlsx';
                $rutaArchivo = $directorioPlantillas . '/' . $nombreArchivo;
                
                if (!file_exists($rutaArchivo)) {
                    $_SESSION['mensaje'] = 'La plantilla no existe.';
                    $_SESSION['tipo_mensaje'] = 'error';
                    header('Location: ../views/usuario_listar.php');
                    exit();
                }
                
                // Limpiar el buffer de salida
                if (ob_get_level()) {
                    ob_end_clean();
                }
                
                // Enviar headers para la descarga
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
                header('Content-Length: ' . filesize($rutaArchivo));
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Expires: 0');
                
                // Leer y enviar el archivo
                readfile($rutaArchivo);
                exit();
            }
            
            header('Location: ../views/guia_documentacion.php');
            exit();
            break;
            
        case 'descargar':
            if (!isset($_GET['archivo'])) {
                header('Location: ../views/guia_documentacion.php');
                exit();
            }
            
            $nombreArchivo = basename($_GET['archivo']);
            $rutaArchivo = $directorioDocumentos . '/' . $nombreArchivo;
            
            // Verificar que el archivo existe y está dentro del directorio permitido
            $directorioReal = realpath($directorioDocumentos);
            $archivoReal = realpath($rutaArchivo);
            
            if ($archivoReal === false || strpos($archivoReal, $directorioReal) !== 0) {
                $_SESSION['mensaje'] = 'Archivo no válido.';
                $_SESSION['tipo_mensaje'] = 'error';
                header('Location: ../views/guia_documentacion.php');
                exit();
            }
            
            if (!file_exists($rutaArchivo)) {
                $_SESSION['mensaje'] = 'El archivo no existe.';
                $_SESSION['tipo_mensaje'] = 'error';
                header('Location: ../views/guia_documentacion.php');
                exit();
            }
            
            // Obtener información del archivo
            $extension = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
            
            // Definir el tipo MIME
            $mimeTypes = [
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xls' => 'application/vnd.ms-excel',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'ppt' => 'application/vnd.ms-powerpoint',
                'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'zip' => 'application/zip',
                'rar' => 'application/x-rar-compressed',
                '7z' => 'application/x-7z-compressed',
                'txt' => 'text/plain'
            ];
            
            $mimeType = isset($mimeTypes[$extension]) ? $mimeTypes[$extension] : 'application/octet-stream';
            
            // Limpiar el buffer de salida
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Enviar headers para la descarga
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
            header('Content-Length: ' . filesize($rutaArchivo));
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Expires: 0');
            
            // Leer y enviar el archivo
            readfile($rutaArchivo);
            exit();
            break;
            
        default:
            header('Location: ../views/guia_documentacion.php');
            exit();
    }
} else {
    header('Location: ../views/guia_documentacion.php');
    exit();
}
?>
