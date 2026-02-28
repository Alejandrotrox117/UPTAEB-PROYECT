const base_url = window.base_url || '';

let rolSeleccionado = null;
let configuracionActual = {};

document.addEventListener('DOMContentLoaded', function () {
    cargarRoles();

    const selectRol = document.getElementById('select-rol');
    if (selectRol) {
        selectRol.addEventListener('change', function () {
            rolSeleccionado = this.value;
            if (rolSeleccionado) {
                cargarConfiguracion(rolSeleccionado);
            } else {
                ocultarConfiguracion();
            }
        });
    }

    const btnGuardar = document.getElementById('btn-guardar');
    if (btnGuardar) {
        btnGuardar.addEventListener('click', guardarConfiguracion);
    }

    const btnCancelar = document.getElementById('btn-cancelar');
    if (btnCancelar) {
        btnCancelar.addEventListener('click', () => {
            if (rolSeleccionado) {
                cargarConfiguracion(rolSeleccionado);
            }
        });
    }
});

function cargarRoles() {
    fetch(base_url + '/notificacionesconfig/obtenerRoles')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('select-rol');
            if (data.status && data.data) {
                select.innerHTML = '<option value="">Seleccione un rol...</option>';
                data.data.forEach(rol => {
                    select.innerHTML += `<option value="${rol.idrol}">${rol.nombre}</option>`;
                });
            } else {
                select.innerHTML = '<option value="">Error cargando roles</option>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const select = document.getElementById('select-rol');
            select.innerHTML = '<option value="">Error de conexión</option>';
        });
}

function cargarConfiguracion(idrol) {
    fetch(base_url + '/notificacionesconfig/obtenerConfiguracion?idrol=' + idrol)
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                configuracionActual = data.data;
                mostrarConfiguracion(data.data);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Error al cargar configuración'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo cargar la configuración'
            });
        });
}

function mostrarConfiguracion(config) {
    const container = document.getElementById('modulos-container');
    container.innerHTML = '';

    const modulosNombres = {
        'compras': 'Compras',
        'productos': 'Productos',
        'ventas': 'Ventas'
    };

    const modulosIconos = {
        'compras': 'fas fa-shopping-cart',
        'productos': 'fas fa-box',
        'ventas': 'fas fa-cash-register'
    };

    const prioridadColors = {
        'CRITICA': 'text-red-600 bg-red-50',
        'ALTA': 'text-orange-600 bg-orange-50',
        'MEDIA': 'text-yellow-600 bg-yellow-50',
        'BAJA': 'text-blue-600 bg-blue-50'
    };

    for (const [modulo, tipos] of Object.entries(config)) {
        const moduloHTML = `
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <!-- Header del módulo -->
                <div class="bg-green-600 p-4">
                    <h3 class="text-lg font-semibold text-white flex items-center">
                        <i class="${modulosIconos[modulo] || 'fas fa-cubes'} mr-2"></i>
                        ${modulosNombres[modulo] || modulo}
                    </h3>
                </div>
                
                <!-- Lista de notificaciones -->
                <div class="p-4 space-y-3" id="modulo-${modulo}">
                    ${Object.entries(tipos).map(([tipo, info]) => {
            const inputId = `notif-${modulo}-${tipo}`;
            return `
                            <div class="flex items-start space-x-3 p-3 hover:bg-gray-50 rounded-lg transition border border-transparent hover:border-gray-200">
                                <div class="flex items-center h-6 mt-1">
                                    <input type="checkbox" 
                                           id="${inputId}"
                                           data-modulo="${modulo}"
                                           data-tipo="${tipo}"
                                           ${info.habilitada ? 'checked' : ''}
                                           class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500 cursor-pointer">
                                </div>
                                <label for="${inputId}" class="flex-1 cursor-pointer">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="font-medium text-gray-900">${info.nombre}</span>
                                        <span class="text-xs px-2 py-1 rounded-full font-medium ${prioridadColors[info.prioridad]}">
                                            ${info.prioridad}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-500">${info.descripcion}</p>
                                </label>
                            </div>
                        `;
        }).join('')}
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', moduloHTML);
    }

    document.getElementById('config-container').classList.remove('hidden');
    document.getElementById('mensaje-seleccionar').classList.add('hidden');
}

function ocultarConfiguracion() {
    document.getElementById('config-container').classList.add('hidden');
    document.getElementById('mensaje-seleccionar').classList.remove('hidden');
}

function guardarConfiguracion() {
    if (!rolSeleccionado) {
        Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: 'Selecciona un rol primero'
        });
        return;
    }

    // Recopilar todas las configuraciones
    const configuraciones = [];

    document.querySelectorAll('input[type="checkbox"][data-modulo]').forEach(checkbox => {
        configuraciones.push({
            modulo: checkbox.dataset.modulo,
            tipo: checkbox.dataset.tipo,
            habilitada: checkbox.checked
        });
    });

    // Confirmación
    const selectRol = document.getElementById('select-rol');
    const rolNombre = selectRol.options[selectRol.selectedIndex].text;

    const habilitadas = configuraciones.filter(c => c.habilitada).length;
    const deshabilitadas = configuraciones.filter(c => !c.habilitada).length;

    Swal.fire({
        title: '¿Guardar configuración?',
        html: `
            <div class="text-left">
                <p class="mb-2"><strong>Rol:</strong> ${rolNombre}</p>
                <p class="mb-2"><strong>Notificaciones habilitadas:</strong> <span class="text-green-600">${habilitadas}</span></p>
                <p><strong>Notificaciones deshabilitadas:</strong> <span class="text-gray-600">${deshabilitadas}</span></p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-check mr-2"></i>Sí, guardar',
        cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            ejecutarGuardado(configuraciones);
        }
    });
}

function ejecutarGuardado(configuraciones) {
    const btnGuardar = document.getElementById('btn-guardar');
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';

    fetch(base_url + '/notificacionesconfig/guardar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            idrol: rolSeleccionado,
            configuraciones: configuraciones
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Error al guardar la configuración'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo guardar la configuración'
            });
        })
        .finally(() => {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="fas fa-save mr-2"></i>Guardar Configuración';
        });
}
