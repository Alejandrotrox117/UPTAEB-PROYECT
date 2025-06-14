let roles = [];
let modulos = [];
let permisos = [];

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
        btnCancelar.addEventListener('click', limpiarFormulario);
    }

    // Event listener para actualizar contadores
    document.addEventListener('change', function(e) {
        if (e.target.type === 'checkbox' && (e.target.name === 'modulos[]' || e.target.name === 'permisos[]')) {
            actualizarContadores();
            actualizarResumen();
        }
    });
}

async function cargarDatosIniciales() {
    try {
        showLoading();
        
        await Promise.all([
            cargarRoles(),
            cargarModulos(),
            cargarPermisos()
        ]);

        hideLoading();
    } catch (error) {
        console.error('Error al cargar datos iniciales:', error);
        showNotification('Error al cargar los datos iniciales', 'error');
        hideLoading();
    }
}

async function cargarRoles() {
    try {
        const response = await fetch(base_url + '/RolesAsignaciones/getRoles');
        const data = await response.json();
        
        if (data.status) {
            roles = data.data;
            llenarSelectRoles();
        } else {
            console.error('Error al cargar roles:', data.message);
        }
    } catch (error) {
        console.error('Error en cargarRoles:', error);
    }
}

async function cargarModulos() {
    try {
        const response = await fetch(base_url + '/RolesAsignaciones/getModulos');
        const data = await response.json();
        
        if (data.status) {
            modulos = data.data;
            mostrarModulos();
        } else {
            console.error('Error al cargar módulos:', data.message);
        }
    } catch (error) {
        console.error('Error en cargarModulos:', error);
    }
}

async function cargarPermisos() {
    try {
        const response = await fetch(base_url + '/RolesAsignaciones/getPermisos');
        const data = await response.json();
        
        if (data.status) {
            permisos = data.data;
            mostrarPermisos();
        } else {
            console.error('Error al cargar permisos:', data.message);
        }
    } catch (error) {
        console.error('Error en cargarPermisos:', error);
    }
}

function llenarSelectRoles() {
    const select = document.getElementById('selectRol');
    if (!select) return;

    select.innerHTML = '<option value="">Seleccione un rol</option>';
    
    roles.forEach(rol => {
        const option = document.createElement('option');
        option.value = rol.idrol;
        option.textContent = rol.nombre;
        select.appendChild(option);
    });
}

function mostrarModulos() {
    const container = document.getElementById('modulosContainer');
    if (!container) return;

    if (modulos.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-cube text-4xl mb-4"></i>
                <p>No hay módulos disponibles</p>
            </div>
        `;
        return;
    }

    let html = '';
    modulos.forEach(modulo => {
        html += `
            <div class="flex items-start space-x-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors duration-200">
                <input type="checkbox" 
                       id="modulo_${modulo.idmodulo}" 
                       value="${modulo.idmodulo}" 
                       name="modulos[]"
                       class="mt-1 h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                <div class="flex-1 min-w-0">
                    <label for="modulo_${modulo.idmodulo}" class="text-sm font-medium text-gray-900 cursor-pointer block">
                        ${modulo.nombre_modulo}
                    </label>
                    ${modulo.descripcion ? `<p class="text-xs text-gray-500 mt-1">${modulo.descripcion}</p>` : ''}
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

function mostrarPermisos() {
    const container = document.getElementById('permisosContainer');
    if (!container) return;

    if (permisos.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-key text-4xl mb-4"></i>
                <p>No hay permisos disponibles</p>
            </div>
        `;
        return;
    }

    let html = '';
    permisos.forEach(permiso => {
        html += `
            <div class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors duration-200">
                <input type="checkbox" 
                       id="permiso_${permiso.idpermiso}" 
                       value="${permiso.idpermiso}" 
                       name="permisos[]"
                       class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded">
                <label for="permiso_${permiso.idpermiso}" class="text-sm font-medium text-gray-900 cursor-pointer">
                    ${permiso.nombre_permiso}
                </label>
            </div>
        `;
    });

    container.innerHTML = html;
}

async function handleRolChange() {
    const selectRol = document.getElementById('selectRol');
    const idrol = selectRol.value;

    if (!idrol) {
        limpiarSelecciones();
        ocultarResumen();
        return;
    }

    try {
        showLoading();
        await cargarAsignacionesRol(idrol);
        actualizarResumen();
        mostrarResumen();
        hideLoading();
    } catch (error) {
        console.error('Error al cargar asignaciones:', error);
        showNotification('Error al cargar las asignaciones del rol', 'error');
        hideLoading();
    }
}

async function cargarAsignacionesRol(idrol) {
    try {
        const response = await fetch(base_url + `/RolesAsignaciones/getAsignacionesByRol/${idrol}`);
        const data = await response.json();
        
        if (data.status) {
            const asignaciones = data.data;
            
            // Limpiar selecciones previas
            limpiarSelecciones();
            
            // Marcar módulos asignados
            if (asignaciones.modulos && asignaciones.modulos.data) {
                asignaciones.modulos.data.forEach(modulo => {
                    const checkbox = document.getElementById(`modulo_${modulo.idmodulo}`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            }
            
            // Marcar permisos asignados
            if (asignaciones.permisos && asignaciones.permisos.data) {
                asignaciones.permisos.data.forEach(permiso => {
                    const checkbox = document.getElementById(`permiso_${permiso.idpermiso}`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            }
            
            actualizarContadores();
            
        } else {
            console.error('Error al cargar asignaciones:', data.message);
        }
    } catch (error) {
        console.error('Error en cargarAsignacionesRol:', error);
    }
}

function limpiarSelecciones() {
    // Desmarcar todos los checkboxes de módulos
    document.querySelectorAll('input[name="modulos[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Desmarcar todos los checkboxes de permisos
    document.querySelectorAll('input[name="permisos[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    actualizarContadores();
}

function actualizarContadores() {
    const modulosSeleccionados = document.querySelectorAll('input[name="modulos[]"]:checked').length;
    const permisosSeleccionados = document.querySelectorAll('input[name="permisos[]"]:checked').length;
    
    const contadorModulos = document.getElementById('contadorModulos');
    const contadorPermisos = document.getElementById('contadorPermisos');
    
    if (contadorModulos) contadorModulos.textContent = modulosSeleccionados;
    if (contadorPermisos) contadorPermisos.textContent = permisosSeleccionados;
}

function actualizarResumen() {
    const selectRol = document.getElementById('selectRol');
    const rolSeleccionado = document.getElementById('rolSeleccionado');
    
    if (selectRol && rolSeleccionado) {
        const rolTexto = selectRol.options[selectRol.selectedIndex]?.text || '-';
        rolSeleccionado.textContent = rolTexto;
    }
}

function mostrarResumen() {
    const resumenContainer = document.getElementById('resumenContainer');
    if (resumenContainer) {
        resumenContainer.classList.remove('hidden');
    }
}

function ocultarResumen() {
    const resumenContainer = document.getElementById('resumenContainer');
    if (resumenContainer) {
        resumenContainer.classList.add('hidden');
    }
}

async function guardarAsignaciones() {
    const selectRol = document.getElementById('selectRol');
    const idrol = selectRol.value;

    if (!idrol) {
        showNotification('Debe seleccionar un rol', 'warning');
        return;
    }

    // Obtener módulos seleccionados
    const modulosSeleccionados = [];
    document.querySelectorAll('input[name="modulos[]"]:checked').forEach(checkbox => {
        modulosSeleccionados.push(parseInt(checkbox.value));
    });

    // Obtener permisos seleccionados
    const permisosSeleccionados = [];
    document.querySelectorAll('input[name="permisos[]"]:checked').forEach(checkbox => {
        permisosSeleccionados.push(parseInt(checkbox.value));
    });

    const datosEnvio = {
        idrol: parseInt(idrol),
        modulos: modulosSeleccionados,
        permisos: permisosSeleccionados
    };

    try {
        showLoading();

        const response = await fetch(base_url + '/RolesAsignaciones/guardarAsignaciones', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(datosEnvio)
        });

        const data = await response.json();
        
        if (data.status) {
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message, 'error');
        }

        hideLoading();

    } catch (error) {
        console.error('Error al guardar asignaciones:', error);
        showNotification('Error al guardar las asignaciones', 'error');
        hideLoading();
    }
}

function limpiarFormulario() {
    document.getElementById('selectRol').value = '';
    limpiarSelecciones();
    ocultarResumen();
}

// Funciones auxiliares
function showLoading() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.classList.remove('hidden');
    }
}

function hideLoading() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.classList.add('hidden');
    }
}

function showNotification(message, type = 'info') {
    const toast = document.getElementById('notificationToast');
    const icon = document.getElementById('notificationIcon');
    const messageEl = document.getElementById('notificationMessage');
    
    if (!toast || !icon || !messageEl) return;
    
    // Configurar ícono y colores según el tipo
    let iconClass = '';
    let iconColor = '';
    
    switch (type) {
        case 'success':
            iconClass = 'fas fa-check-circle';
            iconColor = 'text-green-600';
            break;
        case 'error':
            iconClass = 'fas fa-exclamation-circle';
            iconColor = 'text-red-600';
            break;
        case 'warning':
            iconClass = 'fas fa-exclamation-triangle';
            iconColor = 'text-yellow-600';
            break;
        default:
            iconClass = 'fas fa-info-circle';
            iconColor = 'text-blue-600';
    }
    
    icon.className = `${iconClass} ${iconColor} text-xl`;
    messageEl.textContent = message;
    
    toast.classList.remove('hidden');
    
    // Auto-ocultar después de 5 segundos
    setTimeout(() => {
        hideNotification();
    }, 5000);
}

function hideNotification() {
    const toast = document.getElementById('notificationToast');
    if (toast) {
        toast.classList.add('hidden');
    }
}