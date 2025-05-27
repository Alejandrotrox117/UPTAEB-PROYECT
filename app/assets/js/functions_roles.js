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

document.addEventListener("DOMContentLoaded", function () {
  $(document).ready(function () {
    // DATATABLE ROLES
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
            $("#TablaRoles_processing").hide();
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
          $("#TablaRoles_processing").hide();
          alert(
            "Error de comunicación al cargar los datos de roles. Por favor, intente más tarde."
          );
        },
      },
      columns: [
        { data: "nombre", title: "Nombre" },
        { data: "descripcion", title: "Descripción" },
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
          data: "fecha_creacion",
          title: "Fecha Creación",
          render: function (data, type, row) {
            if (data) {
              const fecha = new Date(data);
              return fecha.toLocaleDateString("es-ES");
            }
            return '<i style="color: silver;">N/A</i>';
          },
        },
        {
          data: null,
          title: "Acciones",
          orderable: false,
          searchable: false,
          render: function (data, type, row) {
            return `
              <button class="ver-rol-btn text-green-500 hover:text-green-700 p-1" data-idrol="${row.idrol}" title="Ver detalles">
                  <i class="fas fa-eye fa-lg"></i>
              </button>
              <button class="editar-rol-btn text-blue-500 hover:text-blue-700 p-1 ml-2" data-idrol="${row.idrol}" title="Editar">
                  <i class="fas fa-edit fa-lg"></i>
              </button>
              <button class="eliminar-rol-btn text-red-500 hover:text-red-700 p-1 ml-2" data-idrol="${row.idrol}" data-nombre="${row.nombre}" title="Eliminar">
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

    // Click para ver rol
    $("#TablaRoles tbody").on("click", ".ver-rol-btn", function () {
      const idRol = $(this).data("idrol");
      verRol(idRol);
    });

    // Click para editar rol
    $("#TablaRoles tbody").on("click", ".editar-rol-btn", function () {
      const idRol = $(this).data("idrol");
      editarRol(idRol);
    });

    // Click para eliminar rol
    $("#TablaRoles tbody").on("click", ".eliminar-rol-btn", function () {
      const idRol = $(this).data("idrol");
      const nombreRol = $(this).data("nombre");
      eliminarRol(idRol, nombreRol);
    });
  });

  // MODAL REGISTRAR ROL
  const btnAbrirModalRegistro = document.getElementById(
    "btnAbrirModalRegistrarRol"
  );
  const formRegistrar = document.getElementById("formRegistrarRol");
  const btnCerrarModalRegistro = document.getElementById(
    "btnCerrarModalRegistrar"
  );
  const btnCancelarModalRegistro = document.getElementById(
    "btnCancelarModalRegistrar"
  );
  const btnGuardarRol = document.getElementById("btnGuardarRol");

  if (btnAbrirModalRegistro) {
    btnAbrirModalRegistro.addEventListener("click", function () {
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

  // SUBMIT FORM REGISTRAR
  if (formRegistrar) {
    formRegistrar.addEventListener("submit", function (e) {
      e.preventDefault();
      registrarRol();
    });
  }

  // MODAL ACTUALIZAR ROL
  const btnCerrarModalActualizar = document.getElementById(
    "btnCerrarModalActualizar"
  );
  const btnCancelarModalActualizar = document.getElementById(
    "btnCancelarModalActualizar"
  );
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
  document.getElementById("descripcionActualizar").value =
    rol.descripcion || "";
  document.getElementById("estatusActualizar").value = rol.estatus || "";
  inicializarValidaciones(camposFormularioActualizarRol, "formActualizarRol");

  abrirModal("modalActualizarRol");
}

function actualizarRol() {
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
  document.getElementById("verDescripcion").textContent =
    rol.descripcion || "N/A";
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
