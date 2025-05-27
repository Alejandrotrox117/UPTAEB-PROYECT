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
    // DATATABLE ROLES MÓDULOS
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
            $("#TablaRolesModulos_processing").hide();
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
          $("#TablaRolesModulos_processing").hide();
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
        { data: "titulo_modulo", title: "Módulo" },
        { 
          data: "descripcion_modulo", 
          title: "Descripción del Módulo",
          render: function (data, type, row) {
            if (data && data.length > 50) {
              return data.substring(0, 50) + "...";
            }
            return data || '<i style="color: silver;">Sin descripción</i>';
          },
        },
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
              <button class="ver-rolmodulo-btn text-green-500 hover:text-green-700 p-1" data-idrolmodulo="${row.idrolmodulo}" title="Ver detalles">
                  <i class="fas fa-eye fa-lg"></i>
              </button>
              <button class="editar-rolmodulo-btn text-blue-500 hover:text-blue-700 p-1 ml-2" data-idrolmodulo="${row.idrolmodulo}" title="Editar">
                  <i class="fas fa-edit fa-lg"></i>
              </button>
              <button class="eliminar-rolmodulo-btn text-red-500 hover:text-red-700 p-1 ml-2" data-idrolmodulo="${row.idrolmodulo}" data-rol="${row.nombre_rol}" data-modulo="${row.titulo_modulo}" title="Eliminar">
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
    $("#TablaRolesModulos tbody").on("click", ".ver-rolmodulo-btn", function () {
      const idRolModulo = $(this).data("idrolmodulo");
      verRolModulo(idRolModulo);
    });

    // Click para editar asignación
    $("#TablaRolesModulos tbody").on("click", ".editar-rolmodulo-btn", function () {
      const idRolModulo = $(this).data("idrolmodulo");
      editarRolModulo(idRolModulo);
    });

    // Click para eliminar asignación
    $("#TablaRolesModulos tbody").on("click", ".eliminar-rolmodulo-btn", function () {
      const idRolModulo = $(this).data("idrolmodulo");
      const nombreRol = $(this).data("rol");
      const tituloModulo = $(this).data("modulo");
      eliminarRolModulo(idRolModulo, nombreRol, tituloModulo);
    });
  });

  // MODAL REGISTRAR ASIGNACIÓN
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

  // SUBMIT FORM REGISTRAR
  if (formRegistrar) {
    formRegistrar.addEventListener("submit", function (e) {
      e.preventDefault();
      registrarRolModulo();
    });
  }

  // MODAL ACTUALIZAR ASIGNACIÓN
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

  // MODAL VER ASIGNACIÓN
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

// FUNCIONES
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
  // Llenar los campos del formulario con los datos existentes
  document.getElementById("idRolModuloActualizar").value = rolModulo.idrolmodulo || "";
  document.getElementById("rolSelectActualizar").value = rolModulo.idrol || "";
  document.getElementById("moduloSelectActualizar").value = rolModulo.idmodulo || "";

  // Inicializar validaciones para el formulario de actualizar
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

  // Validar campos vacíos obligatorios
  const camposObligatorios = camposFormularioActualizarRolModulo.filter((c) => {
    return c.mensajes && c.mensajes.vacio;
  });

  if (!validarCamposVacios(camposObligatorios, "formActualizarRolModulo")) {
    return;
  }

  // Validar formatos específicos
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
  // Llenar los campos del modal de ver
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
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
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
