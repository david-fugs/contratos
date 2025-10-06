# Sistema de Gestión de Contratos
## Departamento de Risaralda - Secretaría de Educación

---

## 📋 Descripción del Proyecto

Sistema completo para la gestión de contratos con funcionalidades de:
- ✅ Autenticación de usuarios (Login/Logout)
- ✅ Gestión de usuarios (CRUD completo)
- ✅ Registro completo de contratos con campos dinámicos
- ✅ Subida y gestión de documentos asociados
- ✅ Dashboard con estadísticas
- ✅ Filtros y búsquedas avanzadas
- ✅ Auditoría de acciones
- ✅ Diseño responsive y moderno

---

## 🛠️ Tecnologías Utilizadas

- **Backend:** PHP 7.4+
- **Base de Datos:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Librerías:** 
  - SweetAlert2 (Alertas modernas)
  - Font Awesome 6 (Iconos)

---

## 📦 Instalación

### Paso 1: Requisitos Previos

Asegúrate de tener instalado:
- XAMPP (o similar con Apache y MySQL)
- PHP 7.4 o superior
- MySQL 5.7 o superior

### Paso 2: Configurar la Base de Datos

1. Abre **phpMyAdmin** en tu navegador: `http://localhost/phpmyadmin`
2. Importa el archivo `database.sql` que se encuentra en la raíz del proyecto
3. Esto creará:
   - La base de datos `contratos`
   - Todas las tablas necesarias
   - Un usuario administrador por defecto

### Paso 3: Configurar la Conexión

El archivo `conexion.php` ya está configurado con los valores por defecto de XAMPP:

```php
DB_HOST: localhost
DB_USER: root
DB_PASS: (vacío)
DB_NAME: contratos
```

Si tu configuración es diferente, edita el archivo `conexion.php` en la raíz del proyecto.

### Paso 4: Permisos de Carpetas

Asegúrate de que la carpeta `uploads/documentos/` tenga permisos de escritura:

**En Windows:**
- Click derecho en la carpeta `uploads`
- Propiedades → Seguridad → Editar
- Dar permisos de "Control total" al usuario

**En Linux/Mac:**
```bash
chmod -R 777 uploads/
```

### Paso 5: Acceder al Sistema

1. Abre tu navegador
2. Visita: `http://localhost/contratos/`
3. Usa las credenciales por defecto:
   - **Usuario:** `admin`
   - **Contraseña:** `admin123`

⚠️ **IMPORTANTE:** Cambia la contraseña del administrador después del primer acceso.

---

## 📁 Estructura del Proyecto

```
contratos/
│
├── assets/              # Recursos estáticos
│   ├── css/            # Estilos
│   │   └── styles.css  # Estilos generales
│   ├── js/             # JavaScript
│   │   └── app.js      # Funciones generales
│   └── img/            # Imágenes
│
├── config/             # Configuración
│   └── config.php      # Configuración general y funciones
│
├── controllers/        # Controladores (Lógica de negocio)
│   ├── auth_controller.php      # Login/Logout
│   ├── usuario_controller.php   # Gestión de usuarios
│   └── contrato_controller.php  # Gestión de contratos
│
├── includes/           # Archivos reutilizables
│   ├── header.php      # Encabezado y menú
│   └── footer.php      # Pie de página
│
├── models/             # Modelos (Reservado para futuras mejoras)
│
├── uploads/            # Archivos subidos
│   └── documentos/     # Documentos de contratos
│
├── views/              # Vistas (Interfaz de usuario)
│   ├── dashboard.php           # Dashboard principal
│   ├── usuario_crear.php       # Crear usuario
│   ├── usuario_listar.php      # Listar usuarios
│   ├── contrato_crear.php      # Crear contrato
│   └── contrato_listar.php     # Listar contratos
│
├── conexion.php        # Conexión a la base de datos
├── index.php           # Página de login
├── logout.php          # Cerrar sesión
├── database.sql        # Script de base de datos
└── README.md           # Este archivo
```

---

## 👥 Gestión de Usuarios

### Tipos de Usuario:

1. **Administrador:**
   - Acceso completo al sistema
   - Gestión de usuarios
   - Gestión de contratos
   - Eliminación de registros

2. **Usuario:**
   - Crear contratos
   - Ver contratos
   - Sin acceso a gestión de usuarios

---

## 📝 Campos del Formulario de Contratos

El formulario incluye las siguientes secciones:

### 1. Información Personal Básica
- Fecha de diligenciamiento (automática)
- Correo electrónico
- Tipo de documento
- Número de documento
- Lugar de expedición
- Nombre completo
- Fecha de nacimiento
- Identidad de género

### 2. Información Social
- Grupo poblacional
- Discapacidad (campo condicional)

### 3. Información de Contacto
- Celular
- Estado civil
- Información de hijos
- Dirección y barrio
- Municipio de residencia

### 4. Información Educativa
- Nivel de estudio (con campos dinámicos)
- Formaciones específicas según nivel
- Datos de posgrado (condicionales)

### 5. Información Laboral
- EPS
- Fondo de pensión
- ARL
- Municipios de trabajo (múltiple selección)

### 6. Tratamiento de Datos
- Aceptación de términos (obligatorio)

### 7. Documentos
- Sistema de carga múltiple de archivos
- 7 tipos de documentos:
  1. Autorización de Tratamiento de Datos
  2. Autorización Consulta Delitos Sexuales
  3. Aportes de Novedades
  4. Declaración Proactiva
  5. Prestación de Servicios
  6. Creación Usuario
  7. Propuesta Económica

---

## 🔒 Seguridad

- Contraseñas encriptadas con `password_hash()` de PHP
- Validación de sesiones en todas las páginas
- Sanitización de datos de entrada
- Protección contra inyección SQL con prepared statements
- Validación de tipos de archivo
- Límite de tamaño de archivo (5MB)

---

## 🎨 Características de Diseño

- Diseño responsive (adaptable a móviles)
- Interfaz moderna y limpia
- Colores consistentes en toda la aplicación
- Iconos con Font Awesome
- Alertas con SweetAlert2
- Modales para edición
- Filtros en tiempo real
- Exportación a Excel/CSV

---

## 🔧 Personalización

### Cambiar Colores

Edita el archivo `assets/css/styles.css` en las variables CSS:

```css
:root {
    --primary-color: #2563eb;    /* Color principal */
    --secondary-color: #10b981;   /* Color secundario */
    --danger-color: #ef4444;      /* Color de peligro */
    /* ... más variables ... */
}
```

### Agregar Nuevos Campos

1. Actualiza la tabla en `database.sql`
2. Modifica el formulario en `views/contrato_crear.php`
3. Actualiza el controlador en `controllers/contrato_controller.php`

---

## 📊 Base de Datos

### Tablas Principales:

1. **usuarios** - Gestión de usuarios del sistema
2. **contratos** - Información completa de contratos
3. **documentos** - Archivos asociados a contratos
4. **auditoria** - Registro de cambios (logs)

### Vistas:

1. **vista_contratos** - Contratos con información de usuario
2. **vista_documentos_contrato** - Documentos con información completa

---

## 🐛 Solución de Problemas

### Error de conexión a la base de datos
- Verifica que MySQL esté ejecutándose
- Confirma las credenciales en `conexion.php`
- Asegúrate de que la base de datos `contratos` exista

### No se pueden subir archivos
- Verifica permisos de la carpeta `uploads/`
- Revisa la configuración de PHP (`upload_max_filesize` y `post_max_size`)

### Sesión expirada constantemente
- Aumenta `session.gc_maxlifetime` en `php.ini`
- Verifica que las cookies estén habilitadas

---

## 📞 Soporte

Para reportar problemas o solicitar mejoras, contacta con el administrador del sistema.

---

## 📄 Licencia

Este sistema fue desarrollado para uso exclusivo de la Secretaría de Educación del Departamento de Risaralda.

---

## ✅ Checklist de Implementación

- [x] Sistema de autenticación
- [x] Gestión de usuarios
- [x] Dashboard con estadísticas
- [x] Formulario completo de contratos
- [x] Campos dinámicos según selección
- [x] Sistema de carga de documentos
- [x] Listado de contratos con filtros
- [x] Visualización de documentos
- [x] Auditoría de acciones
- [x] Diseño responsive

---

## 🚀 Próximas Mejoras (Futuras)

- [ ] Edición completa de contratos
- [ ] Reportes en PDF
- [ ] Notificaciones por email
- [ ] Firma digital de documentos
- [ ] API REST para integraciones
- [ ] Backup automático de base de datos

---

**Versión:** 1.0.0  
**Fecha:** Octubre 2025  
**Desarrollado para:** Secretaría de Educación del Departamento de Risaralda
