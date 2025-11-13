# 🔄 GUÍA RÁPIDA - FLUJO DEL SISTEMA DE CONTRATOS

## 📊 FLUJO COMPLETO PASO A PASO

### 1️⃣ CREACIÓN DEL CONTRATO (Usuario)
**Estado:** `en_creacion`
- Usuario llena formulario de contrato
- Se asigna automáticamente a un abogado
- Usuario sube documentos requeridos

**Siguiente paso:** Abogado debe aprobar

---

### 2️⃣ APROBACIÓN INICIAL (Abogado)
**Estado:** `en_creacion` → `revision_documentos`
- Abogado recibe notificación/asignación
- Accede a "Gestionar Workflow" del contrato
- Click en "Aprobar y Enviar a Revisión de Documentos"
- El contrato cambia a estado `revision_documentos`
- Se asigna automáticamente a revisor de documentos

**Siguiente paso:** Revisor de documentos debe revisar

---

### 3️⃣ REVISIÓN DE DOCUMENTOS (Revisor de Documentos)
**Estado:** `revision_documentos` → `revision_abogado`
- Revisor ve listado de contratos
- Accede al contrato
- Revisa cada documento individualmente:
  - ✅ Aprobar documento
  - ❌ Rechazar documento (con comentarios)
- Cuando todos están aprobados:
  - Asigna un abogado (puede ser el mismo u otro)
- El contrato cambia a `revision_abogado`

**Siguiente paso:** Abogado asignado debe aprobar

---

### 4️⃣ APROBACIÓN POST-DOCUMENTOS (Abogado)
**Estado:** `revision_abogado` → `administracion_tecnica`
- Abogado asignado accede al contrato
- Ve "Gestionar Workflow"
- Click en "Aprobar y Enviar a Administración Técnica"
- El contrato cambia a `administracion_tecnica`
- Se asigna automáticamente al primer admin técnico activo

**Siguiente paso:** Admin técnico debe agregar CDP

---

### 5️⃣ AGREGAR CDP (Administrador Técnico)
**Estado:** `administracion_tecnica` (permanece)
- Admin técnico ve contratos en "administracion_tecnica"
- Click en "Agregar CDP" desde "Gestionar Workflow"
- Completa formulario CDP:
  - 📅 Fecha CDP
  - 💰 Rubro presupuestal
  - 💵 Valor
  - 📄 Archivo PDF
  - 🔢 Número de proceso
  - 🏢 Dependencia
- Guarda CDP
- **IMPORTANTE:** Asigna un abogado para aprobación del CDP

**Siguiente paso:** Abogado debe aprobar el CDP

---

### 6️⃣ APROBACIÓN CDP (Abogado)
**Estado:** `administracion_tecnica` (permanece)
- Abogado asignado accede al contrato
- Ve información del CDP registrado
- Click en "Aprobar CDP"
- Se habilitan campos adicionales para admin técnico

**Siguiente paso:** Admin técnico completa datos técnicos

---

### 7️⃣ DATOS TÉCNICOS POST-APROBACIÓN (Administrador Técnico)
**Estado:** `administracion_tecnica` (permanece)
- Admin técnico ve campos adicionales habilitados:
  - 📋 Número de contrato
  - 🔢 Número SECOP
  - 👤 Supervisor
  - ⏱️ Días de ejecución
  - 📊 Otros datos técnicos
- Completa todos los campos
- Guarda datos técnicos
- **IMPORTANTE:** Asigna abogado para revisión final

**Siguiente paso:** Abogado cambia estado final

---

### 8️⃣ ESTADO FINAL (Abogado)
**Estados posibles:**
- `en_elaboracion` - Contrato en borrador final
- `para_firmas` - Listo para recolectar firmas
- `publicado_aprobado` - ✅ Publicado y aprobado
- `publicado_rechazado` - ❌ Publicado pero rechazado
- `publicado_corregido` - 🔧 Publicado con correcciones

**Acciones:**
- Abogado asignado accede al contrato
- Selecciona el estado final apropiado
- Click en "Cambiar Estado Final"
- El contrato se marca como no editable (`puede_editar = 0`)

**FIN DEL FLUJO** ✅

---

## 🔐 PERMISOS POR ROL

### 👤 Usuario
- ✅ Crear contratos
- ✅ Ver solo sus contratos
- ❌ No puede aprobar

### ⚖️ Abogado
- ✅ Ver contratos asignados
- ✅ Aprobar inicial
- ✅ Aprobar revisión post-documentos
- ✅ Aprobar CDP
- ✅ Cambiar estado final

### 📋 Revisor de Documentos
- ✅ Ver todos los contratos en transición
- ✅ Aprobar/rechazar documentos
- ✅ Asignar abogados

### 🏛️ Administrador Técnico
- ✅ Ver contratos en `administracion_tecnica`
- ✅ Agregar CDP
- ✅ Completar datos técnicos
- ✅ Asignar abogados

### 👨‍💼 Administrador
- ✅ Ver todos los contratos
- ✅ Realizar todas las acciones

---

## 🎯 ACCESO RÁPIDO

### Desde el Listado de Contratos
1. Ir a "Listar Contratos"
2. Buscar contrato deseado
3. Click en botón verde **"Gestionar Workflow"**
4. La interfaz mostrará solo las acciones disponibles para tu rol

### Desde el Menú
- Si está disponible: Click en "Workflow" en el menú superior
- Seleccionar contrato

---

## ⚠️ PROBLEMAS COMUNES

### "No tiene permisos para acceder a este contrato"
**Solución:** 
- Verificar que el contrato esté en el estado correcto
- Verificar que tengas el rol adecuado
- Para admin técnico: El contrato debe estar en `administracion_tecnica`

### "El botón no desaparece después de aprobar"
**Solución:** Recargar la página (F5)

### "No aparece ninguna acción disponible"
**Solución:**
- Verificar el estado del contrato
- Verificar si estás asignado al contrato
- Verificar si el paso anterior ya fue completado

---

## 📌 NOTAS IMPORTANTES

1. **Asignaciones son importantes**: Muchas acciones solo aparecen si estás asignado al contrato

2. **Estados secuenciales**: No puedes saltar pasos, el flujo debe seguirse en orden

3. **CDP es crítico**: Sin CDP aprobado, no se pueden agregar datos técnicos

4. **Datos técnicos necesarios**: Sin datos técnicos, no se puede cambiar a estado final

5. **Recarga la página**: Después de cada acción, recarga para ver los cambios actualizados

---

## 🔍 VERIFICAR EN QUÉ PASO ESTÁ UN CONTRATO

En el listado de contratos, verás:
- **Estado Actual**: Badge con color que indica el estado
- **Asignado a**: Usuario que debe tomar acción
- **Días en Etapa**: Tiempo transcurrido en el estado actual

---

**Fecha de creación:** 2025-11-13  
**Sistema:** Gestión de Contratos  
**Versión:** 1.0
