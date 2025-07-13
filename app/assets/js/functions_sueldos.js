import { abrirModal, cerrarModal } from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  limpiarValidaciones,
  registrarEntidad,
} from "./validaciones.js";

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
    id: "sueldoObservacionActualizar",
    tipo: "textarea",
    regex: expresiones.textoGeneral,
    mensajes: {
      formato: "Observaciones inválidas.",
    },
  },
];

document.addEventListener("DOMContentLoaded", function () {
  verificarSuperUsuario();
  initTablaSueldos();
  initEventListeners();
  cargarPersonasYEmpleados();
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
          return new Intl.NumberFormat("es-VE", {
            style: "currency",
            currency: "VES",
          }).format(data);
        },
      },
      {
        data: "balance",
        title: "Balance",
        className: "desktop whitespace-nowrap py-2 px-3 text-gray-700",
        render: function (data, type, row) {
          return new Intl.NumberFormat("es-VE", {
            style: "currency",
            currency: "VES",
          }).format(data);
        },
      },
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
      },
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
  const observacion = document.getElementById("sueldoObservacion").value;

  if (!tipoPersona || !personaEmpleadoId || !monto) {
    Swal.fire("Error", "Por favor complete todos los campos obligatorios", "error");
    return;
  }

  const datos = {
    monto: parseFloat(monto),
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
  const observacion = document.getElementById("sueldoObservacionActualizar").value;

  if (!tipoPersona || !personaEmpleadoId || !monto) {
    Swal.fire("Error", "Por favor complete todos los campos obligatorios", "error");
    return;
  }

  const datos = {
    idsueldo: parseInt(idsueldo),
    monto: parseFloat(monto),
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

// Exponer funciones globalmente
window.editarSueldo = editarSueldo;
window.verSueldo = verSueldo;
window.eliminarSueldo = eliminarSueldo;
window.reactivarSueldo = reactivarSueldo;
