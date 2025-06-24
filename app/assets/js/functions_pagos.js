import { abrirModal, cerrarModal } from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  limpiarValidaciones,
  registrarEntidad,
  validarCamposVacios,
  validarFecha,
  validarSelect,
} from "./validaciones.js";

let tablaPagos;
let tiposPago = [];
let pagoEditando = null; 

function mostrarModalPermisosDenegados(
  mensaje = "No tienes permisos para realizar esta acción."
) {
  Swal.fire({
    icon: "warning",
    title: "Acceso Denegado",
    text: mensaje,
    confirmButtonColor: "#d33",
  });
}

function tienePermiso(accion) {
  return window.permisosPagos && window.permisosPagos[accion] === true;
}




const camposFormularioPago = [
  {
    id: "tipoPago",
    tipo: "radio",
    mensajes: {
      vacio: "Debe seleccionar un tipo de pago.",
    },
  },
  {
    id: "pagoMonto",
    tipo: "input",
    regex: /^\d+(\.\d{1,2})?$/,
    mensajes: {
      vacio: "El monto es obligatorio.",
      formato: "El monto debe ser un número válido con hasta 2 decimales.",
    },
  },
  {
    id: "pagoMetodoPago",
    tipo: "select",
    mensajes: {
      vacio: "Debe seleccionar un método de pago.",
    },
  },
  {
    id: "pagoFecha",
    tipo: "fecha",
    mensajes: {
      vacio: "La fecha de pago es obligatoria.",
      fechaPosterior: "La fecha no puede ser posterior a hoy.",
      fechaInvalida: "Formato de fecha inválido.",
    },
  },
  {
    id: "pagoReferencia",
    tipo: "input",
    regex: expresiones.textoGeneral,
    mensajes: {
      formato: "La referencia debe tener entre 2 y 100 caracteres.",
    },
  },
  {
    id: "pagoObservaciones",
    tipo: "textarea",
    regex: /^.{0,200}$/,
    mensajes: {
      formato: "Las observaciones no pueden exceder 200 caracteres.",
    },
  },
];

const camposDinamicos = {
  compra: [
    {
      id: "pagoCompra",
      tipo: "select",
      mensajes: { vacio: "Debe seleccionar una compra." },
    },
  ],
  venta: [
    {
      id: "pagoVenta",
      tipo: "select",
      mensajes: { vacio: "Debe seleccionar una venta." },
    },
  ],
  sueldo: [
    {
      id: "pagoSueldo",
      tipo: "select",
      mensajes: { vacio: "Debe seleccionar un sueldo." },
    },
  ],
  otro: [
    {
      id: "pagoDescripcion",
      tipo: "textarea",
      regex: expresiones.textoGeneral,
      mensajes: {
        vacio: "La descripción es obligatoria para otros pagos.",
        formato: "La descripción debe tener entre 2 y 100 caracteres.",
      },
    },
  ],
};

document.addEventListener("DOMContentLoaded", function () {
  inicializarModulo();
});

function inicializarModulo() {
  if (!tienePermiso("ver")) {
    mostrarModalPermisosDenegados("No tienes permisos para ver los pagos.");
    
    const mainContainer = document.querySelector(".container-fluid");
    if (mainContainer) mainContainer.innerHTML = "";
    return;
  }
  inicializarTablaPagos();
  configurarEventos();
  cargarTiposPago();
  inicializarValidaciones(camposFormularioPago, "formRegistrarPago");
}

$.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
  if (settings.nTable.id !== "TablaPagos") {
    return true;
  }
  var api = new $.fn.dataTable.Api(settings);
  var rowData = api.row(dataIndex).data();
  return (
    rowData && rowData.estatus && rowData.estatus.toLowerCase() !== "inactivo"
  );
});

function inicializarTablaPagos() {
  if ($.fn.DataTable.isDataTable("#TablaPagos")) {
    $("#TablaPagos").DataTable().destroy();
  }

  let dataTableButtons = [];
  if (tienePermiso("exportar")) {
    dataTableButtons.push(
      {
        extend: "excelHtml5",
        text: '<i class="fas fa-file-excel mr-2"></i>Excel',
        className:
          "bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-md text-sm inline-flex items-center",
        title: "Reporte_Pagos",
      },
      {
        extend: "pdfHtml5",
        text: '<i class="fas fa-file-pdf mr-2"></i>PDF',
        className:
          "bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-md text-sm inline-flex items-center ml-2",
        title: "Reporte de Pagos",
        orientation: "landscape",
      }
    );
  }

  tablaPagos = $("#TablaPagos").DataTable({
    ajax: {
      url: "Pagos/getPagosData",
      type: "GET",
      dataSrc: function (json) {
        if (json.status === true && Array.isArray(json.data)) {
          return json.data.slice().reverse();
        }
        if (json.message && json.message.includes("permiso")) {
          mostrarModalPermisosDenegados(json.message);
        } else {
          console.error("Error en la respuesta del servidor:", json);
          alert("Error al cargar los datos de pagos.");
        }
        return [];
      },
      error: function (xhr, error, thrown) {
        console.error("Error en la petición AJAX:", error);
        alert("Error de comunicación al cargar los datos de pagos.");
      },
    },
    columns: [
      {
        data: "destinatario",
        title: "Destinatario",
        className:
          "all whitespace-nowrap py-2 px-3 text-gray-700 dt-fixed-col-background",
      },
      {
        data: "tipo_pago_texto",
        title: "Tipo",
        className: "desktop whitespace-nowrap py-2 px-3 text-gray-700",
        render: function (data) {
          const badges = {
            Compra:
              '<span class="px-2 py-1 text-xs font-semibold bg-blue-100 text-blue-800 rounded-full">Compra</span>',
            Venta:
              '<span class="px-2 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded-full">Venta</span>',
            Sueldo:
              '<span class="px-2 py-1 text-xs font-semibold bg-purple-100 text-purple-800 rounded-full">Sueldo</span>',
            Otro:
              '<span class="px-2 py-1 text-xs font-semibold bg-gray-100 text-gray-800 rounded-full">Otro</span>',
          };
          return badges[data] || data;
        },
      },
      {
        data: "monto",
        title: "Monto",
        className: "tablet-l whitespace-nowrap py-2 px-3 text-right",
        render: function (data) {
          return `<span class="font-semibold text-green-600">$${parseFloat(
            data
          ).toFixed(2)}</span>`;
        },
      },
      {
        data: "metodo_pago",
        title: "Método",
        className: "desktop whitespace-nowrap py-2 px-3 text-gray-700",
      },
      {
        data: "fecha_pago_formato",
        title: "Fecha",
        className: "all whitespace-nowrap py-2 px-3 text-gray-700",
      },
      {
        data: "estatus",
        title: "Estatus",
        className: "min-tablet-p text-center py-2 px-3",
        render: function (data) {
          const estatus = String(data).toLowerCase();
          if (estatus === "activo") {
            return '<span class="px-2 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded-full">ACTIVO</span>';
          } else if (estatus === "conciliado") {
            return '<span class="px-2 py-1 text-xs font-semibold bg-blue-100 text-blue-800 rounded-full">CONCILIADO</span>';
          } else {
            return '<span class="px-2 py-1 text-xs font-semibold bg-red-100 text-red-800 rounded-full">INACTIVO</span>';
          }
        },
      },
      {
        data: null,
        title: "Acciones",
        orderable: false,
        searchable: false,
        className: "all text-center actions-column py-1 px-2",
        render: function (data, type, row) {
          let buttons = "";
          if (tienePermiso("ver")) {
            buttons += `
              <button onclick="verPago(${row.idpago})" 
                      class="text-green-600 hover:text-green-700 p-1 transition-colors duration-150"
                      title="Ver detalles">
                <i class="fas fa-eye fa-fw text-base"></i>
              </button>
            `;
          }
          if (row.estatus === "activo") {
            if (tienePermiso("editar")) {
              buttons += `
                <button onclick="editarPago(${row.idpago})" 
                        class="text-blue-600 hover:text-blue-700 p-1 transition-colors duration-150"
                        title="Editar">
                  <i class="fas fa-edit fa-fw text-base"></i>
                </button>
                <button onclick="conciliarPago(${row.idpago}, '${row.destinatario}')" 
                        class="text-green-600 hover:text-green-700 p-1 transition-colors duration-150"
                        title="Conciliar">
                  <i class="fas fa-check fa-fw text-base"></i>
                </button>
              `;
            }
            if (tienePermiso("eliminar")) {
              buttons += `
                <button onclick="eliminarPago(${row.idpago}, '${row.destinatario}')" 
                        class="text-red-600 hover:text-red-700 p-1 transition-colors duration-150"
                        title="Eliminar">
                  <i class="fas fa-trash-alt fa-fw text-base"></i>
                </button>
              `;
            }
          } else if (row.estatus === "conciliado" && tienePermiso("ver")) {
            
          }
          if (buttons === "") {
            return '<span class="text-gray-400 text-xs">Sin permisos</span>';
          }
          return `<div class="inline-flex items-center space-x-1">${buttons}</div>`;
        },
        width: "auto",
      },
    ],
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
    language: {
      processing: `
        <div class="fixed inset-0 bg-transparent backdrop-blur-[2px] bg-opacity-40 flex items-center justify-center z-[9999]" style="margin-left:0;">
            <div class="bg-white p-6 rounded-lg shadow-xl flex items-center space-x-3">
                <i class="fas fa-spinner fa-spin fa-2x text-green-500"></i>
                <span class="text-lg font-medium text-gray-700">Procesando...</span>
            </div>
        </div>`,
      emptyTable:
        '<div class="text-center py-4"><i class="fas fa-dollar-sign fa-2x text-gray-400 mb-2"></i><p class="text-gray-600">No hay pagos disponibles.</p></div>',
      info: "Mostrando _START_ a _END_ de _TOTAL_ pagos",
      infoEmpty: "Mostrando 0 pagos",
      infoFiltered: "(filtrado de _MAX_ pagos totales)",
      lengthMenu: "Mostrar _MENU_ pagos",
      search: "_INPUT_",
      searchPlaceholder: "Buscar pago...",
      zeroRecords:
        '<div class="text-center py-4"><i class="fas fa-search fa-2x text-gray-400 mb-2"></i><p class="text-gray-600">No se encontraron coincidencias.</p></div>',
      paginate: {
        first: '<i class="fas fa-angle-double-left"></i>',
        last: '<i class="fas fa-angle-double-right"></i>',
        next: '<i class="fas fa-angle-right"></i>',
        previous: '<i class="fas fa-angle-left"></i>',
      },
    },
    pageLength: 25,
    lengthMenu: [
      [10, 25, 50, 100, -1],
      [10, 25, 50, 100, "Todos"],
    ],
    order: [[4, "desc"]],
    dom:
      "<'flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center mb-4'" +
      "l" +
      "<'flex items-center'Bf>" +
      ">" +
      "<'overflow-x-auto't>" +
      "<'flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center mt-4'i p>",
    buttons: dataTableButtons,
    autoWidth: false,
    scrollX: true,
    fixedColumns: {
      left: 1,
    },
    className: "compact",
    initComplete: function (settings, json) {
      window.tablaPagos = this.api();
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
}

window.conciliarPago = function (idPago, descripcion) {
  if (!tienePermiso("editar")) {
    mostrarModalPermisosDenegados("No tienes permisos para conciliar pagos.");
    return;
  }

  Swal.fire({
    title: "¿Estás seguro?",
    text: `¿Deseas conciliar el pago "${descripcion}"? Esta acción no se puede deshacer.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#10B981",
    cancelButtonColor: "#6B7280",
    confirmButtonText: "Sí, conciliar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      fetch("Pagos/conciliarPago", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ idpago: idPago }),
      })
        .then((response) => response.json())
        .then((result) => {
          if (result.status) {
            Swal.fire({
              title: "¡Éxito!",
              text: result.message,
              icon: "success",
              confirmButtonText: "Aceptar",
              confirmButtonColor: "#10B981",
            }).then(() => {
              tablaPagos.ajax.reload();
            });
          } else {
            mostrarNotificacion(result.message, "error");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          mostrarNotificacion("Error de conexión al conciliar", "error");
        });
    }
  });
};

function configurarEventos() {
  const btnAbrirModal = document.getElementById("btnAbrirModalRegistrarPago");
  const btnCerrarModal = document.getElementById("btnCerrarModalRegistrar");
  const btnCancelarModal = document.getElementById(
    "btnCancelarModalRegistrar"
  );
  const formRegistrar = document.getElementById("formRegistrarPago");

  if (btnAbrirModal) {
    btnAbrirModal.addEventListener("click", abrirModalRegistro);
  }

  if (btnCerrarModal) {
    btnCerrarModal.addEventListener("click", () => {
      limpiarValidaciones(
        [...camposFormularioPago, ...obtenerCamposDinamicos()],
        "formRegistrarPago"
      );
      cerrarModal("modalRegistrarPago");
    });
  }

  if (btnCancelarModal) {
    btnCancelarModal.addEventListener("click", () => {
      limpiarValidaciones(
        [...camposFormularioPago, ...obtenerCamposDinamicos()],
        "formRegistrarPago"
      );
      cerrarModal("modalRegistrarPago");
    });
  }

  if (formRegistrar) {
    formRegistrar.addEventListener("submit", function (e) {
      e.preventDefault();
      if (pagoEditando) {
        actualizarPago();
      } else {
        registrarPago();
      }
    });
  }

  const btnCerrarModalVer = document.getElementById("btnCerrarModalVer");
  const btnCerrarModalVerFooter = document.getElementById(
    "btnCerrarModalVerFooter"
  );

  if (btnCerrarModalVer) {
    btnCerrarModalVer.addEventListener("click", () =>
      cerrarModal("modalVerPago")
    );
  }

  if (btnCerrarModalVerFooter) {
    btnCerrarModalVerFooter.addEventListener("click", () =>
      cerrarModal("modalVerPago")
    );
  }

  const modalVerPago = document.getElementById("modalVerPago");
  if (modalVerPago) {
    modalVerPago.addEventListener("click", function (e) {
      if (e.target === this) {
        cerrarModal("modalVerPago");
      }
    });
  }
}

function obtenerCamposDinamicos() {
  const tipoPago = document.querySelector(
    'input[name="tipoPago"]:checked'
  )?.value;
  return tipoPago ? camposDinamicos[tipoPago] || [] : [];
}

function abrirModalRegistro() {
  if (!tienePermiso("crear")) {
    mostrarModalPermisosDenegados("No tienes permisos para registrar pagos.");
    return;
  }

  pagoEditando = null;
  resetearFormulario();
  limpiarValidaciones(
    [...camposFormularioPago, ...obtenerCamposDinamicos()],
    "formRegistrarPago"
  );
  configurarEventosTipoPago();
  establecerFechaActual();

  document.getElementById("tituloModalRegistrar").textContent =
    "Registrar Pago";
  document.getElementById("btnGuardarPago").innerHTML =
    '<i class="fas fa-save mr-1 md:mr-2"></i> Guardar Pago';

  cargarMetodosPago().finally(() => {
    abrirModal("modalRegistrarPago");
  });
}

function cargarTiposPago() {
  return fetch("Pagos/getTiposPago")
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        tiposPago = result.data;
      }
      return result;
    })
    .catch((error) => {
      console.error("Error al cargar tipos de pago:", error);
      return { status: false };
    });
}

function cargarMetodosPago() {
  return fetch("Pagos/getTiposPago")
    .then((response) => response.json())
    .then((result) => {
      const select = document.getElementById("pagoMetodoPago");
      if (!select) return result;

      select.innerHTML = '<option value="">Seleccionar método...</option>';

      if (result.status && result.data) {
        result.data.forEach((tipo) => {
          const option = document.createElement("option");
          option.value = tipo.idtipo_pago;
          option.textContent = tipo.nombre;
          select.appendChild(option);
        });
      }
      return result;
    })
    .catch((error) => {
      console.error("Error:", error);
      mostrarNotificacion("Error al cargar métodos de pago", "error");
      return { status: false };
    });
}

function resetearFormulario() {
  const form = document.getElementById("formRegistrarPago");
  if (form) form.reset();

  [
    "containerCompras",
    "containerVentas",
    "containerSueldos",
    "containerDescripcion",
    "containerDestinatario",
  ].forEach((id) => {
    const element = document.getElementById(id);
    if (element) element.classList.add("hidden");
  });

  ["pagoCompra", "pagoVenta", "pagoSueldo"].forEach((id) => {
    const element = document.getElementById(id);
    if (element) element.innerHTML = '<option value="">Seleccionar...</option>';
  });

  limpiarValidaciones(
    [...camposFormularioPago, ...obtenerCamposDinamicos()],
    "formRegistrarPago"
  );
}

function configurarEventosTipoPago() {
  const radioButtons = document.querySelectorAll('input[name="tipoPago"]');

  radioButtons.forEach((radio) => {
    radio.addEventListener("change", function () {
      if (this.checked) {
        limpiarValidaciones(
          [...camposFormularioPago, ...obtenerCamposDinamicos()],
          "formRegistrarPago"
        );

        manejarCambioTipoPago(this.value);

        const camposDinamicosActuales = obtenerCamposDinamicos();
        if (camposDinamicosActuales.length > 0) {
          inicializarValidaciones(camposDinamicosActuales, "formRegistrarPago");
        }
      }
    });
  });
}

function manejarCambioTipoPago(tipoPago) {
  [
    "containerCompras",
    "containerVentas",
    "containerSueldos",
    "containerDescripcion",
    "containerDestinatario",
  ].forEach((id) => {
    const element = document.getElementById(id);
    if (element) element.classList.add("hidden");
  });

  if (!pagoEditando) {
    const montoInput = document.getElementById("pagoMonto");
    if (montoInput) montoInput.value = "";
  }

  switch (tipoPago) {
    case "compra":
      mostrarContainer("containerCompras");
      cargarComprasPendientes();
      break;
    case "venta":
      mostrarContainer("containerVentas");
      cargarVentasPendientes();
      break;
    case "sueldo":
      mostrarContainer("containerSueldos");
      cargarSueldosPendientes();
      break;
    case "otro":
      mostrarContainer("containerDescripcion");
      break;
  }
}

function mostrarContainer(containerId) {
  const container = document.getElementById(containerId);
  if (container) container.classList.remove("hidden");
}

function cargarComprasPendientes() {
  return fetch("Pagos/getComprasPendientes")
    .then((response) => response.json())
    .then((result) => {
      const select = document.getElementById("pagoCompra");
      if (!select) return result;

      select.innerHTML = '<option value="">Seleccionar compra...</option>';

      if (result.status && result.data) {
        result.data.forEach((compra) => {
          const option = document.createElement("option");
          option.value = compra.idcompra;
          option.textContent = `#${compra.nro_compra} - ${compra.proveedor} - Bs.${compra.balance}`;
          option.dataset.proveedor = compra.proveedor;
          option.dataset.identificacion = compra.proveedor_identificacion;
          option.dataset.balance = compra.balance;
          select.appendChild(option);
        });

        select.addEventListener("change", function () {
          if (this.value) {
            const option = this.options[this.selectedIndex];
            mostrarInformacionDestinatario(
              option.dataset.proveedor,
              option.dataset.identificacion,
              option.dataset.balance
            );
            if (!pagoEditando) {
              document.getElementById("pagoMonto").value = option.dataset.balance;
            }
          } else {
            ocultarInformacionDestinatario();
          }
        });
      } else {
        mostrarNotificacion("No hay compras disponibles", "info");
      }
      return result;
    })
    .catch((error) => {
      console.error("Error:", error);
      mostrarNotificacion("Error al cargar compras", "error");
      return { status: false };
    });
}

function cargarVentasPendientes() {
  return fetch("Pagos/getVentasPendientes")
    .then((response) => response.json())
    .then((result) => {
      const select = document.getElementById("pagoVenta");
      if (!select) return result;

      select.innerHTML = '<option value="">Seleccionar venta...</option>';

      if (result.status && result.data) {
        result.data.forEach((venta) => {
          const option = document.createElement("option");
          option.value = venta.idventa;
          option.textContent = `#${venta.nro_venta} - ${venta.cliente} - $${venta.total}`;
          option.dataset.cliente = venta.cliente;
          option.dataset.identificacion = venta.cliente_identificacion;
          option.dataset.total = venta.total;
          select.appendChild(option);
        });

        select.addEventListener("change", function () {
          if (this.value) {
            const option = this.options[this.selectedIndex];
            mostrarInformacionDestinatario(
              option.dataset.cliente,
              option.dataset.identificacion,
              option.dataset.total
            );
            if (!pagoEditando) {
              document.getElementById("pagoMonto").value = option.dataset.total;
            }
          } else {
            ocultarInformacionDestinatario();
          }
        });
      } else {
        mostrarNotificacion("No hay ventas disponibles", "info");
      }
      return result;
    })
    .catch((error) => {
      console.error("Error:", error);
      mostrarNotificacion("Error al cargar ventas", "error");
      return { status: false };
    });
}

function cargarSueldosPendientes() {
  return fetch("Pagos/getSueldosPendientes")
    .then((response) => response.json())
    .then((result) => {
      const select = document.getElementById("pagoSueldo");
      if (!select) return result;

      select.innerHTML = '<option value="">Seleccionar sueldo...</option>';

      if (result.status && result.data) {
        result.data.forEach((sueldo) => {
          const option = document.createElement("option");
          option.value = sueldo.idsueldotemp;
          option.textContent = `${sueldo.empleado} - ${sueldo.periodo} - $${sueldo.total}`;
          option.dataset.empleado = sueldo.empleado;
          option.dataset.identificacion = sueldo.empleado_identificacion;
          option.dataset.total = sueldo.total;
          select.appendChild(option);
        });

        select.addEventListener("change", function () {
          if (this.value) {
            const option = this.options[this.selectedIndex];
            mostrarInformacionDestinatario(
              option.dataset.empleado,
              option.dataset.identificacion,
              option.dataset.total
            );
            if (!pagoEditando) {
              document.getElementById("pagoMonto").value = option.dataset.total;
            }
          } else {
            ocultarInformacionDestinatario();
          }
        });
      } else {
        mostrarNotificacion("No hay sueldos disponibles", "info");
      }
      return result;
    })
    .catch((error) => {
      console.error("Error:", error);
      mostrarNotificacion("Error al cargar sueldos", "error");
      return { status: false };
    });
}

function mostrarInformacionDestinatario(nombre, identificacion, monto) {
  const nombreEl = document.getElementById("destinatarioNombre");
  const identificacionEl = document.getElementById(
    "destinatarioIdentificacion"
  );
  const totalEl = document.getElementById("destinatarioTotal");
  const containerEl = document.getElementById("containerDestinatario");

  if (nombreEl) nombreEl.textContent = nombre;
  if (identificacionEl) identificacionEl.textContent = identificacion;
  if (totalEl) totalEl.textContent = `Bs.${parseFloat(monto).toFixed(2)}`;
  if (containerEl) containerEl.classList.remove("hidden");
}

function ocultarInformacionDestinatario() {
  const containerEl = document.getElementById("containerDestinatario");

  if (containerEl) containerEl.classList.add("hidden");
}

function establecerFechaActual() {
  const fechaEl = document.getElementById("pagoFecha");
  if (fechaEl && !pagoEditando) {
    const hoy = new Date().toISOString().split("T")[0];
    fechaEl.value = hoy;
  }
}

function registrarPago() {
  if (!tienePermiso("crear")) {
    mostrarModalPermisosDenegados("No tienes permisos para registrar pagos.");
    return;
  }

  const btnGuardar = document.getElementById("btnGuardarPago");
  const tipoPago = document.querySelector(
    'input[name="tipoPago"]:checked'
  )?.value;

  if (!tipoPago) {
    mostrarNotificacion("Debe seleccionar un tipo de pago", "warning");
    return;
  }

  const camposCompletos = [
    ...camposFormularioPago,
    ...obtenerCamposDinamicos(),
  ];

  if (!validarCamposVacios(camposCompletos, "formRegistrarPago")) {
    return;
  }

  let formularioConErrores = false;
  for (const campo of camposCompletos) {
    const inputElement = document.getElementById(campo.id);
    if (!inputElement || inputElement.offsetParent === null) continue;

    let esValido = true;

    if (campo.tipo === "select") {
      esValido = validarSelect(
        inputElement,
        campo.mensajes,
        "formRegistrarPago"
      );
    } else if (campo.tipo === "fecha") {
      esValido = validarFecha(inputElement, campo.mensajes);
    } else if (campo.tipo === "radio") {
      continue;
    } else if (["input", "textarea"].includes(campo.tipo) && campo.regex) {
      const valor = inputElement.value.trim();
      if (valor !== "" && !campo.regex.test(valor)) {
        const errorDiv = inputElement.nextElementSibling;
        if (errorDiv && campo.mensajes.formato) {
          errorDiv.textContent = campo.mensajes.formato;
          errorDiv.classList.remove("hidden");
        }
        inputElement.classList.add("border-red-500", "focus:ring-red-500");
        esValido = false;
      }
    }

    if (!esValido) formularioConErrores = true;
  }

  if (formularioConErrores) {
    mostrarNotificacion(
      "Por favor, corrija los campos marcados en rojo",
      "warning"
    );
    return;
  }

  if (btnGuardar) {
    btnGuardar.disabled = true;
    btnGuardar.innerHTML =
      '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';
  }

  const mapeoNombres = {
    tipoPago: "tipo_pago",
    pagoCompra: "idcompra",
    pagoVenta: "idventa",
    pagoSueldo: "idsueldotemp",
    pagoDescripcion: "descripcion",
    pagoMonto: "monto",
    pagoMetodoPago: "idtipo_pago",
    pagoReferencia: "referencia",
    pagoFecha: "fecha_pago",
    pagoObservaciones: "observaciones",
  };

  registrarEntidad({
    formId: "formRegistrarPago",
    endpoint: "Pagos/createPago",
    campos: camposCompletos,
    mapeoNombres: mapeoNombres,
    onSuccess: (result) => {
      Swal.fire({
        title: "¡Éxito!",
        text: result.message || "Pago registrado exitosamente",
        icon: "success",
        confirmButtonText: "Aceptar",
        confirmButtonColor: "#10B981",
      }).then(() => {
        limpiarValidaciones(camposCompletos, "formRegistrarPago");
        cerrarModal("modalRegistrarPago");
        tablaPagos.ajax.reload();
      });
    },
    onError: (result) => {
      Swal.fire({
        title: "¡Error!",
        text: result.message || "Error al registrar el pago",
        icon: "error",
        confirmButtonText: "Aceptar",
        confirmButtonColor: "#EF4444",
      });
    },
  }).finally(() => {
    if (btnGuardar) {
      btnGuardar.disabled = false;
      btnGuardar.innerHTML =
        '<i class="fas fa-save mr-1 md:mr-2"></i> Guardar Pago';
    }
  });
}

function actualizarPago() {
  if (!tienePermiso("editar")) {
    mostrarModalPermisosDenegados("No tienes permisos para actualizar pagos.");
    return;
  }

  const btnGuardar = document.getElementById("btnGuardarPago");

  if (!pagoEditando) {
    mostrarNotificacion("Error: No se está editando ningún pago", "error");
    return;
  }

  const tipoPago = document.querySelector(
    'input[name="tipoPago"]:checked'
  )?.value;

  if (!tipoPago) {
    mostrarNotificacion("Debe seleccionar un tipo de pago", "warning");
    return;
  }

  const camposCompletos = [
    ...camposFormularioPago,
    ...obtenerCamposDinamicos(),
  ];

  if (!validarCamposVacios(camposCompletos, "formRegistrarPago")) {
    return;
  }

  let formularioConErrores = false;
  for (const campo of camposCompletos) {
    const inputElement = document.getElementById(campo.id);
    if (!inputElement || inputElement.offsetParent === null) continue;

    let esValido = true;

    if (campo.tipo === "select") {
      esValido = validarSelect(
        inputElement,
        campo.mensajes,
        "formRegistrarPago"
      );
    } else if (campo.tipo === "fecha") {
      esValido = validarFecha(inputElement, campo.mensajes);
    } else if (campo.tipo === "radio") {
      continue;
    } else if (["input", "textarea"].includes(campo.tipo) && campo.regex) {
      const valor = inputElement.value.trim();
      if (valor !== "" && !campo.regex.test(valor)) {
        const errorDiv = inputElement.nextElementSibling;
        if (errorDiv && campo.mensajes.formato) {
          errorDiv.textContent = campo.mensajes.formato;
          errorDiv.classList.remove("hidden");
        }
        inputElement.classList.add("border-red-500", "focus:ring-red-500");
        esValido = false;
      }
    }

    if (!esValido) formularioConErrores = true;
  }

  if (formularioConErrores) {
    mostrarNotificacion(
      "Por favor, corrija los campos marcados en rojo",
      "warning"
    );
    return;
  }

  if (btnGuardar) {
    btnGuardar.disabled = true;
    btnGuardar.innerHTML =
      '<i class="fas fa-spinner fa-spin mr-2"></i>Actualizando...';
  }

  const formData = new FormData(document.getElementById("formRegistrarPago"));
  const data = {};

  data.idpago = pagoEditando.idpago;

  const mapeoNombres = {
    tipoPago: "tipo_pago",
    pagoCompra: "idcompra",
    pagoVenta: "idventa",
    pagoSueldo: "idsueldotemp",
    pagoDescripcion: "descripcion",
    pagoMonto: "monto",
    pagoMetodoPago: "idtipo_pago",
    pagoReferencia: "referencia",
    pagoFecha: "fecha_pago",
    pagoObservaciones: "observaciones",
  };

  for (const [formKey, dataKey] of Object.entries(mapeoNombres)) {
    const element =
      document.getElementById(formKey) ||
      document.querySelector(`input[name="${formKey}"]:checked`);
    if (element) {
      data[dataKey] = element.value || "";
    }
  }

  fetch("Pagos/updatePago", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(data),
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status) {
        Swal.fire({
          title: "¡Éxito!",
          text: result.message || "Pago actualizado exitosamente",
          icon: "success",
          confirmButtonText: "Aceptar",
          confirmButtonColor: "#10B981",
        }).then(() => {
          limpiarValidaciones(camposCompletos, "formRegistrarPago");
          cerrarModal("modalRegistrarPago");
          tablaPagos.ajax.reload();
          pagoEditando = null;
        });
      } else {
        Swal.fire({
          title: "¡Error!",
          text: result.message || "Error al actualizar el pago",
          icon: "error",
          confirmButtonText: "Aceptar",
          confirmButtonColor: "#EF4444",
        });
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      mostrarNotificacion("Error de conexión al actualizar", "error");
    })
    .finally(() => {
      if (btnGuardar) {
        btnGuardar.disabled = false;
        btnGuardar.innerHTML =
          '<i class="fas fa-save mr-1 md:mr-2"></i> Actualizar Pago';
      }
    });
}

function mostrarNotificacion(mensaje, tipo) {
  if (typeof Swal !== "undefined") {
    const iconos = {
      success: "success",
      error: "error",
      warning: "warning",
      info: "info",
    };

    const colores = {
      success: "#10B981",
      error: "#EF4444",
      warning: "#F59E0B",
      info: "#3B82F6",
    };

    Swal.fire({
      title:
        tipo === "error"
          ? "¡Error!"
          : tipo === "warning"
          ? "¡Atención!"
          : tipo === "info"
          ? "Información"
          : "¡Éxito!",
      text: mensaje,
      icon: iconos[tipo] || "info",
      confirmButtonText: "Aceptar",
      confirmButtonColor: colores[tipo] || "#3B82F6",
    });
  } else {
    alert(mensaje);
  }
}

window.verPago = function (idPago) {
  if (!tienePermiso("ver")) {
    mostrarModalPermisosDenegados("No tienes permisos para ver este pago.");
    return;
  }

  fetch(`Pagos/getPagoById/${idPago}`)
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        mostrarModalVerPago(result.data);
      } else {
        mostrarNotificacion(
          result.message || "Error al obtener el pago",
          "error"
        );
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      mostrarNotificacion("Error de conexión al obtener el pago", "error");
    });
};

window.editarPago = function (idPago) {
  if (!tienePermiso("editar")) {
    mostrarModalPermisosDenegados("No tienes permisos para editar este pago.");
    return;
  }

  fetch(`Pagos/getPagoById/${idPago}`)
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        abrirModalEdicion(result.data);
      } else {
        mostrarNotificacion(
          result.message || "Error al obtener el pago",
          "error"
        );
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      mostrarNotificacion("Error de conexión al obtener el pago", "error");
    });
};

function abrirModalEdicion(pago) {
  try {
    pagoEditando = pago;

    resetearFormulario();
    limpiarValidaciones(
      [...camposFormularioPago, ...obtenerCamposDinamicos()],
      "formRegistrarPago"
    );

    document.getElementById("tituloModalRegistrar").textContent = "Editar Pago";
    document.getElementById("btnGuardarPago").innerHTML =
      '<i class="fas fa-save mr-1 md:mr-2"></i> Actualizar Pago';

    cargarMetodosPago()
      .then(() => {
        llenarFormularioEdicion(pago);
        configurarEventosTipoPago();
        abrirModal("modalRegistrarPago");
      })
      .catch((error) => {
        console.error("Error al cargar métodos de pago:", error);
        mostrarNotificacion("Error al cargar los datos necesarios", "error");
      });
  } catch (error) {
    console.error("Error en abrirModalEdicion:", error);
    mostrarNotificacion("Error al abrir el modal de edición", "error");
  }
}

function llenarFormularioEdicion(pago) {
  try {
    let tipoPago = "otro";
    if (pago.idcompra) tipoPago = "compra";
    else if (pago.idventa) tipoPago = "venta";
    else if (pago.idsueldotemp) tipoPago = "sueldo";

    const radioTipo = document.querySelector(
      `input[name="tipoPago"][value="${tipoPago}"]`
    );
    if (radioTipo) {
      radioTipo.checked = true;
      manejarCambioTipoPago(tipoPago);
    }
    setTimeout(() => {
      document.getElementById("pagoMonto").value = pago.monto || "";
      document.getElementById("pagoMetodoPago").value = pago.idtipo_pago || "";
      document.getElementById("pagoReferencia").value = pago.referencia || "";
      document.getElementById("pagoFecha").value = pago.fecha_pago || "";
      document.getElementById("pagoObservaciones").value =
        pago.observaciones || "";

      setTimeout(() => {
        if (tipoPago === "compra" && pago.idcompra) {
          cargarComprasPendientes().then(() => {
            const select = document.getElementById("pagoCompra");
            let optionExists = false;
            for (let i = 0; i < select.options.length; i++) {
              if (select.options[i].value == pago.idcompra) {
                optionExists = true;
                break;
              }
            }

            if (!optionExists) {
              const option = document.createElement("option");
              option.value = pago.idcompra;
              option.textContent = `Compra #${pago.idcompra} (Actual)`;
              select.appendChild(option);
            }

            select.value = pago.idcompra;
            select.dispatchEvent(new Event("change"));
          });
        } else if (tipoPago === "venta" && pago.idventa) {
          cargarVentasPendientes().then(() => {
            const select = document.getElementById("pagoVenta");
            let optionExists = false;
            for (let i = 0; i < select.options.length; i++) {
              if (select.options[i].value == pago.idventa) {
                optionExists = true;
                break;
              }
            }

            if (!optionExists) {
              const option = document.createElement("option");
              option.value = pago.idventa;
              option.textContent = `Venta #${pago.idventa} (Actual)`;
              select.appendChild(option);
            }

            select.value = pago.idventa;
            select.dispatchEvent(new Event("change"));
          });
        } else if (tipoPago === "sueldo" && pago.idsueldotemp) {
          cargarSueldosPendientes().then(() => {
            const select = document.getElementById("pagoSueldo");
            let optionExists = false;
            for (let i = 0; i < select.options.length; i++) {
              if (select.options[i].value == pago.idsueldotemp) {
                optionExists = true;
                break;
              }
            }

            if (!optionExists) {
              const option = document.createElement("option");
              option.value = pago.idsueldotemp;
              option.textContent = `Sueldo #${pago.idsueldotemp} (Actual)`;
              select.appendChild(option);
            }

            select.value = pago.idsueldotemp;
            select.dispatchEvent(new Event("change"));
          });
        } else if (tipoPago === "otro") {
          
        }
      }, 500);
    }, 200);
  } catch (error) {
    console.error("Error en llenarFormularioEdicion:", error);
    mostrarNotificacion("Error al llenar el formulario", "error");
  }
}

window.eliminarPago = function (idPago, descripcion) {
  if (!tienePermiso("eliminar")) {
    mostrarModalPermisosDenegados(
      "No tienes permisos para desactivar pagos."
    );
    return;
  }

  Swal.fire({
    title: "¿Estás seguro?",
    text: `¿Deseas desactivar el pago "${descripcion}"?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#EF4444",
    cancelButtonColor: "#6B7280",
    confirmButtonText: "Sí, desactivar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      fetch("Pagos/deletePago", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ idpago: idPago }),
      })
        .then((response) => response.json())
        .then((result) => {
          if (result.status) {
            Swal.fire({
              title: "¡Éxito!",
              text: result.message,
              icon: "success",
              confirmButtonText: "Aceptar",
              confirmButtonColor: "#10B981",
            }).then(() => {
              tablaPagos.ajax.reload();
            });
          } else {
            mostrarNotificacion(result.message, "error");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          mostrarNotificacion("Error de conexión al eliminar", "error");
        });
    }
  });
};

function mostrarModalVerPago(pago) {
  const elementos = {
    verPagoId: pago.idpago || "N/A",
    verPagoTipo: pago.tipo_pago_texto || "N/A",
    verPagoDestinatario: pago.destinatario || "N/A",
    verPagoMonto: `$${parseFloat(pago.monto || 0).toFixed(2)}`,
    verPagoMetodo: pago.metodo_pago || "N/A",
    verPagoReferencia: pago.referencia || "Sin referencia",
    verPagoFecha: pago.fecha_pago_formato || "N/A",
    verPagoObservaciones: pago.observaciones || "Sin observaciones",
    verPagoFechaCreacion: pago.fecha_creacion
      ? new Date(pago.fecha_creacion).toLocaleDateString("es-ES")
      : "N/A",
  };

  Object.entries(elementos).forEach(([id, valor]) => {
    const elemento = document.getElementById(id);
    if (elemento) {
      elemento.textContent = valor;
    }
  });

  const estatusEl = document.getElementById("verPagoEstatus");
  if (estatusEl && pago.estatus) {
    const status = pago.estatus.toLowerCase();

    if (status === "activo") {
      estatusEl.className =
        "inline-flex px-2 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded-full";
      estatusEl.textContent = "ACTIVO";
    } else if (status === "conciliado") {
      estatusEl.className =
        "inline-flex px-2 py-1 text-xs font-semibold bg-blue-100 text-blue-800 rounded-full";
      estatusEl.textContent = "CONCILIADO";
    } else {
      estatusEl.className =
        "inline-flex px-2 py-1 text-xs font-semibold bg-red-100 text-red-800 rounded-full";
      estatusEl.textContent = "INACTIVO";
    }
  }

  const personaEl = document.getElementById("verPagoPersona");
  if (personaEl) {
    personaEl.textContent = pago.persona_nombre || "Sin asignar";
  }

  abrirModal("modalVerPago");
}