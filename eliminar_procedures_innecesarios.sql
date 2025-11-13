-- ============================================================
-- PROCEDIMIENTOS ALMACENADOS - YA NO NECESARIOS
-- ============================================================
-- Los siguientes procedimientos almacenados fueron reemplazados
-- por código SQL directo en workflow_controller.php
-- Pueden ser eliminados si ya no se usan en otro lugar
-- ============================================================

-- 1. asignar_usuario_etapa
-- Reemplazado por SQL directo con transacciones en asignarUsuarioEtapa()
-- Ubicación: controllers/workflow_controller.php línea ~103

DROP PROCEDURE IF EXISTS asignar_usuario_etapa;

-- 2. cambiar_estado_contrato  
-- Reemplazado por SQL directo con transacciones en:
--   - cambiarEstadoContrato() línea ~191
--   - devolverContrato() línea ~289
-- Ubicación: controllers/workflow_controller.php

DROP PROCEDURE IF EXISTS cambiar_estado_contrato;

-- ============================================================
-- VERIFICACIÓN
-- ============================================================
-- Verificar que no existen estos procedimientos:

SHOW PROCEDURE STATUS WHERE Db = DATABASE() 
AND Name IN ('asignar_usuario_etapa', 'cambiar_estado_contrato');

-- Si la consulta anterior no devuelve resultados, 
-- los procedimientos han sido eliminados correctamente.
-- ============================================================
