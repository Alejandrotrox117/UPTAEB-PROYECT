import { abrirModal, cerrarModal } from "./exporthelpers.js";

let roles = [];
let modulos = [];
let permisos = [];
let asignacionesActuales = {};
let asignacionesOriginales = {}; // Para detectar cambios

document.addEventListener('DOMContentLoaded', function () {
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
            <div class="border rounded-lg p-4 transition-all duration-200 ${tieneAcceso ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200'}">
                <div class="mb-2">
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
                
                <!-- Indicador de estado -->
                <div class="estado-indicador mt-2 min-h-[20px]"></div>
                
                <div id="permisos_${modulo.idmodulo}" class="permisos-container ${tieneAcceso ? '' : 'hidden'}">
                    <div class="border-t pt-3">
                        <label class="block text-xs font-medium text-gray-700 mb-2">
                            <i class="fas fa-key mr-1"></i>Selecciona un permiso específico:
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
    // Checkbox de módulo
    document.querySelectorAll('.modulo-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const idmodulo = parseInt(this.getAttribute('data-modulo-id'));
            toggleModulo(idmodulo, this.checked);
            actualizarEstadoModulo(idmodulo); // ✅ NUEVO
        });
    });

    // Radio buttons de permisos
    document.querySelectorAll('.permiso-checkbox').forEach(radio => {
        radio.addEventListener('change', function () {
            const idmodulo = parseInt(this.dataset.modulo);
            actualizarEstadoModulo(idmodulo); // ✅ NUEVO
            actualizarContadores();
        });
    });

    // Actualizar estados iniciales
    modulos.forEach(modulo => {
        actualizarEstadoModulo(modulo.idmodulo);
    });
}

function crearCheckboxesPermisos(idmodulo, permisosAsignados = []) {
    return permisos.map(permiso => {
        const checked = permisosAsignados.includes(permiso.idpermiso) ? 'checked' : '';
        return `
            <label class="flex items-center text-sm">
                <input type="radio" 
                       name="permisos_modulo_${idmodulo}" 
                       value="${permiso.idpermiso}" 
                       class="permiso-checkbox mr-2 h-3 w-3 text-blue-600 focus:ring-blue-500 border-gray-300" 
                       data-modulo="${idmodulo}"
                       ${checked}>
                <span class="text-gray-700">${permiso.nombre_permiso}</span>
            </label>
        `;
    }).join('');
}

function toggleModulo(idmodulo, activo) {
    const permisosContainer = document.getElementById(`permisos_${idmodulo}`);
    const checkboxesPermisos = document.querySelectorAll(`input[name="permisos_modulo_${idmodulo}"]`);
    const moduloCard = document.querySelector(`#modulo_${idmodulo}`).closest('.border');

    if (activo) {
        permisosContainer.classList.remove('hidden');
    } else {
        permisosContainer.classList.add('hidden');
        moduloCard.classList.remove('bg-green-50', 'border-green-200', 'bg-yellow-50', 'border-yellow-300');
        moduloCard.classList.add('bg-gray-50', 'border-gray-200');
        checkboxesPermisos.forEach(cb => cb.checked = false);
    }

    actualizarContadores();
}

/**
 * ✅ NUEVA FUNCIÓN: Actualiza el indicador visual de estado del módulo
 */
function actualizarEstadoModulo(idmodulo) {
    const moduloCheckbox = document.getElementById(`modulo_${idmodulo}`);
    if (!moduloCheckbox) return;

    const moduloCard = moduloCheckbox.closest('.border');
    const permisoSeleccionado = document.querySelector(`input[name="permisos_modulo_${idmodulo}"]:checked`);
    const indicadorContainer = moduloCard.querySelector('.estado-indicador');

    // Limpiar estados previos
    moduloCard.classList.remove(
        'bg-green-50', 'border-green-200',    // Completo
        'bg-yellow-50', 'border-yellow-300',  // Incompleto (warning)
        'bg-gray-50', 'border-gray-200'       // Deshabilitado
    );

    if (moduloCheckbox.checked) {
        if (permisoSeleccionado) {
            // ✅ Módulo con permiso seleccionado
            moduloCard.classList.add('bg-green-50', 'border-green-200');
            const permisoNombre = permisoSeleccionado.parentElement.querySelector('span').textContent;
            indicadorContainer.innerHTML = `
                <span class="text-xs text-green-700 flex items-center font-medium">
                    <i class="fas fa-check-circle mr-1"></i>
                    ${permisoNombre}
                </span>
            `;
        } else {
            // ⚠️ Módulo sin permiso (ADVERTENCIA)
            moduloCard.classList.add('bg-yellow-50', 'border-yellow-300');
            indicadorContainer.innerHTML = `
                <span class="text-xs text-yellow-700 flex items-center animate-pulse font-medium">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Selecciona un permiso
                </span>
            `;
        }
    } else {
        // Módulo deshabilitado
        moduloCard.classList.add('bg-gray-50', 'border-gray-200');
        indicadorContainer.innerHTML = '';
    }
}

/**
 * ✅ NUEVA FUNCIÓN: Valida las asignaciones antes de guardar
 */
function validarAsignaciones() {
    const errors = [];
    const warnings = [];
    const modulosSeleccionados = document.querySelectorAll('.modulo-checkbox:checked');

    if (modulosSeleccionados.length === 0) {
        errors.push('No has seleccionado ningún módulo');
        return { valid: false, errors, warnings };
    }

    // Verificar que cada módulo tenga permiso
    let modulosSinPermiso = [];
    modulosSeleccionados.forEach(checkbox => {
        const idmodulo = checkbox.value;
        const permisoSelec = document.querySelector(`input[name="permisos_modulo_${idmodulo}"]:checked`);

        if (!permisoSelec) {
            const moduloNombre = checkbox.closest('label').querySelector('.font-medium').textContent;
            modulosSinPermiso.push(moduloNombre);
        }
    });

    if (modulosSinPermiso.length > 0) {
        errors.push(
            `Los siguientes módulos no tienen permiso seleccionado:<br><ul class="list-disc list-inside mt-1">` +
            modulosSinPermiso.map(m => `<li>${m}</li>`).join('') +
            `</ul>`
        );
    }

    return {
        valid: errors.length === 0,
        errors,
        warnings
    };
}

function actualizarResumen() {
    const selectRol = document.getElementById('selectRol');
    const rolSeleccionado = selectRol.options[selectRol.selectedIndex].text;

    document.getElementById('rolSeleccionado').textContent = rolSeleccionado !== 'Seleccione un rol' ? rolSeleccionado : '-';
    actualizarContadores();
}

function actualizarContadores() {
    const modulosActivos = document.querySelectorAll('.modulo-checkbox:checked').length;

    // Contar cuántos módulos tienen un permiso seleccionado
    let modulosConPermisos = 0;
    document.querySelectorAll('.modulo-checkbox:checked').forEach(checkbox => {
        const idmodulo = checkbox.value;
        const permisoSeleccionado = document.querySelector(`input[name="permisos_modulo_${idmodulo}"]:checked`);
        if (permisoSeleccionado) {
            modulosConPermisos++;
        }
    });

    document.getElementById('contadorModulos').textContent = modulosActivos;
    document.getElementById('contadorPermisosEspecificos').textContent = modulosConPermisos;
}

async function guardarAsignaciones() {
    const selectRol = document.getElementById('selectRol');
    const idrol = parseInt(selectRol.value);

    if (!idrol) {
        Swal.fire({
            icon: 'warning',
            title: 'Rol no seleccionado',
            text: 'Debes seleccionar un rol para configurar',
            confirmButtonColor: '#3b82f6'
        });
        return;
    }

    // ✅ VALIDAR antes de guardar
    const validacion = validarAsignaciones();

    if (!validacion.valid) {
        Swal.fire({
            icon: 'error',
            title: 'Errores en la configuración',
            html: validacion.errors.join('<br><br>'),
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Entendido'
        });
        return;
    }

    // Obtener asignaciones
    const asignaciones = [];
    const modulosSeleccionados = document.querySelectorAll('.modulo-checkbox:checked');

    modulosSeleccionados.forEach(checkbox => {
        const idmodulo = parseInt(checkbox.value);
        const radioPermisoSeleccionado = document.querySelector(`input[name="permisos_modulo_${idmodulo}"]:checked`);

        if (radioPermisoSeleccionado) {
            const permisoSeleccionado = parseInt(radioPermisoSeleccionado.value);
            asignaciones.push({
                idmodulo: idmodulo,
                permisos_especificos: [permisoSeleccionado]
            });
        }
    });

    // ✅ Mostrar confirmación con Swal
    const rolSeleccionado = roles.find(r => r.idrol === idrol);

    let listaHtml = '<div class="text-left max-h-48 overflow-y-auto">';
    asignaciones.forEach(asig => {
        const modulo = modulos.find(m => m.idmodulo === asig.idmodulo);
        const permiso = permisos.find(p => p.idpermiso === asig.permisos_especificos[0]);

        listaHtml += `
            <div class="flex items-center justify-between p-2 bg-gray-50 rounded mb-1">
                <span class="text-sm font-medium text-gray-700">
                    <i class="fas fa-cube text-blue-500 mr-2"></i>${modulo.titulo}
                </span>
                <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">
                    ${permiso.nombre_permiso}
                </span>
            </div>
        `;
    });
    listaHtml += '</div>';

    const result = await Swal.fire({
        title: '<strong>Confirmar Configuración</strong>',
        icon: 'question',
        html: `
            <div class="text-left">
                <p class="mb-3"><strong class="text-blue-600">Rol:</strong> ${rolSeleccionado.nombre}</p>
                <p class="mb-2"><strong class="text-green-600">Módulos:</strong> ${asignaciones.length}</p>
                <hr class="my-3">
                <p class="mb-2 font-semibold">Asignaciones a guardar:</p>
                ${listaHtml}
            </div>
        `,
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-check mr-2"></i>Confirmar y Guardar',
        cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar',
        width: '600px'
    });

    if (!result.isConfirmed) return;

    // Ejecutar guardado
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

        const responseData = await response.json();

        if (responseData.status) {
            await Swal.fire({
                icon: 'success',
                title: '¡Guardado exitoso!',
                text: responseData.message,
                confirmButtonColor: '#10b981'
            });

            await cargarAsignacionesRol(idrol);
            mostrarModulosConPermisos();
            actualizarContadores();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error al guardar',
                text: responseData.message || 'No se pudieron guardar las asignaciones',
                confirmButtonColor: '#ef4444'
            });
        }

    } catch (error) {
        console.error('Error guardando asignaciones:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'No se pudo conectar con el servidor',
            confirmButtonColor: '#ef4444'
        });
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


    switch (type) {
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