-- =====================================================
-- ACTUALIZACIÓN: AGREGAR CAMPO CÉDULA A USUARIOS
-- Ejecutar este script en la base de datos
-- =====================================================

USE contratos;

-- Agregar campo cedula a la tabla usuarios
ALTER TABLE usuarios 
ADD COLUMN cedula VARCHAR(20) NULL UNIQUE AFTER usuario,
ADD INDEX idx_cedula (cedula);

-- Nota: El campo es NULL inicialmente para no afectar usuarios existentes
-- Puedes actualizar usuarios existentes con UPDATE si lo necesitas
