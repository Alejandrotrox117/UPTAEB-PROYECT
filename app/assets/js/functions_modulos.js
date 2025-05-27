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
    // DATATABLE MÓDULOS
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
            $("#TablaModulos_processing").hide();
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
          $("#TablaModulos_processing").hide();
          alert(
            "Error de comunicación al cargar los datos de módulos. Por favor, intente más tarde."
          );
        },
      },
      columns: [
        { data: "titulo", title: "Título" },
        { 
          data: "descripcion", 
          title: "Descripción",
          render: function (data, type, row) {
            if (data && data.length > 50) {
              return data.substring(0, 50) + "...";
            }
            return data || '<i style="color: silver;">Sin descripción</i>';
          },
        },
        {
          data: "estatus",
          title: "Estatus",
          render: function (data, type, row) {
            if (data) {
              const estatusUpper = String(data).toUpperCase();
              if (estatusUpper === "ACTIVO") {
                return `<span class="bg-green-100 text-green-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300">${data}</span>`;
              } else {
                return `<span class="bg-red-100 text-red-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-red-900 dark:text-red-300">${data}</span>`;
              }
            }
            return '<i style="color: silver;">N/A</i>';
          },
        },
        { 
          data: "fecha_creacion_formato", 
          title: "Fecha Creación",
          render: function (data, type, row) {
            return data || '<i style="color: silver;">N/A</i>';
          },
        },
        {
          data: null,
          title: "Acciones",
          orderable: false,
          searchable: false,
          render: function (data, type, row) {
            return `
              <button class="ver-modulo-btn text-green-500 hover:text-green-700 p-1" data-idmodulo="${row.idmodulo}" title="Ver detalles">
                  <i class="fas fa-eye fa-lg"></i>
              </button>
              <button class="editar-modulo-btn text-blue-500 hover:text-blue-700 p-1 ml-2" data-idmodulo="${row.idmodulo}" title="Editar">
                  <i class="fas fa-edit fa-lg"></i>
              </button>
              <button class="eliminar-modulo-btn text-red-500 hover:text-red-700 p-1 ml-2" data-idmodulo="${row.idmodulo}" data-titulo="${row.titulo}" title="Eliminar">
                  <i class="fas fa-trash fa-lg"></i>
              </button>
            `;
          },
          width: "140px",
          className: "text-center",
        },
      ],
      language: {
        decimal: "",
        emptyTable: "No hay información disponible en la tabla",
        info: "Mostrando _START_ a _END_ de _TOTAL_ entradas",
        infoEmpty: "Mostrando 0 a 0 de 0 entradas",
        infoFiltered: "(filtrado de _MAX_ entradas totales)",
        lengthMenu: "Mostrar _MENU_ entradas",
        loadingRecords: "Cargando...",
        processing: "Procesando...",
        search: "Buscar:",
        zeroRecords: "No se encontraron registros coincidentes",
        paginate: {
          first: "Primero",
          last: "Último",
          next: "Siguiente",
          previous: "Anterior",
        },
        aria: {
          sortAscending: ": activar para ordenar la columna ascendentemente",
          sortDescending: ": activar para ordenar la columna descendentemente",
        },
      },
      destroy: true,
      responsive: true,
      pageLength: 10,
      order: [[0, "asc"]],
    });

    // Click para ver módulo
    $("#TablaModulos tbody").on("click", ".ver-modulo-btn", function () {
      const idModulo = $(this).data("idmodulo");
      verModulo(idModulo);
    });

    // Click para editar módulo
    $("#TablaModulos tbody").on("click", ".editar-modulo-btn", function () {
      const idModulo = $(this).data("idmodulo");
      editarModulo(idModulo);
    });

    // Click para eliminar módulo
    $("#TablaModulos tbody").on("click", ".eliminar-modulo-btn", function () {
      const idModulo = $(this).data("idmodulo");
      const tituloModulo = $(this).data("titulo");
      eliminarModulo(idModulo, tituloModulo);
    });
  });

  // MODAL REGISTRAR MÓDULO
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

  // SUBMIT FORM REGISTRAR
  if (formRegistrar) {
    formRegistrar.addEventListener("submit", function (e) {
      e.preventDefault();
      registrarModulo();
    });
  }

  // MODAL ACTUALIZAR MÓDULO
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

  // MODAL VER MÓDULO
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

  // MODAL VER CONTROLADORES
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

// FUNCIONES
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
  // Llenar los campos del formulario con los datos existentes
  document.getElementById("idModuloActualizar").value = modulo.idmodulo || "";
  document.getElementById("moduloTituloActualizar").value = modulo.titulo || "";
  document.getElementById("moduloDescripcionActualizar").value = modulo.descripcion || "";

  // Inicializar validaciones para el formulario de actualizar
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

  // Validar campos vacíos obligatorios
  const camposObligatorios = camposFormularioActualizarModulo.filter((c) => {
    return c.mensajes && c.mensajes.vacio;
  });

  if (!validarCamposVacios(camposObligatorios, "formActualizarModulo")) {
    return;
  }

  // Validar formatos específicos
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
  // Llenar los campos del modal de ver
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
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
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
