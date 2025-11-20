import { abrirModal, cerrarModal } from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  validarCamposVacios,
  validarCampo,
  validarSelect,
  limpiarValidaciones,
} from "./validaciones.js";

let tablaRolesModulos;

const camposFormularioRolModulo = [
  {
    id: "rolSelect",
    tipo: "select",
    mensajes: {
      vacio: "Debe seleccionar un rol.",
    },
  },
  {
    id: "moduloSelect",
    tipo: "select",
    mensajes: {
      vacio: "Debe seleccionar un módulo.",
    },
  },
];

const camposFormularioActualizarRolModulo = [
  {
    id: "rolSelectActualizar",
    tipo: "select",
    mensajes: {
      vacio: "Debe seleccionar un rol.",
    },
  },
  {
    id: "moduloSelectActualizar",
    tipo: "select",
    mensajes: {
      vacio: "Debe seleccionar un módulo.",
    },
  },
];

document.addEventListener("DOMContentLoaded", function () {
  $(document).ready(function () {
  if ($.fn.DataTable.isDataTable("#TablaRolesModulos")) {
    $("#TablaRolesModulos").DataTable().destroy();
  }
  tablaRolesModulos = $("#TablaRolesModulos").DataTable({
    processing: true,
    ajax: {
      url: "RolesModulos/getRolesModulosData",
      type: "GET",
      dataSrc: function (json) {
        if (json && json.data) {
          return json.data;
        } else {
          console.error(
            "La respuesta del servidor no tiene la estructura esperada (falta 'data'):",
            json
          );
          $("#TablaRolesModulos_processing").css("display", "none"); 
          alert(
            "Error: No se pudieron cargar los datos de asignaciones correctamente."
          );
          return [];
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error(
          "Error AJAX al cargar datos para TablaRolesModulos: ",
          textStatus,
          errorThrown,
          jqXHR.responseText
        );
        $("#TablaRolesModulos_processing").css("display", "none"); 
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
        data: "titulo_modulo",
        title: "Módulo",
        className: "all whitespace-nowrap py-2 px-3 text-gray-700",
      },
      {
        data: "descripcion_modulo",
        title: "Descripción del Módulo",
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
              <button class="ver-rolmodulo-btn text-green-600 hover:text-green-700 p-1 transition-colors duration-150" data-idrolmodulo="${row.idrolmodulo}" title="Ver detalles">
                  <i class="fas fa-eye fa-fw text-base"></i>
              </button>
              <button class="editar-rolmodulo-btn text-blue-600 hover:text-blue-700 p-1 transition-colors duration-150" data-idrolmodulo="${row.idrolmodulo}" title="Editar">
                  <i class="fas fa-edit fa-fw text-base"></i>
              </button>
              <button class="eliminar-rolmodulo-btn text-red-600 hover:text-red-700 p-1 transition-colors duration-150" data-idrolmodulo="${row.idrolmodulo}" data-rol="${row.nombre_rol}" data-modulo="${row.titulo_modulo}" title="Eliminar">
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
      console.log("DataTable RolesModulos inicializado correctamente");
      window.tablaRolesModulos = this.api();
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

  $("#TablaRolesModulos tbody").on("click", ".ver-rolmodulo-btn", function () {
    const idRolModulo = $(this).data("idrolmodulo");
    if (idRolModulo && typeof verRolModulo === "function") {
      verRolModulo(idRolModulo);
    } else {
      console.error(
        "Función verRolModulo no definida o idRolModulo no encontrado."
      );
      alert("Error: No se pudo obtener el ID de la asignación para verla.");
    }
  });

  $("#TablaRolesModulos tbody").on(
    "click",
    ".editar-rolmodulo-btn",
    function () {
      const idRolModulo = $(this).data("idrolmodulo");
      if (idRolModulo && typeof editarRolModulo === "function") {
        editarRolModulo(idRolModulo);
      } else {
        console.error(
          "Función editarRolModulo no definida o idRolModulo no encontrado."
        );
        alert("Error: No se pudo obtener el ID de la asignación para editarla.");
      }
    }
  );

  $("#TablaRolesModulos tbody").on(
    "click",
    ".eliminar-rolmodulo-btn",
    function () {
      const idRolModulo = $(this).data("idrolmodulo");
      const nombreRol = $(this).data("rol");
      const tituloModulo = $(this).data("modulo");
      if (idRolModulo && typeof eliminarRolModulo === "function") {
        eliminarRolModulo(idRolModulo, nombreRol, tituloModulo);
      } else {
        console.error(
          "Función eliminarRolModulo no definida o idRolModulo no encontrado."
        );
        alert(
          "Error: No se pudo obtener el ID de la asignación para eliminarla."
        );
      }
    }
  );
  });

  
  const btnAbrirModalRegistro = document.getElementById(
    "btnAbrirModalRegistrarRolModulo"
  );
  const formRegistrar = document.getElementById("formRegistrarRolModulo");
  const btnCerrarModalRegistro = document.getElementById(
    "btnCerrarModalRegistrar"
  );
  const btnCancelarModalRegistro = document.getElementById(
    "btnCancelarModalRegistrar"
  );

  if (btnAbrirModalRegistro) {
    btnAbrirModalRegistro.addEventListener("click", function () {
      abrirModal("modalRegistrarRolModulo");
      if (formRegistrar) formRegistrar.reset();
      cargarRoles();
      cargarModulos();
      inicializarValidaciones(camposFormularioRolModulo, "formRegistrarRolModulo");
    });
  }

  if (btnCerrarModalRegistro) {
    btnCerrarModalRegistro.addEventListener("click", function () {
      cerrarModal("modalRegistrarRolModulo");
    });
  }

  if (btnCancelarModalRegistro) {
    btnCancelarModalRegistro.addEventListener("click", function () {
      cerrarModal("modalRegistrarRolModulo");
    });
  }

  
  if (formRegistrar) {
    formRegistrar.addEventListener("submit", function (e) {
      e.preventDefault();
      registrarRolModulo();
    });
  }

  
  const btnCerrarModalActualizar = document.getElementById(
    "btnCerrarModalActualizar"
  );
  const btnCancelarModalActualizar = document.getElementById(
    "btnCancelarModalActualizar"
  );
  const formActualizar = document.getElementById("formActualizarRolModulo");

  if (btnCerrarModalActualizar) {
    btnCerrarModalActualizar.addEventListener("click", function () {
      cerrarModal("modalActualizarRolModulo");
    });
  }

  if (btnCancelarModalActualizar) {
    btnCancelarModalActualizar.addEventListener("click", function () {
      cerrarModal("modalActualizarRolModulo");
    });
  }

  if (formActualizar) {
    formActualizar.addEventListener("submit", function (e) {
      e.preventDefault();
      actualizarRolModulo();
    });
  }

  
  const btnCerrarModalVer = document.getElementById("btnCerrarModalVer");
  const btnCerrarModalVer2 = document.getElementById("btnCerrarModalVer2");
  
  if (btnCerrarModalVer) {
    btnCerrarModalVer.addEventListener("click", function () {
      cerrarModal("modalVerRolModulo");
    });
  }

  if (btnCerrarModalVer2) {
    btnCerrarModalVer2.addEventListener("click", function () {
      cerrarModal("modalVerRolModulo");
    });
  }
});


function cargarRoles() {
  fetch("RolesModulos/getRoles", {
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

function cargarModulos() {
  fetch("RolesModulos/getModulos", {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        llenarSelectModulos(result.data, "moduloSelect");
        llenarSelectModulos(result.data, "moduloSelectActualizar");
      }
    })
    .catch((error) => {
      console.error("Error al cargar módulos:", error);
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

function llenarSelectModulos(modulos, selectId) {
  const select = document.getElementById(selectId);
  if (select) {
    select.innerHTML = '<option value="">Seleccione un módulo</option>';
    modulos.forEach((modulo) => {
      const option = document.createElement("option");
      option.value = modulo.idmodulo;
      option.textContent = `${modulo.titulo} - ${modulo.descripcion || "Sin descripción"}`;
      select.appendChild(option);
    });
  }
}

function registrarRolModulo() {
  const formRegistrar = document.getElementById("formRegistrarRolModulo");
  const btnGuardarRolModulo = document.getElementById("btnGuardarRolModulo");

  if (!validarCamposVacios(camposFormularioRolModulo, "formRegistrarRolModulo")) {
    return;
  }

  let formularioConErroresEspecificos = false;
  for (const campo of camposFormularioRolModulo) {
    const inputElement = formRegistrar.querySelector(`#${campo.id}`);
    if (!inputElement) continue;

    let esValidoEsteCampo = true;
    if (campo.tipo === "select") {
      if (campo.mensajes && campo.mensajes.vacio) {
        esValidoEsteCampo = validarSelect(
          campo.id,
          campo.mensajes,
          "formRegistrarRolModulo"
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
    idmodulo: formData.get("idmodulo") || "",
  };

  if (btnGuardarRolModulo) {
    btnGuardarRolModulo.disabled = true;
    btnGuardarRolModulo.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...`;
  }

  fetch("RolesModulos/createRolModulo", {
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
        cerrarModal("modalRegistrarRolModulo");
        if (typeof tablaRolesModulos !== "undefined" && tablaRolesModulos.ajax) {
          tablaRolesModulos.ajax.reload(null, false);
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
      if (btnGuardarRolModulo) {
        btnGuardarRolModulo.disabled = false;
        btnGuardarRolModulo.innerHTML = `<i class="fas fa-save mr-2"></i> Guardar Asignación`;
      }
    });
}

function editarRolModulo(idRolModulo) {
  Promise.all([
    fetch(`RolesModulos/getRolModuloById/${idRolModulo}`),
    fetch("RolesModulos/getRoles"),
    fetch("RolesModulos/getModulos")
  ])
    .then(responses => Promise.all(responses.map(r => r.json())))
    .then(([rolModuloResult, rolesResult, modulosResult]) => {
      if (rolModuloResult.status && rolModuloResult.data) {
        const rolModulo = rolModuloResult.data;
        
        if (rolesResult.status && rolesResult.data) {
          llenarSelectRoles(rolesResult.data, "rolSelectActualizar");
        }
        
        if (modulosResult.status && modulosResult.data) {
          llenarSelectModulos(modulosResult.data, "moduloSelectActualizar");
        }
        
        mostrarModalEditarRolModulo(rolModulo);
      } else {
        Swal.fire("Error", "No se pudieron cargar los datos.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    });
}

function mostrarModalEditarRolModulo(rolModulo) {
  
  document.getElementById("idRolModuloActualizar").value = rolModulo.idrolmodulo || "";
  document.getElementById("rolSelectActualizar").value = rolModulo.idrol || "";
  document.getElementById("moduloSelectActualizar").value = rolModulo.idmodulo || "";

  
  inicializarValidaciones(
    camposFormularioActualizarRolModulo,
    "formActualizarRolModulo"
  );

  abrirModal("modalActualizarRolModulo");
}

function actualizarRolModulo() {
  const formActualizar = document.getElementById("formActualizarRolModulo");
  const btnActualizarRolModulo = document.getElementById("btnActualizarRolModulo");
  const idRolModulo = document.getElementById("idRolModuloActualizar").value;

  
  const camposObligatorios = camposFormularioActualizarRolModulo.filter((c) => {
    return c.mensajes && c.mensajes.vacio;
  });

  if (!validarCamposVacios(camposObligatorios, "formActualizarRolModulo")) {
    return;
  }

  
  let formularioConErroresEspecificos = false;
  for (const campo of camposFormularioActualizarRolModulo) {
    const inputElement = formActualizar.querySelector(`#${campo.id}`);
    if (!inputElement) continue;

    let esValidoEsteCampo = true;
    if (campo.tipo === "select") {
      if (campo.mensajes && campo.mensajes.vacio) {
        esValidoEsteCampo = validarSelect(
          campo.id,
          campo.mensajes,
          "formActualizarRolModulo"
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
    idrolmodulo: idRolModulo,
    idrol: formData.get("idrol") || "",
    idmodulo: formData.get("idmodulo") || "",
  };

  if (btnActualizarRolModulo) {
    btnActualizarRolModulo.disabled = true;
    btnActualizarRolModulo.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Actualizando...`;
  }

  fetch("RolesModulos/updateRolModulo", {
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
        cerrarModal("modalActualizarRolModulo");
        if (typeof tablaRolesModulos !== "undefined" && tablaRolesModulos.ajax) {
          tablaRolesModulos.ajax.reload(null, false);
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
      if (btnActualizarRolModulo) {
        btnActualizarRolModulo.disabled = false;
        btnActualizarRolModulo.innerHTML = `<i class="fas fa-save mr-2"></i> Actualizar Asignación`;
      }
    });
}

function verRolModulo(idRolModulo) {
  fetch(`RolesModulos/getRolModuloById/${idRolModulo}`, {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const rolModulo = result.data;
        mostrarModalVerRolModulo(rolModulo);
      } else {
        Swal.fire("Error", "No se pudieron cargar los datos.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    });
}

function mostrarModalVerRolModulo(rolModulo) {
  
  document.getElementById("verRolNombre").textContent = rolModulo.nombre_rol || "N/A";
  document.getElementById("verRolDescripcion").textContent = rolModulo.descripcion_rol || "Sin descripción";
  document.getElementById("verModuloTitulo").textContent = rolModulo.titulo_modulo || "N/A";
  document.getElementById("verModuloDescripcion").textContent = rolModulo.descripcion_modulo || "Sin descripción";

  abrirModal("modalVerRolModulo");
}

function eliminarRolModulo(idRolModulo, nombreRol, tituloModulo) {
  Swal.fire({
    title: "¿Estás seguro?",
    text: `¿Deseas eliminar la asignación del rol "${nombreRol}" al módulo "${tituloModulo}"?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#dc2626",
    cancelButtonColor: "#00c950",
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      const dataParaEnviar = {
        idrolmodulo: idRolModulo,
      };

      fetch("RolesModulos/deleteRolModulo", {
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
            if (typeof tablaRolesModulos !== "undefined" && tablaRolesModulos.ajax) {
              tablaRolesModulos.ajax.reload(null, false);
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
