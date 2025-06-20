import { abrirModal, cerrarModal } from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  validarCamposVacios,
  validarCampo,
  validarSelect,
  limpiarValidaciones,
} from "./validaciones.js";

let tablaRolesPermisos;

const camposFormularioRolPermiso = [
  {
    id: "rolSelect",
    tipo: "select",
    mensajes: {
      vacio: "Debe seleccionar un rol.",
    },
  },
  {
    id: "permisoSelect",
    tipo: "select",
    mensajes: {
      vacio: "Debe seleccionar un permiso.",
    },
  },
];

const camposFormularioActualizarRolPermiso = [
  {
    id: "rolSelectActualizar",
    tipo: "select",
    mensajes: {
      vacio: "Debe seleccionar un rol.",
    },
  },
  {
    id: "permisoSelectActualizar",
    tipo: "select",
    mensajes: {
      vacio: "Debe seleccionar un permiso.",
    },
  },
];

document.addEventListener("DOMContentLoaded", function () {
  $(document).ready(function () {
  if ($.fn.DataTable.isDataTable("#TablaRolesPermisos")) {
    $("#TablaRolesPermisos").DataTable().destroy();
  }

  tablaRolesPermisos = $("#TablaRolesPermisos").DataTable({
    processing: true,
    ajax: {
      url: "RolesPermisos/getRolesPermisosData",
      type: "GET",
      dataSrc: function (json) {
        if (json && json.data) {
          return json.data;
        } else {
          console.error(
            "La respuesta del servidor no tiene la estructura esperada (falta 'data'):",
            json
          );
          $("#TablaRolesPermisos_processing").css("display", "none"); 
          alert(
            "Error: No se pudieron cargar los datos de asignaciones correctamente."
          );
          return [];
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error(
          "Error AJAX al cargar datos para TablaRolesPermisos: ",
          textStatus,
          errorThrown,
          jqXHR.responseText
        );
        $("#TablaRolesPermisos_processing").css("display", "none");
        alert(
          "Error de comunicación al cargar los datos de asignaciones. Por favor, intente más tarde."
        );
      },
    },
    columns: [
      {
        data: "nombre_rol",
        title: "Rol",
        className:
          "all whitespace-nowrap py-2 px-3 text-gray-700 dt-fixed-col-background",
      },
      {
        data: "descripcion_rol",
        title: "Descripción del Rol",
        className: "desktop py-2 px-3 text-gray-700",
        render: function (data, type, row) {
          if (type === "display" && data && data.length > 40) {
            return (
              '<span title="' +
              data.replace(/"/g, "&quot;") +
              '">' +
              data.substring(0, 40) +
              "...</span>"
            );
          }
          return data || '<i class="text-gray-400">Sin descripción</i>';
        },
      },
      {
        data: "nombre_permiso",
        title: "Permiso",
        className: "all whitespace-nowrap py-2 px-3 text-gray-700",
      },
      {
        data: "estatus_rol",
        title: "Estatus Rol",
        className: "min-tablet-p text-center py-2 px-3",
        render: function (data, type, row) {
          if (data) {
            const estatusUpper = String(data).toUpperCase();
            if (estatusUpper === "ACTIVO") {
              return `<span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap">${data}</span>`;
            } else {
              return `<span class="bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap">${data}</span>`;
            }
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
        width: "auto", 
        render: function (data, type, row) {
          return `
            <div class="inline-flex items-center space-x-1">
              <button class="ver-rolpermiso-btn text-green-600 hover:text-green-700 p-1 transition-colors duration-150" data-idrolpermiso="${row.idrolpermiso}" title="Ver detalles">
                  <i class="fas fa-eye fa-fw text-base"></i>
              </button>
              <button class="editar-rolpermiso-btn text-blue-600 hover:text-blue-700 p-1 transition-colors duration-150" data-idrolpermiso="${row.idrolpermiso}" title="Editar">
                  <i class="fas fa-edit fa-fw text-base"></i>
              </button>
              <button class="eliminar-rolpermiso-btn text-red-600 hover:text-red-700 p-1 transition-colors duration-150" data-idrolpermiso="${row.idrolpermiso}" data-rol="${row.nombre_rol}" data-permiso="${row.nombre_permiso}" title="Eliminar">
                  <i class="fas fa-trash-alt fa-fw text-base"></i>
              </button>
            </div>
          `;
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
      emptyTable:
        '<div class="text-center py-4"><i class="fas fa-info-circle fa-2x text-gray-400 mb-2"></i><p class="text-gray-600">No hay asignaciones disponibles.</p></div>',
      info: "Mostrando _START_ a _END_ de _TOTAL_ asignaciones",
      infoEmpty: "Mostrando 0 asignaciones",
      infoFiltered: "(filtrado de _MAX_ asignaciones totales)",
      lengthMenu: "Mostrar _MENU_ asignaciones",
      search: "_INPUT_",
      searchPlaceholder: "Buscar asignación...",
      zeroRecords:
        '<div class="text-center py-4"><i class="fas fa-search fa-2x text-gray-400 mb-2"></i><p class="text-gray-600">No se encontraron coincidencias.</p></div>',
      paginate: {
        first: '<i class="fas fa-angle-double-left"></i>',
        last: '<i class="fas fa-angle-double-right"></i>',
        next: '<i class="fas fa-angle-right"></i>',
        previous: '<i class="fas fa-angle-left"></i>',
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
            ? $(
                '<table class="w-full table-fixed details-table border-t border-gray-200"/>'
              ).append(data)
            : false;
        },
      },
    },
    autoWidth: false,
    pageLength: 10,
    lengthMenu: [
      [10, 25, 50, -1],
      [10, 25, 50, "Todos"],
    ],
    order: [[0, "asc"]],
    scrollX: true,
    fixedColumns: {
      left: 1,
    },
    className: "compact",
    initComplete: function (settings, json) {
      console.log("DataTable RolesPermisos inicializado correctamente");
      window.tablaRolesPermisos = this.api();
    },
    drawCallback: function (settings) {
      $(settings.nTableWrapper)
        .find('.dataTables_filter input[type="search"]')
        .addClass(
          "py-2 px-3 text-sm border-gray-300 rounded-md focus:ring-green-400 focus:border-green-400 text-gray-700 bg-white"
        )
        .removeClass("form-control-sm");

      var api = new $.fn.dataTable.Api(settings);
      if (
        api.fixedColumns &&
        typeof api.fixedColumns === "function" &&
        api.fixedColumns().relayout
      ) {
        api.fixedColumns().relayout();
      }
    },
  });

  $("#TablaRolesPermisos tbody").on(
    "click",
    ".ver-rolpermiso-btn",
    function () {
      const idRolPermiso = $(this).data("idrolpermiso");
      if (idRolPermiso && typeof verRolPermiso === "function") {
        verRolPermiso(idRolPermiso);
      } else {
        console.error(
          "Función verRolPermiso no definida o idRolPermiso no encontrado."
        );
        alert("Error: No se pudo obtener el ID de la asignación para verla.");
      }
    }
  );

  $("#TablaRolesPermisos tbody").on(
    "click",
    ".editar-rolpermiso-btn",
    function () {
      const idRolPermiso = $(this).data("idrolpermiso");
      if (idRolPermiso && typeof editarRolPermiso === "function") {
        editarRolPermiso(idRolPermiso);
      } else {
        console.error(
          "Función editarRolPermiso no definida o idRolPermiso no encontrado."
        );
        alert(
          "Error: No se pudo obtener el ID de la asignación para editarla."
        );
      }
    }
  );

  $("#TablaRolesPermisos tbody").on(
    "click",
    ".eliminar-rolpermiso-btn",
    function () {
      const idRolPermiso = $(this).data("idrolpermiso");
      const nombreRol = $(this).data("rol");
      const nombrePermiso = $(this).data("permiso");
      if (idRolPermiso && typeof eliminarRolPermiso === "function") {
        eliminarRolPermiso(idRolPermiso, nombreRol, nombrePermiso);
      } else {
        console.error(
          "Función eliminarRolPermiso no definida o idRolPermiso no encontrado."
        );
        alert(
          "Error: No se pudo obtener el ID de la asignación para eliminarla."
        );
      }
    }
  );
  });

  
  const btnAbrirModalRegistro = document.getElementById(
    "btnAbrirModalRegistrarRolPermiso"
  );
  const formRegistrar = document.getElementById("formRegistrarRolPermiso");
  const btnCerrarModalRegistro = document.getElementById(
    "btnCerrarModalRegistrar"
  );
  const btnCancelarModalRegistro = document.getElementById(
    "btnCancelarModalRegistrar"
  );

  if (btnAbrirModalRegistro) {
    btnAbrirModalRegistro.addEventListener("click", function () {
      abrirModal("modalRegistrarRolPermiso");
      if (formRegistrar) formRegistrar.reset();
      cargarRoles();
      cargarPermisos();
      inicializarValidaciones(camposFormularioRolPermiso, "formRegistrarRolPermiso");
    });
  }

  if (btnCerrarModalRegistro) {
    btnCerrarModalRegistro.addEventListener("click", function () {
      cerrarModal("modalRegistrarRolPermiso");
    });
  }

  if (btnCancelarModalRegistro) {
    btnCancelarModalRegistro.addEventListener("click", function () {
      cerrarModal("modalRegistrarRolPermiso");
    });
  }

  
  if (formRegistrar) {
    formRegistrar.addEventListener("submit", function (e) {
      e.preventDefault();
      registrarRolPermiso();
    });
  }

  
  const btnCerrarModalActualizar = document.getElementById(
    "btnCerrarModalActualizar"
  );
  const btnCancelarModalActualizar = document.getElementById(
    "btnCancelarModalActualizar"
  );
  const formActualizar = document.getElementById("formActualizarRolPermiso");

  if (btnCerrarModalActualizar) {
    btnCerrarModalActualizar.addEventListener("click", function () {
      cerrarModal("modalActualizarRolPermiso");
    });
  }

  if (btnCancelarModalActualizar) {
    btnCancelarModalActualizar.addEventListener("click", function () {
      cerrarModal("modalActualizarRolPermiso");
    });
  }

  if (formActualizar) {
    formActualizar.addEventListener("submit", function (e) {
      e.preventDefault();
      actualizarRolPermiso();
    });
  }

  
  const btnCerrarModalVer = document.getElementById("btnCerrarModalVer");
  const btnCerrarModalVer2 = document.getElementById("btnCerrarModalVer2");
  
  if (btnCerrarModalVer) {
    btnCerrarModalVer.addEventListener("click", function () {
      cerrarModal("modalVerRolPermiso");
    });
  }

  if (btnCerrarModalVer2) {
    btnCerrarModalVer2.addEventListener("click", function () {
      cerrarModal("modalVerRolPermiso");
    });
  }
});


function cargarRoles() {
  fetch("RolesPermisos/getRoles", {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        llenarSelectRoles(result.data, "rolSelect");
        llenarSelectRoles(result.data, "rolSelectActualizar");
      }
    })
    .catch((error) => {
      console.error("Error al cargar roles:", error);
    });
}

function cargarPermisos() {
  fetch("RolesPermisos/getPermisos", {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        llenarSelectPermisos(result.data, "permisoSelect");
        llenarSelectPermisos(result.data, "permisoSelectActualizar");
      }
    })
    .catch((error) => {
      console.error("Error al cargar permisos:", error);
    });
}

function llenarSelectRoles(roles, selectId) {
  const select = document.getElementById(selectId);
  if (select) {
    select.innerHTML = '<option value="">Seleccione un rol</option>';
    roles.forEach((rol) => {
      const option = document.createElement("option");
      option.value = rol.idrol;
      option.textContent = `${rol.nombre} - ${rol.descripcion || "Sin descripción"}`;
      select.appendChild(option);
    });
  }
}

function llenarSelectPermisos(permisos, selectId) {
  const select = document.getElementById(selectId);
  if (select) {
    select.innerHTML = '<option value="">Seleccione un permiso</option>';
    permisos.forEach((permiso) => {
      const option = document.createElement("option");
      option.value = permiso.idpermiso;
      option.textContent = permiso.nombre_permiso;
      select.appendChild(option);
    });
  }
}

function registrarRolPermiso() {
  const formRegistrar = document.getElementById("formRegistrarRolPermiso");
  const btnGuardarRolPermiso = document.getElementById("btnGuardarRolPermiso");

  if (!validarCamposVacios(camposFormularioRolPermiso, "formRegistrarRolPermiso")) {
    return;
  }

  let formularioConErroresEspecificos = false;
  for (const campo of camposFormularioRolPermiso) {
    const inputElement = formRegistrar.querySelector(`#${campo.id}`);
    if (!inputElement) continue;

    let esValidoEsteCampo = true;
    if (campo.tipo === "select") {
      if (campo.mensajes && campo.mensajes.vacio) {
        esValidoEsteCampo = validarSelect(
          campo.id,
          campo.mensajes,
          "formRegistrarRolPermiso"
        );
      }
    }
    if (!esValidoEsteCampo) {
      formularioConErroresEspecificos = true;
    }
  }

  if (formularioConErroresEspecificos) {
    Swal.fire("Atención", "Por favor, corrija los campos marcados.", "warning");
    return;
  }

  const formData = new FormData(formRegistrar);
  const dataParaEnviar = {
    idrol: formData.get("idrol") || "",
    idpermiso: formData.get("idpermiso") || "",
  };

  if (btnGuardarRolPermiso) {
    btnGuardarRolPermiso.disabled = true;
    btnGuardarRolPermiso.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...`;
  }

  fetch("RolesPermisos/createRolPermiso", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
    body: JSON.stringify(dataParaEnviar),
  })
    .then((response) => {
      if (!response.ok) {
        return response.json().then((errData) => {
          throw { status: response.status, data: errData };
        });
      }
      return response.json();
    })
    .then((result) => {
      if (result.status) {
        Swal.fire("¡Éxito!", result.message, "success");
        cerrarModal("modalRegistrarRolPermiso");
        if (typeof tablaRolesPermisos !== "undefined" && tablaRolesPermisos.ajax) {
          tablaRolesPermisos.ajax.reload(null, false);
        }
      } else {
        Swal.fire(
          "Error",
          result.message || "No se pudo registrar la asignación.",
          "error"
        );
      }
    })
    .catch((error) => {
      console.error("Error en fetch:", error);
      let errorMessage = "Ocurrió un error de conexión.";
      if (error.data && error.data.message) {
        errorMessage = error.data.message;
      } else if (error.status) {
        errorMessage = `Error del servidor: ${error.status}.`;
      }
      Swal.fire("Error", errorMessage, "error");
    })
    .finally(() => {
      if (btnGuardarRolPermiso) {
        btnGuardarRolPermiso.disabled = false;
        btnGuardarRolPermiso.innerHTML = `<i class="fas fa-save mr-2"></i> Guardar Asignación`;
      }
    });
}

function editarRolPermiso(idRolPermiso) {
  Promise.all([
    fetch(`RolesPermisos/getRolPermisoById/${idRolPermiso}`),
    fetch("RolesPermisos/getRoles"),
    fetch("RolesPermisos/getPermisos")
  ])
    .then(responses => Promise.all(responses.map(r => r.json())))
    .then(([rolPermisoResult, rolesResult, permisosResult]) => {
      if (rolPermisoResult.status && rolPermisoResult.data) {
        const rolPermiso = rolPermisoResult.data;
        
        if (rolesResult.status && rolesResult.data) {
          llenarSelectRoles(rolesResult.data, "rolSelectActualizar");
        }
        
        if (permisosResult.status && permisosResult.data) {
          llenarSelectPermisos(permisosResult.data, "permisoSelectActualizar");
        }
        
        mostrarModalEditarRolPermiso(rolPermiso);
      } else {
        Swal.fire("Error", "No se pudieron cargar los datos.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    });
}

function mostrarModalEditarRolPermiso(rolPermiso) {
  
  document.getElementById("idRolPermisoActualizar").value = rolPermiso.idrolpermiso || "";
  document.getElementById("rolSelectActualizar").value = rolPermiso.idrol || "";
  document.getElementById("permisoSelectActualizar").value = rolPermiso.idpermiso || "";

  
  inicializarValidaciones(
    camposFormularioActualizarRolPermiso,
    "formActualizarRolPermiso"
  );

  abrirModal("modalActualizarRolPermiso");
}

function actualizarRolPermiso() {
  const formActualizar = document.getElementById("formActualizarRolPermiso");
  const btnActualizarRolPermiso = document.getElementById("btnActualizarRolPermiso");
  const idRolPermiso = document.getElementById("idRolPermisoActualizar").value;

  
  const camposObligatorios = camposFormularioActualizarRolPermiso.filter((c) => {
    return c.mensajes && c.mensajes.vacio;
  });

  if (!validarCamposVacios(camposObligatorios, "formActualizarRolPermiso")) {
    return;
  }

  
  let formularioConErroresEspecificos = false;
  for (const campo of camposFormularioActualizarRolPermiso) {
    const inputElement = formActualizar.querySelector(`#${campo.id}`);
    if (!inputElement) continue;

    let esValidoEsteCampo = true;
    if (campo.tipo === "select") {
      if (campo.mensajes && campo.mensajes.vacio) {
        esValidoEsteCampo = validarSelect(
          campo.id,
          campo.mensajes,
          "formActualizarRolPermiso"
        );
      }
    }
    if (!esValidoEsteCampo) {
      formularioConErroresEspecificos = true;
    }
  }

  if (formularioConErroresEspecificos) {
    Swal.fire("Atención", "Por favor, corrija los campos marcados.", "warning");
    return;
  }

  const formData = new FormData(formActualizar);
  const dataParaEnviar = {
    idrolpermiso: idRolPermiso,
    idrol: formData.get("idrol") || "",
    idpermiso: formData.get("idpermiso") || "",
  };

  if (btnActualizarRolPermiso) {
    btnActualizarRolPermiso.disabled = true;
    btnActualizarRolPermiso.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Actualizando...`;
  }

  fetch("RolesPermisos/updateRolPermiso", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
    body: JSON.stringify(dataParaEnviar),
  })
    .then((response) => {
      if (!response.ok) {
        return response.json().then((errData) => {
          throw { status: response.status, data: errData };
        });
      }
      return response.json();
    })
    .then((result) => {
      if (result.status) {
        Swal.fire("¡Éxito!", result.message, "success");
        cerrarModal("modalActualizarRolPermiso");
        if (typeof tablaRolesPermisos !== "undefined" && tablaRolesPermisos.ajax) {
          tablaRolesPermisos.ajax.reload(null, false);
        }
      } else {
        Swal.fire(
          "Error",
          result.message || "No se pudo actualizar la asignación.",
          "error"
        );
      }
    })
    .catch((error) => {
      console.error("Error en fetch:", error);
      let errorMessage = "Ocurrió un error de conexión.";
      if (error.data && error.data.message) {
        errorMessage = error.data.message;
      } else if (error.status) {
        errorMessage = `Error del servidor: ${error.status}.`;
      }
      Swal.fire("Error", errorMessage, "error");
    })
    .finally(() => {
      if (btnActualizarRolPermiso) {
        btnActualizarRolPermiso.disabled = false;
        btnActualizarRolPermiso.innerHTML = `<i class="fas fa-save mr-2"></i> Actualizar Asignación`;
      }
    });
}

function verRolPermiso(idRolPermiso) {
  fetch(`RolesPermisos/getRolPermisoById/${idRolPermiso}`, {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const rolPermiso = result.data;
        mostrarModalVerRolPermiso(rolPermiso);
      } else {
        Swal.fire("Error", "No se pudieron cargar los datos.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    });
}

function mostrarModalVerRolPermiso(rolPermiso) {
  
  document.getElementById("verRolNombre").textContent = rolPermiso.nombre_rol || "N/A";
  document.getElementById("verRolDescripcion").textContent = rolPermiso.descripcion_rol || "Sin descripción";
  document.getElementById("verPermisoNombre").textContent = rolPermiso.nombre_permiso || "N/A";

  abrirModal("modalVerRolPermiso");
}

function eliminarRolPermiso(idRolPermiso, nombreRol, nombrePermiso) {
  Swal.fire({
    title: "¿Estás seguro?",
    text: `¿Deseas eliminar la asignación del rol "${nombreRol}" al permiso "${nombrePermiso}"?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      const dataParaEnviar = {
        idrolpermiso: idRolPermiso,
      };

      fetch("RolesPermisos/deleteRolPermiso", {
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
            Swal.fire("¡Eliminado!", result.message, "success");
            if (typeof tablaRolesPermisos !== "undefined" && tablaRolesPermisos.ajax) {
              tablaRolesPermisos.ajax.reload(null, false);
            }
          } else {
            Swal.fire(
              "Error",
              result.message || "No se pudo eliminar la asignación.",
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
