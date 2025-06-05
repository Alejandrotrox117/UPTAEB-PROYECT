import { abrirModal, cerrarModal } from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  validarCamposVacios,
  validarCampo,
  validarSelect,
  limpiarValidaciones,
} from "./validaciones.js";

let tablaUsuarios;

const camposFormularioUsuario = [
  {
    id: "usuarioNombre",
    tipo: "input",
    regex: expresiones.usuario,
    mensajes: {
      vacio: "El nombre de usuario es obligatorio.",
      formato: "Nombre de usuario inválido.",
    },
  },
  {
    id: "usuarioCorreo",
    tipo: "input",
    regex: expresiones.email,
    mensajes: {
      vacio: "El correo es obligatorio.",
      formato: "Correo inválido.",
    },
  },
  {
    id: "usuarioClave",
    tipo: "input",
    regex: expresiones.password,
    mensajes: {
      vacio: "La contraseña es obligatoria.",
      formato: "Contraseña inválida (6-16 caracteres).",
    },
  },
  {
    id: "usuarioRol",
    tipo: "select",
    mensajes: { vacio: "Seleccione un rol." },
  },
  // {
  //   id: "usuarioPersona",
  //   tipo: "select",
  //   mensajes: {},
  // },
];

const camposFormularioActualizarUsuario = [
  {
    id: "usuarioNombreActualizar",
    tipo: "input",
    regex: expresiones.usuario,
    mensajes: {
      vacio: "El nombre de usuario es obligatorio.",
      formato: "Nombre de usuario inválido.",
    },
  },
  {
    id: "usuarioCorreoActualizar",
    tipo: "input",
    regex: expresiones.email,
    mensajes: {
      vacio: "El correo es obligatorio.",
      formato: "Correo inválido.",
    },
  },
  {
    id: "usuarioClaveActualizar",
    tipo: "input",
    regex: expresiones.password,
    mensajes: {
      formato: "Contraseña inválida (dejar en blanco para no cambiar).",
    },
  },
  {
    id: "usuarioRolActualizar",
    tipo: "select",
    mensajes: { vacio: "Seleccione un rol." },
  },
  {
    id: "usuarioPersonaActualizar",
    tipo: "select",
    mensajes: {},
  },
];

document.addEventListener("DOMContentLoaded", function () {
  // Cargar roles y personas al inicio
  cargarRoles();
  cargarPersonas();

  $(document).ready(function () {
    if ($.fn.DataTable.isDataTable("#TablaUsuarios")) {
      $("#TablaUsuarios").DataTable().destroy();
    }

    tablaUsuarios = $("#TablaUsuarios").DataTable({
      processing: true,
      ajax: {
        url: "Usuarios/getUsuariosData",
        type: "GET",
        dataSrc: function (json) {
          if (json && json.data) {
            return json.data;
          } else {
            console.error(
              "La respuesta del servidor no tiene la estructura esperada (falta 'data'):",
              json
            );
            $("#TablaUsuarios_processing").css("display", "none"); 
            alert(
              "Error: No se pudieron cargar los datos de usuarios correctamente."
            );
            return [];
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.error(
            "Error AJAX al cargar datos para TablaUsuarios: ",
            textStatus,
            errorThrown,
            jqXHR.responseText
          );
          $("#TablaUsuarios_processing").css("display", "none"); 
          alert(
            "Error de comunicación al cargar los datos de usuarios. Por favor, intente más tarde."
          );
        },
      },
      columns: [
        {
          data: "correo",
          title: "Correo",
          className:
            "all whitespace-nowrap py-2 px-3 text-gray-700 dt-fixed-col-background break-all",
        },
        {
          data: "rol_nombre",
          title: "Rol",
          className: "desktop whitespace-nowrap py-2 px-3 text-gray-700",
        },
        {
          data: "persona_nombre_completo",
          title: "Persona Asociada",
          className: "tablet-l whitespace-nowrap py-2 px-3 text-gray-700",
          render: function (data, type, row) {
            if (data) {
              return data;
            }
            return '<i class="text-gray-400">Sin asociar</i>';
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
          data: null,
          title: "Acciones",
          orderable: false,
          searchable: false,
          className: "all text-center actions-column py-1 px-2",
          width: "auto",
          render: function (data, type, row) {
            const nombreUsuarioParaEliminar = row.usuario || row.correo; 
            return `
              <div class="inline-flex items-center space-x-1">
                <button class="ver-usuario-btn text-green-600 hover:text-green-700 p-1 transition-colors duration-150" data-idusuario="${row.idusuario}" title="Ver detalles">
                    <i class="fas fa-eye fa-fw text-base"></i>
                </button>
                <button class="editar-usuario-btn text-blue-600 hover:text-blue-700 p-1 transition-colors duration-150" data-idusuario="${row.idusuario}" title="Editar">
                    <i class="fas fa-edit fa-fw text-base"></i>
                </button>
                <button class="eliminar-usuario-btn text-red-600 hover:text-red-700 p-1 transition-colors duration-150" data-idusuario="${row.idusuario}" data-nombre="${nombreUsuarioParaEliminar}" title="Desactivar">
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
          '<div class="text-center py-4"><i class="fas fa-users-slash fa-2x text-gray-400 mb-2"></i><p class="text-gray-600">No hay usuarios disponibles.</p></div>',
        info: "Mostrando _START_ a _END_ de _TOTAL_ usuarios",
        infoEmpty: "Mostrando 0 usuarios",
        infoFiltered: "(filtrado de _MAX_ usuarios totales)",
        lengthMenu: "Mostrar _MENU_ usuarios",
        search: "_INPUT_",
        searchPlaceholder: "Buscar usuario...",
        zeroRecords:
          '<div class="text-center py-4"><i class="fas fa-user-times fa-2x text-gray-400 mb-2"></i><p class="text-gray-600">No se encontraron coincidencias.</p></div>',
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
        console.log("DataTable Usuarios inicializado correctamente");
        window.tablaUsuarios = this.api();
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

    $("#TablaUsuarios tbody").on("click", ".ver-usuario-btn", function () {
      const idUsuario = $(this).data("idusuario");
      if (idUsuario && typeof verUsuario === "function") {
        verUsuario(idUsuario);
      } else {
        console.error(
          "Función verUsuario no definida o idUsuario no encontrado."
        );
        alert("Error: No se pudo obtener el ID del usuario para verlo.");
      }
    });

    $("#TablaUsuarios tbody").on("click", ".editar-usuario-btn", function () {
      const idUsuario = $(this).data("idusuario");
      if (idUsuario && typeof editarUsuario === "function") {
        editarUsuario(idUsuario);
      } else {
        console.error(
          "Función editarUsuario no definida o idUsuario no encontrado."
        );
        alert("Error: No se pudo obtener el ID del usuario para editarlo.");
      }
    });

    $("#TablaUsuarios tbody").on("click", ".eliminar-usuario-btn", function () {
      const idUsuario = $(this).data("idusuario");
      const nombreUsuario = $(this).data("nombre"); 
      if (idUsuario && typeof eliminarUsuario === "function") {
        eliminarUsuario(idUsuario, nombreUsuario);
      } else {
        console.error(
          "Función eliminarUsuario no definida o idUsuario no encontrado."
        );
        alert("Error: No se pudo obtener el ID del usuario para desactivarlo.");
      }
    });
  });

  // MODAL REGISTRAR USUARIO
  const btnAbrirModalRegistro = document.getElementById(
    "btnAbrirModalRegistrarUsuario"
  );
  const formRegistrar = document.getElementById("formRegistrarUsuario");
  const btnCerrarModalRegistro = document.getElementById(
    "btnCerrarModalRegistrar"
  );
  const btnCancelarModalRegistro = document.getElementById(
    "btnCancelarModalRegistrar"
  );

  if (btnAbrirModalRegistro) {
    btnAbrirModalRegistro.addEventListener("click", function () {
      abrirModal("modalRegistrarUsuario");
      if (formRegistrar) formRegistrar.reset();
      inicializarValidaciones(camposFormularioUsuario, "formRegistrarUsuario");
    });
  }

  if (btnCerrarModalRegistro) {
    btnCerrarModalRegistro.addEventListener("click", function () {
      cerrarModal("modalRegistrarUsuario");
    });
  }

  if (btnCancelarModalRegistro) {
    btnCancelarModalRegistro.addEventListener("click", function () {
      cerrarModal("modalRegistrarUsuario");
    });
  }

  // SUBMIT FORM REGISTRAR
  if (formRegistrar) {
    formRegistrar.addEventListener("submit", function (e) {
      e.preventDefault();
      registrarUsuario();
    });
  }

  // MODAL ACTUALIZAR USUARIO
  const btnCerrarModalActualizar = document.getElementById(
    "btnCerrarModalActualizar"
  );
  const btnCancelarModalActualizar = document.getElementById(
    "btnCancelarModalActualizar"
  );
  const formActualizar = document.getElementById("formActualizarUsuario");

  if (btnCerrarModalActualizar) {
    btnCerrarModalActualizar.addEventListener("click", function () {
      cerrarModal("modalActualizarUsuario");
    });
  }

  if (btnCancelarModalActualizar) {
    btnCancelarModalActualizar.addEventListener("click", function () {
      cerrarModal("modalActualizarUsuario");
    });
  }

  if (formActualizar) {
    formActualizar.addEventListener("submit", function (e) {
      e.preventDefault();
      actualizarUsuario();
    });
  }

  // MODAL VER USUARIO
  const btnCerrarModalVer = document.getElementById("btnCerrarModalVer");
  const btnCerrarModalVer2 = document.getElementById("btnCerrarModalVer2");
  
  if (btnCerrarModalVer) {
    btnCerrarModalVer.addEventListener("click", function () {
      cerrarModal("modalVerUsuario");
    });
  }

  if (btnCerrarModalVer2) {
    btnCerrarModalVer2.addEventListener("click", function () {
      cerrarModal("modalVerUsuario");
    });
  }
});

// FUNCIONES
function cargarRoles() {
  fetch("Usuarios/getRoles", {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const selectRolRegistrar = document.getElementById("usuarioRol");
        const selectRolActualizar = document.getElementById("usuarioRolActualizar");

        if (selectRolRegistrar) {
          selectRolRegistrar.innerHTML =
            '<option value="">Seleccione un rol</option>';
          result.data.forEach((rol) => {
            selectRolRegistrar.innerHTML += `<option value="${rol.idrol}">${rol.nombre}</option>`;
          });
        }

        if (selectRolActualizar) {
          selectRolActualizar.innerHTML =
            '<option value="">Seleccione un rol</option>';
          result.data.forEach((rol) => {
            selectRolActualizar.innerHTML += `<option value="${rol.idrol}">${rol.nombre}</option>`;
          });
        }
      }
    })
    .catch((error) => {
      console.error("Error al cargar roles:", error);
    });
}

function cargarPersonas() {
  fetch("Usuarios/getPersonas", {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const selectPersonaRegistrar = document.getElementById("usuarioPersona");
        const selectPersonaActualizar = document.getElementById("usuarioPersonaActualizar");

        if (selectPersonaRegistrar) {
          selectPersonaRegistrar.innerHTML =
            '<option value="">Sin persona asociada</option>';
          result.data.forEach((persona) => {
            selectPersonaRegistrar.innerHTML += `<option value="${persona.identificacion}">${persona.nombre_completo}</option>`;
          });
        }

        if (selectPersonaActualizar) {
          selectPersonaActualizar.innerHTML =
            '<option value="">Sin persona asociada</option>';
          result.data.forEach((persona) => {
            selectPersonaActualizar.innerHTML += `<option value="${persona.identificacion}">${persona.nombre_completo}</option>`;
          });
        }
      }
    })
    .catch((error) => {
      console.error("Error al cargar personas:", error);
    });
}

function registrarUsuario() {
  const formRegistrar = document.getElementById("formRegistrarUsuario");
  const btnGuardarUsuario = document.getElementById("btnGuardarUsuario");

  if (!validarCamposVacios(camposFormularioUsuario, "formRegistrarUsuario")) {
    return;
  }

  let formularioConErroresEspecificos = false;
  for (const campo of camposFormularioUsuario) {
    const inputElement = formRegistrar.querySelector(`#${campo.id}`);
    if (!inputElement) continue;

    let esValidoEsteCampo = true;
    if (campo.tipo === "select") {
      if (campo.mensajes && campo.mensajes.vacio) {
        esValidoEsteCampo = validarSelect(
          campo.id,
          campo.mensajes,
          "formRegistrarUsuario"
        );
      }
    } else if (
      ["input", "email", "password", "text"].includes(campo.tipo)
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

  const formData = new FormData(formRegistrar);
  const dataParaEnviar = {
    usuario: formData.get("usuario") || "",
    clave: formData.get("clave") || "",
    correo: formData.get("correo") || "",
    idrol: formData.get("idrol") || "",
    personaId: formData.get("personaId") || "",
  };

  if (btnGuardarUsuario) {
    btnGuardarUsuario.disabled = true;
    btnGuardarUsuario.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...`;
  }

  fetch("Usuarios/createUsuario", {
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
        cerrarModal("modalRegistrarUsuario");
        if (typeof tablaUsuarios !== "undefined" && tablaUsuarios.ajax) {
          tablaUsuarios.ajax.reload(null, false);
        }
      } else {
        Swal.fire(
          "Error",
          result.message || "No se pudo registrar el usuario.",
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
      if (btnGuardarUsuario) {
        btnGuardarUsuario.disabled = false;
        btnGuardarUsuario.innerHTML = `<i class="fas fa-save mr-2"></i> Guardar Usuario`;
      }
    });
}

function editarUsuario(idUsuario) {
  fetch(`Usuarios/getUsuarioById/${idUsuario}`, {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const usuario = result.data;
        mostrarModalEditarUsuario(usuario);
      } else {
        Swal.fire("Error", "No se pudieron cargar los datos.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    });
}

function mostrarModalEditarUsuario(usuario) {
  // Llenar los campos del formulario con los datos existentes
  document.getElementById("idUsuarioActualizar").value =
    usuario.idusuario || "";
  document.getElementById("usuarioNombreActualizar").value =
    usuario.usuario || "";
  document.getElementById("usuarioCorreoActualizar").value =
    usuario.correo || "";
  document.getElementById("usuarioRolActualizar").value =
    usuario.idrol || "";
  document.getElementById("usuarioPersonaActualizar").value =
    usuario.personaId || "";

  // No llenamos la clave por seguridad
  document.getElementById("usuarioClaveActualizar").value = "";

  // Inicializar validaciones para el formulario de actualizar
  inicializarValidaciones(
    camposFormularioActualizarUsuario,
    "formActualizarUsuario"
  );

  abrirModal("modalActualizarUsuario");
}

function actualizarUsuario() {
  const formActualizar = document.getElementById("formActualizarUsuario");
  const btnActualizarUsuario = document.getElementById("btnActualizarUsuario");
  const idUsuario = document.getElementById("idUsuarioActualizar").value;

  // Validar campos vacíos obligatorios
  const camposObligatorios = camposFormularioActualizarUsuario.filter((c) => {
    return c.mensajes && c.mensajes.vacio;
  });

  if (!validarCamposVacios(camposObligatorios, "formActualizarUsuario")) {
    return;
  }

  // Validar formatos específicos
  let formularioConErroresEspecificos = false;
  for (const campo of camposFormularioActualizarUsuario) {
    const inputElement = formActualizar.querySelector(`#${campo.id}`);
    if (!inputElement) continue;

    let esValidoEsteCampo = true;
    if (campo.tipo === "select") {
      if (campo.mensajes && campo.mensajes.vacio) {
        esValidoEsteCampo = validarSelect(
          campo.id,
          campo.mensajes,
          "formActualizarUsuario"
        );
      }
    } else if (
      ["input", "email", "password", "text"].includes(campo.tipo)
    ) {
      // Solo validar formato si el campo tiene contenido o es obligatorio
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
    idusuario: idUsuario,
    usuario: formData.get("usuario") || "",
    correo: formData.get("correo") || "",
    idrol: formData.get("idrol") || "",
    personaId: formData.get("personaId") || "",
    clave: formData.get("clave") || "",
  };

  if (btnActualizarUsuario) {
    btnActualizarUsuario.disabled = true;
    btnActualizarUsuario.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Actualizando...`;
  }

  fetch("Usuarios/updateUsuario", {
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
        cerrarModal("modalActualizarUsuario");
        if (typeof tablaUsuarios !== "undefined" && tablaUsuarios.ajax) {
          tablaUsuarios.ajax.reload(null, false);
        }
      } else {
        Swal.fire(
          "Error",
          result.message || "No se pudo actualizar el usuario.",
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
      if (btnActualizarUsuario) {
        btnActualizarUsuario.disabled = false;
        btnActualizarUsuario.innerHTML = `<i class="fas fa-save mr-2"></i> Actualizar Usuario`;
      }
    });
}

function verUsuario(idUsuario) {
  fetch(`Usuarios/getUsuarioById/${idUsuario}`, {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const usuario = result.data;
        mostrarModalVerUsuario(usuario);
      } else {
        Swal.fire("Error", "No se pudieron cargar los datos.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    });
}

function mostrarModalVerUsuario(usuario) {
  // Llenar los campos del modal de ver
  document.getElementById("verUsuarioNombre").textContent =
    usuario.usuario || "N/A";
  document.getElementById("verUsuarioCorreo").textContent =
    usuario.correo || "N/A";
  document.getElementById("verUsuarioRol").textContent =
    usuario.rol_nombre || "N/A";
  document.getElementById("verUsuarioEstatus").textContent =
    usuario.estatus || "N/A";

  // Información de persona asociada
  document.getElementById("verPersonaNombre").textContent =
    usuario.persona_nombre_completo || "Sin persona asociada";
  document.getElementById("verPersonaCedula").textContent =
    usuario.persona_cedula || "N/A";

  abrirModal("modalVerUsuario");
}

function eliminarUsuario(idUsuario, nombreUsuario) {
  Swal.fire({
    title: "¿Estás seguro?",
    text: `¿Deseas desactivar al usuario ${nombreUsuario}? Esta acción cambiará su estatus a INACTIVO.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Sí, desactivar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      const dataParaEnviar = {
        idusuario: idUsuario,
      };

      fetch("Usuarios/deleteUsuario", {
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
            if (typeof tablaUsuarios !== "undefined" && tablaUsuarios.ajax) {
              tablaUsuarios.ajax.reload(null, false);
            }
          } else {
            Swal.fire(
              "Error",
              result.message || "No se pudo desactivar el usuario.",
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
