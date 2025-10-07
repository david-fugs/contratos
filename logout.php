<?php
/**
 * Logout - Cerrar Sesión
 */

session_start();
session_destroy();

// Detectar URL base del sitio automáticamente para redirección
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$baseUrl = $protocol . $host . $scriptPath;
// Asegurar que termine con /
if (substr($baseUrl, -1) !== '/') {
    $baseUrl .= '/';
}

header("Location: " . $baseUrl . "index.php?success=logout");
exit();
?>
