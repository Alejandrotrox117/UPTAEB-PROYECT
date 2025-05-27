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
    // DATATABLE ROLES PERMISOS
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
            $("#TablaRolesPermisos_processing").hide();
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
          $("#TablaRolesPermisos_processing").hide();
          alert(
            "Error de comunicación al cargar los datos de asignaciones. Por favor, intente más tarde."
          );
        },
      },
      columns: [
        { data: "nombre_rol", title: "Rol" },
        { 
          data: "descripcion_rol", 
          title: "Descripción del Rol",
          render: function (data, type, row) {
            if (data && data.length > 50) {
              return data.substring(0, 50) + "...";
            }
            return data || '<i style="color: silver;">Sin descripción</i>';
          },
        },
        { data: "nombre_permiso", title: "Permiso" },
        {
          data: "estatus_rol",
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
            return `
              <button class="ver-rolpermiso-btn text-green-500 hover:text-green-700 p-1" data-idrolpermiso="${row.idrolpermiso}" title="Ver detalles">
                  <i class="fas fa-eye fa-lg"></i>
              </button>
              <button class="editar-rolpermiso-btn text-blue-500 hover:text-blue-700 p-1 ml-2" data-idrolpermiso="${row.idrolpermiso}" title="Editar">
                  <i class="fas fa-edit fa-lg"></i>
              </button>
              <button class="eliminar-rolpermiso-btn text-red-500 hover:text-red-700 p-1 ml-2" data-idrolpermiso="${row.idrolpermiso}" data-rol="${row.nombre_rol}" data-permiso="${row.nombre_permiso}" title="Eliminar">
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

    // Click para ver asignación
    $("#TablaRolesPermisos tbody").on("click", ".ver-rolpermiso-btn", function () {
      const idRolPermiso = $(this).data("idrolpermiso");
      verRolPermiso(idRolPermiso);
    });

    // Click para editar asignación
    $("#TablaRolesPermisos tbody").on("click", ".editar-rolpermiso-btn", function () {
      const idRolPermiso = $(this).data("idrolpermiso");
      editarRolPermiso(idRolPermiso);
    });

    // Click para eliminar asignación
    $("#TablaRolesPermisos tbody").on("click", ".eliminar-rolpermiso-btn", function () {
      const idRolPermiso = $(this).data("idrolpermiso");
      const nombreRol = $(this).data("rol");
      const nombrePermiso = $(this).data("permiso");
      eliminarRolPermiso(idRolPermiso, nombreRol, nombrePermiso);
    });
  });

  // MODAL REGISTRAR ASIGNACIÓN
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

  // SUBMIT FORM REGISTRAR
  if (formRegistrar) {
    formRegistrar.addEventListener("submit", function (e) {
      e.preventDefault();
      registrarRolPermiso();
    });
  }

  // MODAL ACTUALIZAR ASIGNACIÓN
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

  // MODAL VER ASIGNACIÓN
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

// FUNCIONES
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
  // Llenar los campos del formulario con los datos existentes
  document.getElementById("idRolPermisoActualizar").value = rolPermiso.idrolpermiso || "";
  document.getElementById("rolSelectActualizar").value = rolPermiso.idrol || "";
  document.getElementById("permisoSelectActualizar").value = rolPermiso.idpermiso || "";

  // Inicializar validaciones para el formulario de actualizar
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

  // Validar campos vacíos obligatorios
  const camposObligatorios = camposFormularioActualizarRolPermiso.filter((c) => {
    return c.mensajes && c.mensajes.vacio;
  });

  if (!validarCamposVacios(camposObligatorios, "formActualizarRolPermiso")) {
    return;
  }

  // Validar formatos específicos
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
  // Llenar los campos del modal de ver
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
