import { abrirModal, cerrarModal } from "./exporthelpers.js";
import { expresiones, inicializarValidaciones, limpiarValidaciones, registrarEntidad } from "./validaciones.js";

let tablaEmpleados;
let esSuperUsuarioActual = false;
let idUsuarioActual = 0;

// ========================================
// MANEJO DE FORMULARIO DINÁMICO
// ========================================

/**
 * Controla la visualización de campos según el tipo de empleado
 */
function actualizarCamposFormulario() {
  const tipoOperario = document.getElementById('tipo_operario');
  const tipoAdministrativo = document.getElementById('tipo_administrativo');
  const camposOperario = document.getElementById('campos_operario');
  const camposAdministrativo = document.getElementById('campos_administrativo');
  
  if (tipoOperario && tipoOperario.checked) {
    // OPERARIO: Mostrar solo campos básicos
    if (camposOperario) camposOperario.classList.remove('hidden');
    if (camposAdministrativo) camposAdministrativo.classList.add('hidden');
    limpiarCamposAdministrativos();
    deshabilitarValidacionesAdministrativas();
  } else if (tipoAdministrativo && tipoAdministrativo.checked) {
    // ADMINISTRATIVO: Mostrar todos los campos
    if (camposOperario) camposOperario.classList.add('hidden');
    if (camposAdministrativo) camposAdministrativo.classList.remove('hidden');
    limpiarCamposOperario();
    habilitarValidacionesAdministrativas();
  }
}

/**
 * Limpia los campos específicos de operarios
 */
function limpiarCamposOperario() {
  const puestoOperario = document.getElementById('puesto_operario');
  const fechaInicioOperario = document.getElementById('fecha_inicio_operario');
  
  if (puestoOperario) puestoOperario.value = '';
  if (fechaInicioOperario) fechaInicioOperario.value = '';
}

/**
 * Limpia los campos específicos de administrativos
 */
function limpiarCamposAdministrativos() {
  const camposAdmin = [
    'genero', 'fecha_nacimiento', 'telefono_principal',
    'correo_electronico', 'direccion', 'puesto_administrativo',
    'salario', 'fecha_inicio_admin', 'fecha_fin'
  ];
  
  camposAdmin.forEach(id => {
    const campo = document.getElementById(id);
    if (campo) campo.value = '';
  });
}

/**
 * Deshabilita validaciones para campos administrativos cuando es operario
 */
function deshabilitarValidacionesAdministrativas() {
  const camposAdmin = [
    'genero', 'fecha_nacimiento', 'telefono_principal',
    'correo_electronico', 'direccion', 'puesto_administrativo',
    'salario', 'fecha_inicio_admin', 'fecha_fin'
  ];
  
  camposAdmin.forEach(id => {
    const campo = document.getElementById(id);
    if (campo) campo.removeAttribute('required');
  });
}

/**
 * Habilita validaciones para campos administrativos
 */
function habilitarValidacionesAdministrativas() {
  const camposObligatorios = ['puesto_administrativo'];
  
  camposObligatorios.forEach(id => {
    const campo = document.getElementById(id);
    if (campo) campo.setAttribute('required', 'required');
  });
}

/**
 * Prepara los datos del formulario según el tipo de empleado
 */
function prepararDatosFormulario() {
  const tipoEmpleado = document.querySelector('input[name="tipo_empleado"]:checked')?.value || 'OPERARIO';
  const formData = new FormData(document.getElementById('empleadoForm'));
  
  formData.set('tipo_empleado', tipoEmpleado);
  
  if (tipoEmpleado === 'OPERARIO') {
    const puestoOperario = document.getElementById('puesto_operario')?.value;
    formData.set('puesto', puestoOperario || 'Operario General');
    
    const fechaInicioOperario = document.getElementById('fecha_inicio_operario')?.value;
    if (fechaInicioOperario) formData.set('fecha_inicio', fechaInicioOperario);
    
    // Campos que no aplican para operarios
    formData.set('salario', '0.00');
    formData.set('genero', '');
    formData.set('fecha_nacimiento', '');
    formData.set('correo_electronico', '');
    formData.set('telefono_principal', '');
    formData.set('direccion', '');
    formData.set('fecha_fin', '');
  } else {
    // ADMINISTRATIVO
    const puestoAdmin = document.getElementById('puesto_administrativo')?.value;
    formData.set('puesto', puestoAdmin);
    
    const fechaInicioAdmin = document.getElementById('fecha_inicio_admin')?.value;
    if (fechaInicioAdmin) formData.set('fecha_inicio', fechaInicioAdmin);
  }
  
  return formData;
}

/**
 * Limpia todo el formulario y resetea al estado inicial
 */
function limpiarFormulario() {
  const form = document.getElementById('empleadoForm');
  if (form) form.reset();
  
  const idempleado = document.getElementById('idempleado');
  if (idempleado) idempleado.value = '';
  
  const tipoOperario = document.getElementById('tipo_operario');
  if (tipoOperario) tipoOperario.checked = true;
  
  actualizarCamposFormulario();
  
  // Limpiar mensajes de error
  const mensajesError = document.querySelectorAll('small[id^="error-"]');
  mensajesError.forEach(msg => msg.classList.add('hidden'));
}

// ========================================
// TABLA DE EMPLEADOS
// ========================================

function recargarTablaEmpleados() {
  try {
    if (tablaEmpleados && tablaEmpleados.ajax && typeof tablaEmpleados.ajax.reload === 'function') {
      tablaEmpleados.ajax.reload(null, false);
      return true;
    }

    if ($.fn.DataTable.isDataTable('#TablaEmpleado')) {
      const tabla = $('#TablaEmpleado').DataTable();
      tabla.ajax.reload(null, false);
      return true;
    }

    window.location.reload();
    return true;
  } catch (error) {
    window.location.reload();
    return false;
  }
}

document.addEventListener("DOMContentLoaded", function () {
  $(document).ready(function () {
    // Event Listeners para cambio de tipo de empleado
    const radioTipos = document.querySelectorAll('input[name="tipo_empleado"]');
    radioTipos.forEach(radio => {
      radio.addEventListener('change', function() {
        actualizarCamposFormulario();
      });
    });
    
    // Inicializar el estado del formulario al cargar
    setTimeout(() => {
      actualizarCamposFormulario();
    }, 100);

    // Verificar el estado de super usuario antes de inicializar la tabla
    verificarSuperUsuario().then((result) => {
      if (result && result.status) {
        esSuperUsuarioActual = result.es_super_usuario;
        idUsuarioActual = result.usuario_id;
        console.log('Super usuario verificado:', esSuperUsuarioActual);
      } else {
        console.error('Error al verificar super usuario:', result ? result.message : 'Sin respuesta');
        esSuperUsuarioActual = false;
        idUsuarioActual = 0;
      }
      
      // Inicializar la tabla después de verificar el estado de super usuario
      inicializarTablaEmpleados();
      
    }).catch((error) => {
      console.error("Error en verificación de super usuario:", error);
      esSuperUsuarioActual = false;
      idUsuarioActual = 0;
      
      // Aún así intentar inicializar la tabla en caso de error
      inicializarTablaEmpleados();
    });
  });
});

function inicializarTablaEmpleados() {
  if ($.fn.DataTable.isDataTable('#TablaEmpleado')) {
    $('#TablaEmpleado').DataTable().destroy();
  }
  
  tablaEmpleados = $("#TablaEmpleado").DataTable({
      processing: true,
      serverSide: false,
      ajax: {
        url: "./Empleados/getEmpleadoData",
        type: "GET",
        dataSrc: function (json) {
          if (json && Array.isArray(json.data)) {
            return json.data;
          } else {
            console.error("Respuesta del servidor no tiene la estructura esperada:", json);
            $("#TablaEmpleado_processing").css("display", "none");
            Swal.fire({
              icon: "error",
              title: "Error de Datos",
              text: "No se pudieron cargar los datos. Respuesta inválida.",
            });
            return [];
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.error("Error AJAX:", textStatus, errorThrown, jqXHR.responseText);
          $("#TablaEmpleado_processing").css("display", "none");
          Swal.fire({
            icon: "error",
            title: "Error de Comunicación",
            text: "Error al cargar los datos. Por favor, intenta de nuevo.",
          });
        },
      },
      columns: [
        { data: "idempleado", title: "ID", className: "none" },
        { data: "nombre", title: "Nombre", className: "all whitespace-nowrap py-2 px-3 text-gray-700 dt-fixed-col-background" },
        { data: "apellido", title: "Apellido", className: "all whitespace-nowrap py-2 px-3 text-gray-700" },
        { data: "identificacion", title: "Identificación", className: "desktop whitespace-nowrap py-2 px-3 text-gray-700" },
        { data: "telefono_principal", title: "Teléfono", className: "tablet-l whitespace-nowrap py-2 px-3 text-gray-700" },
        { data: "puesto", title: "Puesto", className: "desktop whitespace-nowrap py-2 px-3 text-gray-700" },
        { data: "salario", title: "Salario", className: "none whitespace-nowrap py-2 px-3 text-gray-700" },
        {
          data: "estatus",
          title: "Estatus",
          className: "min-tablet-p text-center py-2 px-3",
          render: function (data, type, row) {
            if (data) {
              const estatusNormalizado = String(data).trim().toUpperCase();
              let badgeClass = "bg-gray-200 text-gray-800";
              if (estatusNormalizado === "ACTIVO") {
                badgeClass = "bg-green-100 text-green-800";
              } else if (estatusNormalizado === "INACTIVO") {
                badgeClass = "bg-red-100 text-red-800";
              }
              return `<span class="${badgeClass} text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap">${data}</span>`;
            }
            return '<span class="text-xs italic text-gray-500">N/A</span>';
          },
        },
        {
          data: null,
          title: "Acciones",
          orderable: false,
          searchable: false,
          className: "all text-center py-2 px-3 w-32",
          render: function (data, type, row) {
            if (!row) return "";
            
            const idempleado = row.idempleado || "";
            const nombreEmpleado = row.nombre || "";
            const apellidoEmpleado = row.apellido || "";
            const estatusEmpleado = row.estatus || "";
            
            // Verificar si el empleado está inactivo
            const esEmpleadoInactivo = estatusEmpleado.toUpperCase() === 'INACTIVO';
            
            let acciones = '<div class="flex justify-center items-center space-x-1">';
            
            // Botón Ver - siempre visible
            acciones += `
              <button class="ver-empleado-btn text-green-600 hover:text-green-700 p-1 transition-colors duration-150" 
                      data-idempleado="${idempleado}" 
                      title="Ver detalles">
                  <i class="fas fa-eye text-sm"></i>
              </button>`;
            
            if (esEmpleadoInactivo) {
              // Para empleados inactivos, mostrar solo el botón de reactivar (solo para super usuario)
              if (esSuperUsuarioActual) {
                acciones += `
                  <button class="reactivar-empleado-btn text-green-600 hover:text-green-700 p-1 transition-colors duration-150" 
                          data-idempleado="${idempleado}" 
                          data-nombre="${nombreEmpleado} ${apellidoEmpleado}" 
                          title="Reactivar empleado">
                      <i class="fas fa-undo text-sm"></i>
                  </button>`;
              }
            } else {
              // Para empleados activos, mostrar botones de editar y eliminar
              acciones += `
                <button class="editar-empleado-btn text-blue-600 hover:text-blue-700 p-1 transition-colors duration-150" 
                        data-idempleado="${idempleado}" 
                        title="Editar empleado">
                    <i class="fas fa-edit text-sm"></i>
                </button>
                <button class="eliminar-empleado-btn text-red-600 hover:text-red-700 p-1 transition-colors duration-150" 
                        data-idempleado="${idempleado}" 
                        data-nombre="${nombreEmpleado} ${apellidoEmpleado}" 
                        title="Eliminar empleado">
                    <i class="fas fa-trash text-sm"></i>
                </button>`;
            }
            
            acciones += '</div>';
            return acciones;
          },
        },
      ],
      language: {
        processing: `
          <div class="fixed inset-0 bg-transparent backdrop-blur-[2px] bg-opacity-40 flex items-center justify-center z-[9999]" style="margin-left:0;">
              <div class="bg-white p-6 rounded-lg shadow-xl flex items-center space-x-3">
                  <i class="fas fa-spinner fa-spin fa-2x text-green-500"></i>
                  <span class="text-lg font-medium text-gray-700">Procesando...</span>
              </div>
          </div>`,
        emptyTable: '<div class="text-center py-4"><i class="fas fa-info-circle fa-2x text-gray-400 mb-2"></i><p class="text-gray-600">No hay empleados disponibles.</p></div>',
        info: "Mostrando _START_ a _END_ de _TOTAL_ empleados",
        infoEmpty: "Mostrando 0 empleados",
        infoFiltered: "(filtrado de _MAX_ empleados totales)",
        lengthMenu: "Mostrar _MENU_ empleados",
        search: "_INPUT_",
        searchPlaceholder: "Buscar empleado...",
        zeroRecords: '<div class="text-center py-4"><i class="fas fa-search fa-2x text-gray-400 mb-2"></i><p class="text-gray-600">No se encontraron coincidencias.</p></div>',
        paginate: { first: '<i class="fas fa-angle-double-left"></i>', last: '<i class="fas fa-angle-double-right"></i>', next: '<i class="fas fa-angle-right"></i>', previous: '<i class="fas fa-angle-left"></i>' },
      },
      destroy: true,
      responsive: {
        details: {
          type: "column",
          target: -1,
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
              return col.hidden && col.title
                ? `<tr data-dt-row="${col.rowIndex}" data-dt-column="${col.columnIndex}" class="bg-gray-50 hover:bg-gray-100">
                     <td class="font-semibold pr-2 py-1.5 text-sm text-gray-700 w-1/3">${col.title}:</td>
                     <td class="py-1.5 text-sm text-gray-900">${col.data}</td>
                   </tr>`
                : "";
            }).join("");
            return data
              ? $('<table class="w-full table-fixed details-table border-t border-gray-200"/>').append(data)
              : false;
          },
        },
      },
      autoWidth: false,
      pageLength: 10,
      lengthMenu: [ [10, 25, 50, -1], [10, 25, 50, "Todos"] ],
      order: [[0, "desc"]],
      scrollX: true,
      fixedColumns: {
          left: 1
      },
      initComplete: function (settings, json) {
        window.tablaEmpleados = this.api();
        
        // Forzar redraw para asegurar que los botones se rendericen correctamente
        setTimeout(() => {
          this.api().draw(false);
        }, 100);
      },
      drawCallback: function (settings) {
        $(settings.nTableWrapper).find('.dataTables_filter input[type="search"]')
          .addClass("py-2 px-3 text-sm border-gray-300 rounded-md focus:ring-green-400 focus:border-green-400 text-gray-700 bg-white")
          .removeClass("form-control-sm");

        var api = new $.fn.dataTable.Api(settings); 

        if (api.fixedColumns && typeof api.fixedColumns === 'function' && api.fixedColumns().relayout) {
          api.fixedColumns().relayout();
        }
      },
    });

    // Event handlers
    $("#TablaEmpleado tbody").on("click", ".ver-empleado-btn", function () {
      const idempleado = $(this).data("idempleado");
      if (idempleado && typeof verEmpleado === "function") {
        verEmpleado(idempleado);
      } else {
        console.error("Función verEmpleado no definida o idempleado no encontrado.", idempleado);
        Swal.fire("Error", "No se pudo obtener el ID del empleado.", "error");
      }
    });

    $("#TablaEmpleado tbody").on("click", ".editar-empleado-btn", function () {
      const idempleado = $(this).data("idempleado");
      if (idempleado && typeof editarEmpleado === "function") {
        editarEmpleado(idempleado);
      } else {
        console.error("Función editarEmpleado no definida o idempleado no encontrado.", idempleado);
        Swal.fire("Error", "No se pudo obtener el ID del empleado.", "error");
      }
    });

    $("#TablaEmpleado tbody").on("click", ".eliminar-empleado-btn", function () {
      const idempleado = $(this).data("idempleado");
      const nombreEmpleado = $(this).data("nombre");
      if (idempleado && typeof eliminarEmpleado === "function") {
        eliminarEmpleado(idempleado, nombreEmpleado);
      } else {
        console.error("Función eliminarEmpleado no definida o idempleado no encontrado.", idempleado);
        Swal.fire("Error", "No se pudo obtener el ID del empleado.", "error");
      }
    });

    // Event handler para reactivar empleados
    $("#TablaEmpleado tbody").on("click", ".reactivar-empleado-btn", function () {
      const idempleado = $(this).data("idempleado");
      const nombreEmpleado = $(this).data("nombre");
      if (idempleado && typeof reactivarEmpleado === "function") {
        reactivarEmpleado(idempleado, nombreEmpleado);
      } else {
        console.error("Función reactivarEmpleado no definida o idempleado no encontrado.", idempleado);
        Swal.fire("Error", "No se pudo obtener el ID del empleado.", "error");
      }
    });

    // Configurar event listeners de modales
    setupModalEventListeners();
}

function setupModalEventListeners() {
  const btnAbrirModal = document.getElementById("abrirModalBtn");
  const formEmpleado = document.getElementById("empleadoForm");
  const btnCerrarModal = document.getElementById("cerrarModalBtn");
  const btnCerrarModalRegistrar = document.getElementById("btnCerrarModalRegistrar");

  if (btnAbrirModal) {
    btnAbrirModal.addEventListener("click", function () {
      abrirModal("empleadoModal");
      if (formEmpleado) formEmpleado.reset();
      limpiarFormulario();
    });
  }

  if (btnCerrarModal) {
    btnCerrarModal.addEventListener("click", function () {
      cerrarModal("empleadoModal");
    });
  }

  if (btnCerrarModalRegistrar) {
    btnCerrarModalRegistrar.addEventListener("click", function () {
      cerrarModal("empleadoModal");
    });
  }

  if (formEmpleado) {
    formEmpleado.addEventListener("submit", function (e) {
      e.preventDefault();
      registrarEmpleado();
    });
  }
}

// ========================================
// FUNCIONES CRUD
// ========================================

function registrarEmpleado() {
  const formData = prepararDatosFormulario();
  const data = {};
  formData.forEach((value, key) => {
    data[key] = value;
  });

  if (!data.nombre || !data.apellido || !data.identificacion) {
    Swal.fire("Error", "Por favor, completa todos los campos obligatorios.", "error");
    return;
  }

  const idempleado = document.getElementById("idempleado")?.value;
  const url = idempleado ? "Empleados/updateEmpleado" : "Empleados/createEmpleado";
  const method = idempleado ? "PUT" : "POST";

  fetch(url, {
    method: method,
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data),
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }
      return response.json();
    })
    .then((result) => {
      if (result.status) {
        Swal.fire({
          icon: "success",
          title: "¡Éxito!",
          text: result.message || "Empleado guardado correctamente.",
          confirmButtonText: "Aceptar",
        }).then(() => {
          recargarTablaEmpleados();
          cerrarModal("empleadoModal");
          limpiarFormulario();
        });
      } else {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: result.message || "No se pudo guardar el empleado.",
          confirmButtonText: "Aceptar",
        });
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Ocurrió un error al procesar la solicitud.",
        confirmButtonText: "Aceptar",
      });
    });
}

function verEmpleado(idempleado) {
  fetch(`Empleados/getEmpleadoById/${idempleado}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      if (!data.status) {
        throw new Error(data.message || "Error al cargar los datos.");
      }

      const empleado = data.data;
      
      // Construir HTML con los detalles del empleado
      const tipoEmpleado = empleado.tipo_empleado || 'OPERARIO';
      const estatusBadge = empleado.estatus === 'ACTIVO' 
        ? '<span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-1 rounded-full">ACTIVO</span>'
        : '<span class="bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-1 rounded-full">INACTIVO</span>';
      
      let detallesHTML = `
        <div class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <p class="text-sm font-semibold text-gray-600">Nombre Completo:</p>
              <p class="text-base text-gray-900">${empleado.nombre || ''} ${empleado.apellido || ''}</p>
            </div>
            <div>
              <p class="text-sm font-semibold text-gray-600">Identificación:</p>
              <p class="text-base text-gray-900">${empleado.identificacion || 'N/A'}</p>
            </div>
            <div>
              <p class="text-sm font-semibold text-gray-600">Tipo de Empleado:</p>
              <p class="text-base text-gray-900">${tipoEmpleado}</p>
            </div>
            <div>
              <p class="text-sm font-semibold text-gray-600">Estatus:</p>
              <p class="text-base">${estatusBadge}</p>
            </div>
          </div>
          
          ${tipoEmpleado === 'ADMINISTRATIVO' ? `
          <hr class="my-4">
          <h4 class="font-semibold text-gray-700 mb-3">Información Administrativa</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <p class="text-sm font-semibold text-gray-600">Género:</p>
              <p class="text-base text-gray-900">${empleado.genero || 'N/A'}</p>
            </div>
            <div>
              <p class="text-sm font-semibold text-gray-600">Fecha de Nacimiento:</p>
              <p class="text-base text-gray-900">${empleado.fecha_nacimiento_formato || empleado.fecha_nacimiento || 'N/A'}</p>
            </div>
            <div>
              <p class="text-sm font-semibold text-gray-600">Teléfono:</p>
              <p class="text-base text-gray-900">${empleado.telefono_principal || 'N/A'}</p>
            </div>
            <div>
              <p class="text-sm font-semibold text-gray-600">Correo Electrónico:</p>
              <p class="text-base text-gray-900">${empleado.correo_electronico || 'N/A'}</p>
            </div>
            <div class="md:col-span-2">
              <p class="text-sm font-semibold text-gray-600">Dirección:</p>
              <p class="text-base text-gray-900">${empleado.direccion || 'N/A'}</p>
            </div>
          </div>
          ` : ''}
          
          <hr class="my-4">
          <h4 class="font-semibold text-gray-700 mb-3">Información Laboral</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <p class="text-sm font-semibold text-gray-600">Puesto:</p>
              <p class="text-base text-gray-900">${empleado.puesto || 'N/A'}</p>
            </div>
            ${tipoEmpleado === 'ADMINISTRATIVO' ? `
            <div>
              <p class="text-sm font-semibold text-gray-600">Salario:</p>
              <p class="text-base text-gray-900">${empleado.salario ? '$' + parseFloat(empleado.salario).toFixed(2) : 'N/A'}</p>
            </div>
            ` : ''}
            <div>
              <p class="text-sm font-semibold text-gray-600">Fecha de Inicio:</p>
              <p class="text-base text-gray-900">${empleado.fecha_inicio_formato || empleado.fecha_inicio || 'N/A'}</p>
            </div>
            ${empleado.fecha_fin ? `
            <div>
              <p class="text-sm font-semibold text-gray-600">Fecha de Fin:</p>
              <p class="text-base text-gray-900">${empleado.fecha_fin_formato || empleado.fecha_fin}</p>
            </div>
            ` : ''}
          </div>
          
          ${empleado.observaciones ? `
          <hr class="my-4">
          <div>
            <p class="text-sm font-semibold text-gray-600 mb-2">Observaciones:</p>
            <p class="text-base text-gray-900 bg-gray-50 p-3 rounded">${empleado.observaciones}</p>
          </div>
          ` : ''}
        </div>
      `;
      
      Swal.fire({
        title: '<strong>Detalles del Empleado</strong>',
        html: detallesHTML,
        width: '800px',
        showCloseButton: true,
        showCancelButton: false,
        confirmButtonText: 'Cerrar',
        confirmButtonColor: '#00c950',
        customClass: {
          popup: 'text-left'
        }
      });
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire({
        icon: "error",
        title: "Error",
        text: error.message || "Ocurrió un error al cargar los datos.",
        confirmButtonText: "Aceptar",
      });
    });
}

function editarEmpleado(idempleado) {
  fetch(`Empleados/getEmpleadoById/${idempleado}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      if (!data.status) {
        throw new Error(data.message || "Error al cargar los datos.");
      }

      const empleado = data.data;
      
      // Asignar campos básicos
      document.getElementById("idempleado").value = empleado.idempleado || "";
      document.getElementById("nombre").value = empleado.nombre || "";
      document.getElementById("apellido").value = empleado.apellido || "";
      document.getElementById("identificacion").value = empleado.identificacion || "";
      
      // Seleccionar el tipo de empleado y actualizar campos
      const tipoEmpleado = empleado.tipo_empleado || 'OPERARIO';
      if (tipoEmpleado === 'OPERARIO') {
        document.getElementById('tipo_operario').checked = true;
      } else {
        document.getElementById('tipo_administrativo').checked = true;
      }
      
      // Trigger del evento change para actualizar visibilidad de campos
      actualizarCamposFormulario();
      
      // Esperar a que se actualicen los campos visibles
      setTimeout(() => {
        if (tipoEmpleado === 'OPERARIO') {
          // Campos de operario
          const puestoOperario = document.getElementById("puesto_operario");
          if (puestoOperario) puestoOperario.value = empleado.puesto || "";
          
          const fechaInicioOperario = document.getElementById("fecha_inicio_operario");
          if (fechaInicioOperario) fechaInicioOperario.value = empleado.fecha_inicio || "";
          
        } else {
          // Campos de administrativo
          const genero = document.getElementById("genero");
          if (genero) genero.value = empleado.genero || "";
          
          const fechaNacimiento = document.getElementById("fecha_nacimiento");
          if (fechaNacimiento) fechaNacimiento.value = empleado.fecha_nacimiento || "";
          
          const telefonoPrincipal = document.getElementById("telefono_principal");
          if (telefonoPrincipal) telefonoPrincipal.value = empleado.telefono_principal || "";
          
          const correo = document.getElementById("correo_electronico");
          if (correo) correo.value = empleado.correo_electronico || "";
          
          const direccion = document.getElementById("direccion");
          if (direccion) direccion.value = empleado.direccion || "";
          
          const puestoAdmin = document.getElementById("puesto_administrativo");
          if (puestoAdmin) puestoAdmin.value = empleado.puesto || "";
          
          const salario = document.getElementById("salario");
          if (salario) salario.value = empleado.salario || "";
          
          const fechaInicioAdmin = document.getElementById("fecha_inicio_admin");
          if (fechaInicioAdmin) fechaInicioAdmin.value = empleado.fecha_inicio || "";
          
          const fechaFin = document.getElementById("fecha_fin");
          if (fechaFin) fechaFin.value = empleado.fecha_fin || "";
        }
        
        // Campo estatus (común para ambos)
        const estatus = document.getElementById("estatus");
        if (estatus) estatus.value = empleado.estatus || "activo";
        
        // Observaciones
        const observaciones = document.getElementById("observaciones");
        if (observaciones) observaciones.value = empleado.observaciones || "";
        
      }, 200);
      
      // Abrir modal
      abrirModal("empleadoModal");
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire({
        icon: "error",
        title: "Error",
        text: error.message || "Ocurrió un error al cargar los datos.",
        confirmButtonText: "Aceptar",
      });
    });
}

function eliminarEmpleado(idempleado, nombreEmpleado) {
  Swal.fire({
    title: '¿Estás seguro?',
    text: `¿Deseas desactivar al empleado ${nombreEmpleado}?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#00c950',
    cancelButtonColor: '#dc2626',
    confirmButtonText: 'Sí, desactivar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      fetch(`Empleados/deleteEmpleado`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ idempleado }),
      })
        .then((response) => response.json())
        .then((result) => {
          if (result.status) {
            Swal.fire({
              icon: "success",
              title: "¡Éxito!",
              text: result.message || "Empleado desactivado correctamente.",
              confirmButtonText: "Aceptar",
            }).then(() => {
              recargarTablaEmpleados();
            });
          } else {
            Swal.fire({
              icon: "error",
              title: "Error",
              text: result.message || "No se pudo desactivar el empleado.",
              confirmButtonText: "Aceptar",
            });
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          Swal.fire({
            icon: "error",
            title: "Error",
            text: "Ocurrió un error al procesar la solicitud.",
            confirmButtonText: "Aceptar",
          });
        });
    }
  });
}

function reactivarEmpleado(idempleado, nombreEmpleado) {
  Swal.fire({
    title: '¿Reactivar empleado?',
    text: `¿Deseas reactivar al empleado ${nombreEmpleado}?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#00c950',
    cancelButtonColor: '#dc2626',
    confirmButtonText: 'Sí, reactivar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      fetch(`Empleados/reactivarEmpleado`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ idempleado }),
      })
        .then((response) => response.json())
        .then((result) => {
          if (result.status) {
            Swal.fire({
              icon: "success",
              title: "¡Éxito!",
              text: result.message || "Empleado reactivado correctamente.",
              confirmButtonText: "Aceptar",
            }).then(() => {
              recargarTablaEmpleados();
            });
          } else {
            Swal.fire({
              icon: "error",
              title: "Error",
              text: result.message || "No se pudo reactivar el empleado.",
              confirmButtonText: "Aceptar",
            });
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          Swal.fire({
            icon: "error",
            title: "Error",
            text: "Error de conexión al reactivar empleado.",
            confirmButtonText: "Aceptar",
          });
        });
    }
  });
}

/**
 * Verificar si el usuario actual es super usuario
 */
function verificarSuperUsuario() {
  return fetch("Empleados/verificarSuperUsuario", {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then(response => {
      return response.json();
    })
    .then(result => {
      if (result.status) {
        esSuperUsuarioActual = result.es_super_usuario;
        idUsuarioActual = result.usuario_id;
        return result;
      } else {
        console.error('Error al verificar super usuario:', result.message);
        esSuperUsuarioActual = false;
        idUsuarioActual = 0;
        return { es_super_usuario: false, usuario_id: 0 };
      }
    })
    .catch(error => {
      console.error("Error en verificarSuperUsuario:", error);
      esSuperUsuarioActual = false;
      idUsuarioActual = 0;
      return { es_super_usuario: false, usuario_id: 0 };
    });
}

// Funciones auxiliares
function abrirModalEmpleado() {
  abrirModal("empleadoModal");
}

function cerrarModalEmpleado() {
  cerrarModal("empleadoModal");
  limpiarFormulario();
}

// Exportar funciones globalmente si es necesario
window.abrirModalEmpleado = abrirModalEmpleado;
window.cerrarModalEmpleado = cerrarModalEmpleado;
window.recargarTablaEmpleados = recargarTablaEmpleados;
