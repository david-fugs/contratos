# Estructura de la Plantilla usuarios.xlsx

## Encabezados (Fila 1):
- Columna A: NOMBRE COMPLETO
- Columna B: CEDULA
- Columna C: USUARIO
- Columna D: TIPO USUARIO

## Ejemplos de Datos (Fila 2 en adelante):

### Fila 2:
- A2: María López Sánchez
- B2: 9876543210
- C2: mlopez
- D2: abogado

### Fila 3:
- A3: Carlos Ruiz Torres
- B3: 1122334455
- C3: cruiz
- D3: administrador

### Fila 4:
- A4: Ana Gómez Díaz
- B4: 5544332211
- C4: agomez
- D4: abogado

## Notas Importantes:

1. **NOMBRE COMPLETO** (Columna A):
   - Obligatorio
   - Nombre y apellidos completos
   - Ejemplo: Juan Pérez García

2. **CEDULA** (Columna B):
   - Obligatorio
   - Solo números
   - Será la contraseña inicial
   - Debe ser única
   - Ejemplo: 1234567890

3. **USUARIO** (Columna C):
   - Opcional (si está vacío, se usará la cédula)
   - Sin espacios
   - Minúsculas recomendadas
   - Debe ser único
   - Ejemplo: jperez

4. **TIPO USUARIO** (Columna D):
   - Obligatorio
   - Valores permitidos: "administrador" o "abogado"
   - Todo en minúsculas
   - Si no se especifica o es inválido, se asignará "usuario"

## Formato del Archivo:
- Extensión: .xlsx o .xls
- No eliminar la fila de encabezados
- Los datos comienzan en la fila 2
- No dejar filas vacías entre registros

## Resultado de la Importación:
- Usuario creado con contraseña = cédula
- Estado: activo
- El usuario debe cambiar su contraseña al iniciar sesión

## Credenciales de Acceso:
- Usuario: El valor de la columna USUARIO (o CEDULA si USUARIO está vacío)
- Contraseña: El valor de la columna CEDULA
