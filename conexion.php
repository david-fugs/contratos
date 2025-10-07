<?php
/**
 * Archivo de Conexión a Base de Datos
 * Sistema de Gestión de Contratos
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'softepuc_contratagober');
define('DB_PASS', 'ULykn#+3vH5Z');
define('DB_NAME', 'softepuc_contratagober');

// Crear conexión
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Verificar conexión
if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}

// Establecer charset UTF-8
$mysqli->set_charset('utf8');

// Zona horaria para Colombia
date_default_timezone_set('America/Bogota');
?>
