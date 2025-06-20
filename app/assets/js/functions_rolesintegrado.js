import { abrirModal, cerrarModal } from "./exporthelpers.js";

let roles = [];
let modulos = [];
let permisos = [];
let asignacionesActuales = {};

document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    cargarDatosIniciales();
});

function initializeEventListeners() {
    const selectRol = document.getElementById('selectRol');
    const btnGuardar = document.getElementById('btnGuardarAsignaciones');
    const btnCancelar = document.getElementById('btnCancelar');

    if (selectRol) {
        selectRol.addEventListener('change', handleRolChange);
    }

    if (btnGuardar) {
        btnGuardar.addEventListener('click', guardarAsignaciones);
    }

    if (btnCancelar) {
        btnCancelar.addEventListener('click', limpiarSeleccion);
    }
}

async function cargarDatosIniciales() {
    try {
        await Promise.all([
            cargarRoles(),
            cargarModulos(),
            cargarPermisos()
        ]);
    } catch (error) {
        console.error('Error cargando datos iniciales:', error);
        showNotification('Error al cargar los datos iniciales', 'error');
    }
}

async function cargarRoles() {
    try {
        const response = await fetch('RolesIntegrado/getRoles');
        const data = await response.json();
        
        if (data.status) {
            roles = data.data;
            llenarSelectRoles();
        }
    } catch (error) {
        console.error('Error cargando roles:', error);
    }
}

async function cargarModulos() {
    try {
        const response = await fetch('RolesIntegrado/getModulosDisponibles');
        const data = await response.json();
        
        if (data.status) {
            modulos = data.data;
        }
    } catch (error) {
        console.error('Error cargando módulos:', error);
    }
}

async function cargarPermisos() {
    try {
        const response = await fetch('RolesIntegrado/getPermisosDisponibles');
        const data = await response.json();
        
        if (data.status) {
            permisos = data.data;
        }
    } catch (error) {
        console.error('Error cargando permisos:', error);
    }
}

function llenarSelectRoles() {
    const select = document.getElementById('selectRol');
    if (!select) return;

    select.innerHTML = '<option value="">Seleccione un rol</option>';
    roles.forEach(rol => {
        const option = document.createElement('option');
        option.value = rol.idrol;
        option.textContent = `${rol.nombre} - ${rol.descripcion || 'Sin descripción'}`;
        select.appendChild(option);
    });
}

async function handleRolChange() {
    const selectRol = document.getElementById('selectRol');
    const idrol = selectRol.value;
    const container = document.getElementById('modulosPermisosContainer');
    const resumenContainer = document.getElementById('resumenContainer');

    if (!idrol) {
        container.classList.add('hidden');
        resumenContainer.classList.add('hidden');
        return;
    }

    try {
        await cargarAsignacionesRol(idrol);
        mostrarModulosConPermisos();
        actualizarResumen();
        container.classList.remove('hidden');
        resumenContainer.classList.remove('hidden');
    } catch (error) {
        console.error('Error al cargar asignaciones:', error);
        showNotification('Error al cargar las asignaciones del rol', 'error');
    }
}

async function cargarAsignacionesRol(idrol) {
    try {
        const response = await fetch(`RolesIntegrado/getAsignacionesRol/${idrol}`);
        const data = await response.json();
        
        asignacionesActuales = {};
        if (data.status && data.data) {
            data.data.forEach(asignacion => {
                if (asignacion.permisos_especificos && asignacion.permisos_especificos.length > 0) {
                    asignacionesActuales[asignacion.idmodulo] = asignacion.permisos_especificos.map(p => p.idpermiso);
                }
            });
        }
    } catch (error) {
        console.error('Error cargando asignaciones:', error);
        asignacionesActuales = {};
    }
}

function mostrarModulosConPermisos() {
    const container = document.getElementById('listaModulosPermisos');
    if (!container) return;

    let html = '';
    modulos.forEach(modulo => {
        const permisosAsignados = asignacionesActuales[modulo.idmodulo] || [];
        const tieneAcceso = permisosAsignados.length > 0;
        
        html += `
            <div class="border rounded-lg p-4 ${tieneAcceso ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200'}">
                <div class="mb-4">
                    <label class="flex items-start">
                        <input type="checkbox" 
                               id="modulo_${modulo.idmodulo}" 
                               value="${modulo.idmodulo}" 
                               class="modulo-checkbox mt-1 mr-3 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                               ${tieneAcceso ? 'checked' : ''}
                               data-modulo-id="${modulo.idmodulo}">
                        <div class="flex-1">
                            <span class="font-medium text-gray-800 block">${modulo.titulo}</span>
                            <p class="text-sm text-gray-600 mt-1">${modulo.descripcion || 'Sin descripción'}</p>
                        </div>
                    </label>
                </div>
                
                <div id="permisos_${modulo.idmodulo}" class="permisos-container ${tieneAcceso ? '' : 'hidden'}">
                    <div class="border-t pt-3">
                        <label class="block text-xs font-medium text-gray-700 mb-2">
                            <i class="fas fa-key mr-1"></i>Permisos específicos:
                        </label>
                        <div class="space-y-2 max-h-32 overflow-y-auto">
                            ${crearCheckboxesPermisos(modulo.idmodulo, permisosAsignados)}
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    
    
    agregarEventListenersModulos();
}

function agregarEventListenersModulos() {
    
    document.querySelectorAll('.modulo-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const idmodulo = parseInt(this.getAttribute('data-modulo-id'));
            toggleModulo(idmodulo, this.checked);
        });
    });
    
    
    document.querySelectorAll('.permiso-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', actualizarContadores);
    });
}

function crearCheckboxesPermisos(idmodulo, permisosAsignados = []) {
    return permisos.map(permiso => {
        const checked = permisosAsignados.includes(permiso.idpermiso) ? 'checked' : '';
        return `
            <label class="flex items-center text-sm">
                <input type="checkbox" 
                       name="permisos_modulo_${idmodulo}[]" 
                       value="${permiso.idpermiso}" 
                       class="permiso-checkbox mr-2 h-3 w-3 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" 
                       data-modulo="${idmodulo}"
                       ${checked}>
                <span class="text-gray-700">${permiso.nombre_permiso}</span>
            </label>
        `;
    }).join('');
}

function toggleModulo(idmodulo, activo) {
    const permisosContainer = document.getElementById(`permisos_${idmodulo}`);
    const checkboxesPermisos = document.querySelectorAll(`input[name="permisos_modulo_${idmodulo}[]"]`);
    const moduloCard = document.querySelector(`#modulo_${idmodulo}`).closest('.border');
    
    if (activo) {
        permisosContainer.classList.remove('hidden');
        moduloCard.classList.remove('bg-gray-50', 'border-gray-200');
        moduloCard.classList.add('bg-green-50', 'border-green-200');
    } else {
        permisosContainer.classList.add('hidden');
        moduloCard.classList.remove('bg-green-50', 'border-green-200');
        moduloCard.classList.add('bg-gray-50', 'border-gray-200');
        
        checkboxesPermisos.forEach(cb => cb.checked = false);
    }
    
    actualizarContadores();
}

function actualizarResumen() {
    const selectRol = document.getElementById('selectRol');
    const rolSeleccionado = selectRol.options[selectRol.selectedIndex].text;
    
    document.getElementById('rolSeleccionado').textContent = rolSeleccionado !== 'Seleccione un rol' ? rolSeleccionado : '-';
    actualizarContadores();
}

function actualizarContadores() {
    const modulosActivos = document.querySelectorAll('.modulo-checkbox:checked').length;
    const permisosActivos = document.querySelectorAll('.permiso-checkbox:checked').length;
    
    document.getElementById('contadorModulos').textContent = modulosActivos;
    document.getElementById('contadorPermisosEspecificos').textContent = permisosActivos;
}

async function guardarAsignaciones() {
    const selectRol = document.getElementById('selectRol');
    const idrol = parseInt(selectRol.value);

    if (!idrol) {
        showNotification('Debe seleccionar un rol', 'warning');
        return;
    }

    
    const asignaciones = [];
    const modulosSeleccionados = document.querySelectorAll('.modulo-checkbox:checked');

    modulosSeleccionados.forEach(checkbox => {
        const idmodulo = parseInt(checkbox.value);
        const checkboxesPermisos = document.querySelectorAll(`input[name="permisos_modulo_${idmodulo}[]"]:checked`);
        
        if (checkboxesPermisos.length > 0) {
            const permisosSeleccionados = Array.from(checkboxesPermisos).map(cb => parseInt(cb.value));
            asignaciones.push({
                idmodulo: idmodulo,
                permisos_especificos: permisosSeleccionados
            });
        }
    });

    const datosEnvio = {
        idrol: idrol,
        asignaciones: asignaciones
    };

    try {
        const btnGuardar = document.getElementById('btnGuardarAsignaciones');
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';

        const response = await fetch('RolesIntegrado/guardarAsignacionesCompletas', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(datosEnvio)
        });

        const result = await response.json();

        if (result.status) {
            showNotification(result.message, 'success');
            
            await cargarAsignacionesRol(idrol);
            mostrarModulosConPermisos();
            actualizarContadores();
        } else {
            showNotification(result.message || 'No se pudieron guardar las asignaciones', 'error');
        }

    } catch (error) {
        console.error('Error guardando asignaciones:', error);
        showNotification('Error de conexión al guardar', 'error');
    } finally {
        const btnGuardar = document.getElementById('btnGuardarAsignaciones');
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = '<i class="fas fa-save mr-2"></i>Guardar Configuración';
    }
}

function limpiarSeleccion() {
    const selectRol = document.getElementById('selectRol');
    selectRol.value = '';
    document.getElementById('modulosPermisosContainer').classList.add('hidden');
    document.getElementById('resumenContainer').classList.add('hidden');
    asignacionesActuales = {};
}


function showNotification(message, type = 'info') {
    const toast = document.getElementById('notificationToast');
    const icon = document.getElementById('notificationIcon');
    const messageEl = document.getElementById('notificationMessage');

    
    switch(type) {
        case 'success':
            icon.className = 'fas fa-check-circle text-green-500 text-xl';
            break;
        case 'error':
            icon.className = 'fas fa-exclamation-circle text-red-500 text-xl';
            break;
        case 'warning':
            icon.className = 'fas fa-exclamation-triangle text-yellow-500 text-xl';
            break;
        default:
            icon.className = 'fas fa-info-circle text-blue-500 text-xl';
    }

    messageEl.textContent = message;
    toast.classList.remove('hidden');

    
    setTimeout(() => {
        hideNotification();
    }, 3000);
}

function hideNotification() {
    const toast = document.getElementById('notificationToast');
    toast.classList.add('hidden');
}


window.RolesIntegrado = {
    toggleModulo,
    actualizarContadores,
    showNotification,
    hideNotification
};