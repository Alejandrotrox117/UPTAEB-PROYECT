import { abrirModal, cerrarModal } from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  validarCamposVacios,
  validarCampo,
  validarSelect,
  limpiarValidaciones,
} from "./validaciones.js";

let tablaProveedores;

const camposFormularioProveedor = [
  {
    id: "proveedorNombre",
    tipo: "input",
    regex: expresiones.nombre,
    mensajes: {
      vacio: "El nombre es obligatorio.",
      formato: "El nombre solo puede contener letras y espacios.",
    },
  },
  {
    id: "proveedorApellido",
    tipo: "input",
    regex: expresiones.nombre,
    mensajes: {
      vacio: "El apellido es obligatorio.",
      formato: "El apellido solo puede contener letras y espacios.",
    },
  },
  {
    id: "proveedorIdentificacion",
    tipo: "input",
    regex: expresiones.identificacion,
    mensajes: {
      vacio: "La identificación es obligatoria.",
      formato: "Formato de identificación inválido.",
    },
  },
  {
    id: "proveedorTelefono",
    tipo: "input",
    regex: expresiones.telefono,
    mensajes: {
      vacio: "El teléfono es obligatorio.",
      formato: "Formato de teléfono inválido.",
    },
  },
  {
    id: "proveedorCorreo",
    tipo: "input",
    regex: expresiones.correo,
    mensajes: {
      formato: "Formato de correo electrónico inválido.",
    },
  },
  {
    id: "proveedorDireccion",
    tipo: "textarea",
    regex: expresiones.textoGeneral,
    mensajes: {
      formato: "Dirección inválida.",
    },
  },
  {
    id: "proveedorObservaciones",
    tipo: "textarea",
    regex: expresiones.textoGeneral,
    mensajes: {
      formato: "Observaciones inválidas.",
    },
  },
];

const camposFormularioActualizarProveedor = [
  {
    id: "proveedorNombreActualizar",
    tipo: "input",
    regex: expresiones.nombre,
    mensajes: {
      vacio: "El nombre es obligatorio.",
      formato: "El nombre solo puede contener letras y espacios.",
    },
  },
  {
    id: "proveedorApellidoActualizar",
    tipo: "input",
    regex: expresiones.nombre,
    mensajes: {
      vacio: "El apellido es obligatorio.",
      formato: "El apellido solo puede contener letras y espacios.",
    },
  },
  {
    id: "proveedorIdentificacionActualizar",
    tipo: "input",
    regex: expresiones.identificacion,
    mensajes: {
      vacio: "La identificación es obligatoria.",
      formato: "Formato de identificación inválido.",
    },
  },
  {
    id: "proveedorTelefonoActualizar",
    tipo: "input",
    regex: expresiones.telefono,
    mensajes: {
      vacio: "El teléfono es obligatorio.",
      formato: "Formato de teléfono inválido.",
    },
  },
  {
    id: "proveedorCorreoActualizar",
    tipo: "input",
    regex: expresiones.correo,
    mensajes: {
      formato: "Formato de correo electrónico inválido.",
    },
  },
  {
    id: "proveedorDireccionActualizar",
    tipo: "textarea",
    regex: expresiones.textoGeneral,
    mensajes: {
      formato: "Dirección inválida.",
    },
  },
  {
    id: "proveedorObservacionesActualizar",
    tipo: "textarea",
    regex: expresiones.textoGeneral,
    mensajes: {
      formato: "Observaciones inválidas.",
    },
  },
];

document.addEventListener("DOMContentLoaded", function () {
  $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
    if (settings.nTable.id !== "TablaProveedores") {
      return true;
    }
    var api = new $.fn.dataTable.Api(settings);
    var rowData = api.row(dataIndex).data();
    return rowData && rowData.estatus !== "inactivo";
  });

  $(document).ready(function () {
    $("#TablaProveedores").addClass("compact fuente-tabla-pequena");

    const colorFilaImpar = "#ffffff"; 
    const colorFilaPar = "#f3f4f6";
    const colorFilaHover = "#e0e7ff";
    const paddingVerticalCelda = "5px"; 

    tablaProveedores = $("#TablaProveedores").DataTable({
      processing: true,
      ajax: {
        url: "Proveedores/getProveedoresData",
        type: "GET",
        dataSrc: function (json) {
          if (json && json.data) {
            return json.data;
          } else {
            console.error(
              "La respuesta del servidor no tiene la estructura esperada:",
              json
            );
            $("#TablaProveedores_processing").hide();
            alert("Error: No se pudieron cargar los datos de proveedores.");
            return [];
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.error(
            "Error AJAX al cargar datos:",
            textStatus,
            errorThrown,
            jqXHR.responseText
          );
          $("#TablaProveedores_processing").hide();
          alert("Error de comunicación al cargar los datos.");
        },
      },
      columns: [
        { data: "nombre", title: "Nombre" },
        { data: "apellido", title: "Apellido" },
        { data: "identificacion", title: "Identificación" },
        { data: "telefono_principal", title: "Teléfono" },
        {
          data: "estatus",
          title: "Estatus",
          render: function (data, type, row) {
            if (data) {
              const estatusUpper = String(data).toUpperCase();
              let badgeClass = "bg-gray-100 text-gray-800"; 
              if (estatusUpper === "ACTIVO") {
                badgeClass =
                  "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300";
              } else if (estatusUpper === "INACTIVO") {
                badgeClass =
                  "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300";
              }
              return `<span class="${badgeClass} text-xs font-medium me-2 px-2.5 py-0.5 rounded">${data}</span>`;
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
              <button class="ver-proveedor-btn text-green-500 hover:text-green-700 p-1 ml-1.5" data-idproveedor="${row.idproveedor}" title="Ver detalles">
                  <i class="fas fa-eye"></i>
              </button>
              <button class="editar-proveedor-btn text-blue-500 hover:text-blue-700 p-1 ml-1.5" data-idproveedor="${row.idproveedor}" title="Editar">
                  <i class="fas fa-edit"></i>
              </button>
              <button class="eliminar-proveedor-btn text-red-500 hover:text-red-700 p-1 ml-1.5" data-idproveedor="${row.idproveedor}" data-nombre="${row.nombre} ${row.apellido}" title="Desactivar Proveedor">
                  <i class="fas fas fa-trash"></i>
              </button>
            `;
          },
          width: "140px", 
          className: "text-center acciones-columna",
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
      rowCallback: function (row, data, index) {
        let colorDeFondoActual;
        const celdas = $(row).children("td");
        celdas.css({
          "padding-top": paddingVerticalCelda,
          "padding-bottom": paddingVerticalCelda,
        });

        if (index % 2 === 0) {
          colorDeFondoActual = colorFilaImpar;
          celdas.css("background-color", colorFilaImpar);
        } else {
          colorDeFondoActual = colorFilaPar;
          celdas.css("background-color", colorFilaPar);
        }

        $(row)
          .off("mouseenter mouseleave")
          .on("mouseenter", function () {
            $(this).children("td").css("background-color", colorFilaHover);
          })
          .on("mouseleave", function () {
            $(this)
              .children("td")
              .css("background-color", colorDeFondoActual);
          });
      },
    });

    $("#TablaProveedores tbody").on("click", ".ver-proveedor-btn", function () {
      const idProveedor = $(this).data("idproveedor");
      verProveedor(idProveedor);
    });

    $("#TablaProveedores tbody").on(
      "click",
      ".editar-proveedor-btn",
      function () {
        const idProveedor = $(this).data("idproveedor");
        editarProveedor(idProveedor); 
      }
    );

    $("#TablaProveedores tbody").on(
      "click",
      ".eliminar-proveedor-btn",
      function () {
        const idProveedor = $(this).data("idproveedor");
        const nombreProveedor = $(this).data("nombre");
        eliminarProveedor(idProveedor, nombreProveedor);
      }
    );
  });

  // MODAL REGISTRAR PROVEEDOR
  const btnAbrirModalRegistro = document.getElementById(
    "btnAbrirModalRegistrarProveedor"
  );
  const formRegistrar = document.getElementById("formRegistrarProveedor");
  const btnCerrarModalRegistro = document.getElementById(
    "btnCerrarModalRegistrar"
  );
  const btnCancelarModalRegistro = document.getElementById(
    "btnCancelarModalRegistrar"
  );

  if (btnAbrirModalRegistro) {
    btnAbrirModalRegistro.addEventListener("click", function () {
      abrirModal("modalRegistrarProveedor");
      if (formRegistrar) formRegistrar.reset();
      inicializarValidaciones(
        camposFormularioProveedor,
        "formRegistrarProveedor"
      );
    });
  }

  if (btnCerrarModalRegistro) {
    btnCerrarModalRegistro.addEventListener("click", function () {
      cerrarModal("modalRegistrarProveedor");
    });
  }

  if (btnCancelarModalRegistro) {
    btnCancelarModalRegistro.addEventListener("click", function () {
      cerrarModal("modalRegistrarProveedor");
    });
  }

  // SUBMIT FORM REGISTRAR
  if (formRegistrar) {
    formRegistrar.addEventListener("submit", function (e) {
      e.preventDefault();
      registrarProveedor();
    });
  }

  // MODAL ACTUALIZAR PROVEEDOR
  const btnCerrarModalActualizar = document.getElementById(
    "btnCerrarModalActualizar"
  );
  const btnCancelarModalActualizar = document.getElementById(
    "btnCancelarModalActualizar"
  );
  const formActualizar = document.getElementById("formActualizarProveedor");

  if (btnCerrarModalActualizar) {
    btnCerrarModalActualizar.addEventListener("click", function () {
      cerrarModal("modalActualizarProveedor");
    });
  }

  if (btnCancelarModalActualizar) {
    btnCancelarModalActualizar.addEventListener("click", function () {
      cerrarModal("modalActualizarProveedor");
    });
  }

  if (formActualizar) {
    formActualizar.addEventListener("submit", function (e) {
      e.preventDefault();
      actualizarProveedor();
    });
  }

  // MODAL VER PROVEEDOR
  const btnCerrarModalVer = document.getElementById("btnCerrarModalVer");
  const btnCerrarModalVer2 = document.getElementById("btnCerrarModalVer2");

  if (btnCerrarModalVer) {
    btnCerrarModalVer.addEventListener("click", function () {
      cerrarModal("modalVerProveedor");
    });
  }

  if (btnCerrarModalVer2) {
    btnCerrarModalVer2.addEventListener("click", function () {
      cerrarModal("modalVerProveedor");
    });
  }
});

// FUNCIONES
function registrarProveedor() {
  const formRegistrar = document.getElementById("formRegistrarProveedor");
  const btnGuardarProveedor = document.getElementById("btnGuardarProveedor");

  if (!validarCamposVacios(camposFormularioProveedor, "formRegistrarProveedor")) {
    return;
  }

  let formularioConErroresEspecificos = false;
  for (const campo of camposFormularioProveedor) {
    const inputElement = formRegistrar.querySelector(`#${campo.id}`);
    if (!inputElement) continue;

    let esValidoEsteCampo = true;
    if (campo.tipo === "select") {
      if (campo.mensajes && campo.mensajes.vacio) {
        esValidoEsteCampo = validarSelect(
          campo.id,
          campo.mensajes,
          "formRegistrarProveedor"
        );
      }
    } else if (["input", "textarea", "text"].includes(campo.tipo)) {
      if (
        inputElement.value.trim() !== "" ||
        (campo.mensajes && campo.mensajes.vacio)
      ) {
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
    nombre: formData.get("nombre") || "",
    apellido: formData.get("apellido") || "",
    identificacion: formData.get("identificacion") || "",
    fecha_nacimiento: formData.get("fecha_nacimiento") || "",
    direccion: formData.get("direccion") || "",
    correo_electronico: formData.get("correo_electronico") || "",
    telefono_principal: formData.get("telefono_principal") || "",
    observaciones: formData.get("observaciones") || "",
    genero: formData.get("genero") || "",
  };

  if (btnGuardarProveedor) {
    btnGuardarProveedor.disabled = true;
    btnGuardarProveedor.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...`;
  }

  fetch("Proveedores/createProveedor", {
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
        cerrarModal("modalRegistrarProveedor");
        if (typeof tablaProveedores !== "undefined" && tablaProveedores.ajax) {
          tablaProveedores.ajax.reload(null, false);
        }
      } else {
        Swal.fire(
          "Error",
          result.message || "No se pudo registrar el proveedor.",
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
      if (btnGuardarProveedor) {
        btnGuardarProveedor.disabled = false;
        btnGuardarProveedor.innerHTML = `<i class="fas fa-save mr-2"></i> Guardar Proveedor`;
      }
    });
}

function editarProveedor(idProveedor) {
  fetch(`Proveedores/getProveedorById/${idProveedor}`, {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const proveedor = result.data;
        mostrarModalEditarProveedor(proveedor);
      } else {
        Swal.fire("Error", "No se pudieron cargar los datos.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    });
}

function mostrarModalEditarProveedor(proveedor) {
  // Llenar los campos del formulario con los datos existentes
  document.getElementById("idProveedorActualizar").value =
    proveedor.idproveedor || "";
  document.getElementById("proveedorNombreActualizar").value =
    proveedor.nombre || "";
  document.getElementById("proveedorApellidoActualizar").value =
    proveedor.apellido || "";
  document.getElementById("proveedorIdentificacionActualizar").value =
    proveedor.identificacion || "";
  document.getElementById("proveedorTelefonoActualizar").value =
    proveedor.telefono_principal || "";
  document.getElementById("proveedorFechaNacimientoActualizar").value =
    proveedor.fecha_nacimiento || "";
  document.getElementById("proveedorGeneroActualizar").value =
    proveedor.genero || "";
  document.getElementById("proveedorCorreoActualizar").value =
    proveedor.correo_electronico || "";
  document.getElementById("proveedorDireccionActualizar").value =
    proveedor.direccion || "";
  document.getElementById("proveedorObservacionesActualizar").value =
    proveedor.observaciones || "";

  // Inicializar validaciones para el formulario de actualizar
  inicializarValidaciones(
    camposFormularioActualizarProveedor,
    "formActualizarProveedor"
  );

  abrirModal("modalActualizarProveedor");
}

function actualizarProveedor() {
  const formActualizar = document.getElementById("formActualizarProveedor");
  const btnActualizarProveedor = document.getElementById(
    "btnActualizarProveedor"
  );
  const idProveedor = document.getElementById("idProveedorActualizar").value;

  // Validar campos vacíos obligatorios
  const camposObligatorios = camposFormularioActualizarProveedor.filter((c) => {
    return c.mensajes && c.mensajes.vacio;
  });

  if (!validarCamposVacios(camposObligatorios, "formActualizarProveedor")) {
    return;
  }

  // Validar formatos específicos
  let formularioConErroresEspecificos = false;
  for (const campo of camposFormularioActualizarProveedor) {
    const inputElement = formActualizar.querySelector(`#${campo.id}`);
    if (!inputElement) continue;

    let esValidoEsteCampo = true;
    if (campo.tipo === "select") {
      if (campo.mensajes && campo.mensajes.vacio) {
        esValidoEsteCampo = validarSelect(
          campo.id,
          campo.mensajes,
          "formActualizarProveedor"
        );
      }
    } else if (["input", "textarea", "text"].includes(campo.tipo)) {
      if (
        inputElement.value.trim() !== "" ||
        (campo.mensajes && campo.mensajes.vacio)
      ) {
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
    idproveedor: idProveedor,
    nombre: formData.get("nombre") || "",
    apellido: formData.get("apellido") || "",
    identificacion: formData.get("identificacion") || "",
    fecha_nacimiento: formData.get("fecha_nacimiento") || "",
    direccion: formData.get("direccion") || "",
    correo_electronico: formData.get("correo_electronico") || "",
    telefono_principal: formData.get("telefono_principal") || "",
    observaciones: formData.get("observaciones") || "",
    genero: formData.get("genero") || "",
  };

  if (btnActualizarProveedor) {
    btnActualizarProveedor.disabled = true;
    btnActualizarProveedor.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Actualizando...`;
  }

  fetch("Proveedores/updateProveedor", {
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
        cerrarModal("modalActualizarProveedor");
        if (typeof tablaProveedores !== "undefined" && tablaProveedores.ajax) {
          tablaProveedores.ajax.reload(null, false);
        }
      } else {
        Swal.fire(
          "Error",
          result.message || "No se pudo actualizar el proveedor.",
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
      if (btnActualizarProveedor) {
        btnActualizarProveedor.disabled = false;
        btnActualizarProveedor.innerHTML = `<i class="fas fa-save mr-2"></i> Actualizar Proveedor`;
      }
    });
}

function verProveedor(idProveedor) {
  fetch(`Proveedores/getProveedorById/${idProveedor}`, {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const proveedor = result.data;
        mostrarModalVerProveedor(proveedor);
      } else {
        Swal.fire("Error", "No se pudieron cargar los datos.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    });
}

function mostrarModalVerProveedor(proveedor) {
  // Llenar los campos del modal de ver
  document.getElementById("verProveedorNombre").textContent =
    proveedor.nombre || "N/A";
  document.getElementById("verProveedorApellido").textContent =
    proveedor.apellido || "N/A";
  document.getElementById("verProveedorIdentificacion").textContent =
    proveedor.identificacion || "N/A";
  document.getElementById("verProveedorTelefono").textContent =
    proveedor.telefono_principal || "N/A";
  document.getElementById("verProveedorFechaNacimiento").textContent =
    proveedor.fecha_nacimiento_formato || "N/A";
  document.getElementById("verProveedorGenero").textContent =
    proveedor.genero || "N/A";
  document.getElementById("verProveedorCorreo").textContent =
    proveedor.correo_electronico || "Sin correo";
  document.getElementById("verProveedorDireccion").textContent =
    proveedor.direccion || "Sin dirección";
  document.getElementById("verProveedorObservaciones").textContent =
    proveedor.observaciones || "Sin observaciones";
  document.getElementById("verProveedorEstatus").textContent =
    proveedor.estatus || "N/A";
  document.getElementById("verProveedorFechaCreacion").textContent =
    proveedor.fecha_creacion_formato || "N/A";
  document.getElementById("verProveedorFechaModificacion").textContent =
    proveedor.fecha_modificacion_formato || "N/A";

  abrirModal("modalVerProveedor");
}

function eliminarProveedor(idProveedor, nombreProveedor) {
  Swal.fire({
    title: "¿Estás seguro?",
    text: `¿Deseas desactivar al proveedor "${nombreProveedor}"? Esta acción cambiará su estatus a INACTIVO.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Sí, desactivar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      const dataParaEnviar = {
        idproveedor: idProveedor,
      };

      fetch("Proveedores/deleteProveedor", {
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
            if (
              typeof tablaProveedores !== "undefined" &&
              tablaProveedores.ajax
            ) {
              tablaProveedores.ajax.reload(null, false);
            }
          } else {
            Swal.fire(
              "Error",
              result.message || "No se pudo desactivar al proveedor.",
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