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
let esSuperUsuarioPersonas = false;
let idUsuarioPersonas = 0;

/**
 * Verifica si el usuario tiene un permiso específico en el módulo personas.
 * Super usuarios siempre tienen todos los permisos.
 */
function tienePermisoPersonas(accion) {
  if (esSuperUsuarioPersonas) {
    return true;
  }
  return window.permisosPersonas && window.permisosPersonas[accion] === true;
}

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

function verificarSuperUsuarioPersonas() {
  return fetch("Personas/verificarSuperUsuario", {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => result)
    .catch((error) => {
      console.error("Error al verificar super usuario:", error);
      return { status: false, es_super_usuario: false, usuario_id: 0 };
    });
}

function recargarTablaPersonas() {
  try {
    if (tablaPersonas && tablaPersonas.ajax && typeof tablaPersonas.ajax.reload === 'function') {
      tablaPersonas.ajax.reload(null, false);
      return true;
    }
    window.location.reload();
    return true;
  } catch (error) {
    console.error("Error al recargar tabla:", error);
    window.location.reload();
    return false;
  }
}

document.addEventListener("DOMContentLoaded", function () {
  
  cargarRoles();

  verificarSuperUsuarioPersonas().then((result) => {
    if (result && result.status) {
      esSuperUsuarioPersonas = result.es_super_usuario;
      idUsuarioPersonas = result.usuario_id;
    } else {
      esSuperUsuarioPersonas = false;
      idUsuarioPersonas = 0;
    }

    // Filtro: super usuarios ven todos, usuarios normales solo activos
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
      if (settings.nTable.id !== "TablaPersonas") {
        return true;
      }
      if (esSuperUsuarioPersonas) {
        return true;
      }
      var api = new $.fn.dataTable.Api(settings);
      var rowData = api.row(dataIndex).data();
      return rowData && rowData.persona_estatus && rowData.persona_estatus.toUpperCase() !== "INACTIVO";
    });

    inicializarTablaPersonas();

    setTimeout(() => {
      if (tablaPersonas && typeof tablaPersonas.draw === 'function') {
        tablaPersonas.draw(false);
      }
    }, 500);
  }).catch((error) => {
    console.error("Error en verificación de super usuario:", error);
    esSuperUsuarioPersonas = false;
    idUsuarioPersonas = 0;

    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
      if (settings.nTable.id !== "TablaPersonas") {
        return true;
      }
      var api = new $.fn.dataTable.Api(settings);
      var rowData = api.row(dataIndex).data();
      return rowData && rowData.persona_estatus && rowData.persona_estatus.toUpperCase() !== "INACTIVO";
    });

    inicializarTablaPersonas();
  });
});

function inicializarTablaPersonas() {
  if (!tienePermisoPersonas('ver')) {
    console.warn('Sin permisos para ver personas');
    return;
  }

  $(document).ready(function () {
    if ($.fn.DataTable.isDataTable('#TablaPersonas')) {
      $('#TablaPersonas').DataTable().destroy();
    }

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
            const estatusPersona = (row.persona_estatus || "").toUpperCase();
            const esInactivo = estatusPersona === "INACTIVO";

            let acciones = '<div class="inline-flex items-center space-x-1">';

            const idUsuarioAsociado = parseInt(row.usuario_id) || 0;
            const esUsuarioPropio = idUsuarioAsociado > 0 && idUsuarioAsociado === idUsuarioPersonas;

            // Botón Ver - solo si tiene permiso de ver
            if (tienePermisoPersonas('ver')) {
              acciones += `
              <button class="ver-persona-btn text-green-500 hover:text-green-700 p-1" data-idpersona-pk="${row.idpersona_pk}" title="Ver detalles">
                  <i class="fas fa-eye fa-lg"></i>
              </button>`;
            }

            if (esInactivo && esSuperUsuarioPersonas) {
              // Para personas inactivas, mostrar solo botón de reactivar (solo super usuarios)
              acciones += `
              <button class="reactivar-persona-btn text-green-600 hover:text-green-700 p-1 ml-2" data-idpersona-pk="${row.idpersona_pk}" data-nombre="${nombreCompleto}" title="Reactivar persona">
                  <i class="fas fa-undo fa-lg"></i>
              </button>`;
            } else if (!esInactivo) {
              // Botón Editar - solo si tiene permiso de editar
              if (tienePermisoPersonas('editar')) {
                acciones += `
                <button class="editar-persona-btn text-blue-500 hover:text-blue-700 p-1 ml-2" data-idpersona-pk="${row.idpersona_pk}" title="Editar">
                    <i class="fas fa-edit fa-lg"></i>
                </button>`;
              }
              // Botón Eliminar - solo si tiene permiso de eliminar y no es su propia persona
              if (tienePermisoPersonas('eliminar') && !esUsuarioPropio) {
                acciones += `
                <button class="eliminar-persona-btn text-red-500 hover:text-red-700 p-1 ml-2" data-idpersona-pk="${row.idpersona_pk}" data-nombre="${nombreCompleto}" title="Desactivar">
                    <i class="fas fa-trash fa-lg"></i>
                </button>`;
              }
            }

            acciones += '</div>';
            return acciones;
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

    
    $("#TablaPersonas tbody").on("click", ".ver-persona-btn", function () {
      const idPersona = $(this).data("idpersona-pk");
      verPersona(idPersona);
    });

    
    $("#TablaPersonas tbody").on("click", ".editar-persona-btn", function () {
      const idPersona = $(this).data("idpersona-pk");
      editarPersona(idPersona);
    });

    
    $("#TablaPersonas tbody").on(
      "click",
      ".eliminar-persona-btn",
      function () {
        const idPersona = $(this).data("idpersona-pk");
        const nombrePersona = $(this).data("nombre");
        eliminarPersona(idPersona, nombrePersona);
      }
    );

    $("#TablaPersonas tbody").on(
      "click",
      ".reactivar-persona-btn",
      function () {
        const idPersona = $(this).data("idpersona-pk");
        const nombrePersona = $(this).data("nombre");
        reactivarPersona(idPersona, nombrePersona);
      }
    );
  });
}

document.addEventListener("DOMContentLoaded", function () {
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

  
  if (formRegistrar && checkboxCrearUsuario) {
    formRegistrar.addEventListener("submit", function (e) {
      e.preventDefault();
      registrarPersona();
    });
  }

  
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

  // Checkbox para asociar usuario en modo edición
  const checkboxAsociarUsuario = document.getElementById("asociarUsuarioCheck");
  const camposAsociarContainer = document.getElementById("asociarUsuarioCampos");
  if (checkboxAsociarUsuario && camposAsociarContainer) {
    checkboxAsociarUsuario.addEventListener("change", function () {
      if (this.checked) {
        camposAsociarContainer.classList.remove("hidden");
      } else {
        camposAsociarContainer.classList.add("hidden");
      }
    });
  }

  // Botón confirmar asociar usuario
  const btnConfirmarAsociar = document.getElementById("btnConfirmarAsociarUsuario");
  if (btnConfirmarAsociar) {
    btnConfirmarAsociar.addEventListener("click", function () {
      const idPersona = document.getElementById("idPersonaActualizar").value;
      asociarUsuario(idPersona);
    });
  }

  
  const btnCerrarModalVer = document.getElementById("btnCerrarModalVer");
  const btnCerrarModalVer2 = document.getElementById("btnCerrarModalVer2");
  
  if (btnCerrarModalVer) {
    btnCerrarModalVer.addEventListener("click", function () {
      cerrarModal("modalVerPersona");
    });
  }
  
  if (btnCerrarModalVer2) {
    btnCerrarModalVer2.addEventListener("click", function () {
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

  const btnDesasociar = document.getElementById("btnDesasociarUsuario");
  const camposUsuarioContenido = document.getElementById("usuarioCamposActualizarContenido");
  const sinUsuarioMsg = document.getElementById("sinUsuarioAsociado");

  // Elementos de asociar usuario
  const checkboxAsociar = document.getElementById("asociarUsuarioCheck");
  const camposAsociar = document.getElementById("asociarUsuarioCampos");

  if (persona.idusuario) {
    // Persona tiene usuario asociado: mostrar campos y botón desasociar
    document.getElementById("correoUsuarioActualizar").value =
      persona.correo_usuario_login || "";
    document.getElementById("rolActualizar").value = persona.idrol || "";
    document.getElementById("claveActualizar").value = "";
    
    if (camposUsuarioContenido) camposUsuarioContenido.classList.remove("hidden");
    if (sinUsuarioMsg) sinUsuarioMsg.classList.add("hidden");
    if (btnDesasociar) {
      btnDesasociar.classList.remove("hidden");
      btnDesasociar.onclick = function () {
        desasociarUsuario(persona.idpersona_pk, persona.correo_usuario_login || "el usuario");
      };
    }
  } else {
    // Persona sin usuario asociado: ocultar campos, mostrar sección asociar
    document.getElementById("correoUsuarioActualizar").value = "";
    document.getElementById("rolActualizar").value = "";
    document.getElementById("claveActualizar").value = "";

    if (camposUsuarioContenido) camposUsuarioContenido.classList.add("hidden");
    if (sinUsuarioMsg) sinUsuarioMsg.classList.remove("hidden");
    if (btnDesasociar) btnDesasociar.classList.add("hidden");

    // Reset campos de asociar
    if (checkboxAsociar) checkboxAsociar.checked = false;
    if (camposAsociar) camposAsociar.classList.add("hidden");
    const correoAsociar = document.getElementById("correoUsuarioAsociar");
    const claveAsociar = document.getElementById("claveUsuarioAsociar");
    const rolAsociar = document.getElementById("rolUsuarioAsociar");
    if (correoAsociar) correoAsociar.value = "";
    if (claveAsociar) claveAsociar.value = "";
    if (rolAsociar) rolAsociar.value = "";
  }

  
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

  
  const camposObligatorios = camposFormularioActualizarPersona.filter((c) => {
    return c.mensajes && c.mensajes.vacio;
  });

  if (!validarCamposVacios(camposObligatorios, "formActualizarPersona")) {
    return;
  }

  
  let formularioConErroresEspecificos = false;
  for (const campo of camposFormularioActualizarPersona) {
    const inputElement = formActualizar.querySelector(`#${campo.id}`);
    if (!inputElement) continue;

    let esValidoEsteCampo = true;
    if (campo.tipo === "select") {
      
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

        // Poblar select de rol para asociar usuario
        const selectRolAsociar = document.getElementById("rolUsuarioAsociar");
        if (selectRolAsociar) {
          selectRolAsociar.innerHTML =
            '<option value="">Seleccione un Rol</option>';
          result.data.forEach((rol) => {
            selectRolAsociar.innerHTML += `<option value="${rol.idrol}">${rol.nombre}</option>`;
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
    text: `¿Deseas desactivar a ${nombrePersona}? Podrás reactivarla después.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#dc2626",
    cancelButtonColor: "#00c950",
    confirmButtonText: "Sí, desactivar",
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
            Swal.fire("¡Desactivado!", result.message, "success");
            recargarTablaPersonas();
          } else {
            Swal.fire(
              "Error",
              result.message || "No se pudo desactivar la persona.",
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

function reactivarPersona(idPersona, nombrePersona) {
  Swal.fire({
    title: "¿Reactivar persona?",
    text: `¿Deseas reactivar a ${nombrePersona}? Su estatus cambiará a ACTIVO.`,
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#00c950",
    cancelButtonColor: "#6b7280",
    confirmButtonText: "Sí, reactivar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      fetch("Personas/reactivarPersona", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify({ idpersona_pk: idPersona }),
      })
        .then((response) => response.json())
        .then((result) => {
          if (result.status) {
            Swal.fire("¡Reactivado!", result.message || "Persona reactivada correctamente.", "success");
            recargarTablaPersonas();
          } else {
            Swal.fire("Error", result.message || "No se pudo reactivar la persona.", "error");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          Swal.fire("Error", "Error de conexión.", "error");
        });
    }
  });
}

function asociarUsuario(idPersona) {
  const correo = document.getElementById("correoUsuarioAsociar").value.trim();
  const clave = document.getElementById("claveUsuarioAsociar").value;
  const rol = document.getElementById("rolUsuarioAsociar").value;

  if (!correo || !clave || !rol) {
    Swal.fire("Atención", "Correo, contraseña y rol son obligatorios.", "warning");
    return;
  }

  Swal.fire({
    title: "¿Asociar usuario?",
    html: `Se creará un nuevo usuario con el correo <strong>${correo}</strong> asociado a esta persona.`,
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#00c950",
    cancelButtonColor: "#6b7280",
    confirmButtonText: "Sí, asociar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      const btnConfirmar = document.getElementById("btnConfirmarAsociarUsuario");
      if (btnConfirmar) {
        btnConfirmar.disabled = true;
        btnConfirmar.innerHTML = `<i class="fas fa-spinner fa-spin mr-1"></i> Asociando...`;
      }

      fetch("Personas/asociarUsuario", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify({
          idpersona_pk: idPersona,
          correo_electronico_usuario: correo,
          clave_usuario: clave,
          idrol_usuario: rol,
        }),
      })
        .then((response) => response.json())
        .then((result) => {
          if (result.status) {
            Swal.fire("¡Asociado!", result.message || "Usuario asociado correctamente.", "success");
            cerrarModal("modalActualizarPersona");
            recargarTablaPersonas();
          } else {
            Swal.fire("Error", result.message || "No se pudo asociar el usuario.", "error");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          Swal.fire("Error", "Error de conexión.", "error");
        })
        .finally(() => {
          if (btnConfirmar) {
            btnConfirmar.disabled = false;
            btnConfirmar.innerHTML = `<i class="fas fa-link mr-1"></i> Asociar Usuario`;
          }
        });
    }
  });
}

function desasociarUsuario(idPersona, correoUsuario) {
  Swal.fire({
    title: "¿Desasociar usuario?",
    html: `¿Deseas desasociar el usuario <strong>${correoUsuario}</strong> de esta persona?<br><small class="text-gray-500">El usuario seguirá existiendo pero ya no estará vinculado a esta persona.</small>`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#ef4444",
    cancelButtonColor: "#6b7280",
    confirmButtonText: "Sí, desasociar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      fetch("Personas/desasociarUsuario", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify({ idpersona_pk: idPersona }),
      })
        .then((response) => response.json())
        .then((result) => {
          if (result.status) {
            Swal.fire("¡Desasociado!", result.message || "Usuario desasociado correctamente.", "success");
            cerrarModal("modalActualizarPersona");
            recargarTablaPersonas();
          } else {
            Swal.fire("Error", result.message || "No se pudo desasociar el usuario.", "error");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          Swal.fire("Error", "Error de conexión.", "error");
        });
    }
  });
}