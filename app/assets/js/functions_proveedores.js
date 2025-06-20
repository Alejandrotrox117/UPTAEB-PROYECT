import { abrirModal, cerrarModal } from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  limpiarValidaciones,
  registrarEntidad,
} from "./validaciones.js";

let tablaProveedores;

const camposFormularioProveedor = [
  {id: "proveedorNombre",tipo: "input",regex: expresiones.nombre,
    mensajes: {
      vacio: "El nombre es obligatorio.",
      formato: "El nombre solo puede contener letras y espacios.",
    },
  },
  {id: "proveedorApellido",tipo: "input",regex: expresiones.apellido,
    mensajes: {
      vacio: "El apellido es obligatorio.",
      formato: "El apellido solo puede contener letras y espacios.",
    },
  },
  {id: "proveedorIdentificacion",tipo: "input",regex: expresiones.cedula,
    mensajes: {
      vacio: "La identificación es obligatoria.",
      formato: "Formato de identificación inválido. Debe contener el formato V/J/E Ejemplo V-12345678 o J-12345678.",
    },
  },
  {id: "proveedorTelefono",tipo: "input",regex: expresiones.telefono_principal,
    mensajes: {
      vacio: "El teléfono es obligatorio.",
      formato: "Formato de teléfono inválido. Debe ser 0414-0424-0412-0416-0426",
    },
  },
  {id: "proveedorCorreo",tipo: "input",regex: expresiones.email,
    mensajes: {
      formato: "Formato de correo electrónico inválido.",
    },
  },
  {id: "proveedorDireccion",tipo: "textarea",regex: expresiones.textoGeneral,
    mensajes: {
      formato: "Dirección inválida.",
    },
  },
  {
    id: "proveedorGenero",
    tipo: "select",
    regex: expresiones.genero,
    mensajes: {
      formato: "Género inválido.",
    },
  },

  {
    id: "proveedorFechaNacimiento",
    tipo: "fechaNacimiento",
    mensajes: {
      vacio: "La fecha de nacimiento es obligatoria.",
      fechaPosterior: "La fecha de nacimiento no puede ser superior a hoy.",
    },
  }
];

const camposFormularioActualizarProveedor = [
  {id: "proveedorNombreActualizar",tipo: "input",regex: expresiones.nombre,
    mensajes: {
      vacio: "El nombre es obligatorio.",
      formato: "El nombre solo puede contener letras y espacios.",
    },
  },
  {id: "proveedorApellidoActualizar",tipo: "input",regex: expresiones.apellido,
    mensajes: {
      vacio: "El apellido es obligatorio.",
      formato: "El apellido solo puede contener letras y espacios.",
    },
  },
  {id: "proveedorIdentificacionActualizar",tipo: "input",regex: expresiones.cedula,
    mensajes: {
      vacio: "La identificación es obligatoria.",
      formato: "Formato de identificación inválido.",
    },
  },
  {id: "proveedorTelefonoActualizar",tipo: "input",regex: expresiones.telefono_principal,
    mensajes: {
      vacio: "El teléfono es obligatorio.",
      formato: "Formato de teléfono inválido.",
    },
  },
  {id: "proveedorCorreoActualizar",tipo: "input",regex: expresiones.email,
    mensajes: {
      formato: "Formato de correo electrónico inválido.",
    },
  },
  {id: "proveedorDireccionActualizar",tipo: "textarea",regex: expresiones.textoGeneral,
    mensajes: {
      formato: "Dirección inválida.",
    },
  },
  {id: "proveedorObservacionesActualizar",tipo: "textarea",regex: expresiones.textoGeneral,
    mensajes: {
      formato: "Observaciones inválidas.",
    },
  },
  {
    id: "proveedorGeneroActualizar",
    tipo: "select",
    regex: expresiones.genero,
    mensajes: {
      formato: "Género inválido.",
    },
  },

  {
    id: "proveedorFechaNacimientoActualizar",
    tipo: "fechaNacimiento",
    mensajes: {
      vacio: "La fecha de nacimiento es obligatoria.",
      fechaPosterior: "La fecha de nacimiento no puede ser posterior a hoy.",
    },
  }
];


function recargarTablaProveedores() {
  try {
    if (tablaProveedores && tablaProveedores.ajax && typeof tablaProveedores.ajax.reload === 'function') {
      console.log("Recargando tabla con variable global");
      tablaProveedores.ajax.reload(null, false);
      return true;
    }

    if ($.fn.DataTable.isDataTable('#TablaProveedores')) {
      console.log("Recargando tabla con selector ID");
      const tabla = $('#TablaProveedores').DataTable();
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
    if (settings.nTable.id !== "TablaProveedores") {
      return true;
    }
    var api = new $.fn.dataTable.Api(settings);
    var rowData = api.row(dataIndex).data();
    return rowData && rowData.estatus && rowData.estatus.toLowerCase() !== "inactivo";
  });

  $(document).ready(function () {
    if ($.fn.DataTable.isDataTable('#TablaProveedores')) {
      $('#TablaProveedores').DataTable().destroy();
    }
    tablaProveedores = $("#TablaProveedores").DataTable({
      processing: true,
      serverSide: false,
      ajax: {
        url: "./Proveedores/getProveedoresData",
        type: "GET",
        dataSrc: function (json) {
          if (json && Array.isArray(json.data)) {
            return json.data;
          } else {
            console.error("Respuesta del servidor no tiene la estructura esperada:", json);
            $("#TablaProveedores_processing").css("display", "none");
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
          $("#TablaProveedores_processing").css("display", "none");
          Swal.fire({
            icon: "error",
            title: "Error de Comunicación",
            text: "Fallo al cargar datos. Intente más tarde.",
            footer: `Detalle: ${textStatus} - ${errorThrown}`,
          });
        },
      },
      columns: [
        { data: "nombre", title: "Nombre", className: "all whitespace-nowrap py-2 px-3 text-gray-700 dt-fixed-col-background" },
        { data: "apellido", title: "Apellido", className: "all whitespace-nowrap py-2 px-3 text-gray-700" },
        { data: "identificacion", title: "Identificación", className: "desktop whitespace-nowrap py-2 px-3 text-gray-700" },
        { data: "telefono_principal", title: "Teléfono", className: "tablet-l whitespace-nowrap py-2 px-3 text-gray-700" },
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
          className: "all text-center actions-column py-1 px-2",
          render: function (data, type, row) {
            const idProveedor = row.idproveedor || "";
            const nombreCompleto = `${row.nombre || ""} ${row.apellido || ""}`.trim();
            return `
              <div class="inline-flex items-center space-x-1">
                <button class="ver-proveedor-btn text-green-600 hover:text-green-700 p-1 transition-colors duration-150" data-idproveedor="${idProveedor}" title="Ver detalles">
                    <i class="fas fa-eye fa-fw text-base"></i>
                </button>
                <button class="editar-proveedor-btn text-blue-600 hover:text-blue-700 p-1 transition-colors duration-150" data-idproveedor="${idProveedor}" title="Editar">
                    <i class="fas fa-edit fa-fw text-base"></i>
                </button>
                <button class="eliminar-proveedor-btn text-red-600 hover:text-red-700 p-1 transition-colors duration-150" data-idproveedor="${idProveedor}" data-nombre="${nombreCompleto}" title="Desactivar">
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
        emptyTable: '<div class="text-center py-4"><i class="fas fa-info-circle fa-2x text-gray-400 mb-2"></i><p class="text-gray-600">No hay proveedores disponibles.</p></div>',
        info: "Mostrando _START_ a _END_ de _TOTAL_ proveedores",
        infoEmpty: "Mostrando 0 proveedores",
        infoFiltered: "(filtrado de _MAX_ proveedores totales)",
        lengthMenu: "Mostrar _MENU_ proveedores",
        search: "_INPUT_",
        searchPlaceholder: "Buscar proveedor...",
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
      order: [[0, "asc"]],
      scrollX: true,
      fixedColumns: {
          left: 1
      },
      initComplete: function (settings, json) {
        console.log("DataTable inicializado correctamente");te
        window.tablaProveedores = this.api();
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

    $("#TablaProveedores tbody").on("click", ".ver-proveedor-btn", function () {
      const idProveedor = $(this).data("idproveedor");
      if (idProveedor && typeof verProveedor === "function") {
        verProveedor(idProveedor);
      } else {
        console.error("Función verProveedor no definida o idProveedor no encontrado.", idProveedor);
        Swal.fire("Error", "No se pudo obtener el ID del proveedor.", "error");
      }
    });

    $("#TablaProveedores tbody").on("click", ".editar-proveedor-btn", function () {
      const idProveedor = $(this).data("idproveedor");
      if (idProveedor && typeof editarProveedor === "function") {
        editarProveedor(idProveedor);
      } else {
        console.error("Función editarProveedor no definida o idProveedor no encontrado.", idProveedor);
        Swal.fire("Error", "No se pudo obtener el ID del proveedor.", "error");
      }
    });

    $("#TablaProveedores tbody").on(
      "click",
      ".eliminar-proveedor-btn",
      function () {
        const idProveedor = $(this).data("idproveedor");
        const nombreProveedor = $(this).data("nombre");
        if (idProveedor && typeof eliminarProveedor === "function") {
          eliminarProveedor(idProveedor, nombreProveedor);
        } else {
          console.error("Función eliminarProveedor no definida o idProveedor no encontrado.", idProveedor);
          Swal.fire("Error", "No se pudo obtener el ID del proveedor.", "error");
        }
      }
    );
  });

  
  const btnAbrirModalRegistro = document.getElementById(
    "btnAbrirModalRegistrarProveedor"
  );
  const formRegistrar = document.getElementById("formRegistrarProveedor");
  const btnCerrarModalRegistro = document.getElementById(
    "btnCerrarModalRegistrar"
  );
  const btnCancelarModalRegistro = document.getElementById(
    "btnCancelarModalRegistrar"
  );

  if (btnAbrirModalRegistro) {
    btnAbrirModalRegistro.addEventListener("click", function () {
      abrirModal("modalRegistrarProveedor");
      if (formRegistrar) formRegistrar.reset();
      inicializarValidaciones(
        camposFormularioProveedor,
        "formRegistrarProveedor"
      );
    });
  }

  if (btnCerrarModalRegistro) {
    btnCerrarModalRegistro.addEventListener("click", function () {
      cerrarModal("modalRegistrarProveedor");
    });
  }

  if (btnCancelarModalRegistro) {
    btnCancelarModalRegistro.addEventListener("click", function () {
      cerrarModal("modalRegistrarProveedor");
    });
  }

  
  if (formRegistrar) {
    formRegistrar.addEventListener("submit", function (e) {
      e.preventDefault();
      registrarProveedor();
    });
  }

  
  const btnCerrarModalActualizar = document.getElementById(
    "btnCerrarModalActualizar"
  );
  const btnCancelarModalActualizar = document.getElementById(
    "btnCancelarModalActualizar"
  );
  const formActualizar = document.getElementById("formActualizarProveedor");

  if (btnCerrarModalActualizar) {
    btnCerrarModalActualizar.addEventListener("click", function () {
      cerrarModal("modalActualizarProveedor");
    });
  }

  if (btnCancelarModalActualizar) {
    btnCancelarModalActualizar.addEventListener("click", function () {
      cerrarModal("modalActualizarProveedor");
    });
  }

  if (formActualizar) {
    formActualizar.addEventListener("submit", function (e) {
      e.preventDefault();
      actualizarProveedor();
    });
  }

  
  const btnCerrarModalVer = document.getElementById("btnCerrarModalVer");
  const btnCerrarModalVer2 = document.getElementById("btnCerrarModalVer2");

  if (btnCerrarModalVer) {
    btnCerrarModalVer.addEventListener("click", function () {
      cerrarModal("modalVerProveedor");
    });
  }

  if (btnCerrarModalVer2) {
    btnCerrarModalVer2.addEventListener("click", function () {
      cerrarModal("modalVerProveedor");
    });
  }
});


function registrarProveedor() {
  const btnGuardarProveedor = document.getElementById("btnGuardarProveedor");

  if (btnGuardarProveedor) {
    btnGuardarProveedor.disabled = true;
    btnGuardarProveedor.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...`;
  }
   
  registrarEntidad({
    formId: "formRegistrarProveedor",
    endpoint: "Proveedores/createProveedor",
    campos: camposFormularioProveedor,
    mapeoNombres: {
  
      "proveedorNombre": "nombre",
      "proveedorApellido": "apellido",
      "proveedorIdentificacion": "identificacion",
      "proveedorTelefono": "telefono_principal",
      "proveedorCorreo": "correo_electronico",
      "proveedorDireccion": "direccion",
      "proveedorFechaNacimiento": "fecha_nacimiento"
    },

    onSuccess: (result) => {
      Swal.fire("¡Éxito!", result.message, "success").then(() => {
        cerrarModal("modalRegistrarProveedor");
        recargarTablaProveedores();
        
        const formRegistrar = document.getElementById("formRegistrarProveedor");
        if (formRegistrar) {
          formRegistrar.reset();
          limpiarValidaciones(camposFormularioProveedor, "formRegistrarProveedor");
        }
      });
    },
    onError: (result) => {
      Swal.fire(
        "Error",
        result.message || "No se pudo registrar el proveedor.",
        "error"
      );
    }
  }).finally(() => {
    if (btnGuardarProveedor) {
      btnGuardarProveedor.disabled = false;
      btnGuardarProveedor.innerHTML = `<i class="fas fa-save mr-2"></i> Guardar Proveedor`;
    }
  });
}


function editarProveedor(idProveedor) {
  fetch(`Proveedores/getProveedorById/${idProveedor}`, {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const proveedor = result.data;
        mostrarModalEditarProveedor(proveedor);
      } else {
        Swal.fire("Error", "No se pudieron cargar los datos.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    });
}

function mostrarModalEditarProveedor(proveedor) {
  
  document.getElementById("idProveedorActualizar").value =
    proveedor.idproveedor || "";
  document.getElementById("proveedorNombreActualizar").value =
    proveedor.nombre || "";
  document.getElementById("proveedorApellidoActualizar").value =
    proveedor.apellido || "";
  document.getElementById("proveedorIdentificacionActualizar").value =
    proveedor.identificacion || "";
  document.getElementById("proveedorTelefonoActualizar").value =
    proveedor.telefono_principal || "";
  document.getElementById("proveedorFechaNacimientoActualizar").value =
    proveedor.fecha_nacimiento || "";
  document.getElementById("proveedorGeneroActualizar").value =
    proveedor.genero || "";
  document.getElementById("proveedorCorreoActualizar").value =
    proveedor.correo_electronico || "";
  document.getElementById("proveedorDireccionActualizar").value =
    proveedor.direccion || "";
  document.getElementById("proveedorObservacionesActualizar").value =
    proveedor.observaciones || "";

  
  inicializarValidaciones(
    camposFormularioActualizarProveedor,
    "formActualizarProveedor"
  );

  abrirModal("modalActualizarProveedor");
}

function actualizarProveedor() {
  const btnActualizarProveedor = document.getElementById("btnActualizarProveedor");

  if (btnActualizarProveedor) {
    btnActualizarProveedor.disabled = true;
    btnActualizarProveedor.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Actualizando...`;
  }

  registrarEntidad({
    formId: "formActualizarProveedor",
    endpoint: "Proveedores/updateProveedor",
    campos: camposFormularioActualizarProveedor,
    mapeoNombres: {
      "idProveedorActualizar": "idproveedor",
      "proveedorNombreActualizar": "nombre",
      "proveedorApellidoActualizar": "apellido",
      "proveedorIdentificacionActualizar": "identificacion",
      "proveedorTelefonoActualizar": "telefono_principal",
      "proveedorCorreoActualizar": "correo_electronico",
      "proveedorDireccionActualizar": "direccion",
      "proveedorFechaNacimientoActualizar": "fecha_nacimiento",
      "proveedorGeneroActualizar": "genero",
      "proveedorObservacionesActualizar": "observaciones"
    },
    onSuccess: (result) => {
      Swal.fire("¡Éxito!", result.message, "success").then(() => {
        cerrarModal("modalActualizarProveedor");
        recargarTablaProveedores();

        const formActualizar = document.getElementById("formActualizarProveedor");
        if (formActualizar) {
          formActualizar.reset();
          limpiarValidaciones(camposFormularioActualizarProveedor, "formActualizarProveedor");
        }
      });
    },
    onError: (result) => {
      Swal.fire(
        "Error",
        result.message || "No se pudo actualizar el proveedor.",
        "error"
      );
    }
  }).finally(() => {
    if (btnActualizarProveedor) {
      btnActualizarProveedor.disabled = false;
      btnActualizarProveedor.innerHTML = `<i class="fas fa-save mr-2"></i> Actualizar Proveedor`;
    }
  });
}
function verProveedor(idProveedor) {
  fetch(`Proveedores/getProveedorById/${idProveedor}`, {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const proveedor = result.data;
        mostrarModalVerProveedor(proveedor);
      } else {
        Swal.fire("Error", "No se pudieron cargar los datos.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    });
}

function mostrarModalVerProveedor(proveedor) {
  document.getElementById("verProveedorNombre").textContent =
    proveedor.nombre || "N/A";
  document.getElementById("verProveedorApellido").textContent =
    proveedor.apellido || "N/A";
  document.getElementById("verProveedorIdentificacion").textContent =
    proveedor.identificacion || "N/A";
  document.getElementById("verProveedorTelefono").textContent =
    proveedor.telefono_principal || "N/A";
  document.getElementById("verProveedorFechaNacimiento").textContent =
    proveedor.fecha_nacimiento_formato || "N/A";
  document.getElementById("verProveedorGenero").textContent =
    proveedor.genero || "N/A";
  document.getElementById("verProveedorCorreo").textContent =
    proveedor.correo_electronico || "Sin correo";
  document.getElementById("verProveedorDireccion").textContent =
    proveedor.direccion || "Sin dirección";
  document.getElementById("verProveedorObservaciones").textContent =
    proveedor.observaciones || "Sin observaciones";
  document.getElementById("verProveedorEstatus").textContent =
    proveedor.estatus || "N/A";

  abrirModal("modalVerProveedor");
}

function eliminarProveedor(idProveedor, nombreProveedor) {
  Swal.fire({
    title: "¿Estás seguro?",
    text: `¿Deseas desactivar al proveedor "${nombreProveedor}"? Esta acción cambiará su estatus a INACTIVO.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Sí, desactivar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      const dataParaEnviar = {
        idproveedor: idProveedor,
      };

      fetch("Proveedores/deleteProveedor", {
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
              recargarTablaProveedores();
            });
          } else {
            Swal.fire(
              "Error",
              result.message || "No se pudo desactivar al proveedor.",
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