// Variables globales
let backupsData = [];
let accionPendiente = null;

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    inicializarEventos();
    cargarInformacionSistema();
    cargarListaBackups();
});

/**
 * Inicializa todos los event listeners
 */
function inicializarEventos() {
    // Botones principales
    document.getElementById('btnCrearBackup').addEventListener('click', crearBackup);
    document.getElementById('btnActualizarBackups').addEventListener('click', cargarListaBackups);
    document.getElementById('btnActualizarInfo').addEventListener('click', cargarInformacionSistema);
    
    // Modal de confirmación
    document.getElementById('btnConfirmarAccion').addEventListener('click', ejecutarAccionPendiente);
}

/**
 * Carga la información del sistema
 */
async function cargarInformacionSistema() {
    try {
        mostrarCargandoInfo(true);
        
        const response = await fetch('administracion/getInfoSistema', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }

        const result = await response.json();

        if (result.status) {
            mostrarInformacionSistema(result.info);
        } else {
            mostrarError('Error al cargar información del sistema: ' + result.message);
        }

    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error de conexión al cargar información del sistema');
    } finally {
        mostrarCargandoInfo(false);
    }
}

/**
 * Muestra la información del sistema en el HTML
 */
function mostrarInformacionSistema(info) {
    const container = document.getElementById('infoSistema');
    
    const html = `
        <div class="info-grid">
            <div class="info-item">
                <h6><i class="fas fa-server me-2 text-primary"></i>Servidor</h6>
                <p class="mb-1"><strong>Sistema:</strong> ${info.sistema_operativo}</p>
                <p class="mb-1"><strong>Servidor Web:</strong> ${info.servidor}</p>
                <p class="mb-0"><strong>PHP:</strong> ${info.php_version}</p>
            </div>
            
            <div class="info-item">
                <h6><i class="fas fa-database me-2 text-success"></i>Base de Datos</h6>
                <p class="mb-1"><strong>Motor:</strong> ${info.base_datos.motor}</p>
                <p class="mb-1"><strong>Versión:</strong> ${info.base_datos.version}</p>
                <p class="mb-1"><strong>Tablas:</strong> ${info.base_datos.total_tablas}</p>
                <p class="mb-0"><strong>Tamaño:</strong> ${info.base_datos.tamaño_mb}</p>
            </div>
            
            <div class="info-item">
                <h6><i class="fas fa-cogs me-2 text-warning"></i>Configuración PHP</h6>
                <p class="mb-1"><strong>Memoria:</strong> ${info.memoria_php}</p>
                <p class="mb-1"><strong>Tiempo Ejecución:</strong> ${info.tiempo_ejecucion_max}</p>
                <p class="mb-0"><strong>Upload Máximo:</strong> ${info.tamaño_upload_max}</p>
            </div>
            
            <div class="info-item">
                <h6><i class="fas fa-hdd me-2 text-info"></i>Espacio en Disco</h6>
                <p class="mb-1"><strong>Espacio Libre:</strong> ${info.espacio_disco.libre}</p>
                <p class="mb-0"><strong>Espacio Total:</strong> ${info.espacio_disco.total}</p>
            </div>
        </div>
    `;
    
    container.innerHTML = html;
}

/**
 * Carga la lista de backups disponibles
 */
async function cargarListaBackups() {
    try {
        mostrarCargandoBackups(true);
        
        const response = await fetch('administracion/listarBackups', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }

        const result = await response.json();

        if (result.status) {
            backupsData = result.backups || [];
            mostrarListaBackups(backupsData, result.total || 0);
        } else {
            mostrarError('Error al cargar lista de backups: ' + result.message);
        }

    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error de conexión al cargar lista de backups');
    } finally {
        mostrarCargandoBackups(false);
    }
}

/**
 * Muestra la lista de backups en el HTML
 */
function mostrarListaBackups(backups, total) {
    const container = document.getElementById('listaBackups');
    
    if (backups.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay backups disponibles</h5>
                <p class="text-muted">Crea tu primer backup haciendo clic en "Crear Backup"</p>
            </div>
        `;
        return;
    }

    let html = `
        <div class="mb-3">
            <h6 class="text-muted">
                <i class="fas fa-list me-2"></i>
                Total de backups: ${total}
            </h6>
        </div>
    `;

    backups.forEach((backup, index) => {
        html += `
            <div class="backup-item">
                <div class="d-flex justify-content-between align-items-start flex-wrap">
                    <div class="flex-grow-1 me-3">
                        <h6 class="mb-1">
                            <i class="fas fa-file-archive me-2 text-primary"></i>
                            ${backup.nombre}
                        </h6>
                        <p class="mb-1 text-size">
                            <i class="fas fa-calendar me-1"></i>
                            Creado: ${backup.fecha}
                        </p>
                        <p class="mb-0 text-size">
                            <i class="fas fa-weight-hanging me-1"></i>
                            Tamaño: ${backup.tamaño}
                        </p>
                    </div>
                    <div class="backup-actions">
                        <button type="button" 
                                class="btn btn-primary btn-xs" 
                                onclick="descargarBackup('${backup.nombre}')"
                                title="Descargar backup">
                            <i class="fas fa-download me-1"></i>
                            Descargar
                        </button>
                        <button type="button" 
                                class="btn btn-danger btn-xs" 
                                onclick="confirmarEliminarBackup('${backup.nombre}')"
                                title="Eliminar backup">
                            <i class="fas fa-trash me-1"></i>
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

/**
 * Crea un nuevo backup
 */
async function crearBackup() {
    try {
        // Deshabilitar botón y mostrar progreso
        const btn = document.getElementById('btnCrearBackup');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Creando...';

        mostrarAlerta('Creando backup de la base de datos...', 'info');

        const response = await fetch('administracion/crearBackup', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({})
        });

        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }

        const result = await response.json();

        if (result.status) {
            mostrarExito('Backup creado exitosamente: ' + result.archivo);
            // Recargar lista de backups
            setTimeout(() => {
                cargarListaBackups();
            }, 1000);
        } else {
            mostrarError('Error al crear backup: ' + result.message);
        }

    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error de conexión al crear backup');
    } finally {
        // Restaurar botón
        const btn = document.getElementById('btnCrearBackup');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plus me-1"></i>Crear Backup';
    }
}

/**
 * Descarga un backup específico
 */
function descargarBackup(nombreArchivo) {
    try {
        const url = `administracion/descargarBackup?archivo=${encodeURIComponent(nombreArchivo)}`;
        
        // Crear enlace temporal para descarga
        const link = document.createElement('a');
        link.href = url;
        link.download = nombreArchivo;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        mostrarExito('Descarga iniciada: ' + nombreArchivo);

    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al iniciar descarga');
    }
}

/**
 * Confirma la eliminación de un backup
 */
function confirmarEliminarBackup(nombreArchivo) {
    const modal = new bootstrap.Modal(document.getElementById('modalConfirmacion'));
    
    document.getElementById('modalConfirmacionLabel').textContent = 'Eliminar Backup';
    document.getElementById('mensajeConfirmacion').innerHTML = `
        ¿Estás seguro de que deseas eliminar el backup:<br>
        <strong>${nombreArchivo}</strong><br><br>
        <em class="text-danger">Esta acción no se puede deshacer.</em>
    `;
    
    accionPendiente = {
        tipo: 'eliminar',
        archivo: nombreArchivo
    };
    
    modal.show();
}

/**
 * Ejecuta la acción pendiente confirmada por el usuario
 */
async function ejecutarAccionPendiente() {
    if (!accionPendiente) return;

    const modal = bootstrap.Modal.getInstance(document.getElementById('modalConfirmacion'));
    modal.hide();

    if (accionPendiente.tipo === 'eliminar') {
        await eliminarBackup(accionPendiente.archivo);
    }

    accionPendiente = null;
}

/**
 * Elimina un backup específico
 */
async function eliminarBackup(nombreArchivo) {
    try {
        mostrarAlerta('Eliminando backup...', 'warning');

        const response = await fetch('administracion/eliminarBackup', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ archivo: nombreArchivo })
        });

        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }

        const result = await response.json();

        if (result.status) {
            mostrarExito('Backup eliminado exitosamente');
            // Recargar lista de backups
            cargarListaBackups();
        } else {
            mostrarError('Error al eliminar backup: ' + result.message);
        }

    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error de conexión al eliminar backup');
    }
}

// Funciones de utilidad para mostrar mensajes y estados

function mostrarCargandoInfo(mostrar) {
    const container = document.getElementById('infoSistema');
    if (mostrar) {
        container.innerHTML = `
            <div class="text-center py-3">
                <i class="fas fa-spinner fa-spin me-2"></i>
                Cargando información del sistema...
            </div>
        `;
    }
}

function mostrarCargandoBackups(mostrar) {
    const container = document.getElementById('listaBackups');
    if (mostrar) {
        container.innerHTML = `
            <div class="text-center py-3">
                <i class="fas fa-spinner fa-spin me-2"></i>
                Cargando lista de backups...
            </div>
        `;
    }
}

function mostrarAlerta(mensaje, tipo = 'info') {
    const alerta = document.getElementById('alertaBackup');
    const mensajeSpan = document.getElementById('mensajeAlerta');
    
    // Remover clases anteriores
    alerta.className = 'alert d-block';
    
    // Agregar clase según tipo
    switch (tipo) {
        case 'success':
            alerta.classList.add('alert-success');
            break;
        case 'warning':
            alerta.classList.add('alert-warning');
            break;
        case 'error':
        case 'danger':
            alerta.classList.add('alert-danger');
            break;
        default:
            alerta.classList.add('alert-info');
    }
    
    mensajeSpan.textContent = mensaje;
    
    // Auto-ocultar después de 5 segundos para mensajes de éxito
    if (tipo === 'success') {
        setTimeout(() => {
            alerta.classList.add('d-none');
        }, 5000);
    }
}

function mostrarExito(mensaje) {
    mostrarAlerta(mensaje, 'success');
}

function mostrarError(mensaje) {
    mostrarAlerta(mensaje, 'error');
}

function ocultarAlerta() {
    document.getElementById('alertaBackup').classList.add('d-none');
}
