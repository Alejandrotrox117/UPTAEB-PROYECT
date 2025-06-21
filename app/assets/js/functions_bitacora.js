import { abrirModal, cerrarModal } from "./exporthelpers.js";

let TablaBitacora = null;

document.addEventListener("DOMContentLoaded", function () {
    initializeBitacora();
    setupEventListeners();
    cargarFiltros();
});

function initializeBitacora() {
    
    if ($.fn.DataTable.isDataTable("#TablaBitacora")) {
        $("#TablaBitacora").DataTable().destroy();
    }

    TablaBitacora = $("#TablaBitacora").DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: "bitacora/getBitacoraData",
            type: "POST",
            dataSrc: function(json) {
                console.log("Datos recibidos:", json);
                if (json && json.data) {
                    return json.data;
                } else {
                    console.error("Estructura de datos incorrecta:", json);
                    return [];
                }
            },
            data: function (d) {
                const filtros = obtenerFiltrosActivos();
                return { 
                    ...d, 
                    ...filtros,
                    draw: d.draw || 1 
                };
            },
            error: function (xhr, error, thrown) {
                console.error("Error AJAX completo:", {
                    xhr: xhr,
                    error: error,
                    thrown: thrown,
                    status: xhr.status,
                    responseText: xhr.responseText
                });
                
                let errorMessage = "Error al cargar los datos de la bitácora.";
                if (xhr.responseText) {
                    try {
                        const errorData = JSON.parse(xhr.responseText);
                        errorMessage = errorData.message || errorMessage;
                    } catch (e) {
                        errorMessage = `Error ${xhr.status}: ${xhr.statusText}`;
                    }
                }
                
                showNotification(errorMessage, 'error');
            },
        },
        columns: [
            {
                data: "idbitacora",
                title: "ID",
                className: "all whitespace-nowrap py-2 px-3 text-gray-700 text-center",
                width: "80px"
            },
            {
                data: "tabla",
                title: "Módulo",
                className: "desktop whitespace-nowrap py-2 px-3 text-gray-700 font-medium"
            },
            {
                data: "accion",
                title: "Acción",
                className: "tablet-l whitespace-nowrap py-2 px-3 text-gray-700 text-center",
                render: function(data, type, row) {
                    return data || "N/A";
                }
            },
            {
                data: "usuario",
                title: "Usuario",
                className: "desktop whitespace-nowrap py-2 px-3 text-gray-700"
            },
            {
                data: "fecha",
                title: "Fecha y Hora",
                className: "all whitespace-nowrap py-2 px-3 text-gray-700 text-center"
            },
            {
                data: "acciones",
                title: "Acciones",
                orderable: false,
                searchable: false,
                className: "all text-center py-2 px-3",
                width: "100px",
                render: function (data, type, row) {
                    return data || `
                        <button type="button" class="text-blue-600 hover:text-blue-800 p-1 transition-colors duration-150 btn-ver-detalle" 
                                data-id="${row.idbitacora}" title="Ver detalles">
                            <i class="fas fa-eye"></i>
                        </button>
                    `;
                }
            }
        ],
        dom: "<'flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center mb-4'" +
             "l<'flex items-center gap-2'Bf>" +
             ">" +
             "<'overflow-x-auto't>" +
             "<'flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center mt-4'ip>",
        buttons: [
            {
                extend: "excelHtml5",
                text: '<i class="fas fa-file-excel mr-2"></i>Excel',
                titleAttr: "Exportar a Excel",
                className: "bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-md text-sm inline-flex items-center mr-2",
                exportOptions: {
                    columns: [0, 1, 2, 3, 4]
                }
            },
            {
                extend: "pdfHtml5",
                text: '<i class="fas fa-file-pdf mr-2"></i>PDF',
                titleAttr: "Exportar a PDF",
                className: "bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-md text-sm inline-flex items-center mr-2",
                exportOptions: {
                    columns: [0, 1, 2, 3, 4]
                },
                customize: function(doc) {
                    doc.content[1].table.widths = ['10%', '20%', '15%', '25%', '30%'];
                }
            }
        ],
        language: {
            processing: `
                <div class="flex items-center justify-center py-4">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-500 mr-3"></div>
                    <span class="text-lg font-medium text-gray-700">Cargando registros...</span>
                </div>`,
            emptyTable: '<div class="text-center py-8"><i class="fas fa-history fa-3x text-gray-300 mb-4"></i><p class="text-gray-500 text-lg">No hay registros en la bitácora</p></div>',
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Mostrando 0 registros",
            infoFiltered: "(filtrado de _MAX_ registros totales)",
            lengthMenu: "Mostrar _MENU_ registros",
            search: "Buscar:",
            searchPlaceholder: "Buscar en bitácora...",
            zeroRecords: '<div class="text-center py-8"><i class="fas fa-search fa-3x text-gray-300 mb-4"></i><p class="text-gray-500 text-lg">No se encontraron coincidencias</p></div>',
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Siguiente",
                previous: "Anterior"
            }
        },
        responsive: true,
        autoWidth: false,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
        order: [[0, "desc"]],
        initComplete: function () {
            console.log(" DataTable Bitacora inicializado correctamente");
            window.TablaBitacora = this.api();
        },
        drawCallback: function () {
            $('.dataTables_filter input[type="search"]').addClass(
                "py-2 px-3 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-green-400 focus:border-green-400"
            );
        }
    });
}

function setupEventListeners() {
    
    $(document).on('click', '.btn-ver-detalle', function() {
        const idbitacora = $(this).data('id');
        console.log("Ver detalle ID:", idbitacora);
        verDetalleBitacora(idbitacora);
    });

    
    $('#filtroModulo').on('change', function() {
        aplicarFiltros();
    });

    $('#filtroFechaDesde, #filtroFechaHasta').on('change', function() {
        aplicarFiltros();
    });

    $('#btnLimpiarFiltros').on('click', function() {
        limpiarFiltros();
    });

    $('#btnActualizarBitacora').on('click', function() {
        actualizarBitacora();
    });

    
    $('#btnEstadisticas').on('click', function() {
        mostrarEstadisticas();
    });

    
    $('#btnLimpiarBitacora').on('click', function() {
        abrirModalLimpieza();
    });

    
    $('#btnCerrarModalDetalle, #btnCerrarModalDetalle2').on('click', function() {
        cerrarModal('modalDetalleBitacora');
    });

    
    $('#btnCerrarModalEstadisticas, #btnCerrarModalEstadisticas2').on('click', function() {
        cerrarModal('modalEstadisticas');
    });

    
    $('#btnExportarDetalle').on('click', function() {
        exportarDetalle();
    });

    
    $(document).on('click', '#modalDetalleBitacora', function(e) {
        if (e.target === this) {
            cerrarModal('modalDetalleBitacora');
        }
    });

    $(document).on('click', '#modalEstadisticas', function(e) {
        if (e.target === this) {
            cerrarModal('modalEstadisticas');
        }
    });

    
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarModal('modalDetalleBitacora');
            cerrarModal('modalEstadisticas');
        }
    });
}


async function cargarFiltros() {
    try {
        const response = await fetch('bitacora/getModulosDisponibles');
        const result = await response.json();
        
        if (result.status && result.data) {
            const selectModulo = document.getElementById('filtroModulo');
            if (selectModulo) {
                selectModulo.innerHTML = '<option value="">Todos los módulos</option>';
                result.data.forEach(modulo => {
                    selectModulo.innerHTML += `<option value="${modulo.modulo}">${modulo.modulo.toUpperCase()}</option>`;
                });
            }
        }
    } catch (error) {
        console.error('Error al cargar filtros:', error);
    }
}

function obtenerFiltrosActivos() {
    const filtros = {};
    
    const modulo = document.getElementById('filtroModulo')?.value;
    if (modulo) filtros.modulo = modulo;
    
    const fechaDesde = document.getElementById('filtroFechaDesde')?.value;
    if (fechaDesde) filtros.fecha_desde = fechaDesde;
    
    const fechaHasta = document.getElementById('filtroFechaHasta')?.value;
    if (fechaHasta) filtros.fecha_hasta = fechaHasta;
    
    return filtros;
}

function aplicarFiltros() {
    if (TablaBitacora) {
        TablaBitacora.ajax.reload();
        showNotification('Filtros aplicados', 'success');
    }
}

function limpiarFiltros() {
    document.getElementById('filtroModulo').value = '';
    document.getElementById('filtroFechaDesde').value = '';
    document.getElementById('filtroFechaHasta').value = '';
    aplicarFiltros();
    showNotification('Filtros limpiados', 'info');
}

function actualizarBitacora() {
    if (TablaBitacora) {
        TablaBitacora.ajax.reload(null, false);
        showNotification('Bitácora actualizada', 'success');
    }
}


async function verDetalleBitacora(idbitacora) {
    try {
        showLoading();
        
        const response = await fetch(`bitacora/getBitacoraById/${idbitacora}`);
        const result = await response.json();
        
        console.log("Detalle bitácora:", result);
        
        if (result.status && result.data) {
            const data = result.data;
            
            
            document.getElementById('detalleId').textContent = data.id || 'N/A';
            document.getElementById('detalleModulo').textContent = data.modulo || 'N/A';
            document.getElementById('detalleAccion').innerHTML = formatearAccionDetalle(data.accion);
            document.getElementById('detalleUsuario').querySelector('span').textContent = data.usuario || 'Usuario desconocido';
            document.getElementById('detalleFecha').querySelector('span').textContent = data.fecha || 'N/A';
            
            
            if (data.fecha_raw) {
                const tiempoTranscurrido = calcularTiempoTranscurrido(data.fecha_raw);
                document.getElementById('detalleTiempoTranscurrido').querySelector('span').textContent = tiempoTranscurrido;
            }
            
            
            window.detalleActual = data;
            
            abrirModal('modalDetalleBitacora');
        } else {
            showNotification(result.message || 'Error al cargar detalle', 'error');
        }
    } catch (error) {
        console.error('Error al ver detalle:', error);
        showNotification('Error de conexión', 'error');
    } finally {
        hideLoading();
    }
}


async function mostrarEstadisticas() {
    try {
        abrirModal('modalEstadisticas');
        
        
        setTimeout(() => {
            const totalRegistros = TablaBitacora ? TablaBitacora.data().length : 0;
            const contenido = document.getElementById('contenidoEstadisticas');
            
            contenido.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-blue-50 p-6 rounded-lg text-center border border-blue-200">
                        <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-database fa-2x text-blue-600"></i>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-800 mb-2">Total Registros</h4>
                        <p class="text-3xl font-bold text-blue-600">${totalRegistros}</p>
                        <p class="text-sm text-gray-500 mt-1">Registros en total</p>
                    </div>
                    
                    <div class="bg-green-50 p-6 rounded-lg text-center border border-green-200">
                        <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-calendar-day fa-2x text-green-600"></i>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-800 mb-2">Hoy</h4>
                        <p class="text-3xl font-bold text-green-600">${calcularRegistrosHoy()}</p>
                        <p class="text-sm text-gray-500 mt-1">Registros de hoy</p>
                    </div>
                    
                    <div class="bg-purple-50 p-6 rounded-lg text-center border border-purple-200">
                        <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-chart-line fa-2x text-purple-600"></i>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-800 mb-2">Actividad</h4>
                        <p class="text-3xl font-bold text-purple-600">${calcularPromedioDiario()}</p>
                        <p class="text-sm text-gray-500 mt-1">Promedio diario</p>
                    </div>
                </div>
                
                <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-chart-bar text-gray-600 mr-2"></i>
                        Acciones Más Comunes
                    </h4>
                    <div class="space-y-3">
                        ${generarEstadisticasAcciones()}
                    </div>
                </div>
                
                <div class="bg-indigo-50 p-6 rounded-lg border border-indigo-200 mt-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-info-circle text-indigo-600 mr-2"></i>
                        Información del Sistema
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="font-medium text-gray-600">Último registro:</span>
                            <span class="text-gray-800">${obtenerUltimoRegistro()}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-600">Módulos activos:</span>
                            <span class="text-gray-800">${obtenerModulosActivos()}</span>
                        </div>
                    </div>
                </div>
            `;
        }, 1000);
        
    } catch (error) {
        console.error('Error al mostrar estadísticas:', error);
        showNotification('Error al cargar estadísticas', 'error');
    }
}


async function abrirModalLimpieza() {
    const { value: dias } = await Swal.fire({
        title: '¿Limpiar Bitácora?',
        html: `
            <div class="text-left">
                <p class="mb-4 text-gray-700">Esta acción eliminará registros antiguos de la bitácora de forma permanente.</p>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Eliminar registros anteriores a:
                </label>
                <input type="number" id="diasLimpieza" value="30" min="1" max="365" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                <small class="text-gray-500">días (mínimo 1, máximo 365)</small>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-trash mr-2"></i>Limpiar',
        cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar',
        preConfirm: () => {
            const dias = document.getElementById('diasLimpieza').value;
            if (!dias || dias < 1 || dias > 365) {
                Swal.showValidationMessage('Debe especificar un número válido de días (1-365)');
                return false;
            }
            return dias;
        }
    });

    if (dias) {
        await ejecutarLimpieza(dias);
    }
}

async function ejecutarLimpieza(dias) {
    try {
        showLoading();
        
        const formData = new FormData();
        formData.append('dias', dias);
        
        const response = await fetch('bitacora/limpiarBitacora', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.status) {
            Swal.fire({
                title: '¡Limpieza Exitosa!',
                text: result.message,
                icon: 'success',
                confirmButtonColor: '#10b981'
            });
            actualizarBitacora();
        } else {
            Swal.fire({
                title: 'Error',
                text: result.message || 'Error al limpiar bitácora',
                icon: 'error',
                confirmButtonColor: '#dc2626'
            });
        }
    } catch (error) {
        console.error('Error al limpiar bitácora:', error);
        Swal.fire({
            title: 'Error de Conexión',
            text: 'No se pudo conectar con el servidor',
            icon: 'error',
            confirmButtonColor: '#dc2626'
        });
    } finally {
        hideLoading();
    }
}


function calcularRegistrosHoy() {
    if (!TablaBitacora) return 0;
    
    const hoy = new Date().toISOString().split('T')[0];
    const datos = TablaBitacora.data().toArray();
    
    return datos.filter(registro => {
        const fechaRegistro = registro.fecha.split(' ')[0];
        return fechaRegistro === hoy;
    }).length;
}

function calcularPromedioDiario() {
    if (!TablaBitacora) return 0;
    
    const total = TablaBitacora.data().length;
    const diasAproximados = 30; 
    
    return Math.round(total / diasAproximados);
}

function generarEstadisticasAcciones() {
    if (!TablaBitacora) return '<p class="text-gray-500">No hay datos disponibles</p>';
    
    const datos = TablaBitacora.data().toArray();
    const contadorAcciones = {};
    
    
    datos.forEach(registro => {
        const accion = registro.accion;
        contadorAcciones[accion] = (contadorAcciones[accion] || 0) + 1;
    });
    
    
    const accionesOrdenadas = Object.entries(contadorAcciones)
        .sort(([,a], [,b]) => b - a)
        .slice(0, 5);
    
    if (accionesOrdenadas.length === 0) {
        return '<p class="text-gray-500">No hay datos disponibles</p>';
    }
    
    const total = datos.length;
    return accionesOrdenadas.map(([accion, cantidad]) => {
        const porcentaje = Math.round((cantidad / total) * 100);
        const colorClass = obtenerColorAccion(accion);
        
        return `
            <div class="flex justify-between items-center p-3 bg-white rounded border">
                <div class="flex items-center">
                    <div class="w-3 h-3 rounded-full ${colorClass} mr-3"></div>
                    <span class="font-medium text-gray-700">${accion}</span>
                </div>
                <div class="flex items-center">
                    <span class="text-sm text-gray-500 mr-2">${cantidad} registros</span>
                    <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded text-sm font-medium">${porcentaje}%</span>
                </div>
            </div>
        `;
    }).join('');
}

function obtenerColorAccion(accion) {
    const colores = {
        'ACCESO_MODULO': 'bg-blue-500',
        'INSERTAR': 'bg-green-500',
        'CREAR': 'bg-green-500',
        'ACTUALIZAR': 'bg-yellow-500',
        'ELIMINAR': 'bg-red-500',
        'VER': 'bg-gray-500',
        'LOGIN': 'bg-purple-500',
        'CONSULTA_DATOS': 'bg-cyan-500'
    };
    return colores[accion] || 'bg-gray-400';
}

function obtenerUltimoRegistro() {
    if (!TablaBitacora || TablaBitacora.data().length === 0) {
        return 'No disponible';
    }
    
    const datos = TablaBitacora.data().toArray();
    const ultimo = datos[0]; 
    return ultimo.fecha;
}

function obtenerModulosActivos() {
    if (!TablaBitacora) return 0;
    
    const datos = TablaBitacora.data().toArray();
    const modulos = new Set(datos.map(registro => registro.tabla));
    return modulos.size;
}


function formatearAccionDetalle(accion) {
    const acciones = {
        'ACCESO_MODULO': '<span class="px-3 py-1 text-sm font-medium bg-blue-100 text-blue-800 rounded-full flex items-center w-fit"><i class="fas fa-sign-in-alt mr-2"></i>ACCESO A MÓDULO</span>',
        'INSERTAR': '<span class="px-3 py-1 text-sm font-medium bg-green-100 text-green-800 rounded-full flex items-center w-fit"><i class="fas fa-plus mr-2"></i>CREAR REGISTRO</span>',
        'CREAR': '<span class="px-3 py-1 text-sm font-medium bg-green-100 text-green-800 rounded-full flex items-center w-fit"><i class="fas fa-plus mr-2"></i>CREAR REGISTRO</span>',
        'ACTUALIZAR': '<span class="px-3 py-1 text-sm font-medium bg-yellow-100 text-yellow-800 rounded-full flex items-center w-fit"><i class="fas fa-edit mr-2"></i>ACTUALIZAR</span>',
        'ELIMINAR': '<span class="px-3 py-1 text-sm font-medium bg-red-100 text-red-800 rounded-full flex items-center w-fit"><i class="fas fa-trash mr-2"></i>ELIMINAR</span>',
        'VER': '<span class="px-3 py-1 text-sm font-medium bg-gray-100 text-gray-800 rounded-full flex items-center w-fit"><i class="fas fa-eye mr-2"></i>VER DETALLE</span>',
        'LOGIN': '<span class="px-3 py-1 text-sm font-medium bg-purple-100 text-purple-800 rounded-full flex items-center w-fit"><i class="fas fa-sign-in-alt mr-2"></i>INICIAR SESIÓN</span>',
        'LOGOUT': '<span class="px-3 py-1 text-sm font-medium bg-indigo-100 text-indigo-800 rounded-full flex items-center w-fit"><i class="fas fa-sign-out-alt mr-2"></i>CERRAR SESIÓN</span>',
        'CONSULTA_DATOS': '<span class="px-3 py-1 text-sm font-medium bg-cyan-100 text-cyan-800 rounded-full flex items-center w-fit"><i class="fas fa-search mr-2"></i>CONSULTA</span>',
        'VISITA_VISTA_PRINCIP': '<span class="px-3 py-1 text-sm font-medium bg-green-100 text-green-800 rounded-full flex items-center w-fit"><i class="fas fa-home mr-2"></i>VISTA PRINCIPAL</span>',
        'CONSULTA_AUXILIAR': '<span class="px-3 py-1 text-sm font-medium bg-blue-100 text-blue-800 rounded-full flex items-center w-fit"><i class="fas fa-question-circle mr-2"></i>CONSULTA AUXILIAR</span>',
        'CONSULTA_INDIVIDUAL': '<span class="px-3 py-1 text-sm font-medium bg-purple-100 text-purple-800 rounded-full flex items-center w-fit"><i class="fas fa-user mr-2"></i>CONSULTA INDIVIDUAL</span>'
    };

    return acciones[accion] || `<span class="px-3 py-1 text-sm font-medium bg-gray-100 text-gray-800 rounded-full flex items-center w-fit"><i class="fas fa-cog mr-2"></i>${accion}</span>`;
}

function calcularTiempoTranscurrido(fechaRegistro) {
    try {
        const fecha = new Date(fechaRegistro);
        const ahora = new Date();
        const diferencia = ahora - fecha;

        const minutos = Math.floor(diferencia / 60000);
        const horas = Math.floor(diferencia / 3600000);
        const dias = Math.floor(diferencia / 86400000);
        const meses = Math.floor(diferencia / 2628000000);

        if (meses > 0) {
            return `Hace ${meses} mes${meses > 1 ? 'es' : ''}`;
        } else if (dias > 0) {
            return `Hace ${dias} día${dias > 1 ? 's' : ''}`;
        } else if (horas > 0) {
            return `Hace ${horas} hora${horas > 1 ? 's' : ''}`;
        } else if (minutos > 0) {
            return `Hace ${minutos} minuto${minutos > 1 ? 's' : ''}`;
        } else {
            return 'Hace unos momentos';
        }
    } catch (error) {
        return 'Tiempo no disponible';
    }
}

function exportarDetalle() {
    if (!window.detalleActual) {
        showNotification('No hay detalle para exportar', 'warning');
        return;
    }
    
    const data = window.detalleActual;
    const contenido = `
        Detalle de Registro de Bitácora
        ==============================
        
        ID: ${data.id}
        Módulo: ${data.modulo}
        Acción: ${data.accion}
        Usuario: ${data.usuario}
        Fecha: ${data.fecha}
        
        Generado el: ${new Date().toLocaleString()}
    `;
    
    
    const blob = new Blob([contenido], { type: 'text/plain;charset=utf-8' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `bitacora_detalle_${data.id}.txt`;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
    
    showNotification('Detalle exportado exitosamente', 'success');
}


function showNotification(message, type = 'info') {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-500',
        info: 'bg-blue-500'
    };
    
    const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
    };
    
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300 flex items-center`;
    notification.innerHTML = `
        <i class="${icons[type]} mr-2"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (document.body.contains(notification)) {
                document.body.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

function showLoading() {
    const loading = document.createElement('div');
    loading.id = 'loadingOverlay';
    loading.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    loading.innerHTML = `
        <div class="bg-white p-6 rounded-lg shadow-xl flex items-center space-x-3">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-500"></div>
            <span class="text-lg font-medium text-gray-700">Procesando...</span>
        </div>
    `;
    document.body.appendChild(loading);
}

function hideLoading() {
    const loading = document.getElementById('loadingOverlay');
    if (loading) {
        document.body.removeChild(loading);
    }
}




window.verDetalleBitacora = verDetalleBitacora;
window.actualizarBitacora = actualizarBitacora;
window.mostrarEstadisticas = mostrarEstadisticas;
window.abrirModalLimpieza = abrirModalLimpieza;
