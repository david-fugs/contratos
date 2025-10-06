/**
 * SISTEMA DE GESTIÓN DE CONTRATOS
 * JavaScript General - app.js
 */

// ===== VARIABLES GLOBALES =====
const siteUrl = 'http://localhost/contratos/';

// ===== FUNCIONES DE UTILIDAD =====

// Mostrar modal
function mostrarModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
    }
}

// Ocultar modal
function ocultarModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
    }
}

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('show');
    }
}

// Mostrar loading overlay
function mostrarLoading() {
    let overlay = document.querySelector('.loading-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = '<div class="spinner"></div>';
        document.body.appendChild(overlay);
    }
    overlay.classList.add('show');
}

// Ocultar loading overlay
function ocultarLoading() {
    const overlay = document.querySelector('.loading-overlay');
    if (overlay) {
        overlay.classList.remove('show');
    }
}

// Toggle sidebar en móvil
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('active');
    }
}

// Confirmar eliminación
function confirmarEliminacion(mensaje = '¿Está seguro de que desea eliminar este registro?') {
    return confirm(mensaje);
}

// Validar formulario
function validarFormulario(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    let valido = true;
    const campos = form.querySelectorAll('[required]');
    
    campos.forEach(campo => {
        if (!campo.value.trim()) {
            campo.classList.add('error');
            valido = false;
            
            // Mostrar mensaje de error
            let errorMsg = campo.nextElementSibling;
            if (!errorMsg || !errorMsg.classList.contains('form-error')) {
                errorMsg = document.createElement('div');
                errorMsg.className = 'form-error';
                errorMsg.textContent = 'Este campo es obligatorio';
                campo.parentNode.insertBefore(errorMsg, campo.nextSibling);
            }
            errorMsg.classList.add('show');
        } else {
            campo.classList.remove('error');
            const errorMsg = campo.nextElementSibling;
            if (errorMsg && errorMsg.classList.contains('form-error')) {
                errorMsg.classList.remove('show');
            }
        }
    });
    
    return valido;
}

// Limpiar errores de formulario
function limpiarErrores(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    form.querySelectorAll('.error').forEach(campo => {
        campo.classList.remove('error');
    });
    
    form.querySelectorAll('.form-error').forEach(error => {
        error.classList.remove('show');
    });
}

// Validar email
function validarEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

// Validar solo números
function validarSoloNumeros(valor) {
    return /^\d+$/.test(valor);
}

// Formatear fecha a DD/MM/YYYY
function formatearFecha(fecha) {
    if (!fecha) return '';
    const d = new Date(fecha);
    const dia = String(d.getDate()).padStart(2, '0');
    const mes = String(d.getMonth() + 1).padStart(2, '0');
    const anio = d.getFullYear();
    return `${dia}/${mes}/${anio}`;
}

// Formatear número de documento
function formatearDocumento(valor) {
    return valor.replace(/\D/g, '');
}

// Formatear teléfono
function formatearTelefono(valor) {
    return valor.replace(/\D/g, '');
}

// Hacer petición AJAX
async function hacerPeticion(url, method = 'GET', data = null) {
    try {
        mostrarLoading();
        
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            }
        };
        
        if (data && method !== 'GET') {
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(url, options);
        const result = await response.json();
        
        ocultarLoading();
        return result;
    } catch (error) {
        ocultarLoading();
        console.error('Error en petición:', error);
        mostrarAlerta('Error al procesar la solicitud', 'danger');
        return { success: false, message: 'Error al procesar la solicitud' };
    }
}

// Hacer petición con FormData (para archivos)
async function hacerPeticionFormData(url, formData) {
    try {
        mostrarLoading();
        
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        ocultarLoading();
        return result;
    } catch (error) {
        ocultarLoading();
        console.error('Error en petición:', error);
        mostrarAlerta('Error al procesar la solicitud', 'danger');
        return { success: false, message: 'Error al procesar la solicitud' };
    }
}

// Mostrar alerta temporal
function mostrarAlerta(mensaje, tipo = 'info', duracion = 3000) {
    // Crear alerta
    const alerta = document.createElement('div');
    alerta.className = `alert alert-${tipo}`;
    alerta.style.position = 'fixed';
    alerta.style.top = '20px';
    alerta.style.right = '20px';
    alerta.style.zIndex = '99999';
    alerta.style.minWidth = '300px';
    alerta.innerHTML = `
        <span>${mensaje}</span>
    `;
    
    document.body.appendChild(alerta);
    
    // Animar entrada
    setTimeout(() => {
        alerta.style.animation = 'slideDown 0.3s';
    }, 10);
    
    // Remover después de la duración
    setTimeout(() => {
        alerta.style.opacity = '0';
        alerta.style.transform = 'translateX(100%)';
        alerta.style.transition = 'all 0.3s';
        setTimeout(() => {
            alerta.remove();
        }, 300);
    }, duracion);
}

// Obtener fecha actual en formato YYYY-MM-DD
function obtenerFechaActual() {
    const hoy = new Date();
    const anio = hoy.getFullYear();
    const mes = String(hoy.getMonth() + 1).padStart(2, '0');
    const dia = String(hoy.getDate()).padStart(2, '0');
    return `${anio}-${mes}-${dia}`;
}

// Calcular edad desde fecha de nacimiento
function calcularEdad(fechaNacimiento) {
    const hoy = new Date();
    const nacimiento = new Date(fechaNacimiento);
    let edad = hoy.getFullYear() - nacimiento.getFullYear();
    const mes = hoy.getMonth() - nacimiento.getMonth();
    
    if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
        edad--;
    }
    
    return edad;
}

// Validar tamaño de archivo
function validarTamanoArchivo(archivo, maxSizeMB = 5) {
    const maxSize = maxSizeMB * 1024 * 1024; // Convertir a bytes
    return archivo.size <= maxSize;
}

// Validar extensión de archivo
function validarExtensionArchivo(archivo, extensionesPermitidas = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']) {
    const extension = archivo.name.split('.').pop().toLowerCase();
    return extensionesPermitidas.includes(extension);
}

// Filtrar tabla
function filtrarTabla(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    
    if (!input || !table) return;
    
    input.addEventListener('keyup', function() {
        const filtro = this.value.toLowerCase();
        const filas = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        
        for (let i = 0; i < filas.length; i++) {
            let mostrar = false;
            const celdas = filas[i].getElementsByTagName('td');
            
            for (let j = 0; j < celdas.length; j++) {
                const textoCelda = celdas[j].textContent || celdas[j].innerText;
                if (textoCelda.toLowerCase().indexOf(filtro) > -1) {
                    mostrar = true;
                    break;
                }
            }
            
            filas[i].style.display = mostrar ? '' : 'none';
        }
    });
}

// Exportar tabla a Excel (CSV)
function exportarAExcel(tableId, nombreArchivo = 'datos') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const filas = table.querySelectorAll('tr');
    
    for (let i = 0; i < filas.length; i++) {
        let fila = [];
        const cols = filas[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length - 1; j++) { // -1 para excluir columna de acciones
            fila.push(cols[j].innerText);
        }
        
        csv.push(fila.join(','));
    }
    
    // Descargar archivo
    const csvString = csv.join('\n');
    const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', `${nombreArchivo}_${obtenerFechaActual()}.csv`);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Inicializar tooltips (si se usa)
function inicializarTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(elemento => {
        elemento.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.getAttribute('data-tooltip');
            tooltip.style.position = 'absolute';
            tooltip.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
            tooltip.style.color = 'white';
            tooltip.style.padding = '5px 10px';
            tooltip.style.borderRadius = '4px';
            tooltip.style.fontSize = '12px';
            tooltip.style.zIndex = '99999';
            
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.top = `${rect.top - tooltip.offsetHeight - 5}px`;
            tooltip.style.left = `${rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2)}px`;
            
            this.tooltipElement = tooltip;
        });
        
        elemento.addEventListener('mouseleave', function() {
            if (this.tooltipElement) {
                this.tooltipElement.remove();
                this.tooltipElement = null;
            }
        });
    });
}

// Ejecutar al cargar el DOM
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    inicializarTooltips();
    
    // Marcar página activa en menú
    const currentPath = window.location.pathname;
    const menuLinks = document.querySelectorAll('.sidebar-menu a');
    
    menuLinks.forEach(link => {
        if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href'))) {
            link.classList.add('active');
        }
    });
    
    // Remover alertas automáticamente después de 5 segundos
    const alertas = document.querySelectorAll('.alert');
    alertas.forEach(alerta => {
        setTimeout(() => {
            alerta.style.opacity = '0';
            alerta.style.transition = 'opacity 0.3s';
            setTimeout(() => {
                alerta.remove();
            }, 300);
        }, 5000);
    });
});

// Confirmar antes de salir si hay cambios sin guardar
let cambiosSinGuardar = false;

function marcarCambios() {
    cambiosSinGuardar = true;
}

function limpiarCambios() {
    cambiosSinGuardar = false;
}

window.addEventListener('beforeunload', function(e) {
    if (cambiosSinGuardar) {
        e.preventDefault();
        e.returnValue = '';
        return '';
    }
});

// Prevenir envío múltiple de formularios
document.addEventListener('submit', function(e) {
    const form = e.target;
    const submitBtn = form.querySelector('[type="submit"]');
    
    if (submitBtn && !submitBtn.disabled) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="loading"></span> Procesando...';
        
        // Rehabilitar después de 5 segundos por seguridad
        setTimeout(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = submitBtn.getAttribute('data-original-text') || 'Enviar';
        }, 5000);
    }
}, true);
