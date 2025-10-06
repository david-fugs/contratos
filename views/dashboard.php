<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';

// Obtener estadísticas
$total_contratos = $mysqli->query("SELECT COUNT(*) as total FROM contratos WHERE estado = 'activo'")->fetch_assoc()['total'];
$total_usuarios = $mysqli->query("SELECT COUNT(*) as total FROM usuarios WHERE estado = 'activo'")->fetch_assoc()['total'];
$total_documentos = $mysqli->query("SELECT COUNT(*) as total FROM documentos")->fetch_assoc()['total'];
$contratos_mes = $mysqli->query("SELECT COUNT(*) as total FROM contratos WHERE MONTH(fecha_creacion) = MONTH(CURRENT_DATE()) AND YEAR(fecha_creacion) = YEAR(CURRENT_DATE())")->fetch_assoc()['total'];

// Obtener últimos contratos
$ultimos_contratos = $mysqli->query("SELECT * FROM contratos ORDER BY fecha_creacion DESC LIMIT 5");

// Contratos por municipio
$contratos_municipio = $mysqli->query("SELECT municipio_residencia, COUNT(*) as total FROM contratos GROUP BY municipio_residencia ORDER BY total DESC LIMIT 5");
?>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: var(--white);
    padding: 25px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-lg);
    transition: var(--transition);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-xl);
}

.stat-card.secondary {
    background: linear-gradient(135deg, var(--secondary-color), var(--secondary-dark));
}

.stat-card.warning {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.stat-card.info {
    background: linear-gradient(135deg, #06b6d4, #0891b2);
}

.stat-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.stat-card-icon {
    font-size: 40px;
    opacity: 0.8;
}

.stat-card-body h3 {
    font-size: 36px;
    font-weight: bold;
    margin: 0 0 5px 0;
}

.stat-card-body p {
    font-size: 14px;
    opacity: 0.9;
    margin: 0;
}

.chart-container {
    background-color: var(--white);
    padding: 20px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-md);
}

.welcome-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: var(--white);
    padding: 40px;
    border-radius: var(--border-radius);
    margin-bottom: 30px;
    text-align: center;
}

.welcome-section h2 {
    font-size: 32px;
    margin-bottom: 10px;
}

.welcome-section p {
    font-size: 18px;
    opacity: 0.9;
}
</style>

<!-- Sección de Bienvenida -->
<div class="welcome-section">
    <h2>¡Bienvenido, <?php echo htmlspecialchars($usuario['nombre']); ?>! 👋</h2>
    <p>Sistema de Gestión de Contratos - Departamento de Risaralda</p>
</div>

<!-- Estadísticas -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-header">
            <div class="stat-card-body">
                <h3><?php echo $total_contratos; ?></h3>
                <p>Contratos Activos</p>
            </div>
            <div class="stat-card-icon">
                <i class="fas fa-file-contract"></i>
            </div>
        </div>
    </div>

    <div class="stat-card secondary">
        <div class="stat-card-header">
            <div class="stat-card-body">
                <h3><?php echo $contratos_mes; ?></h3>
                <p>Contratos Este Mes</p>
            </div>
            <div class="stat-card-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>
    </div>

    <?php if (esAdministrador()): ?>
    <div class="stat-card warning">
        <div class="stat-card-header">
            <div class="stat-card-body">
                <h3><?php echo $total_usuarios; ?></h3>
                <p>Usuarios Activos</p>
            </div>
            <div class="stat-card-icon">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="stat-card info">
        <div class="stat-card-header">
            <div class="stat-card-body">
                <h3><?php echo $total_documentos; ?></h3>
                <p>Documentos Subidos</p>
            </div>
            <div class="stat-card-icon">
                <i class="fas fa-folder-open"></i>
            </div>
        </div>
    </div>
</div>

<!-- Contenido Principal -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 25px;">
    
    <!-- Últimos Contratos -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-clock"></i> Últimos Contratos</h3>
            <a href="contrato_listar.php" class="btn btn-sm btn-primary">Ver Todos</a>
        </div>
        <div class="card-body">
            <?php if ($ultimos_contratos->num_rows > 0): ?>
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <?php while($contrato = $ultimos_contratos->fetch_assoc()): ?>
                        <div style="padding: 15px; background-color: var(--gray-100); border-radius: 8px; border-left: 4px solid var(--primary-color);">
                            <h4 style="margin: 0 0 5px 0; font-size: 16px; color: var(--gray-800);">
                                <?php echo htmlspecialchars($contrato['nombre_completo']); ?>
                            </h4>
                            <p style="margin: 0; font-size: 13px; color: var(--gray-600);">
                                <i class="fas fa-id-card"></i> <?php echo $contrato['numero_documento']; ?> |
                                <i class="fas fa-calendar"></i> <?php echo formatearFecha($contrato['fecha_diligenciamiento']); ?>
                            </p>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: var(--gray-500); padding: 20px;">
                    No hay contratos registrados aún
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Contratos por Municipio -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-map-marker-alt"></i> Top Municipios</h3>
        </div>
        <div class="card-body">
            <?php if ($contratos_municipio->num_rows > 0): ?>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php 
                    $colores = ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];
                    $index = 0;
                    while($municipio = $contratos_municipio->fetch_assoc()): 
                        $porcentaje = ($municipio['total'] / $total_contratos) * 100;
                    ?>
                        <div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <span style="font-size: 14px; font-weight: 500; text-transform: capitalize;">
                                    <?php echo ucwords(str_replace('_', ' ', $municipio['municipio_residencia'])); ?>
                                </span>
                                <span style="font-size: 14px; font-weight: 600; color: var(--primary-color);">
                                    <?php echo $municipio['total']; ?>
                                </span>
                            </div>
                            <div style="width: 100%; height: 8px; background-color: var(--gray-200); border-radius: 4px; overflow: hidden;">
                                <div style="width: <?php echo $porcentaje; ?>%; height: 100%; background-color: <?php echo $colores[$index % 5]; ?>; transition: width 0.3s;"></div>
                            </div>
                        </div>
                    <?php 
                        $index++;
                    endwhile; 
                    ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: var(--gray-500); padding: 20px;">
                    No hay datos disponibles
                </p>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Acciones Rápidas -->
<div class="card" style="margin-top: 25px;">
    <div class="card-header">
        <h3><i class="fas fa-bolt"></i> Acciones Rápidas</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <a href="contrato_crear.php" class="btn btn-primary btn-lg" style="padding: 20px;">
                <i class="fas fa-plus-circle" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                Nuevo Contrato
            </a>
            
            <a href="contrato_listar.php" class="btn btn-secondary btn-lg" style="padding: 20px;">
                <i class="fas fa-list" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                Ver Contratos
            </a>
            
            <?php if (esAdministrador()): ?>
            <a href="usuario_crear.php" class="btn btn-info btn-lg" style="padding: 20px; background-color: var(--info-color);">
                <i class="fas fa-user-plus" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                Nuevo Usuario
            </a>
            
            <a href="usuario_listar.php" class="btn btn-warning btn-lg" style="padding: 20px;">
                <i class="fas fa-users-cog" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                Gestionar Usuarios
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
