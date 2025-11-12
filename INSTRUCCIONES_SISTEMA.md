# 📋 Instrucciones del Sistema de Gestión de Contratos

## 🔧 Configuración Inicial

### 1. Actualizar la Base de Datos

**IMPORTANTE:** Antes de usar las nuevas funcionalidades, ejecute el siguiente script SQL en su base de datos:

```sql
-- Ejecutar el archivo: agregar_campo_cedula.sql
USE contratos;

ALTER TABLE usuarios 
ADD COLUMN cedula VARCHAR(20) NULL UNIQUE AFTER usuario,
ADD INDEX idx_cedula (cedula);
```

O simplemente ejecute el archivo `agregar_campo_cedula.sql` que se encuentra en la raíz del proyecto.

---

## 📝 Cómo Crear un Nuevo Contrato

### Paso a Paso:

1. **Acceder al Formulario**
   - En el menú lateral, haga clic en **"Nuevo Contrato"**

2. **Completar Información Básica**
   - Llene todos los campos marcados con asterisco (*) - son obligatorios
   - Fecha de diligenciamiento
   - Correo electrónico
   - Datos personales (nombre, documento, etc.)

3. **Información de Contacto**
   - Número de celular
   - Dirección de residencia
   - Municipio

4. **Información Académica y Laboral**
   - Nivel educativo
   - Experiencia laboral
   - Conocimientos específicos

5. **Adjuntar Documentos**
   - En la sección de documentación, adjunte los archivos requeridos
   - Formatos aceptados: PDF, Word, imágenes

6. **Guardar**
   - Revise toda la información
   - Haga clic en **"Guardar Contrato"**
   - Espere la confirmación de éxito

---

## 👥 Cómo Crear Usuarios de Forma Masiva

### Preparación del Archivo Excel:

1. **Descargar la Plantilla**
   - Vaya a **"Listar Usuarios"** en el menú
   - Haga clic en el botón verde **"Descargar Formato Excel"**
   - Guarde el archivo `usuarios.xlsx` en su computadora

2. **Completar la Plantilla**

   La plantilla tiene las siguientes columnas:

   | Columna | Descripción | Ejemplo |
   |---------|-------------|---------|
   | **NOMBRE COMPLETO** | Nombre y apellidos completos | Juan Pérez García |
   | **CEDULA** | Número de cédula (será el usuario y contraseña inicial) | 1234567890 |
   | **USUARIO** | Nombre de usuario (opcional, si está vacío usará la cédula) | jperez |
   | **TIPO USUARIO** | Escriba: "administrador" o "abogado" | abogado |

   **⚠️ IMPORTANTE:**
   - No elimine la fila de encabezados (fila 1)
   - Complete los datos a partir de la fila 2
   - La cédula es OBLIGATORIA
   - El tipo de usuario debe ser exactamente: "administrador" o "abogado" (sin mayúsculas)

3. **Ejemplo de Datos:**

   ```
   NOMBRE COMPLETO      | CEDULA     | USUARIO  | TIPO USUARIO
   María López Sánchez  | 9876543210 | mlopez   | abogado
   Carlos Ruiz Torres   | 1122334455 | cruiz    | administrador
   Ana Gómez Díaz       | 5544332211 | agomez   | abogado
   ```

4. **Subir el Archivo**
   - En la vista **"Listar Usuarios"**, haga clic en el botón azul **"Subir Archivo Excel"**
   - Seleccione su archivo Excel completado
   - Haga clic en **"Subir"**
   - Espere a que el sistema procese el archivo

5. **Resultado**
   - El sistema mostrará cuántos usuarios se crearon exitosamente
   - Si hay errores, se mostrarán para que pueda corregirlos

### 🔐 Credenciales de Acceso Iniciales

**MUY IMPORTANTE:**
- El **usuario** será el número de cédula (o el usuario especificado en el Excel)
- La **contraseña inicial** será el número de cédula
- Los usuarios **DEBEN cambiar su contraseña** al iniciar sesión por primera vez

**Ejemplo:**
- Si la cédula es: 1234567890
- Usuario: 1234567890 (o el especificado)
- Contraseña inicial: 1234567890

---

## 📚 Guía de Documentación

### Acceso a Documentos

En el menú lateral encontrará **"Guía de Documentación"** donde podrá:

1. **Ver Instrucciones Detalladas**
   - Cómo crear contratos
   - Cómo crear usuarios masivamente

2. **Descargar Documentos de Referencia**
   - Plantillas
   - Manuales
   - Formatos requeridos

3. **Buscar Documentos**
   - Use el buscador para encontrar documentos específicos
   - Los documentos se muestran con iconos de colores según su tipo

---

## ⚙️ Funcionalidades Adicionales

### Gestión Individual de Usuarios

Si necesita crear usuarios uno por uno:

1. Vaya a **"Crear Usuario"**
2. Complete el formulario:
   - Nombre completo
   - **Cédula** (nuevo campo)
   - Usuario
   - Contraseña
   - Tipo de usuario

### Edición de Usuarios

1. En **"Listar Usuarios"**, haga clic en el botón de editar (icono de lápiz)
2. Modifique los datos necesarios (incluida la cédula)
3. Guarde los cambios

---

## 🆘 Solución de Problemas Comunes

### Error: "La cédula ya existe"
- Verifique que no haya cédulas duplicadas en su archivo Excel
- Revise que el usuario no exista previamente en el sistema

### Error al subir el archivo Excel
- Asegúrese de que el archivo tenga extensión .xlsx o .xls
- Verifique que todas las columnas obligatorias estén presentes
- No debe haber filas completamente vacías entre los datos

### Los usuarios no pueden iniciar sesión
- Verifique que estén usando la cédula como contraseña
- Confirme que el usuario esté en estado "activo"

---

## 📞 Contacto y Soporte

Para soporte adicional o reportar problemas, contacte al administrador del sistema.

---

**Última actualización:** Noviembre 2025
**Versión del Sistema:** 1.0
