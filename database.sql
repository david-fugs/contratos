-- =====================================================
-- SISTEMA DE GESTIÓN DE CONTRATOS
-- Base de Datos Completa
-- =====================================================

CREATE DATABASE IF NOT EXISTS contratos CHARACTER SET utf8 COLLATE utf8_general_ci;
USE contratos;

-- =====================================================
-- TABLA DE USUARIOS
-- =====================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    tipo_usuario ENUM('administrador', 'usuario') NOT NULL DEFAULT 'usuario',
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario),
    INDEX idx_tipo (tipo_usuario),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- =====================================================
-- TABLA DE CONTRATOS
-- =====================================================
CREATE TABLE IF NOT EXISTS contratos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha_diligenciamiento DATE NOT NULL,
    correo_electronico VARCHAR(100) NOT NULL,
    tipo_documento ENUM('cedula_ciudadania', 'tarjeta_identidad', 'cedula_extranjeria') NOT NULL,
    numero_documento VARCHAR(20) NOT NULL UNIQUE,
    lugar_expedicion VARCHAR(100) NOT NULL,
    nombre_completo VARCHAR(150) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    identidad_genero ENUM('hombre', 'mujer', 'prefiero_no_decirlo') NOT NULL,
    grupo_poblacional ENUM('afrocolombiano', 'desplazado', 'discapacitado', 'indigena', 'mestizo', 'victima_conflicto', 'no_aplica') NOT NULL,
    posee_discapacidad ENUM('si', 'no') NOT NULL,
    especifique_discapacidad TEXT NULL,
    celular_contacto VARCHAR(20) NOT NULL,
    estado_civil ENUM('casado', 'separado_divorciado', 'soltero', 'union_libre', 'viudo') NOT NULL,
    numero_hijos_dependientes INT DEFAULT 0,
    tiene_hijos_menores ENUM('si', 'no') NOT NULL,
    cuantos_hijos_menores INT NULL,
    padre_madre_soltero ENUM('si', 'no') NOT NULL,
    direccion_residencia VARCHAR(200) NOT NULL,
    barrio VARCHAR(100) NOT NULL,
    municipio_residencia VARCHAR(50) NOT NULL,
    nivel_estudio ENUM('bachiller', 'tecnico', 'tecnologo', 'profesional', 'posgrado') NOT NULL,
    formacion_tecnica VARCHAR(200) NULL,
    formacion_tecnologica VARCHAR(200) NULL,
    formacion_pregrado VARCHAR(200) NULL,
    formacion_posgrado VARCHAR(200) NULL,
    datos_posgrado ENUM('especializacion', 'maestria', 'doctorado', 'no_aplica') NULL,
    maestria VARCHAR(200) NULL,
    posee_doctorado ENUM('si', 'no') NULL,
    eps_afiliado VARCHAR(100) NOT NULL,
    fondo_pension ENUM('colfondos', 'colpensiones', 'old_mutual', 'porvenir', 'proteccion') NOT NULL,
    arl VARCHAR(100) NOT NULL,
    trabajo_municipio TEXT NOT NULL,
    aceptacion_datos ENUM('si', 'no') NOT NULL,
    usuario_creacion INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    INDEX idx_numero_documento (numero_documento),
    INDEX idx_nombre (nombre_completo),
    INDEX idx_municipio (municipio_residencia),
    INDEX idx_fecha (fecha_diligenciamiento),
    INDEX idx_estado (estado),
    FOREIGN KEY (usuario_creacion) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- =====================================================
-- TABLA DE DOCUMENTOS
-- =====================================================
CREATE TABLE IF NOT EXISTS documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contrato_id INT NOT NULL,
    numero_documento VARCHAR(20) NOT NULL,
    tipo_documento ENUM(
        'autorizacion_tratamiento_datos',
        'autorizacion_consulta_delitos',
        'aportes_novedades',
        'declaracion_proactiva',
        'prestacion_servicios',
        'creacion_usuario',
        'propuesta_economica'
    ) NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_subida INT NOT NULL,
    INDEX idx_contrato (contrato_id),
    INDEX idx_numero_doc (numero_documento),
    INDEX idx_tipo (tipo_documento),
    FOREIGN KEY (contrato_id) REFERENCES contratos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_subida) REFERENCES usuarios(id) ON DELETE RESTRICT,
    UNIQUE KEY unique_contrato_tipo (contrato_id, tipo_documento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- =====================================================
-- TABLA DE AUDITORÍA
-- =====================================================
CREATE TABLE IF NOT EXISTS auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tabla VARCHAR(50) NOT NULL,
    registro_id INT NOT NULL,
    accion ENUM('crear', 'editar', 'eliminar') NOT NULL,
    usuario_id INT NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    datos_anteriores TEXT NULL,
    datos_nuevos TEXT NULL,
    INDEX idx_tabla (tabla),
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha (fecha),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- =====================================================
-- INSERTAR USUARIO ADMINISTRADOR POR DEFECTO
-- Contraseña: admin123
-- Hash generado con password_hash('admin123', PASSWORD_DEFAULT)
-- =====================================================
-- Si esta contraseña no funciona, ejecutar: http://localhost/contratos/generar_password.php
INSERT INTO usuarios (nombre, usuario, contrasena, tipo_usuario) 
VALUES ('Administrador', 'admin', '$2y$10$e0MYzXyjpJS7Pd2ALwFOR.xMNjH3GfxqPU0GvDCxgFvKZZzKMZLmu', 'administrador')
ON DUPLICATE KEY UPDATE id=id;

-- =====================================================
-- VISTAS ÚTILES
-- =====================================================

-- Vista de contratos con información de usuario
CREATE OR REPLACE VIEW vista_contratos AS
SELECT 
    c.*,
    u.nombre as nombre_usuario_creacion,
    u.usuario as usuario_username,
    (SELECT COUNT(*) FROM documentos WHERE contrato_id = c.id) as total_documentos
FROM contratos c
LEFT JOIN usuarios u ON c.usuario_creacion = u.id;

-- Vista de documentos por contrato
CREATE OR REPLACE VIEW vista_documentos_contrato AS
SELECT 
    d.*,
    c.nombre_completo,
    c.numero_documento as documento_contrato,
    u.nombre as nombre_usuario_subida
FROM documentos d
INNER JOIN contratos c ON d.contrato_id = c.id
LEFT JOIN usuarios u ON d.usuario_subida = u.id;

-- =====================================================
-- PROCEDIMIENTOS ALMACENADOS
-- =====================================================

DELIMITER $$

-- Procedimiento para registrar auditoría
CREATE PROCEDURE IF NOT EXISTS registrar_auditoria(
    IN p_tabla VARCHAR(50),
    IN p_registro_id INT,
    IN p_accion ENUM('crear', 'editar', 'eliminar'),
    IN p_usuario_id INT,
    IN p_datos_anteriores TEXT,
    IN p_datos_nuevos TEXT
)
BEGIN
    INSERT INTO auditoria (tabla, registro_id, accion, usuario_id, datos_anteriores, datos_nuevos)
    VALUES (p_tabla, p_registro_id, p_accion, p_usuario_id, p_datos_anteriores, p_datos_nuevos);
END$$

DELIMITER ;

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
