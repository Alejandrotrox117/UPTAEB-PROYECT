import { abrirModal, cerrarModal } from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  limpiarValidaciones,
  registrarEntidad,
} from "./validaciones.js";

let tablaTiposPagos;

const camposFormularioTipoPago = [
  {
    id: "tipoPagoNombre",
    tipo: "input",
    regex: expresiones.nombre,
    mensajes: {
      vacio: "El nombre es obligatorio.",
      formato: "El nombre solo puede contener letras y espacios.",
    },
  }
];

const camposFormularioActualizarTipoPago = [
  {
    id: "tipoPagoNombreActualizar",
    tipo: "input",
    regex: expresiones.nombre,
    mensajes: {
      vacio: "El nombre es obligatorio.",
      formato: "El nombre solo puede contener letras y espacios.",
    },
  }
];

function recargarTablaTiposPagos() {
  try {
    if (tablaTiposPagos && tablaTiposPagos.ajax && typeof tablaTiposPagos.ajax.reload === 'function') {
      console.log("Recargando tabla con variable global");
      tablaTiposPagos.ajax.reload(null, false);
      return true;
    }

    if ($.fn.DataTable.isDataTable('#TablaTiposPagos')) {
      console.log("Recargando tabla con selector ID");
      const tabla = $('#TablaTiposPagos').DataTable();
      tabla.ajax.reload(null, false);
      return true;
    }

    console.log("Recargando página completa");
    window.location.reload();
    return true;

  } catch (error) {
    console.error("Error al recargar tabla:", error);
    window.location.reload();
    return false;
  }
}

document.addEventListener("DOMContentLoaded", function () {
  $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
    if (settings.nTable.id !== "TablaTiposPagos") {
      return true;
    }
    var api = new $.fn.dataTable.Api(settings);
    var rowData = api.row(dataIndex).data();
    return rowData && rowData.estatus && rowData.estatus.toLowerCase() !== "inactivo";
  });

  $(document).ready(function () {
    if ($.fn.DataTable.isDataTable('#TablaTiposPagos')) {
      $('#TablaTiposPagos').DataTable().destroy();
    }
    
    tablaTiposPagos = $("#TablaTiposPagos").DataTable({
      processing: true,
      serverSide: false,
      ajax: {
        url: "./TiposPagos/getTiposPagosData",
        type: "GET",
        dataSrc: function (json) {
          if (json && Array.isArray(json.data)) {
            return json.data;
          } else {
            console.error("Respuesta del servidor no tiene la estructura esperada:", json);
            $("#TablaTiposPagos_processing").css("display", "none");
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
          $("#TablaTiposPagos_processing").css("display", "none");
          Swal.fire({
            icon: "error",
            title: "Error de Comunicación",
            text: "Fallo al cargar datos. Intente más tarde.",
            footer: `Detalle: ${textStatus} - ${errorThrown}`,
          });
        },
      },
      columns: [
        { 
          data: "nombre", 
          title: "Nombre", 
          className: "all whitespace-nowrap py-2 px-3 text-gray-700 dt-fixed-col-background" 
        },
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
          data: "fecha_creacion_formato", 
          title: "Fecha Creación", 
          className: "desktop whitespace-nowrap py-2 px-3 text-gray-700" 
        },
        {
          data: null,
          title: "Acciones",
          orderable: false,
          searchable: false,
          className: "all text-center actions-column py-1 px-2",
          render: function (data, type, row) {
            const idTipoPago = row.idtipo_pago || "";
            const nombreTipoPago = row.nombre || "";
            return `
              <div class="inline-flex items-center space-x-1">
                <button class="ver-tipo-pago-btn text-green-600 hover:text-green-700 p-1 transition-colors duration-150" data-idtipo_pago="${idTipoPago}" title="Ver detalles">
                    <i class="fas fa-eye fa-fw text-base"></i>
                </button>
                <button class="editar-tipo-pago-btn text-blue-600 hover:text-blue-700 p-1 transition-colors duration-150" data-idtipo_pago="${idTipoPago}" title="Editar">
                    <i class="fas fa-edit fa-fw text-base"></i>
                </button>
                <button class="eliminar-tipo-pago-btn text-red-600 hover:text-red-700 p-1 transition-colors duration-150" data-idtipo_pago="${idTipoPago}" data-nombre="${nombreTipoPago}" title="Desactivar">
                    <i class="fas fa-trash-alt fa-fw text-base"></i>
                </button>
              </div>`;
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
        emptyTable: '<div class="text-center py-4"><i class="fas fa-info-circle fa-2x text-gray-400 mb-2"></i><p class="text-gray-600">No hay tipos de pagos disponibles.</p></div>',
        info: "Mostrando _START_ a _END_ de _TOTAL_ tipos de pagos",
        infoEmpty: "Mostrando 0 tipos de pagos",
        infoFiltered: "(filtrado de _MAX_ tipos de pagos totales)",
        lengthMenu: "Mostrar _MENU_ tipos de pagos",
        search: "_INPUT_",
        searchPlaceholder: "Buscar tipo de pago...",
        zeroRecords: '<div class="text-center py-4"><i class="fas fa-search fa-2x text-gray-400 mb-2"></i><p class="text-gray-600">No se encontraron coincidencias.</p></div>',
        paginate: { 
          first: '<i class="fas fa-angle-double-left"></i>', 
          last: '<i class="fas fa-angle-double-right"></i>', 
          next: '<i class="fas fa-angle-right"></i>', 
          previous: '<i class="fas fa-angle-left"></i>' 
        },
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
      order: [[0, "asc"]],
      scrollX: true,
      fixedColumns: {
          left: 1
      },
      initComplete: function (settings, json) {
        console.log("DataTable inicializado correctamente");
        window.tablaTiposPagos = this.api();
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

    $("#TablaTiposPagos tbody").on("click", ".ver-tipo-pago-btn", function () {
      const idTipoPago = $(this).data("idtipo_pago");
      if (idTipoPago && typeof verTipoPago === "function") {
        verTipoPago(idTipoPago);
      } else {
        console.error("Función verTipoPago no definida o idTipoPago no encontrado.", idTipoPago);
        Swal.fire("Error", "No se pudo obtener el ID del tipo de pago.", "error");
      }
    });

    $("#TablaTiposPagos tbody").on("click", ".editar-tipo-pago-btn", function () {
      const idTipoPago = $(this).data("idtipo_pago");
      if (idTipoPago && typeof editarTipoPago === "function") {
        editarTipoPago(idTipoPago);
      } else {
        console.error("Función editarTipoPago no definida o idTipoPago no encontrado.", idTipoPago);
        Swal.fire("Error", "No se pudo obtener el ID del tipo de pago.", "error");
      }
    });

    $("#TablaTiposPagos tbody").on("click", ".eliminar-tipo-pago-btn", function () {
      const idTipoPago = $(this).data("idtipo_pago");
      const nombreTipoPago = $(this).data("nombre");
      if (idTipoPago && typeof eliminarTipoPago === "function") {
        eliminarTipoPago(idTipoPago, nombreTipoPago);
      } else {
        console.error("Función eliminarTipoPago no definida o idTipoPago no encontrado.", idTipoPago);
        Swal.fire("Error", "No se pudo obtener el ID del tipo de pago.", "error");
      }
    });
  });

  
  const btnAbrirModalRegistro = document.getElementById("btnAbrirModalRegistrarTipoPago");
  const formRegistrar = document.getElementById("formRegistrarTipoPago");
  const btnCerrarModalRegistro = document.getElementById("btnCerrarModalRegistrar");
  const btnCancelarModalRegistro = document.getElementById("btnCancelarModalRegistrar");

  if (btnAbrirModalRegistro) {
    btnAbrirModalRegistro.addEventListener("click", function () {
      abrirModal("modalRegistrarTipoPago");
      if (formRegistrar) formRegistrar.reset();
      inicializarValidaciones(camposFormularioTipoPago, "formRegistrarTipoPago");
    });
  }

  if (btnCerrarModalRegistro) {
    btnCerrarModalRegistro.addEventListener("click", function () {
      cerrarModal("modalRegistrarTipoPago");
    });
  }

  if (btnCancelarModalRegistro) {
    btnCancelarModalRegistro.addEventListener("click", function () {
      cerrarModal("modalRegistrarTipoPago");
    });
  }

  
  if (formRegistrar) {
    formRegistrar.addEventListener("submit", function (e) {
      e.preventDefault();
      registrarTipoPago();
    });
  }

  
  const btnCerrarModalActualizar = document.getElementById("btnCerrarModalActualizar");
  const btnCancelarModalActualizar = document.getElementById("btnCancelarModalActualizar");
  const formActualizar = document.getElementById("formActualizarTipoPago");

  if (btnCerrarModalActualizar) {
    btnCerrarModalActualizar.addEventListener("click", function () {
      cerrarModal("modalActualizarTipoPago");
    });
  }

  if (btnCancelarModalActualizar) {
    btnCancelarModalActualizar.addEventListener("click", function () {
      cerrarModal("modalActualizarTipoPago");
    });
  }

  if (formActualizar) {
    formActualizar.addEventListener("submit", function (e) {
      e.preventDefault();
      actualizarTipoPago();
    });
  }

  
  const btnCerrarModalVer = document.getElementById("btnCerrarModalVer");
  const btnCerrarModalVer2 = document.getElementById("btnCerrarModalVer2");

  if (btnCerrarModalVer) {
    btnCerrarModalVer.addEventListener("click", function () {
      cerrarModal("modalVerTipoPago");
    });
  }

  if (btnCerrarModalVer2) {
    btnCerrarModalVer2.addEventListener("click", function () {
      cerrarModal("modalVerTipoPago");
    });
  }
});


function registrarTipoPago() {
  const btnGuardarTipoPago = document.getElementById("btnGuardarTipoPago");

  if (btnGuardarTipoPago) {
    btnGuardarTipoPago.disabled = true;
    btnGuardarTipoPago.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...`;
  }
   
  registrarEntidad({
    formId: "formRegistrarTipoPago",
    endpoint: "TiposPagos/createTipoPago",
    campos: camposFormularioTipoPago,
    mapeoNombres: {
      "tipoPagoNombre": "nombre"
    },
    onSuccess: (result) => {
      Swal.fire("¡Éxito!", result.message, "success").then(() => {
        cerrarModal("modalRegistrarTipoPago");
        recargarTablaTiposPagos();
        
        const formRegistrar = document.getElementById("formRegistrarTipoPago");
        if (formRegistrar) {
          formRegistrar.reset();
          limpiarValidaciones(camposFormularioTipoPago, "formRegistrarTipoPago");
        }
      });
    },
    onError: (result) => {
      Swal.fire(
        "Error",
        result.message || "No se pudo registrar el tipo de pago.",
        "error"
      );
    }
  }).finally(() => {
    if (btnGuardarTipoPago) {
      btnGuardarTipoPago.disabled = false;
      btnGuardarTipoPago.innerHTML = `<i class="fas fa-save mr-2"></i> Guardar Tipo de Pago`;
    }
  });
}

function editarTipoPago(idTipoPago) {
  fetch(`TiposPagos/getTipoPagoById/${idTipoPago}`, {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const tipoPago = result.data;
        mostrarModalEditarTipoPago(tipoPago);
      } else {
        Swal.fire("Error", "No se pudieron cargar los datos.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    });
}

function mostrarModalEditarTipoPago(tipoPago) {
  document.getElementById("idTipoPagoActualizar").value = tipoPago.idtipo_pago || "";
  document.getElementById("tipoPagoNombreActualizar").value = tipoPago.nombre || "";

  inicializarValidaciones(camposFormularioActualizarTipoPago, "formActualizarTipoPago");
  abrirModal("modalActualizarTipoPago");
}

function actualizarTipoPago() {
  const btnActualizarTipoPago = document.getElementById("btnActualizarTipoPago");

  if (btnActualizarTipoPago) {
    btnActualizarTipoPago.disabled = true;
    btnActualizarTipoPago.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Actualizando...`;
  }

  registrarEntidad({
    formId: "formActualizarTipoPago",
    endpoint: "TiposPagos/updateTipoPago",
    campos: camposFormularioActualizarTipoPago,
    mapeoNombres: {
      "idTipoPagoActualizar": "idtipo_pago",
      "tipoPagoNombreActualizar": "nombre"
    },
    onSuccess: (result) => {
      Swal.fire("¡Éxito!", result.message, "success").then(() => {
        cerrarModal("modalActualizarTipoPago");
        recargarTablaTiposPagos();

        const formActualizar = document.getElementById("formActualizarTipoPago");
        if (formActualizar) {
          formActualizar.reset();
          limpiarValidaciones(camposFormularioActualizarTipoPago, "formActualizarTipoPago");
        }
      });
    },
    onError: (result) => {
      Swal.fire(
        "Error",
        result.message || "No se pudo actualizar el tipo de pago.",
        "error"
      );
    }
  }).finally(() => {
    if (btnActualizarTipoPago) {
      btnActualizarTipoPago.disabled = false;
      btnActualizarTipoPago.innerHTML = `<i class="fas fa-save mr-2"></i> Actualizar Tipo de Pago`;
    }
  });
}

function verTipoPago(idTipoPago) {
  fetch(`TiposPagos/getTipoPagoById/${idTipoPago}`, {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const tipoPago = result.data;
        mostrarModalVerTipoPago(tipoPago);
      } else {
        Swal.fire("Error", "No se pudieron cargar los datos.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    });
}

function mostrarModalVerTipoPago(tipoPago) {
  document.getElementById("verTipoPagoNombre").textContent = tipoPago.nombre || "N/A";
  document.getElementById("verTipoPagoEstatus").textContent = tipoPago.estatus || "N/A";
  document.getElementById("verTipoPagoFechaCreacion").textContent = tipoPago.fecha_creacion_formato || "N/A";
  document.getElementById("verTipoPagoFechaModificacion").textContent = tipoPago.fecha_modificacion_formato || "N/A";

  abrirModal("modalVerTipoPago");
}

function eliminarTipoPago(idTipoPago, nombreTipoPago) {
  Swal.fire({
    title: "¿Estás seguro?",
    text: `¿Deseas desactivar el tipo de pago "${nombreTipoPago}"? Esta acción cambiará su estatus a INACTIVO.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#dc2626",
    cancelButtonColor: "#00c950",
    confirmButtonText: "Sí, desactivar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      const dataParaEnviar = {
        idtipo_pago: idTipoPago,
      };

      fetch("TiposPagos/deleteTipoPago", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify(dataParaEnviar),
      })
        .then((response) => response.json())
        .then((result) => {
          if (result.status) {
            Swal.fire("¡Desactivado!", result.message, "success").then(() => {
              recargarTablaTiposPagos();
            });
          } else {
            Swal.fire(
              "Error",
              result.message || "No se pudo desactivar el tipo de pago.",
              "error"
            );
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          Swal.fire("Error", "Error de conexión.", "error");
        });
    }
  });
}


window.verTipoPago = verTipoPago;
window.editarTipoPago = editarTipoPago;
window.eliminarTipoPago = eliminarTipoPago;