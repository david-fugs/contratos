<?php
require_once __DIR__ . '/../config/config.php';
verificarSesion();

$usuario = obtenerUsuarioActual();
$paginaActual = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Sistema de Gestión de Contratos'; ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="main-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-file-contract" style="font-size: 30px; margin-bottom: 10px;"></i>
                <h2>SGC</h2>
                <p>Sistema de Contratos</p>
            </div>

            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php" class="<?php echo $paginaActual === 'dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <?php if (esAdministrador()): ?>
                <li>
                    <a href="usuario_crear.php" class="<?php echo $paginaActual === 'usuario_crear' ? 'active' : ''; ?>">
                        <i class="fas fa-user-plus"></i>
                        <span>Crear Usuario</span>
                    </a>
                </li>
                <li>
                    <a href="usuario_listar.php" class="<?php echo $paginaActual === 'usuario_listar' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Listar Usuarios</span>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (esAdministrador() || $_SESSION['tipo_usuario'] === 'usuario'): ?>
                <li>
                    <a href="contrato_crear.php" class="<?php echo $paginaActual === 'contrato_crear' ? 'active' : ''; ?>">
                        <i class="fas fa-file-signature"></i>
                        <span>Nuevo Contrato</span>
                    </a>
                </li>
                <?php endif; ?>

                <li>
                    <a href="contrato_listar.php" class="<?php echo $paginaActual === 'contrato_listar' ? 'active' : ''; ?>">
                        <i class="fas fa-list-alt"></i>
                        <span>Listar Contratos</span>
                    </a>
                </li>

                <?php if (esAbogado() || $_SESSION['tipo_usuario'] === 'usuario'): ?>
                <li>
                    <a href="contrato_seguimiento.php" class="<?php echo $paginaActual === 'contrato_seguimiento' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line"></i>
                        <span>Seguimiento Contratos</span>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (esAdministradorTecnico()): ?>
                <li>
                    <a href="cdp_gestionar.php" class="<?php echo $paginaActual === 'cdp_gestionar' ? 'active' : ''; ?>">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span>Gestión CDP</span>
                    </a>
                </li>
                <?php endif; ?>



                <li>
                    <a href="guia_documentacion.php" class="<?php echo $paginaActual === 'guia_documentacion' ? 'active' : ''; ?>">
                        <i class="fas fa-book"></i>
                        <span>Guía de Documentación</span>
                    </a>
                </li>

                <li style="margin-top: 30px;">
                    <a href="../logout.php" style="color: #fca5a5;">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Navbar -->
            <nav class="top-navbar">
                <div class="top-navbar-left">
                    <button class="btn btn-light" onclick="toggleSidebar()" style="display: none; margin-right: 15px;">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
                </div>
                <div class="top-navbar-right">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($usuario['nombre'], 0, 2)); ?>
                        </div>
                        <div class="user-details">
                            <h4><?php echo htmlspecialchars($usuario['nombre']); ?></h4>
                            <p><?php echo ucfirst($usuario['tipo_usuario']); ?></p>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Content Area -->
            <div class="content-area">
