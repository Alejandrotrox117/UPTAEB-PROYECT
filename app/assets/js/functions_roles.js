import { abrirModal, cerrarModal } from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  validarCamposVacios,
  validarCampo,
  validarSelect,
  limpiarValidaciones,
} from "./validaciones.js";

let tablaRoles;

const camposFormularioRol = [
  {
    id: "nombreRol",
    tipo: "input",
    regex: expresiones.textoGeneral,
    mensajes: {
      vacio: "El nombre del rol es obligatorio.",
      formato: "Nombre inválido.",
    },
  },
  {
    id: "descripcionRol",
    tipo: "textarea",
    regex: expresiones.textoGeneral,
    mensajes: {
      vacio: "La descripción del rol es obligatoria.",
      formato: "Descripción inválida.",
    },
  },
  {
    id: "estatusRol",
    tipo: "select",
    mensajes: { vacio: "Seleccione un estatus." },
  },
];

const camposFormularioActualizarRol = [
  {
    id: "nombreActualizar",
    tipo: "input",
    regex: expresiones.textoGeneral,
    mensajes: {
      vacio: "El nombre del rol es obligatorio.",
      formato: "Nombre inválido.",
    },
  },
  {
    id: "descripcionActualizar",
    tipo: "textarea",
    regex: expresiones.textoGeneral,
    mensajes: {
      vacio: "La descripción del rol es obligatoria.",
      formato: "Descripción inválida.",
    },
  },
  {
    id: "estatusActualizar",
    tipo: "select",
    mensajes: { vacio: "Seleccione un estatus." },
  },
];

// ✅ FUNCIÓN PARA OBTENER PERMISOS
function obtenerPermisos() {
  return {
    crear: document.getElementById('permisoCrear')?.value === '1',
    editar: document.getElementById('permisoEditar')?.value === '1',
    eliminar: document.getElementById('permisoEliminar')?.value === '1',
    ver: document.getElementById('permisoVer')?.value === '1'
  };
}

// ✅ FUNCIÓN PARA VERIFICAR PERMISOS
function tienePermiso(accion) {
  const permisos = obtenerPermisos();
  return permisos[accion] || false;
}

// ✅ FUNCIÓN PARA GENERAR BOTONES SEGÚN PERMISOS
function generarBotonesAccion(row) {
  const permisos = obtenerPermisos();
  let botones = '';

  // Botón Ver (siempre disponible si puede ver)
  if (permisos.ver) {
    botones += `
      <button class="ver-rol-btn text-green-600 hover:text-green-700 p-1 transition-colors duration-150" 
              data-idrol="${row.idrol}" 
              title="Ver detalles">
        <i class="fas fa-eye fa-fw text-base"></i>
      </button>
    `;
  }

  // Botón Editar (solo si puede editar)
  if (permisos.editar) {
    botones += `
      <button class="editar-rol-btn text-blue-600 hover:text-blue-700 p-1 transition-colors duration-150" 
              data-idrol="${row.idrol}" 
              title="Editar">
        <i class="fas fa-edit fa-fw text-base"></i>
      </button>
    `;
  }

  // Botón Eliminar (solo si puede eliminar)
  if (permisos.eliminar) {
    botones += `
      <button class="eliminar-rol-btn text-red-600 hover:text-red-700 p-1 transition-colors duration-150" 
              data-idrol="${row.idrol}" 
              data-nombre="${row.nombre}" 
              title="Desactivar">
        <i class="fas fa-trash-alt fa-fw text-base"></i>
      </button>
    `;
  }

  // Si no tiene permisos, mostrar mensaje
  if (!botones) {
    botones = '<span class="text-gray-400 text-xs">Sin permisos</span>';
  }

  return `<div class="inline-flex items-center space-x-1">${botones}</div>`;
}

document.addEventListener("DOMContentLoaded", function () {
  // ✅ VERIFICAR PERMISOS AL CARGAR
  console.log('🔐 Permisos detectados:', obtenerPermisos());

  $(document).ready(function () {

    if ($.fn.DataTable.isDataTable("#TablaRoles")) {
      $("#TablaRoles").DataTable().destroy();
    }

    tablaRoles = $("#TablaRoles").DataTable({
      processing: true,
      ajax: {
        url: "Roles/getRolesData",
        type: "GET",
        dataSrc: function (json) {
          if (json && json.data) {
            return json.data;
          } else {
            console.error(
              "La respuesta del servidor no tiene la estructura esperada (falta 'data'):",
              json
            );
            $("#TablaRoles_processing").css("display", "none");
            alert(
              "Error: No se pudieron cargar los datos de roles correctamente."
            );
            return [];
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.error(
            "Error AJAX al cargar datos para TablaRoles: ",
            textStatus,
            errorThrown,
            jqXHR.responseText
          );
          $("#TablaRoles_processing").css("display", "none");
          alert(
            "Error de comunicación al cargar los datos de roles. Por favor, intente más tarde."
          );
        },
      },
      columns: [
        {
          data: "nombre",
          title: "Nombre",
          className:
            "all whitespace-nowrap py-2 px-3 text-gray-700 dt-fixed-col-background",
        },
        {
          data: "descripcion",
          title: "Descripción",
          className: "desktop py-2 px-3 text-gray-700",
          render: function (data, type, row) {
            if (type === "display" && data && data.length > 50) {
              return (
                '<span title="' +
                data.replace(/"/g, "&quot;") +
                '">' +
                data.substring(0, 50) +
                "...</span>"
              );
            }
            return data || '<i class="text-gray-400">Sin descripción</i>';
          },
        },
        {
          data: "estatus",
          title: "Estatus",
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
          data: "fecha_creacion",
          title: "Fecha Creación",
          className: "tablet-l whitespace-nowrap py-2 px-3 text-gray-700",
          render: function (data, type, row) {
            if (data) {
              const fecha = new Date(data);
              return !isNaN(fecha.getTime())
                ? fecha.toLocaleDateString("es-ES")
                : '<i class="text-gray-400">Fecha inválida</i>';
            }
            return '<i class="text-gray-400">N/A</i>';
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
            // ✅ USAR FUNCIÓN QUE RESPETA PERMISOS
            return generarBotonesAccion(row);
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
          '<div class="text-center py-4"><i class="fas fa-info-circle fa-2x text-gray-400 mb-2"></i><p class="text-gray-600">No hay roles disponibles.</p></div>',
        info: "Mostrando _START_ a _END_ de _TOTAL_ roles",
        infoEmpty: "Mostrando 0 roles",
        infoFiltered: "(filtrado de _MAX_ roles totales)",
        lengthMenu: "Mostrar _MENU_ roles",
        search: "_INPUT_",
        searchPlaceholder: "Buscar rol...",
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
        console.log("DataTable Roles inicializado correctamente");
        window.tablaRoles = this.api();
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

    // ✅ EVENT LISTENERS CON VERIFICACIÓN DE PERMISOS
    $("#TablaRoles tbody").on("click", ".ver-rol-btn", function () {
      if (!tienePermiso('ver')) {
        Swal.fire("Sin permisos", "No tiene permisos para ver detalles de roles.", "warning");
        return;
      }

      const idRol = $(this).data("idrol");
      if (idRol && typeof verRol === "function") {
        verRol(idRol);
      } else {
        console.error("Función verRol no definida o idRol no encontrado.");
        alert("Error: No se pudo obtener el ID del rol para verlo.");
      }
    });

    $("#TablaRoles tbody").on("click", ".editar-rol-btn", function () {
      if (!tienePermiso('editar')) {
        Swal.fire("Sin permisos", "No tiene permisos para editar roles.", "warning");
        return;
      }

      const idRol = $(this).data("idrol");
      if (idRol && typeof editarRol === "function") {
        editarRol(idRol);
      } else {
        console.error("Función editarRol no definida o idRol no encontrado.");
        alert("Error: No se pudo obtener el ID del rol para editarlo.");
      }
    });

    $("#TablaRoles tbody").on("click", ".eliminar-rol-btn", function () {
      if (!tienePermiso('eliminar')) {
        Swal.fire("Sin permisos", "No tiene permisos para eliminar roles.", "warning");
        return;
      }

      const idRol = $(this).data("idrol");
      const nombreRol = $(this).data("nombre");
      if (idRol && typeof eliminarRol === "function") {
        eliminarRol(idRol, nombreRol);
      } else {
        console.error("Función eliminarRol no definida o idRol no encontrado.");
        alert("Error: No se pudo obtener el ID del rol para desactivarlo.");
      }
    });
  });

  // ✅ MODAL REGISTRAR ROL - VERIFICAR PERMISOS
  const btnAbrirModalRegistro = document.getElementById("btnAbrirModalRegistrarRol");
  const formRegistrar = document.getElementById("formRegistrarRol");
  const btnCerrarModalRegistro = document.getElementById("btnCerrarModalRegistrar");
  const btnCancelarModalRegistro = document.getElementById("btnCancelarModalRegistrar");
  const btnCerrarModalVer2 = document.getElementById("btnCerrarModalVer2");
  const btnGuardarRol = document.getElementById("btnGuardarRol");

  if (btnAbrirModalRegistro) {
    btnAbrirModalRegistro.addEventListener("click", function () {
      if (!tienePermiso('crear')) {
        Swal.fire("Sin permisos", "No tiene permisos para crear roles.", "warning");
        return;
      }

      abrirModal("modalRegistrarRol");
      if (formRegistrar) formRegistrar.reset();
      inicializarValidaciones(camposFormularioRol, "formRegistrarRol");
    });
  }

  if (btnCerrarModalRegistro) {
    btnCerrarModalRegistro.addEventListener("click", function () {
      cerrarModal("modalRegistrarRol");
    });
  }

  if (btnCancelarModalRegistro) {
    btnCancelarModalRegistro.addEventListener("click", function () {
      cerrarModal("modalRegistrarRol");
    });
  }

  if (btnCerrarModalVer2) {
    btnCerrarModalVer2.addEventListener("click", function () {
      cerrarModal("modalVerRol");
    });
  }

  // SUBMIT FORM REGISTRAR
  if (formRegistrar) {
    formRegistrar.addEventListener("submit", function (e) {
      e.preventDefault();
      
      if (!tienePermiso('crear')) {
        Swal.fire("Sin permisos", "No tiene permisos para crear roles.", "warning");
        return;
      }

      registrarRol();
    });
  }

  // MODAL ACTUALIZAR ROL
  const btnCerrarModalActualizar = document.getElementById("btnCerrarModalActualizar");
  const btnCancelarModalActualizar = document.getElementById("btnCancelarModalActualizar");
  const formActualizar = document.getElementById("formActualizarRol");
  const btnActualizarRol = document.getElementById("btnActualizarRol");

  if (btnCerrarModalActualizar) {
    btnCerrarModalActualizar.addEventListener("click", function () {
      cerrarModal("modalActualizarRol");
    });
  }

  if (btnCancelarModalActualizar) {
    btnCancelarModalActualizar.addEventListener("click", function () {
      cerrarModal("modalActualizarRol");
    });
  }

  if (formActualizar) {
    formActualizar.addEventListener("submit", function (e) {
      e.preventDefault();
      
      if (!tienePermiso('editar')) {
        Swal.fire("Sin permisos", "No tiene permisos para editar roles.", "warning");
        return;
      }

      actualizarRol();
    });
  }

  // MODAL VER ROL
  const btnCerrarModalVer = document.getElementById("btnCerrarModalVer");
  if (btnCerrarModalVer) {
    btnCerrarModalVer.addEventListener("click", function () {
      cerrarModal("modalVerRol");
    });
  }
});

function editarRol(idRol) {
  // ✅ VERIFICAR PERMISOS ANTES DE PROCEDER
  if (!tienePermiso('editar')) {
    Swal.fire("Sin permisos", "No tiene permisos para editar roles.", "warning");
    return;
  }

  fetch(`Roles/getRolById/${idRol}`, {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const rol = result.data;
        mostrarModalEditarRol(rol);
      } else {
        Swal.fire("Error", "No se pudieron cargar los datos.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    });
}

function mostrarModalEditarRol(rol) {
  document.getElementById("idRolActualizar").value = rol.idrol || "";
  document.getElementById("nombreActualizar").value = rol.nombre || "";
  document.getElementById("descripcionActualizar").value = rol.descripcion || "";
  document.getElementById("estatusActualizar").value = rol.estatus || "";
  inicializarValidaciones(camposFormularioActualizarRol, "formActualizarRol");

  abrirModal("modalActualizarRol");
}

function actualizarRol() {
  // ✅ VERIFICAR PERMISOS ANTES DE PROCEDER
  if (!tienePermiso('editar')) {
    Swal.fire("Sin permisos", "No tiene permisos para editar roles.", "warning");
    return;
  }

  const formActualizar = document.getElementById("formActualizarRol");
  const btnActualizarRol = document.getElementById("btnActualizarRol");
  const idRol = document.getElementById("idRolActualizar").value;

  // Validar campos vacíos obligatorios
  if (!validarCamposVacios(camposFormularioActualizarRol, "formActualizarRol")) {
    return;
  }

  // Validar formatos específicos
  let formularioConErroresEspecificos = false;
  for (const campo of camposFormularioActualizarRol) {
    const inputElement = formActualizar.querySelector(`#${campo.id}`);
    if (!inputElement) continue;

    let esValidoEsteCampo = true;
    if (campo.tipo === "select") {
      esValidoEsteCampo = validarSelect(
        campo.id,
        campo.mensajes,
        "formActualizarRol"
      );
    } else if (
      ["input", "textarea", "text"].includes(campo.tipo)
    ) {
      esValidoEsteCampo = validarCampo(
        inputElement,
        campo.regex,
        campo.mensajes
      );
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
    idrol: idRol,
    nombre: formData.get("nombre") || "",
    descripcion: formData.get("descripcion") || "",
    estatus: formData.get("estatus") || "",
  };

  if (btnActualizarRol) {
    btnActualizarRol.disabled = true;
    btnActualizarRol.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Actualizando...`;
  }

  fetch("Roles/updateRol", {
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
        cerrarModal("modalActualizarRol");
        if (typeof tablaRoles !== "undefined" && tablaRoles.ajax) {
          tablaRoles.ajax.reload(null, false);
        }
      } else {
        Swal.fire(
          "Error",
          result.message || "No se pudo actualizar el rol.",
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
      if (btnActualizarRol) {
        btnActualizarRol.disabled = false;
        btnActualizarRol.innerHTML = `<i class="fas fa-save mr-2"></i> Actualizar Rol`;
      }
    });
}

function registrarRol() {
  // ✅ VERIFICAR PERMISOS ANTES DE PROCEDER
  if (!tienePermiso('crear')) {
    Swal.fire("Sin permisos", "No tiene permisos para crear roles.", "warning");
    return;
  }

  const formRegistrar = document.getElementById("formRegistrarRol");
  const btnGuardarRol = document.getElementById("btnGuardarRol");

  if (!validarCamposVacios(camposFormularioRol, "formRegistrarRol")) {
    return;
  }

  let formularioConErroresEspecificos = false;
  for (const campo of camposFormularioRol) {
    const inputElement = formRegistrar.querySelector(`#${campo.id}`);
    if (!inputElement) continue;

    let esValidoEsteCampo = true;
    if (campo.tipo === "select") {
      esValidoEsteCampo = validarSelect(
        campo.id,
        campo.mensajes,
        "formRegistrarRol"
      );
    } else if (["input", "textarea", "text"].includes(campo.tipo)) {
      esValidoEsteCampo = validarCampo(
        inputElement,
        campo.regex,
        campo.mensajes
      );
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
    nombre: formData.get("nombre") || "",
    descripcion: formData.get("descripcion") || "",
    estatus: formData.get("estatus") || "",
  };

  if (btnGuardarRol) {
    btnGuardarRol.disabled = true;
    btnGuardarRol.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...`;
  }

  fetch("Roles/createRol", {
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
        cerrarModal("modalRegistrarRol");
        if (typeof tablaRoles !== "undefined" && tablaRoles.ajax) {
          tablaRoles.ajax.reload(null, false);
        }
      } else {
        Swal.fire(
          "Error",
          result.message || "No se pudo registrar el rol.",
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
      if (btnGuardarRol) {
        btnGuardarRol.disabled = false;
        btnGuardarRol.innerHTML = `<i class="fas fa-save mr-2"></i> Guardar Rol`;
      }
    });
}

function verRol(idRol) {
  // ✅ VERIFICAR PERMISOS ANTES DE PROCEDER
  if (!tienePermiso('ver')) {
    Swal.fire("Sin permisos", "No tiene permisos para ver detalles de roles.", "warning");
    return;
  }

  fetch(`Roles/getRolById/${idRol}`, {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const rol = result.data;
        mostrarModalVerRol(rol);
      } else {
        Swal.fire("Error", "No se pudieron cargar los datos.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    });
}

function mostrarModalVerRol(rol) {
  // Llenar los campos del modal de ver
  document.getElementById("verNombre").textContent = rol.nombre || "N/A";
  document.getElementById("verDescripcion").textContent = rol.descripcion || "N/A";
  document.getElementById("verEstatus").textContent = rol.estatus || "N/A";
  document.getElementById("verFechaCreacion").textContent =
    rol.fecha_creacion
      ? new Date(rol.fecha_creacion).toLocaleDateString("es-ES")
      : "N/A";
  document.getElementById("verUltimaModificacion").textContent =
    rol.ultima_modificacion
      ? new Date(rol.ultima_modificacion).toLocaleDateString("es-ES")
      : "N/A";

  abrirModal("modalVerRol");
}

function eliminarRol(idRol, nombreRol) {
  // ✅ VERIFICAR PERMISOS ANTES DE PROCEDER
  if (!tienePermiso('eliminar')) {
    Swal.fire("Sin permisos", "No tiene permisos para eliminar roles.", "warning");
    return;
  }

  Swal.fire({
    title: "¿Estás seguro?",
    text: `¿Deseas eliminar el rol "${nombreRol}"? Esta acción no se puede deshacer.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      const dataParaEnviar = {
        idrol: idRol,
      };

      fetch("Roles/deleteRol", {
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
            if (typeof tablaRoles !== "undefined" && tablaRoles.ajax) {
              tablaRoles.ajax.reload(null, false);
            }
          } else {
            Swal.fire(
              "Error",
              result.message || "No se pudo eliminar el rol.",
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
