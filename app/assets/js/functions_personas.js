import { abrirModal, cerrarModal } from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  validarCamposVacios,
  validarCampo,
  validarSelect,
  limpiarValidaciones,
} from "./validaciones.js";

let tablaPersonas;

const camposFormularioPersona = [
  {
    id: "nombrePersona",
    tipo: "input",
    regex: expresiones.nombre,
    mensajes: {
      vacio: "El nombre es obligatorio.",
      formato: "Nombre inválido.",
    },
  },
  {
    id: "apellidoPersona",
    tipo: "input",
    regex: expresiones.nombre,
    mensajes: {
      vacio: "El apellido es obligatorio.",
      formato: "Apellido inválido.",
    },
  },
  {
    id: "cedulaPersona",
    tipo: "input",
    regex: expresiones.cedula,
    mensajes: {
      vacio: "La cédula es obligatoria.",
      formato: "Cédula inválida.",
    },
  },
  {
    id: "rifPersona",
    tipo: "input",
    regex: expresiones.rif,
    mensajes: {
      vacio: "El RIF es obligatorio.",
      formato: "RIF inválido (Ej: V-12345678-9).",
    },
  },
  {
    id: "telefonoPersona",
    tipo: "input",
    regex: expresiones.telefono_principal,
    mensajes: {
      vacio: "El teléfono es obligatorio.",
      formato: "Teléfono inválido.",
    },
  },
  {
    id: "tipoPersona",
    tipo: "select",
    mensajes: { vacio: "Seleccione un tipo de persona." },
  },
  {
    id: "generoPersona",
    tipo: "select",
    mensajes: { vacio: "Seleccione un género." },
  },
  {
    id: "fechaNacimientoPersona",
    tipo: "date",
    mensajes: { vacio: "La fecha de nacimiento es obligatoria." },
  },
  {
    id: "estadoPersona",
    tipo: "input",
    regex: expresiones.textoGeneral,
    mensajes: { vacio: "El estado es obligatorio." },
  },
  {
    id: "ciudadPersona",
    tipo: "input",
    regex: expresiones.textoGeneral,
    mensajes: { vacio: "La ciudad es obligatoria." },
  },
  {
    id: "paisPersona",
    tipo: "input",
    regex: expresiones.textoGeneral,
    mensajes: { vacio: "El país es obligatorio." },
  },
  {
    id: "correoPersona",
    tipo: "input",
    regex: expresiones.email,
    mensajes: {
      vacio: "El correo es obligatorio para el usuario.",
      formato: "Correo inválido.",
    },
  },
  {
    id: "clavePersona",
    tipo: "input",
    regex: expresiones.password,
    mensajes: {
      vacio: "La clave es obligatoria para el usuario.",
      formato: "Clave inválida (6-16 caracteres).",
    },
  },
  {
    id: "rol",
    tipo: "select",
    mensajes: { vacio: "Seleccione un rol para el usuario." },
  },
];

const camposFormularioActualizarPersona = [
  {
    id: "nombreActualizar",
    tipo: "input",
    regex: expresiones.nombre,
    mensajes: {
      vacio: "El nombre es obligatorio.",
      formato: "Nombre inválido.",
    },
  },
  {
    id: "apellidoActualizar",
    tipo: "input",
    regex: expresiones.nombre,
    mensajes: {
      vacio: "El apellido es obligatorio.",
      formato: "Apellido inválido.",
    },
  },
  {
    id: "cedulaActualizar",
    tipo: "input",
    regex: expresiones.cedula,
    mensajes: {
      vacio: "La cédula es obligatoria.",
      formato: "Cédula inválida.",
    },
  },
  {
    id: "rifActualizar",
    tipo: "input",
    regex: expresiones.rif,
    mensajes: { formato: "RIF inválido." },
  },
  {
    id: "telefonoActualizar",
    tipo: "input",
    regex: expresiones.telefono_principal,
    mensajes: {
      vacio: "El teléfono es obligatorio.",
      formato: "Teléfono inválido.",
    },
  },
  {
    id: "tipoActualizar",
    tipo: "select",
    mensajes: { vacio: "Seleccione un tipo de persona." },
  },
  {
    id: "generoActualizar",
    tipo: "select",
    mensajes: { vacio: "Seleccione un género." },
  },
  {
    id: "fechaNacimientoActualizar",
    tipo: "date",
    mensajes: { vacio: "La fecha de nacimiento es obligatoria." },
  },
  {
    id: "correoActualizar",
    tipo: "input",
    regex: expresiones.email,
    mensajes: { formato: "Correo inválido." },
  },
  {
    id: "claveActualizar",
    tipo: "input",
    regex: expresiones.password,
    mensajes: {
      formato: "Clave inválida (dejar en blanco para no cambiar).",
    },
  },
  {
    id: "rolActualizar",
    tipo: "select",
    mensajes: {},
  },
];

document.addEventListener("DOMContentLoaded", function () {
  // Cargar roles al inicio
  cargarRoles();

  $(document).ready(function () {
    // DATATABLE PERSONAS
    tablaPersonas = $("#TablaPersonas").DataTable({
      processing: true,
      ajax: {
        url: "Personas/getPersonasData",
        type: "GET",
        dataSrc: function (json) {
          if (json && json.data) {
            return json.data;
          } else {
            console.error(
              "La respuesta del servidor no tiene la estructura esperada (falta 'data'):",
              json
            );
            $("#TablaPersonas_processing").hide();
            alert(
              "Error: No se pudieron cargar los datos de personas correctamente."
            );
            return [];
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.error(
            "Error AJAX al cargar datos para TablaPersonas: ",
            textStatus,
            errorThrown,
            jqXHR.responseText
          );
          $("#TablaPersonas_processing").hide();
          alert(
            "Error de comunicación al cargar los datos de personas. Por favor, intente más tarde."
          );
        },
      },
      columns: [
        { data: "persona_nombre", title: "Nombre" },
        { data: "persona_apellido", title: "Apellido" },
        { data: "persona_cedula", title: "Cédula" },
        {
          data: "persona_genero",
          title: "Género",
          render: function (data, type, row) {
            if (data) {
              return data.charAt(0).toUpperCase() + data.slice(1);
            }
            return '<i style="color: silver;">N/A</i>';
          },
        },
        { data: "telefono_principal", title: "Teléfono" },
        {
          data: "persona_estatus",
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
          data: null,
          title: "Acciones",
          orderable: false,
          searchable: false,
          render: function (data, type, row) {
            const nombreCompleto = `${row.persona_nombre || ""} ${
              row.persona_apellido || ""
            }`.trim();
            return `
              <button class="ver-persona-btn text-green-500 hover:text-green-700 p-1" data-idpersona-pk="${row.idpersona_pk}" title="Ver detalles">
                  <i class="fas fa-eye fa-lg"></i>
              </button>
              <button class="editar-persona-btn text-blue-500 hover:text-blue-700 p-1 ml-2" data-idpersona-pk="${row.idpersona_pk}" title="Editar">
                  <i class="fas fa-edit fa-lg"></i>
              </button>
              <button class="eliminar-persona-btn text-red-500 hover:text-red-700 p-1 ml-2" data-idpersona-pk="${row.idpersona_pk}" data-nombre="${nombreCompleto}" title="Eliminar">
                  <i class="fas fa-trash fa-lg"></i>
              </button>
            `;
          },
          width: "120px",
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
      order: [[1, "asc"]],
    });

    // Click para ver persona
    $("#TablaPersonas tbody").on("click", ".ver-persona-btn", function () {
      const idPersona = $(this).data("idpersona-pk");
      verPersona(idPersona);
    });

    // Click para editar persona
    $("#TablaPersonas tbody").on("click", ".editar-persona-btn", function () {
      const idPersona = $(this).data("idpersona-pk");
      editarPersona(idPersona);
    });

    // Click para eliminar persona
    $("#TablaPersonas tbody").on(
      "click",
      ".eliminar-persona-btn",
      function () {
        const idPersona = $(this).data("idpersona-pk");
        const nombrePersona = $(this).data("nombre");
        eliminarPersona(idPersona, nombrePersona);
      }
    );
  });

  // MODAL REGISTRAR PERSONA
  const btnAbrirModalRegistro = document.getElementById(
    "btnAbrirModalRegistrarPersona"
  );
  const formRegistrar = document.getElementById("formRegistrarPersona");
  const btnCerrarModalRegistro = document.getElementById(
    "btnCerrarModalRegistrar"
  );
  const checkboxCrearUsuario = document.getElementById("crearUsuario");
  const camposUsuarioContainer = document.getElementById(
    "usuarioCamposRegistrar"
  );
  const btnCancelarModalRegistro = document.getElementById(
    "btnCancelarModalRegistrar"
  );
  const btnGuardarPersona = document.getElementById("btnGuardarPersona");

  if (btnAbrirModalRegistro) {
    btnAbrirModalRegistro.addEventListener("click", function () {
      abrirModal("modalRegistrarPersona");
      if (checkboxCrearUsuario) checkboxCrearUsuario.checked = false;
      if (camposUsuarioContainer)
        camposUsuarioContainer.classList.add("hidden");
      if (formRegistrar) formRegistrar.reset();
      inicializarValidaciones(camposFormularioPersona, "formRegistrarPersona");
    });
  }

  if (btnCerrarModalRegistro) {
    btnCerrarModalRegistro.addEventListener("click", function () {
      cerrarModal("modalRegistrarPersona");
    });
  }

  if (btnCancelarModalRegistro) {
    btnCancelarModalRegistro.addEventListener("click", function () {
      cerrarModal("modalRegistrarPersona");
    });
  }

  if (checkboxCrearUsuario && camposUsuarioContainer) {
    function actualizarVisibilidadCamposUsuario() {
      if (checkboxCrearUsuario.checked) {
        camposUsuarioContainer.classList.remove("hidden");
      } else {
        camposUsuarioContainer.classList.add("hidden");
      }
    }
    actualizarVisibilidadCamposUsuario();
    checkboxCrearUsuario.addEventListener(
      "change",
      actualizarVisibilidadCamposUsuario
    );
  }

  // SUBMIT FORM REGISTRAR
  if (formRegistrar && checkboxCrearUsuario) {
    formRegistrar.addEventListener("submit", function (e) {
      e.preventDefault();
      registrarPersona();
    });
  }

  // MODAL ACTUALIZAR PERSONA
  const btnCerrarModalActualizar = document.getElementById(
    "btnCerrarModalActualizar"
  );
  const btnCancelarModalActualizar = document.getElementById(
    "btnCancelarModalActualizar"
  );
  const formActualizar = document.getElementById("formActualizarPersona");
  const btnActualizarPersona = document.getElementById("btnActualizarPersona");

  if (btnCerrarModalActualizar) {
    btnCerrarModalActualizar.addEventListener("click", function () {
      cerrarModal("modalActualizarPersona");
    });
  }

  if (btnCancelarModalActualizar) {
    btnCancelarModalActualizar.addEventListener("click", function () {
      cerrarModal("modalActualizarPersona");
    });
  }

  if (formActualizar) {
    formActualizar.addEventListener("submit", function (e) {
      e.preventDefault();
      actualizarPersona();
    });
  }

  // MODAL VER PERSONA
  const btnCerrarModalVer = document.getElementById("btnCerrarModalVer");
  if (btnCerrarModalVer) {
    btnCerrarModalVer.addEventListener("click", function () {
      cerrarModal("modalVerPersona");
    });
  }
});

function editarPersona(idPersona) {
  fetch(`Personas/getPersonaById/${idPersona}`, {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const persona = result.data;
        mostrarModalEditarPersona(persona);
      } else {
        Swal.fire("Error", "No se pudieron cargar los datos.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    });
}

function mostrarModalEditarPersona(persona) {
  // Llenar los campos del formulario con los datos existentes
  document.getElementById("idPersonaActualizar").value =
    persona.idpersona_pk || "";
  document.getElementById("nombreActualizar").value =
    persona.persona_nombre || "";
  document.getElementById("apellidoActualizar").value =
    persona.persona_apellido || "";
  document.getElementById("cedulaActualizar").value =
    persona.persona_cedula || "";
  document.getElementById("telefonoActualizar").value =
    persona.telefono_principal || "";
  document.getElementById("tipoActualizar").value =
    persona.persona_genero || "";
  document.getElementById("fechaNacimientoActualizar").value =
    persona.persona_fecha || "";
  document.getElementById("correoActualizar").value =
    persona.persona_correo_info || "";
  document.getElementById("direccionActualizar").value =
    persona.persona_direccion || "";
  document.getElementById("observacionesActualizar").value =
    persona.persona_observaciones || "";

  // Llenar campos de usuario si existen
  if (persona.idusuario) {
    document.getElementById("correoUsuarioActualizar").value =
      persona.correo_usuario_login || "";
    document.getElementById("rolActualizar").value = persona.idrol || "";
    // No llenamos la clave por seguridad
  }

  // Inicializar validaciones para el formulario de actualizar
  inicializarValidaciones(
    camposFormularioActualizarPersona,
    "formActualizarPersona"
  );

  abrirModal("modalActualizarPersona");
}

function actualizarPersona() {
  const formActualizar = document.getElementById("formActualizarPersona");
  const btnActualizarPersona = document.getElementById("btnActualizarPersona");
  const idPersona = document.getElementById("idPersonaActualizar").value;

  // Validar campos vacíos obligatorios
  const camposObligatorios = camposFormularioActualizarPersona.filter((c) => {
    return c.mensajes && c.mensajes.vacio;
  });

  if (!validarCamposVacios(camposObligatorios, "formActualizarPersona")) {
    return;
  }

  // Validar formatos específicos
  let formularioConErroresEspecificos = false;
  for (const campo of camposFormularioActualizarPersona) {
    const inputElement = formActualizar.querySelector(`#${campo.id}`);
    if (!inputElement) continue;

    let esValidoEsteCampo = true;
    if (campo.tipo === "select") {
      // Solo validar selects obligatorios
      if (campo.mensajes && campo.mensajes.vacio) {
        esValidoEsteCampo = validarSelect(
          campo.id,
          campo.mensajes,
          "formActualizarPersona"
        );
      }
    } else if (
      ["input", "date", "email", "password", "textarea", "text"].includes(
        campo.tipo
      )
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
    idpersona_pk: idPersona,
    persona: {},
    usuario: null,
  };

  // Datos de persona
  dataParaEnviar.persona.nombre = formData.get("nombre") || "";
  dataParaEnviar.persona.apellido = formData.get("apellido") || "";
  dataParaEnviar.persona.identificacion = formData.get("identificacion") || "";
  dataParaEnviar.persona.genero = formData.get("genero") || "";
  dataParaEnviar.persona.fecha_nacimiento =
    formData.get("fecha_nacimiento") || null;
  dataParaEnviar.persona.correo_electronico =
    formData.get("correo_electronico") || "";
  dataParaEnviar.persona.direccion = formData.get("direccion") || "";
  dataParaEnviar.persona.observaciones = formData.get("observaciones") || "";
  dataParaEnviar.persona.telefono_principal =
    formData.get("telefono_principal") || "";

  // Datos de usuario si se proporcionan
  const correoUsuario = formData.get("correo_electronico_usuario") || "";
  const rolUsuario = formData.get("idrol_usuario") || "";
  
  if (correoUsuario || rolUsuario) {
    dataParaEnviar.actualizar_usuario_flag = "1";
    dataParaEnviar.usuario = {};
    dataParaEnviar.usuario.correo_electronico_usuario = correoUsuario;
    dataParaEnviar.usuario.clave_usuario = formData.get("clave_usuario") || "";
    dataParaEnviar.usuario.idrol_usuario = rolUsuario;
  }

  if (btnActualizarPersona) {
    btnActualizarPersona.disabled = true;
    btnActualizarPersona.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Actualizando...`;
  }

  fetch("Personas/updatePersona", {
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
        cerrarModal("modalActualizarPersona");
        if (typeof tablaPersonas !== "undefined" && tablaPersonas.ajax) {
          tablaPersonas.ajax.reload(null, false);
        }
      } else {
        Swal.fire(
          "Error",
          result.message || "No se pudo actualizar la persona.",
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
      if (btnActualizarPersona) {
        btnActualizarPersona.disabled = false;
        btnActualizarPersona.innerHTML = `<i class="fas fa-save mr-2"></i> Actualizar Persona`;
      }
    });
}


// FUNCIONES
function cargarRoles() {
  fetch("Personas/getRoles", {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const selectRolRegistrar = document.getElementById("rol");
        const selectRolActualizar = document.getElementById("rolActualizar");

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

function registrarPersona() {
  const formRegistrar = document.getElementById("formRegistrarPersona");
  const checkboxCrearUsuario = document.getElementById("crearUsuario");
  const btnGuardarPersona = document.getElementById("btnGuardarPersona");

  const esCrearUsuario = checkboxCrearUsuario.checked;

  const camposParaValidarVacios = camposFormularioPersona.filter((c) => {
    const idsCamposUsuario = ["correoPersona", "clavePersona", "rol"];
    if (idsCamposUsuario.includes(c.id)) {
      return esCrearUsuario;
    }
    return true;
  });

  if (!validarCamposVacios(camposParaValidarVacios, "formRegistrarPersona")) {
    return;
  }

  let formularioConErroresEspecificos = false;
  for (const campo of camposFormularioPersona) {
    const inputElement = formRegistrar.querySelector(`#${campo.id}`);
    if (!inputElement) continue;

    const idsCamposUsuario = ["correoPersona", "clavePersona", "rol"];
    if (idsCamposUsuario.includes(campo.id) && !esCrearUsuario) {
      continue;
    }

    let esValidoEsteCampo = true;
    if (campo.tipo === "select") {
      esValidoEsteCampo = validarSelect(
        campo.id,
        campo.mensajes,
        "formRegistrarPersona"
      );
    } else if (
      ["input", "date", "email", "password", "textarea", "text"].includes(
        campo.tipo
      )
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
    persona: {},
    usuario: null,
  };

  dataParaEnviar.persona.nombre = formData.get("nombre") || "";
  dataParaEnviar.persona.apellido = formData.get("apellido") || "";
  dataParaEnviar.persona.identificacion = formData.get("identificacion") || "";
  dataParaEnviar.persona.genero = formData.get("genero") || "";
  dataParaEnviar.persona.fecha_nacimiento =
    formData.get("fecha_nacimiento") || null;
  dataParaEnviar.persona.correo_electronico =
    formData.get("correo_electronico") || "";
  dataParaEnviar.persona.direccion = formData.get("direccion") || "";
  dataParaEnviar.persona.observaciones = formData.get("observaciones") || "";
  dataParaEnviar.persona.telefono_principal =
    formData.get("telefono_principal") || "";

  dataParaEnviar.crear_usuario_flag = esCrearUsuario ? "1" : "0";

  if (esCrearUsuario) {
    dataParaEnviar.usuario = {};
    dataParaEnviar.usuario.idrol = formData.get("idrol_usuario") || "";
    dataParaEnviar.usuario.correo_login =
      formData.get("correo_electronico_usuario") || "";
    dataParaEnviar.usuario.clave = formData.get("clave_usuario") || "";
    dataParaEnviar.usuario.username =
      formData.get("correo_electronico_usuario") || "";
  }

  if (btnGuardarPersona) {
    btnGuardarPersona.disabled = true;
    btnGuardarPersona.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...`;
  }

  fetch("Personas/createPersona", {
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
        cerrarModal("modalRegistrarPersona");
        if (typeof tablaPersonas !== "undefined" && tablaPersonas.ajax) {
          tablaPersonas.ajax.reload(null, false);
        }
      } else {
        Swal.fire(
          "Error",
          result.message || "No se pudo registrar la persona.",
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
      if (btnGuardarPersona) {
        btnGuardarPersona.disabled = false;
        btnGuardarPersona.innerHTML = `<i class="fas fa-save mr-2"></i> Guardar Persona`;
      }
    });
}

function verPersona(idPersona) {
  fetch(`Personas/getPersonaById/${idPersona}`, {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const persona = result.data;
        mostrarModalVerPersona(persona);
      } else {
        Swal.fire("Error", "No se pudieron cargar los datos.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    });
}

function mostrarModalVerPersona(persona) {
  // Llenar los campos del modal de ver
  document.getElementById("verNombre").textContent = persona.persona_nombre || "N/A";
  document.getElementById("verApellido").textContent = persona.persona_apellido || "N/A";
  document.getElementById("verCedula").textContent = persona.persona_cedula || "N/A";
  document.getElementById("verGenero").textContent = persona.persona_genero || "N/A";
  document.getElementById("verFechaNacimiento").textContent = persona.fecha_nacimiento_formato || "N/A";
  document.getElementById("verCorreo").textContent = persona.persona_correo_info || "N/A";
  document.getElementById("verEstado").textContent = persona.persona_estatus || "N/A";
  document.getElementById("verTelefono").textContent = persona.telefono_principal || "N/A";
  document.getElementById("verDire").textContent= persona.persona_direccion || "N/A";
  document.getElementById("verObser").textContent= persona.persona_observaciones || "N/A";


  // Información de usuario
  const tieneUsuario = persona.idusuario ? "Sí" : "No";
  document.getElementById("verTieneUsuario").textContent = tieneUsuario;
  document.getElementById("verUsuarioCorreo").textContent = persona.correo_usuario_login || "N/A";
  document.getElementById("verUsuarioRol").textContent = persona.rol_nombre || "N/A";
  document.getElementById("verUsuarioEstatus").textContent = persona.estatus_usuario || "N/A";

  abrirModal("modalVerPersona");
}

function eliminarPersona(idPersona, nombrePersona) {
  Swal.fire({
    title: "¿Estás seguro?",
    text: `¿Deseas eliminar a ${nombrePersona}? Esta acción no se puede deshacer.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      const dataParaEnviar = {
        idpersona_pk: idPersona,
      };

      fetch("Personas/deletePersona", {
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
            if (typeof tablaPersonas !== "undefined" && tablaPersonas.ajax) {
              tablaPersonas.ajax.reload(null, false);
            }
          } else {
            Swal.fire(
              "Error",
              result.message || "No se pudo eliminar la persona.",
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
