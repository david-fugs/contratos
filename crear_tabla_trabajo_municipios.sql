-- Crear tabla trabajo_municipios si no existe
USE contratos;

CREATE TABLE IF NOT EXISTS trabajo_municipios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contrato_id INT NOT NULL,
    municipio VARCHAR(100) NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contrato_id) REFERENCES contratos(id) ON DELETE CASCADE,
    INDEX idx_contrato (contrato_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mensaje de confirmación
SELECT 'Tabla trabajo_municipios creada o ya existe' as Mensaje;
