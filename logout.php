<?php
/**
 * Logout - Cerrar Sesión
 */

session_start();
session_destroy();

header("Location: ../index.php?success=logout");
exit();
?>
