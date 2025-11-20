import { abrirModal, cerrarModal } from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  validarCamposVacios,
  validarCampo,
  validarSelect,
  limpiarValidaciones,
} from "./validaciones.js";

let tablaModulos;

const camposFormularioModulo = [
  {
    id: "moduloTitulo",
    tipo: "input",
    regex: expresiones.textoGeneral,
    mensajes: {
      vacio: "El título del módulo es obligatorio.",
      formato: "Título inválido.",
    },
  },
  {
    id: "moduloDescripcion",
    tipo: "textarea",
    regex: expresiones.textoGeneral,
    mensajes: {
      formato: "Descripción inválida.",
    },
  },
];

const camposFormularioActualizarModulo = [
  {
    id: "moduloTituloActualizar",
    tipo: "input",
    regex: expresiones.textoGeneral,
    mensajes: {
      vacio: "El título del módulo es obligatorio.",
      formato: "Título inválido.",
    },
  },
  {
    id: "moduloDescripcionActualizar",
    tipo: "textarea",
    regex: expresiones.textoGeneral,
    mensajes: {
      formato: "Descripción inválida.",
    },
  },
];

document.addEventListener("DOMContentLoaded", function () {
  $(document).ready(function () {
    if ($.fn.DataTable.isDataTable("#TablaModulos")) {
      $("#TablaModulos").DataTable().destroy();
    }

    tablaModulos = $("#TablaModulos").DataTable({
      processing: true,
      ajax: {
        url: "Modulos/getModulosData",
        type: "GET",
        dataSrc: function (json) {
          if (json && json.data) {
            return json.data;
          } else {
            console.error(
              "La respuesta del servidor no tiene la estructura esperada (falta 'data'):",
              json
            );
            $("#TablaModulos_processing").css("display", "none");
            alert(
              "Error: No se pudieron cargar los datos de módulos correctamente."
            );
            return [];
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.error(
            "Error AJAX al cargar datos para TablaModulos: ",
            textStatus,
            errorThrown,
            jqXHR.responseText
          );
          $("#TablaModulos_processing").css("display", "none"); 
          alert(
            "Error de comunicación al cargar los datos de módulos. Por favor, intente más tarde."
          );
        },
      },
      columns: [
        {
          data: "titulo",
          title: "Título",
          className:
            "all whitespace-nowrap py-2 px-3 text-gray-700 dt-fixed-col-background",
        },
        {
          data: "descripcion",
          title: "Descripción",
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
          data: "fecha_creacion_formato",
          title: "Fecha Creación",
          className: "tablet-l whitespace-nowrap py-2 px-3 text-gray-700",
          render: function (data, type, row) {
            return data || '<i class="text-gray-400">N/A</i>';
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
                <button class="ver-modulo-btn text-green-600 hover:text-green-700 p-1 transition-colors duration-150" data-idmodulo="${row.idmodulo}" title="Ver detalles">
                    <i class="fas fa-eye fa-fw text-base"></i>
                </button>
                <button class="editar-modulo-btn text-blue-600 hover:text-blue-700 p-1 transition-colors duration-150" data-idmodulo="${row.idmodulo}" title="Editar">
                    <i class="fas fa-edit fa-fw text-base"></i>
                </button>
                <button class="eliminar-modulo-btn text-red-600 hover:text-red-700 p-1 transition-colors duration-150" data-idmodulo="${row.idmodulo}" data-titulo="${row.titulo}" title="Eliminar">
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
          '<div class="text-center py-4"><i class="fas fa-info-circle fa-2x text-gray-400 mb-2"></i><p class="text-gray-600">No hay módulos disponibles.</p></div>',
        info: "Mostrando _START_ a _END_ de _TOTAL_ módulos",
        infoEmpty: "Mostrando 0 módulos",
        infoFiltered: "(filtrado de _MAX_ módulos totales)",
        lengthMenu: "Mostrar _MENU_ módulos",
        search: "_INPUT_",
        searchPlaceholder: "Buscar módulo...",
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
        console.log("DataTable Modulos inicializado correctamente");
        window.tablaModulos = this.api();
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

    $("#TablaModulos tbody").on("click", ".ver-modulo-btn", function () {
      const idModulo = $(this).data("idmodulo");
      if (idModulo && typeof verModulo === "function") {
        verModulo(idModulo);
      } else {
        console.error("Función verModulo no definida o idModulo no encontrado.");
        alert("Error: No se pudo obtener el ID del módulo para verlo.");
      }
    });

    $("#TablaModulos tbody").on("click", ".editar-modulo-btn", function () {
      const idModulo = $(this).data("idmodulo");
      if (idModulo && typeof editarModulo === "function") {
        editarModulo(idModulo);
      } else {
        console.error(
          "Función editarModulo no definida o idModulo no encontrado."
        );
        alert("Error: No se pudo obtener el ID del módulo para editarlo.");
      }
    });

    $("#TablaModulos tbody").on("click", ".eliminar-modulo-btn", function () {
      const idModulo = $(this).data("idmodulo");
      const tituloModulo = $(this).data("titulo");
      if (idModulo && typeof eliminarModulo === "function") {
        eliminarModulo(idModulo, tituloModulo);
      } else {
        console.error(
          "Función eliminarModulo no definida o idModulo no encontrado."
        );
        alert("Error: No se pudo obtener el ID del módulo para eliminarlo.");
      }
    });
  });

  
  const btnAbrirModalRegistro = document.getElementById(
    "btnAbrirModalRegistrarModulo"
  );
  const formRegistrar = document.getElementById("formRegistrarModulo");
  const btnCerrarModalRegistro = document.getElementById(
    "btnCerrarModalRegistrar"
  );
  const btnCancelarModalRegistro = document.getElementById(
    "btnCancelarModalRegistrar"
  );

  if (btnAbrirModalRegistro) {
    btnAbrirModalRegistro.addEventListener("click", function () {
      abrirModal("modalRegistrarModulo");
      if (formRegistrar) formRegistrar.reset();
      inicializarValidaciones(camposFormularioModulo, "formRegistrarModulo");
    });
  }

  if (btnCerrarModalRegistro) {
    btnCerrarModalRegistro.addEventListener("click", function () {
      cerrarModal("modalRegistrarModulo");
    });
  }

  if (btnCancelarModalRegistro) {
    btnCancelarModalRegistro.addEventListener("click", function () {
      cerrarModal("modalRegistrarModulo");
    });
  }

  
  if (formRegistrar) {
    formRegistrar.addEventListener("submit", function (e) {
      e.preventDefault();
      registrarModulo();
    });
  }

  
  const btnCerrarModalActualizar = document.getElementById(
    "btnCerrarModalActualizar"
  );
  const btnCancelarModalActualizar = document.getElementById(
    "btnCancelarModalActualizar"
  );
  const formActualizar = document.getElementById("formActualizarModulo");

  if (btnCerrarModalActualizar) {
    btnCerrarModalActualizar.addEventListener("click", function () {
      cerrarModal("modalActualizarModulo");
    });
  }

  if (btnCancelarModalActualizar) {
    btnCancelarModalActualizar.addEventListener("click", function () {
      cerrarModal("modalActualizarModulo");
    });
  }

  if (formActualizar) {
    formActualizar.addEventListener("submit", function (e) {
      e.preventDefault();
      actualizarModulo();
    });
  }

  
  const btnCerrarModalVer = document.getElementById("btnCerrarModalVer");
  const btnCerrarModalVer2 = document.getElementById("btnCerrarModalVer2");
  
  if (btnCerrarModalVer) {
    btnCerrarModalVer.addEventListener("click", function () {
      cerrarModal("modalVerModulo");
    });
  }

  if (btnCerrarModalVer2) {
    btnCerrarModalVer2.addEventListener("click", function () {
      cerrarModal("modalVerModulo");
    });
  }

  
  const btnVerControladores = document.getElementById("btnVerControladores");
  const btnCerrarModalControladores = document.getElementById("btnCerrarModalControladores");
  const btnCerrarModalControladores2 = document.getElementById("btnCerrarModalControladores2");

  if (btnVerControladores) {
    btnVerControladores.addEventListener("click", function () {
      verControladores();
    });
  }

  if (btnCerrarModalControladores) {
    btnCerrarModalControladores.addEventListener("click", function () {
      cerrarModal("modalVerControladores");
    });
  }

  if (btnCerrarModalControladores2) {
    btnCerrarModalControladores2.addEventListener("click", function () {
      cerrarModal("modalVerControladores");
    });
  }
});


function registrarModulo() {
  const formRegistrar = document.getElementById("formRegistrarModulo");
  const btnGuardarModulo = document.getElementById("btnGuardarModulo");

  if (!validarCamposVacios(camposFormularioModulo, "formRegistrarModulo")) {
    return;
  }

  let formularioConErroresEspecificos = false;
  for (const campo of camposFormularioModulo) {
    const inputElement = formRegistrar.querySelector(`#${campo.id}`);
    if (!inputElement) continue;

    let esValidoEsteCampo = true;
    if (campo.tipo === "select") {
      if (campo.mensajes && campo.mensajes.vacio) {
        esValidoEsteCampo = validarSelect(
          campo.id,
          campo.mensajes,
          "formRegistrarModulo"
        );
      }
    } else if (
      ["input", "textarea", "text"].includes(campo.tipo)
    ) {
      if (inputElement.value.trim() !== "" || (campo.mensajes && campo.mensajes.vacio)) {
        esValidoEsteCampo = validarCampo(
          inputElement,
          campo.regex,
          campo.mensajes
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
    titulo: formData.get("titulo") || "",
    descripcion: formData.get("descripcion") || "",
  };

  if (btnGuardarModulo) {
    btnGuardarModulo.disabled = true;
    btnGuardarModulo.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...`;
  }

  fetch("Modulos/createModulo", {
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
        cerrarModal("modalRegistrarModulo");
        if (typeof tablaModulos !== "undefined" && tablaModulos.ajax) {
          tablaModulos.ajax.reload(null, false);
        }
      } else {
        Swal.fire(
          "Error",
          result.message || "No se pudo registrar el módulo.",
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
      if (btnGuardarModulo) {
        btnGuardarModulo.disabled = false;
        btnGuardarModulo.innerHTML = `<i class="fas fa-save mr-2"></i> Guardar Módulo`;
      }
    });
}

function editarModulo(idModulo) {
  fetch(`Modulos/getModuloById/${idModulo}`, {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const modulo = result.data;
        mostrarModalEditarModulo(modulo);
      } else {
        Swal.fire("Error", "No se pudieron cargar los datos.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    });
}

function mostrarModalEditarModulo(modulo) {
  
  document.getElementById("idModuloActualizar").value = modulo.idmodulo || "";
  document.getElementById("moduloTituloActualizar").value = modulo.titulo || "";
  document.getElementById("moduloDescripcionActualizar").value = modulo.descripcion || "";

  
  inicializarValidaciones(
    camposFormularioActualizarModulo,
    "formActualizarModulo"
  );

  abrirModal("modalActualizarModulo");
}

function actualizarModulo() {
  const formActualizar = document.getElementById("formActualizarModulo");
  const btnActualizarModulo = document.getElementById("btnActualizarModulo");
  const idModulo = document.getElementById("idModuloActualizar").value;

  
  const camposObligatorios = camposFormularioActualizarModulo.filter((c) => {
    return c.mensajes && c.mensajes.vacio;
  });

  if (!validarCamposVacios(camposObligatorios, "formActualizarModulo")) {
    return;
  }

  
  let formularioConErroresEspecificos = false;
  for (const campo of camposFormularioActualizarModulo) {
    const inputElement = formActualizar.querySelector(`#${campo.id}`);
    if (!inputElement) continue;

    let esValidoEsteCampo = true;
    if (campo.tipo === "select") {
      if (campo.mensajes && campo.mensajes.vacio) {
        esValidoEsteCampo = validarSelect(
          campo.id,
          campo.mensajes,
          "formActualizarModulo"
        );
      }
    } else if (
      ["input", "textarea", "text"].includes(campo.tipo)
    ) {
      if (inputElement.value.trim() !== "" || (campo.mensajes && campo.mensajes.vacio)) {
        esValidoEsteCampo = validarCampo(
          inputElement,
          campo.regex,
          campo.mensajes
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
    idmodulo: idModulo,
    titulo: formData.get("titulo") || "",
    descripcion: formData.get("descripcion") || "",
  };

  if (btnActualizarModulo) {
    btnActualizarModulo.disabled = true;
    btnActualizarModulo.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Actualizando...`;
  }

  fetch("Modulos/updateModulo", {
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
        cerrarModal("modalActualizarModulo");
        if (typeof tablaModulos !== "undefined" && tablaModulos.ajax) {
          tablaModulos.ajax.reload(null, false);
        }
      } else {
        Swal.fire(
          "Error",
          result.message || "No se pudo actualizar el módulo.",
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
      if (btnActualizarModulo) {
        btnActualizarModulo.disabled = false;
        btnActualizarModulo.innerHTML = `<i class="fas fa-save mr-2"></i> Actualizar Módulo`;
      }
    });
}

function verModulo(idModulo) {
  fetch(`Modulos/getModuloById/${idModulo}`, {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const modulo = result.data;
        mostrarModalVerModulo(modulo);
      } else {
        Swal.fire("Error", "No se pudieron cargar los datos.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    });
}

function mostrarModalVerModulo(modulo) {
  
  document.getElementById("verModuloTitulo").textContent = modulo.titulo || "N/A";
  document.getElementById("verModuloDescripcion").textContent = modulo.descripcion || "Sin descripción";
  document.getElementById("verModuloEstatus").textContent = modulo.estatus || "N/A";
  document.getElementById("verModuloFechaCreacion").textContent = modulo.fecha_creacion_formato || "N/A";
  document.getElementById("verModuloFechaModificacion").textContent = modulo.fecha_modificacion_formato || "N/A";

  abrirModal("modalVerModulo");
}

function eliminarModulo(idModulo, tituloModulo) {
  Swal.fire({
    title: "¿Estás seguro?",
    text: `¿Deseas desactivar el módulo "${tituloModulo}"? Esta acción cambiará su estatus a INACTIVO.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#dc2626",
    cancelButtonColor: "#00c950",
    confirmButtonText: "Sí, desactivar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      const dataParaEnviar = {
        idmodulo: idModulo,
      };

      fetch("Modulos/deleteModulo", {
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
            Swal.fire("¡Desactivado!", result.message, "success");
            if (typeof tablaModulos !== "undefined" && tablaModulos.ajax) {
              tablaModulos.ajax.reload(null, false);
            }
          } else {
            Swal.fire(
              "Error",
              result.message || "No se pudo desactivar el módulo.",
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

function verControladores() {
  fetch("Modulos/getControladores", {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        mostrarControladores(result.data);
      } else {
        Swal.fire("Error", "No se pudieron cargar los controladores.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    });
}

function mostrarControladores(controladores) {
  const contenedor = document.getElementById("listaControladores");
  contenedor.innerHTML = "";

  if (controladores.length === 0) {
    contenedor.innerHTML = '<p class="text-gray-500 col-span-full text-center">No se encontraron controladores.</p>';
  } else {
    controladores.forEach((controller) => {
      const tarjeta = document.createElement("div");
      tarjeta.className = "bg-gray-50 border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow";
      tarjeta.innerHTML = `
        <div class="flex items-center mb-2">
          <i class="fas fa-code text-blue-500 mr-2"></i>
          <h4 class="font-semibold text-gray-900">${controller.nombre}</h4>
        </div>
        <p class="text-sm text-gray-600">${controller.archivo}</p>
        <div class="mt-2">
          <span class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded">
            <i class="fas fa-check mr-1"></i>Disponible
          </span>
        </div>
      `;
      contenedor.appendChild(tarjeta);
    });
  }

  abrirModal("modalVerControladores");
}
