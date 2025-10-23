import { abrirModal, cerrarModal } from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  limpiarValidaciones,
  registrarEntidad,
} from "./validaciones.js";

// Obtener base_url de la variable global o construirla dinámicamente como fallback
const base_url = window.base_url || (window.location.protocol + "//" + window.location.host + "/project");

let tablaSueldos;
let esSuperUsuarioActual = false;
let idUsuarioActual = 0;

const camposFormularioSueldo = [
  {
    id: "tipoPersona",
    tipo: "select",
    mensajes: {
      vacio: "Debe seleccionar un tipo de persona.",
    },
  },
  {
    id: "sueldoPersonaEmpleado",
    tipo: "select",
    mensajes: {
      vacio: "Debe seleccionar una persona o empleado.",
    },
  },
  {
    id: "sueldoMonto",
    tipo: "input",
    regex: expresiones.decimal2,
    mensajes: {
      vacio: "El monto es obligatorio.",
      formato: "El monto debe ser un número válido mayor a 0.",
    },
  },
  {
    id: "sueldoMoneda",
    tipo: "select",
    mensajes: {
      vacio: "Debe seleccionar una moneda.",
    },
  },
  {
    id: "sueldoObservacion",
    tipo: "textarea",
    regex: expresiones.textoGeneral,
    mensajes: {
      formato: "Observaciones inválidas.",
    },
  },
];

const camposFormularioActualizarSueldo = [
  {
    id: "tipoPersonaActualizar",
    tipo: "select",
    mensajes: {
      vacio: "Debe seleccionar un tipo de persona.",
    },
  },
  {
    id: "sueldoPersonaEmpleadoActualizar",
    tipo: "select",
    mensajes: {
      vacio: "Debe seleccionar una persona o empleado.",
    },
  },
  {
    id: "sueldoMontoActualizar",
    tipo: "input",
    regex: expresiones.decimal2,
    mensajes: {
      vacio: "El monto es obligatorio.",
      formato: "El monto debe ser un número válido mayor a 0.",
    },
  },
  {
    id: "sueldoMonedaActualizar",
    tipo: "select",
    mensajes: {
      vacio: "Debe seleccionar una moneda.",
    },
  },
  {
    id: "sueldoObservacionActualizar",
    tipo: "textarea",
    regex: expresiones.textoGeneral,
    mensajes: {
      formato: "Observaciones inválidas.",
    },
  },
];

document.addEventListener("DOMContentLoaded", function () {
  console.log("Functions_sueldos.js - DOM cargado");
  console.log("Base URL:", base_url);
  
  verificarSuperUsuario();
  initTablaSueldos();
  initEventListeners();
  cargarPersonasYEmpleados();
  cargarMonedas();
  
  console.log("Functions_sueldos.js - Todas las funciones inicializadas");
});

function verificarSuperUsuario() {
  fetch(base_url + "/sueldos/verificarSuperUsuario", {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
    },
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status) {
        esSuperUsuarioActual = data.es_super_usuario;
        idUsuarioActual = data.usuario_id;
        console.log("Estado super usuario:", esSuperUsuarioActual);
      }
    })
    .catch((error) => {
      console.error("Error verificando super usuario:", error);
    });
}

function initTablaSueldos() {
  if ($.fn.DataTable.isDataTable("#TablaSueldos")) {
    $("#TablaSueldos").DataTable().destroy();
  }

  tablaSueldos = $("#TablaSueldos").DataTable({
    aProcessing: true,
    aServerSide: false,
    ajax: {
      url: base_url + "/sueldos/getSueldosData",
      dataSrc: function (json) {
        if (json.status === false) {
          Swal.fire("Error", json.message, "error");
          return [];
        }
        return json.data || [];
      },
    },
    columns: [
      {
        data: "fecha_creacion_formato",
        title: "Fecha Registro",
        className: "min-tablet-p whitespace-nowrap py-2 px-3 text-gray-700",
      },
      {
        data: null,
        title: "Beneficiario",
        className: "all whitespace-nowrap py-2 px-3 text-gray-700",
        render: function (data, type, row) {
          // Debug log
          console.log("Rendering beneficiario for row:", row);
          
          // Determinar basándose en los IDs, no en los nombres
          if (row.idpersona && row.nombre_persona && row.nombre_persona.trim() !== '') {
            return `${row.nombre_persona} (${row.identificacion_persona || 'Sin ID'})`;
          } else if (row.idempleado && row.nombre_empleado && row.nombre_empleado.trim() !== '') {
            return `${row.nombre_empleado} (${row.identificacion_empleado || 'Sin ID'})`;
          } else if (row.idpersona) {
            return `<span class="text-red-600">Persona ID: ${row.idpersona} (datos no encontrados)</span>`;
          } else if (row.idempleado) {
            return `<span class="text-red-600">Empleado ID: ${row.idempleado} (datos no encontrados)</span>`;
          }
          return `<span class="text-gray-500">N/A</span>`;
        },
      },
      {
        data: null,
        title: "Tipo",
        className: "desktop whitespace-nowrap py-2 px-3 text-gray-700",
        render: function (data, type, row) {
          // Determinar basándose en los IDs, no en los nombres
          if (row.idpersona) {
            return '<span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap">Persona</span>';
          } else if (row.idempleado) {
            return '<span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap">Empleado</span>';
          }
          return "N/A";
        },
      },
      {
        data: "monto",
        title: "Monto",
        className: "desktop whitespace-nowrap py-2 px-3 text-gray-700",
        render: function (data, type, row) {
          const codigo_moneda = row.codigo_moneda || 'VES';
          return new Intl.NumberFormat("es-VE", {
            style: "decimal",
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          }).format(data) + ' ' + codigo_moneda;
        },
      },/*
      {
        data: "balance",
        title: "Balance",
        className: "desktop whitespace-nowrap py-2 px-3 text-gray-700",
        render: function (data, type, row) {
          const codigo_moneda = row.codigo_moneda || 'VES';
          return new Intl.NumberFormat("es-VE", {
            style: "decimal",
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          }).format(data) + ' ' + codigo_moneda;
        },
      },*/
      {
        data: "estatus",
        title: "Estatus",
        className: "min-tablet-p text-center py-2 px-3",
        render: function (data, type, row) {
          if (data) {
            const estatusNormalizado = String(data).trim().toUpperCase();
            let badgeClass = "bg-gray-200 text-gray-800";
            if (estatusNormalizado === "POR_PAGAR") {
              badgeClass = "bg-yellow-100 text-yellow-800";
            } else if (estatusNormalizado === "PAGO_FRACCIONADO") {
              badgeClass = "bg-blue-100 text-blue-800";
            } else if (estatusNormalizado === "PAGADO") {
              badgeClass = "bg-green-100 text-green-800";
            } else if (estatusNormalizado === "INACTIVO") {
              badgeClass = "bg-red-100 text-red-800";
            }
            return `<span class="${badgeClass} text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap">${data}</span>`;
          }
          return '<span class="text-xs italic text-gray-500">N/A</span>';
        },
      },/*
      {
        data: null,
        title: "Pago",
        className: "desktop text-center py-2 px-3",
        render: function (data, type, row) {
          if (!row) return "";
          
          const estatus = row.estatus || "";
          const idsueldo = row.idsueldo || "";
          
          // Solo mostrar botón de pago si el sueldo está por pagar o con pago fraccionado
          if (estatus === "POR_PAGAR" || estatus === "PAGO_FRACCIONADO") {
            return `
              <button class="pagar-sueldo-btn bg-green-500 hover:bg-green-600 text-white text-xs px-3 py-1 rounded-full transition-colors duration-150" 
                      data-idsueldo="${idsueldo}" 
                      title="Realizar pago">
                  <i class="fas fa-dollar-sign mr-1"></i>Pagar
              </button>`;
          } else if (estatus === "PAGADO") {
            return '<span class="text-green-600 text-xs font-semibold"><i class="fas fa-check-circle mr-1"></i>Pagado</span>';
          } else {
            return '<span class="text-gray-400 text-xs">-</span>';
          }
        },
      },*/
      {
        data: null,
        title: "Acciones",
        orderable: false,
        searchable: false,
        className: "all text-center py-2 px-3 w-32",
        render: function (data, type, row) {
          if (!row) return "";
          
          const idSueldo = row.idsueldo || "";
          
          // Determinar el nombre basándose en los IDs, no solo en los nombres
          let nombreCompleto = "Sin nombre";
          if (row.idpersona && row.nombre_persona) {
            nombreCompleto = row.nombre_persona;
          } else if (row.idempleado && row.nombre_empleado) {
            nombreCompleto = row.nombre_empleado;
          } else if (row.idpersona) {
            nombreCompleto = `Persona ID: ${row.idpersona}`;
          } else if (row.idempleado) {
            nombreCompleto = `Empleado ID: ${row.idempleado}`;
          }
          
          const estatusSueldo = row.estatus || "";
          
          // Verificar si el sueldo está inactivo
          const esSueldoInactivo = estatusSueldo.toUpperCase() === 'INACTIVO';
          
          let acciones = '<div class="flex justify-center items-center space-x-1">';
          
          // Botón Ver - siempre visible
          acciones += `
            <button class="ver-sueldo-btn text-green-600 hover:text-green-700 p-1 transition-colors duration-150" 
                    data-idsueldo="${idSueldo}" 
                    title="Ver detalles">
                <i class="fas fa-eye text-sm"></i>
            </button>`;
          
          if (esSueldoInactivo) {
            // Para sueldos inactivos, mostrar solo el botón de reactivar (solo super usuarios)
            if (esSuperUsuarioActual) {
              acciones += `
                <button class="reactivar-sueldo-btn text-green-600 hover:text-green-700 p-1 transition-colors duration-150" 
                        data-idsueldo="${idSueldo}" 
                        data-nombre="${nombreCompleto}" 
                        title="Reactivar sueldo">
                    <i class="fas fa-undo text-sm"></i>
                </button>`;
            }
          } else {
            // Para sueldos activos, mostrar botones de editar y eliminar
            acciones += `
              <button class="editar-sueldo-btn text-blue-600 hover:text-blue-700 p-1 transition-colors duration-150" 
                      data-idsueldo="${idSueldo}" 
                      title="Editar sueldo">
                  <i class="fas fa-edit text-sm"></i>
              </button>
              <button class="eliminar-sueldo-btn text-red-600 hover:text-red-700 p-1 transition-colors duration-150" 
                      data-idsueldo="${idSueldo}" 
                      data-nombre="${nombreCompleto}" 
                      title="Inactivar sueldo">
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
      emptyTable: '<div class="text-center py-4"><i class="fas fa-info-circle fa-2x text-gray-400 mb-2"></i><p class="text-gray-600">No hay sueldos disponibles.</p></div>',
      info: "Mostrando _START_ a _END_ de _TOTAL_ sueldos",
      infoEmpty: "Mostrando 0 sueldos",
      infoFiltered: "(filtrado de _MAX_ sueldos totales)",
      lengthMenu: "Mostrar _MENU_ sueldos",
      search: "_INPUT_",
      searchPlaceholder: "Buscar sueldo...",
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
      window.tablaSueldos = this.api();
      
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

  // Event handlers para los botones de acciones
  $("#TablaSueldos tbody").on("click", ".ver-sueldo-btn", function () {
    const idSueldo = $(this).data("idsueldo");
    if (idSueldo && typeof verSueldo === "function") {
      verSueldo(idSueldo);
    } else {
      console.error("Función verSueldo no definida o idSueldo no encontrado.", idSueldo);
      Swal.fire("Error", "No se pudo obtener el ID del sueldo.", "error");
    }
  });

  $("#TablaSueldos tbody").on("click", ".editar-sueldo-btn", function () {
    const idSueldo = $(this).data("idsueldo");
    if (idSueldo && typeof editarSueldo === "function") {
      editarSueldo(idSueldo);
    } else {
      console.error("Función editarSueldo no definida o idSueldo no encontrado.", idSueldo);
      Swal.fire("Error", "No se pudo obtener el ID del sueldo.", "error");
    }
  });

  $("#TablaSueldos tbody").on("click", ".pagar-sueldo-btn", function () {
    const idSueldo = $(this).data("idsueldo");
    if (idSueldo) {
      abrirModalPagoSueldo(idSueldo);
    } else {
      console.error("ID de sueldo no encontrado.");
      Swal.fire("Error", "No se pudo obtener el ID del sueldo.", "error");
    }
  });

  $("#TablaSueldos tbody").on("click", ".eliminar-sueldo-btn", function () {
    const idSueldo = $(this).data("idsueldo");
    const nombreSueldo = $(this).data("nombre");
    if (idSueldo && typeof eliminarSueldo === "function") {
      eliminarSueldo(idSueldo, nombreSueldo);
    } else {
      console.error("Función eliminarSueldo no definida o idSueldo no encontrado.", idSueldo);
      Swal.fire("Error", "No se pudo obtener el ID del sueldo.", "error");
    }
  });

  // Event handler para reactivar sueldos
  $("#TablaSueldos tbody").on("click", ".reactivar-sueldo-btn", function () {
    const idSueldo = $(this).data("idsueldo");
    const nombreSueldo = $(this).data("nombre");
    if (idSueldo && typeof reactivarSueldo === "function") {
      reactivarSueldo(idSueldo, nombreSueldo);
    } else {
      console.error("Función reactivarSueldo no definida o idSueldo no encontrado.", idSueldo);
      Swal.fire("Error", "No se pudo obtener el ID del sueldo.", "error");
    }
  });

  // Evento para ver historial de pagos
  $("#TablaSueldos tbody").on("click", ".historial-pagos-btn", function () {
    const idSueldo = $(this).data("idsueldo");
    if (idSueldo) {
      verHistorialPagos(idSueldo);
    }
  });
}

function initEventListeners() {
  // Modal registrar sueldo
  document
    .getElementById("btnAbrirModalRegistrarSueldo")
    .addEventListener("click", function () {
      limpiarFormulario("formRegistrarSueldo");
      limpiarValidaciones(camposFormularioSueldo, "formRegistrarSueldo");
      abrirModal("modalRegistrarSueldo");
      inicializarValidaciones(camposFormularioSueldo, "formRegistrarSueldo");
    });

  document
    .getElementById("btnCerrarModalRegistrar")
    .addEventListener("click", function () {
      cerrarModal("modalRegistrarSueldo");
    });

  document
    .getElementById("btnCancelarRegistroSueldo")
    .addEventListener("click", function () {
      cerrarModal("modalRegistrarSueldo");
    });

  // Modal actualizar sueldo
  document
    .getElementById("btnCerrarModalActualizar")
    .addEventListener("click", function () {
      cerrarModal("modalActualizarSueldo");
    });

  document
    .getElementById("btnCancelarActualizacionSueldo")
    .addEventListener("click", function () {
      cerrarModal("modalActualizarSueldo");
    });

  // Modal ver sueldo
  document
    .getElementById("btnCerrarModalVer")
    .addEventListener("click", function () {
      cerrarModal("modalVerSueldo");
    });

  document
    .getElementById("btnCerrarVer")
    .addEventListener("click", function () {
      cerrarModal("modalVerSueldo");
    });

  // Modal pagar sueldo
  document
    .getElementById("btnCerrarModalPagar")
    .addEventListener("click", function () {
      cerrarModal("modalPagarSueldo");
    });

  document
    .getElementById("btnCancelarPago")
    .addEventListener("click", function () {
      cerrarModal("modalPagarSueldo");
    });

  // Formulario registrar
  document
    .getElementById("formRegistrarSueldo")
    .addEventListener("submit", function (e) {
      e.preventDefault();
      registrarSueldo();
    });

  // Formulario actualizar
  document
    .getElementById("formActualizarSueldo")
    .addEventListener("submit", function (e) {
      e.preventDefault();
      actualizarSueldo();
    });

  // Formulario pagar sueldo
  document
    .getElementById("formPagarSueldo")
    .addEventListener("submit", function (e) {
      e.preventDefault();
      procesarPagoSueldo();
    });

  // Cambio de tipo de persona
  document
    .getElementById("tipoPersona")
    .addEventListener("change", function () {
      cargarPersonasEmpleadosPorTipo(this.value, "sueldoPersonaEmpleado");
    });

  document
    .getElementById("tipoPersonaActualizar")
    .addEventListener("change", function () {
      cargarPersonasEmpleadosPorTipo(
        this.value,
        "sueldoPersonaEmpleadoActualizar"
      );
    });
}

function cargarPersonasYEmpleados() {
  // Cargar personas
  fetch(base_url + "/sueldos/getPersonasActivas")
    .then((response) => response.json())
    .then((data) => {
      if (data.status) {
        window.personasActivas = data.data;
      }
    })
    .catch((error) => {
      console.error("Error cargando personas:", error);
    });

  // Cargar empleados
  fetch(base_url + "/sueldos/getEmpleadosActivos")
    .then((response) => response.json())
    .then((data) => {
      if (data.status) {
        window.empleadosActivos = data.data;
      }
    })
    .catch((error) => {
      console.error("Error cargando empleados:", error);
    });
}

function cargarMonedas() {
  console.log("Cargando monedas...");
  fetch(base_url + "/sueldos/getMonedas")
    .then((response) => {
      console.log("Response monedas:", response);
      return response.json();
    })
    .then((data) => {
      console.log("Datos monedas recibidos:", data);
      if (data.status) {
        const selectRegistrar = document.getElementById("sueldoMoneda");
        const selectActualizar = document.getElementById("sueldoMonedaActualizar");
        
        console.log("Selects encontrados:", {
          registrar: !!selectRegistrar,
          actualizar: !!selectActualizar
        });
        
        if (selectRegistrar && selectActualizar) {
          // Limpiar opciones existentes
          selectRegistrar.innerHTML = '<option value="">Seleccionar moneda...</option>';
          selectActualizar.innerHTML = '<option value="">Seleccionar moneda...</option>';
          
          // Agregar opciones de monedas
          data.data.forEach(moneda => {
            const optionRegistrar = document.createElement("option");
            optionRegistrar.value = moneda.idmoneda;
            optionRegistrar.textContent = `${moneda.codigo_moneda} - ${moneda.nombre_moneda}`;
            selectRegistrar.appendChild(optionRegistrar);
            
            const optionActualizar = document.createElement("option");
            optionActualizar.value = moneda.idmoneda;
            optionActualizar.textContent = `${moneda.codigo_moneda} - ${moneda.nombre_moneda}`;
            selectActualizar.appendChild(optionActualizar);
          });
          
          console.log("Monedas cargadas exitosamente");
        } else {
          console.error("No se encontraron los selects de moneda");
        }
      } else {
        console.error("Error en respuesta:", data.message);
      }
    })
    .catch((error) => {
      console.error("Error cargando monedas:", error);
    });
}

function cargarPersonasEmpleadosPorTipo(tipo, selectId) {
  const select = document.getElementById(selectId);
  select.innerHTML = '<option value="">Seleccionar...</option>';
  select.disabled = false;

  if (tipo === "persona" && window.personasActivas) {
    window.personasActivas.forEach((persona) => {
      const option = document.createElement("option");
      option.value = persona.idpersona;
      option.textContent = `${persona.nombre_completo} (${persona.identificacion})`;
      select.appendChild(option);
    });
  } else if (tipo === "empleado" && window.empleadosActivos) {
    window.empleadosActivos.forEach((empleado) => {
      const option = document.createElement("option");
      option.value = empleado.idempleado;
      option.textContent = `${empleado.nombre_completo} (${empleado.identificacion}) - ${empleado.puesto}`;
      select.appendChild(option);
    });
  } else {
    select.disabled = true;
    select.innerHTML = '<option value="">Seleccionar primero el tipo...</option>';
  }
}

function registrarSueldo() {
  const tipoPersona = document.getElementById("tipoPersona").value;
  const personaEmpleadoId = document.getElementById("sueldoPersonaEmpleado").value;
  const monto = document.getElementById("sueldoMonto").value;
  const idmoneda = document.getElementById("sueldoMoneda").value;
  const observacion = document.getElementById("sueldoObservacion").value;

  if (!tipoPersona || !personaEmpleadoId || !monto || !idmoneda) {
    Swal.fire("Error", "Por favor complete todos los campos obligatorios", "error");
    return;
  }

  const datos = {
    monto: parseFloat(monto),
    idmoneda: parseInt(idmoneda),
    observacion: observacion,
  };

  // Log para debugging
  console.log("Tipo seleccionado:", tipoPersona);
  console.log("ID seleccionado:", personaEmpleadoId);

  if (tipoPersona === "persona") {
    datos.idpersona = parseInt(personaEmpleadoId);
    datos.idempleado = null; // Asegurar que el otro campo esté null
  } else if (tipoPersona === "empleado") {
    datos.idempleado = parseInt(personaEmpleadoId);
    datos.idpersona = null; // Asegurar que el otro campo esté null
  } else {
    Swal.fire("Error", "Tipo de persona inválido", "error");
    return;
  }

  // Log para verificar datos finales
  console.log("Datos a enviar:", datos);

  const btnGuardar = document.getElementById("btnGuardarSueldo");
  btnGuardar.disabled = true;
  btnGuardar.textContent = "Guardando...";

  fetch(base_url + "/sueldos/createSueldo", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(datos),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status) {
        Swal.fire("Éxito", data.message, "success");
        tablaSueldos.ajax.reload();
        cerrarModal("modalRegistrarSueldo");
      } else {
        Swal.fire("Error", data.message, "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión", "error");
    })
    .finally(() => {
      btnGuardar.disabled = false;
      btnGuardar.textContent = "Registrar Sueldo";
    });
}

function editarSueldo(idSueldo) {
  fetch(base_url + "/sueldos/getSueldoById/" + idSueldo)
    .then((response) => response.json())
    .then((data) => {
      if (data.status) {
        mostrarModalEditarSueldo(data.data);
      } else {
        Swal.fire("Error", data.message, "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error al obtener los datos del sueldo", "error");
    });
}

function mostrarModalEditarSueldo(sueldo) {
  // Llenar formulario
  document.getElementById("idSueldoActualizar").value = sueldo.idsueldo || "";
  document.getElementById("sueldoMontoActualizar").value = sueldo.monto || "";
  document.getElementById("sueldoMonedaActualizar").value = sueldo.idmoneda || "";
  document.getElementById("sueldoObservacionActualizar").value = sueldo.observacion || "";

  // Determinar tipo y cargar select
  if (sueldo.idpersona) {
    document.getElementById("tipoPersonaActualizar").value = "persona";
    cargarPersonasEmpleadosPorTipo("persona", "sueldoPersonaEmpleadoActualizar");
    setTimeout(() => {
      document.getElementById("sueldoPersonaEmpleadoActualizar").value = sueldo.idpersona;
    }, 100);
  } else if (sueldo.idempleado) {
    document.getElementById("tipoPersonaActualizar").value = "empleado";
    cargarPersonasEmpleadosPorTipo("empleado", "sueldoPersonaEmpleadoActualizar");
    setTimeout(() => {
      document.getElementById("sueldoPersonaEmpleadoActualizar").value = sueldo.idempleado;
    }, 100);
  }

  // Inicializar validaciones
  inicializarValidaciones(
    camposFormularioActualizarSueldo,
    "formActualizarSueldo"
  );

  abrirModal("modalActualizarSueldo");
}

function actualizarSueldo() {
  const tipoPersona = document.getElementById("tipoPersonaActualizar").value;
  const personaEmpleadoId = document.getElementById("sueldoPersonaEmpleadoActualizar").value;
  const idsueldo = document.getElementById("idSueldoActualizar").value;
  const monto = document.getElementById("sueldoMontoActualizar").value;
  const idmoneda = document.getElementById("sueldoMonedaActualizar").value;
  const observacion = document.getElementById("sueldoObservacionActualizar").value;

  if (!tipoPersona || !personaEmpleadoId || !monto || !idmoneda) {
    Swal.fire("Error", "Por favor complete todos los campos obligatorios", "error");
    return;
  }

  const datos = {
    idsueldo: parseInt(idsueldo),
    monto: parseFloat(monto),
    idmoneda: parseInt(idmoneda),
    observacion: observacion,
  };

  // Log para debugging
  console.log("Actualizando - Tipo seleccionado:", tipoPersona);
  console.log("Actualizando - ID seleccionado:", personaEmpleadoId);

  if (tipoPersona === "persona") {
    datos.idpersona = parseInt(personaEmpleadoId);
    datos.idempleado = null; // Asegurar que el otro campo esté null
  } else if (tipoPersona === "empleado") {
    datos.idempleado = parseInt(personaEmpleadoId);
    datos.idpersona = null; // Asegurar que el otro campo esté null
  } else {
    Swal.fire("Error", "Tipo de persona inválido", "error");
    return;
  }

  // Log para verificar datos finales
  console.log("Datos a enviar para actualización:", datos);

  const btnActualizar = document.getElementById("btnActualizarSueldo");
  btnActualizar.disabled = true;
  btnActualizar.textContent = "Actualizando...";

  fetch(base_url + "/sueldos/updateSueldo", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(datos),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status) {
        Swal.fire("Éxito", data.message, "success");
        tablaSueldos.ajax.reload();
        cerrarModal("modalActualizarSueldo");
      } else {
        Swal.fire("Error", data.message, "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión", "error");
    })
    .finally(() => {
      btnActualizar.disabled = false;
      btnActualizar.textContent = "Actualizar Sueldo";
    });
}

function verSueldo(idSueldo) {
  fetch(base_url + "/sueldos/getSueldoById/" + idSueldo)
    .then((response) => response.json())
    .then((data) => {
      if (data.status) {
        mostrarModalVerSueldo(data.data);
      } else {
        Swal.fire("Error", data.message, "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error al obtener los datos del sueldo", "error");
    });
}

function mostrarModalVerSueldo(sueldo) {
  const contenido = `
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="bg-gray-50 p-4 rounded-lg">
        <h4 class="font-semibold text-gray-700 mb-2">ID del Sueldo</h4>
        <p class="text-gray-600">${sueldo.idsueldo}</p>
      </div>
      <div class="bg-gray-50 p-4 rounded-lg">
        <h4 class="font-semibold text-gray-700 mb-2">Tipo de Beneficiario</h4>
        <p class="text-gray-600">${sueldo.nombre_persona ? 'Persona' : 'Empleado'}</p>
      </div>
      <div class="bg-gray-50 p-4 rounded-lg">
        <h4 class="font-semibold text-gray-700 mb-2">Beneficiario</h4>
        <p class="text-gray-600">${sueldo.nombre_persona || sueldo.nombre_empleado}</p>
      </div>
      <div class="bg-gray-50 p-4 rounded-lg">
        <h4 class="font-semibold text-gray-700 mb-2">Identificación</h4>
        <p class="text-gray-600">${sueldo.identificacion_persona || sueldo.identificacion_empleado}</p>
      </div>
      ${sueldo.puesto ? `
      <div class="bg-gray-50 p-4 rounded-lg">
        <h4 class="font-semibold text-gray-700 mb-2">Puesto</h4>
        <p class="text-gray-600">${sueldo.puesto}</p>
      </div>
      ` : ''}
      <div class="bg-gray-50 p-4 rounded-lg">
        <h4 class="font-semibold text-gray-700 mb-2">Monto</h4>
        <p class="text-gray-600">${new Intl.NumberFormat("es-VE", {
          style: "currency",
          currency: "VES",
        }).format(sueldo.monto)}</p>
      </div>
      <div class="bg-gray-50 p-4 rounded-lg">
        <h4 class="font-semibold text-gray-700 mb-2">Balance</h4>
        <p class="text-gray-600">${new Intl.NumberFormat("es-VE", {
          style: "currency",
          currency: "VES",
        }).format(sueldo.balance)}</p>
      </div>
      <div class="bg-gray-50 p-4 rounded-lg">
        <h4 class="font-semibold text-gray-700 mb-2">Estatus</h4>
        <p class="text-gray-600">${sueldo.estatus || 'POR_PAGAR'}</p>
      </div>
      <div class="bg-gray-50 p-4 rounded-lg">
        <h4 class="font-semibold text-gray-700 mb-2">Fecha de Registro</h4>
        <p class="text-gray-600">${sueldo.fecha_creacion_formato}</p>
      </div>
      <div class="bg-gray-50 p-4 rounded-lg md:col-span-2">
        <h4 class="font-semibold text-gray-700 mb-2">Observaciones</h4>
        <p class="text-gray-600">${sueldo.observacion || 'Sin observaciones'}</p>
      </div>
    </div>
  `;

  document.getElementById("contenidoModalVer").innerHTML = contenido;
  abrirModal("modalVerSueldo");
}

function eliminarSueldo(idSueldo, nombreBeneficiario) {
  Swal.fire({
    title: "¿Está seguro?",
    text: `¿Desea inactivar el sueldo de "${nombreBeneficiario}"?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Sí, inactivar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      fetch(base_url + "/sueldos/deleteSueldo", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ idsueldo: idSueldo }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.status) {
            Swal.fire("Inactivado", data.message, "success");
            tablaSueldos.ajax.reload();
          } else {
            Swal.fire("Error", data.message, "error");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          Swal.fire("Error", "Error de conexión", "error");
        });
    }
  });
}

function reactivarSueldo(idSueldo, nombreBeneficiario) {
  Swal.fire({
    title: "¿Reactivar sueldo?",
    text: `¿Desea reactivar el sueldo de "${nombreBeneficiario}"?`,
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#28a745",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Sí, reactivar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      fetch(base_url + "/sueldos/reactivarSueldo", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ idsueldo: idSueldo }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.status) {
            Swal.fire("Reactivado", data.message, "success");
            tablaSueldos.ajax.reload();
          } else {
            Swal.fire("Error", data.message, "error");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          Swal.fire("Error", "Error de conexión", "error");
        });
    }
  });
}

function limpiarFormulario(formId) {
  const form = document.getElementById(formId);
  if (form) {
    form.reset();
    
    // Deshabilitar select de persona/empleado
    const selectPersonaEmpleado = form.querySelector('select[name="persona_empleado"]');
    if (selectPersonaEmpleado) {
      selectPersonaEmpleado.disabled = true;
      selectPersonaEmpleado.innerHTML = '<option value="">Seleccionar primero el tipo...</option>';
    }
  }
}

// Función para cargar tipos de pago
async function cargarTiposPago() {
    try {
        const response = await fetch(base_url + '/sueldos/getTiposPagos');
        const data = await response.json();
        
        const selectTipoPago = document.getElementById('tipoPagoPago');
        if (selectTipoPago && data.status) {
            selectTipoPago.innerHTML = '<option value="">Seleccionar tipo de pago</option>';
            
            data.data.forEach(tipo => {
                const option = document.createElement('option');
                option.value = tipo.idtipo_pago;
                option.textContent = tipo.nombre;
                selectTipoPago.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error cargando tipos de pago:', error);
    }
}

// Función para abrir modal de pago de sueldo
function abrirModalPagoSueldo(idSueldo) {
  console.log("Abriendo modal de pago para sueldo:", idSueldo);
  
  // Cargar tipos de pago
  cargarTiposPago();
  
  // Obtener información del sueldo y monto en bolívares
  fetch(base_url + "/sueldos/getMontoBolivares/" + idSueldo)
    .then(response => response.json())
    .then(data => {
      console.log("Datos de conversión:", data);
      
      if (data.status) {
        mostrarModalPagoSueldo(idSueldo, data.data);
      } else {
        Swal.fire("Error", data.message, "error");
      }
    })
    .catch(error => {
      console.error("Error:", error);
      Swal.fire("Error", "Error al obtener información del sueldo", "error");
    });
}

function mostrarModalPagoSueldo(idSueldo, conversionData) {
  // Llenar información del sueldo
  document.getElementById("idSueldoPagar").value = idSueldo;
  document.getElementById("montoTotalBolivares").value = conversionData.monto_bolivares;
  
  // Mostrar información de conversión
  const infoDiv = document.getElementById("infoSueldoPago");
  let infoHTML = `
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
      <div>
        <span class="font-semibold text-gray-700">Monto Original:</span>
        <span class="text-gray-900">${conversionData.monto_original} ${conversionData.codigo_moneda}</span>
      </div>
      <div>
        <span class="font-semibold text-gray-700">Monto en Bolívares:</span>
        <span class="text-green-600 font-bold">${new Intl.NumberFormat('es-VE', {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2
        }).format(conversionData.monto_bolivares)} Bs.</span>
      </div>`;
  
  if (conversionData.codigo_moneda !== 'VES') {
    infoHTML += `
      <div>
        <span class="font-semibold text-gray-700">Tasa de Cambio:</span>
        <span class="text-blue-600">${conversionData.tasa_cambio} Bs./${conversionData.codigo_moneda}</span>
      </div>
      <div>
        <span class="font-semibold text-gray-700">Fecha Tasa:</span>
        <span class="text-gray-900">${conversionData.fecha_tasa}</span>
      </div>`;
  }
  
  infoHTML += `</div>`;
  infoDiv.innerHTML = infoHTML;
  
  // Establecer monto por defecto
  document.getElementById("montoPago").value = conversionData.monto_bolivares;
  document.getElementById("montoPago").setAttribute("max", conversionData.monto_bolivares);
  
  // Establecer fecha actual
  document.getElementById("fechaPagoPago").value = new Date().toISOString().split('T')[0];
  
  // Cargar tipos de pago
  cargarTiposPago();
  
  // Abrir modal
  abrirModal("modalPagarSueldo");
}

// Función para procesar el pago del sueldo
function procesarPagoSueldo() {
    const formPagoSueldo = document.getElementById('formPagarSueldo');
    if (!formPagoSueldo) {
        console.error('Formulario de pago no encontrado');
        return;
    }

    const formData = new FormData(formPagoSueldo);
    
    // Validar campos requeridos
    const requiredFields = ['idsueldo', 'monto', 'idtipo_pago', 'fecha_pago'];
    for (const field of requiredFields) {
        if (!formData.get(field)) {
            fntSweetAlert('error', `El campo ${field} es requerido`, '');
            return;
        }
    }

    // Validar monto
    const monto = parseFloat(formData.get('monto'));
    if (isNaN(monto) || monto <= 0) {
        fntSweetAlert('error', 'El monto debe ser un número mayor a 0', '');
        return;
    }

    // Crear objeto con los datos
    const data = {
        idsueldo: formData.get('idsueldo'),
        monto: monto,
        idtipo_pago: formData.get('idtipo_pago'),
        fecha_pago: formData.get('fecha_pago'),
        referencia: formData.get('referencia') || '',
        observaciones: formData.get('observaciones') || ''
    };

    // Mostrar confirmación
    Swal.fire({
        title: '¿Confirmar pago?',
        text: `¿Está seguro de procesar el pago por ${monto.toLocaleString('es-VE', {
            style: 'currency',
            currency: 'VES'
        })}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, procesar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Realizar petición AJAX
            const ajaxRequest = new XMLHttpRequest();
            ajaxRequest.open('POST', base_url + '/sueldos/procesarPagoSueldo', true);
            ajaxRequest.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            const postData = Object.keys(data).map(key => 
                encodeURIComponent(key) + '=' + encodeURIComponent(data[key])
            ).join('&');
            
            ajaxRequest.onreadystatechange = function() {
                if (ajaxRequest.readyState === 4) {
                    if (ajaxRequest.status === 200) {
                        try {
                            const response = JSON.parse(ajaxRequest.responseText);
                            
                            if (response.status) {
                                fntSweetAlert('success', 'Pago procesado exitosamente', response.message);
                                
                                // Cerrar modal
                                const modal = document.getElementById('modalPagarSueldo');
                                if (modal) {
                                    const bsModal = bootstrap.Modal.getInstance(modal);
                                    if (bsModal) {
                                        bsModal.hide();
                                    }
                                }
                                
                                // Recargar tabla
                                if (typeof tableSueldos !== 'undefined') {
                                    tableSueldos.ajax.reload();
                                }
                                
                                // Limpiar formulario
                                formPagoSueldo.reset();
                                
                            } else {
                                fntSweetAlert('error', 'Error al procesar pago', response.message);
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            fntSweetAlert('error', 'Error de respuesta del servidor', '');
                        }
                    } else {
                        fntSweetAlert('error', 'Error de conexión', 'No se pudo conectar con el servidor');
                    }
                }
            };
            
            ajaxRequest.send(postData);
        }
    });
}

// Función para mostrar historial de pagos de un sueldo
function verHistorialPagos(idSueldo) {
    fetch(base_url + "/sueldos/getPagosSueldo?idsueldo=" + idSueldo)
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                mostrarModalHistorialPagos(idSueldo, data.data);
            } else {
                Swal.fire("Error", data.message, "error");
            }
        })
        .catch(error => {
            console.error("Error:", error);
            Swal.fire("Error", "Error al obtener historial de pagos", "error");
        });
}

function mostrarModalHistorialPagos(idSueldo, pagos) {
    let html = `
        <div class="p-4">
            <h4 class="text-lg font-bold mb-4">Historial de Pagos - Sueldo #${idSueldo}</h4>
            <div class="max-h-96 overflow-y-auto">
    `;
    
    if (pagos.length === 0) {
        html += `
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-inbox text-4xl mb-4"></i>
                <p>No se han registrado pagos para este sueldo</p>
            </div>
        `;
    } else {
        html += `
            <div class="space-y-3">
        `;
        
        pagos.forEach(pago => {
            const estatusClass = pago.estatus === 'activo' ? 'bg-green-100 text-green-800' : 
                               pago.estatus === 'conciliado' ? 'bg-blue-100 text-blue-800' : 
                               'bg-red-100 text-red-800';
                               
            html += `
                <div class="border rounded-lg p-4 bg-gray-50">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <span class="font-semibold text-lg">${Number(pago.monto).toLocaleString('es-VE')} Bs.</span>
                            <span class="ml-2 px-2 py-1 rounded-full text-xs ${estatusClass}">${pago.estatus}</span>
                        </div>
                        <div class="text-sm text-gray-500">
                            Pago #${pago.idpago}
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="font-medium">Tipo:</span> ${pago.tipo_pago || 'N/A'}
                        </div>
                        <div>
                            <span class="font-medium">Fecha:</span> ${pago.fecha_pago_formato}
                        </div>
                        ${pago.referencia ? `
                        <div>
                            <span class="font-medium">Referencia:</span> ${pago.referencia}
                        </div>
                        ` : ''}
                        <div>
                            <span class="font-medium">Registrado:</span> ${pago.fecha_creacion_formato}
                        </div>
                    </div>
                    ${pago.observaciones ? `
                    <div class="mt-2 text-sm">
                        <span class="font-medium">Observaciones:</span> ${pago.observaciones}
                    </div>
                    ` : ''}
                </div>
            `;
        });
        
        html += `</div>`;
    }
    
    html += `
            </div>
        </div>
    `;
    
    Swal.fire({
        title: '',
        html: html,
        width: '600px',
        showCloseButton: true,
        showConfirmButton: false,
        customClass: {
            popup: 'text-left'
        }
    });
}
