import { abrirModal, cerrarModal } from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  validarCamposVacios,
  validarCampo,
  validarSelect,
  limpiarValidaciones,
  registrarEntidad,
} from "./validaciones.js";

let tablaCompras;
let detalleCompraItemsModal = [];
let detalleCompraItemsActualizar = [];
let tasasMonedasActualizar = {};
let fechaActualCompraActualizar = null;

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
    regex: expresiones.apellido,
    mensajes: {
      vacio: "El apellido es obligatorio.",
      formato: "El apellido solo puede contener letras y espacios.",
    },
  },
  {
    id: "proveedorIdentificacion",
    tipo: "input",
    regex: expresiones.cedula,
    mensajes: {
      vacio: "La identificación es obligatoria.",
      formato:
        "Formato de identificación inválido. Debe contener el formato V/J/E Ejemplo V-12345678 o J-12345678.",
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
    regex: expresiones.email,
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
    id: "proveedorGenero",
    tipo: "select",
    regex: expresiones.genero,
    mensajes: {
      formato: "Género inválido.",
    },
  },

  {
    id: "proveedorFechaNacimiento",
    tipo: "fechaNacimiento",
    mensajes: {
      vacio: "La fecha de nacimiento es obligatoria.",
      fechaPosterior: "La fecha de nacimiento no puede ser posterior a hoy.",
    },
  },
];

const camposCompras = [
  {
    id: "observaciones_compra_modal",
    tipo: "textarea",
    regex: expresiones.observaciones,
    mensajes: {
      formato: "Las observaciones no deben exceder los 100 caracteres.",
    },
  },
];

const camposFormularioActualizarCompra = [
  {
    id: "fechaActualizar",
    tipo: "date",
    mensajes: { vacio: "La fecha es obligatoria." },
  },
  {
    id: "observacionesActualizar",
    tipo: "textarea",
    regex: expresiones.observaciones,
    mensajes: {
      formato: "Las observaciones no deben exceder los 100 caracteres.",
    },
  },
];

document.addEventListener("DOMContentLoaded", function () {
  $(document).ready(function () {
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
      if (settings.nTable.id !== "TablaCompras") {
        return true;
      }
      var api = new $.fn.dataTable.Api(settings);
      var rowData = api.row(dataIndex).data();

      return (
        rowData &&
        rowData.estatus_compra &&
        rowData.estatus_compra.toLowerCase() !== "inactivo"
      );
    });

    if ($.fn.DataTable.isDataTable("#TablaCompras")) {
      $("#TablaCompras").DataTable().destroy();
    }

    tablaCompras = $("#TablaCompras").DataTable({
      processing: true,
      ajax: {
        url: "Compras/getComprasDataTable",
        type: "GET",
        dataSrc: function (json) {
          if (json && json.data) {
            return json.data;
          } else {
            console.error("Error en respuesta del servidor (Compras):", json);
            $("#TablaCompras_processing").css("display", "none");
            alert("Error: No se pudieron cargar los datos de compras.");
            return [];
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.error("Error AJAX (Compras):", textStatus, errorThrown);
          $("#TablaCompras_processing").css("display", "none");
          alert("Error de comunicación al cargar los datos de compras.");
        },
      },
      columns: [
        {
          data: "nro_compra",
          title: "Nro. Compra",
          className:
            "all whitespace-nowrap py-2 px-3 text-gray-700 dt-fixed-col-background",
        },
        {
          data: "fecha",
          title: "Fecha",
          className: "all whitespace-nowrap py-2 px-3 text-gray-700",
          render: function (data) {
            return data ? new Date(data).toLocaleDateString("es-ES") : "N/A";
          },
        },
        {
          data: "proveedor",
          title: "Proveedor",
          className: "desktop whitespace-nowrap py-2 px-3 text-gray-700",
        },
        {
          data: "total_general",
          title: "Total",
          className:
            "tablet-l whitespace-nowrap py-2 px-3 text-gray-700 text-right",
          render: function (data) {
            return data
              ? "Bs. " +
                  parseFloat(data).toLocaleString("es-ES", {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  })
              : "Bs. 0.00";
          },
        },
        {
          data: "estatus_compra",
          title: "Estado",
          className: "min-tablet-p text-center py-2 px-3",
          render: function (data) {
            if (!data) return '<i style="color: silver;">N/A</i>';
            var estatusUpper = String(data).toUpperCase();
            var badgeClass = "bg-gray-100 text-gray-800";
            switch (estatusUpper) {
              case "BORRADOR":
                badgeClass = "bg-yellow-100 text-yellow-800";
                break;
              case "POR_AUTORIZAR":
                badgeClass = "bg-blue-100 text-blue-800";
                break;
              case "AUTORIZADA":
                badgeClass = "bg-green-100 text-green-800";
                break;
              case "POR_PAGAR":
                badgeClass = "bg-orange-100 text-orange-800";
                break;
              case "PAGADA":
                badgeClass = "bg-purple-100 text-purple-800";
                break;
            }
            return `<span class="${badgeClass} text-xs font-medium me-2 px-1.5 py-0.5 rounded">${data}</span>`;
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
            var idCompra = row.idcompra || "";
            var nroCompra = row.nro_compra || "Sin número";
            var estadoActual = row.estatus_compra || "";
            var botonesEstado = "";

            switch (estadoActual.toUpperCase()) {
              case "BORRADOR":
                botonesEstado = `
                  <button
                    class="cambiar-estado-btn text-blue-500 hover:text-blue-700 p-1 transition-colors duration-150"
                    data-idcompra="${idCompra}"
                    data-nuevo-estado="POR_AUTORIZAR"
                    title="Enviar a Autorización"
                  >
                    <i class="fas fa-paper-plane fa-fw text-base"></i>
                  </button>`;
                break;
              case "POR_AUTORIZAR":
                botonesEstado = `
                  <button
                    class="cambiar-estado-btn text-green-500 hover:text-green-700 p-1 transition-colors duration-150"
                    data-idcompra="${idCompra}"
                    data-nuevo-estado="AUTORIZADA"
                    title="Autorizar"
                  >
                    <i class="fas fa-check fa-fw text-base"></i>
                  </button>
                  <button
                    class="cambiar-estado-btn text-yellow-500 hover:text-yellow-700 p-1 transition-colors duration-150"
                    data-idcompra="${idCompra}"
                    data-nuevo-estado="BORRADOR"
                    title="Devolver a Borrador"
                  >
                    <i class="fas fa-undo fa-fw text-base"></i>
                  </button>`;
                break;
              case "AUTORIZADA":
                botonesEstado = `
                  <button
                    class="cambiar-estado-btn text-orange-500 hover:text-orange-700 p-1 transition-colors duration-150"
                    data-idcompra="${idCompra}"
                    data-nuevo-estado="POR_PAGAR"
                    title="Marcar para Pago"
                  >
                    <i class="fas fa-credit-card fa-fw text-base"></i>
                  </button>`;
                break;
              case "POR_PAGAR":
                botonesEstado = `
                  <button
                    class="cambiar-estado-btn text-purple-500 hover:text-purple-700 p-1 transition-colors duration-150"
                    data-idcompra="${idCompra}"
                    data-nuevo-estado="PAGADA"
                    title="Marcar como Pagada"
                  >
                    <i class="fas fa-money-check-alt fa-fw text-base"></i>
                  </button>
                  <button
                    class="cambiar-estado-btn text-orange-500 hover:text-orange-700 p-1 transition-colors duration-150"
                    data-idcompra="${idCompra}"
                    data-nuevo-estado="AUTORIZADA"
                    title="Devolver a Autorizada"
                  >
                    <i class="fas fa-undo fa-fw text-base"></i>
                  </button>`;
                break;
              case "PAGADA":
                botonesEstado = `
                  <a
                    href="./compras/factura/${idCompra}"
                    target="_blank"
                    class="text-blue-600 hover:text-blue-800 p-1 transition-colors duration-150"
                    title="Ver Factura"
                  >
                    <i class="fas fa-file-alt fa-fw text-base"></i>
                  </a>
                  <span class="text-green-600 font-semibold text-xs px-1.5 py-0.5">
                    FINALIZADA
                  </span>`;
                break;
            }

            let botonesPrincipales = "";
            if (estadoActual.toUpperCase() === "BORRADOR") {
              botonesPrincipales = `
                <button
                  class="editar-compra-btn text-blue-600 hover:text-blue-700 p-1 transition-colors duration-150"
                  data-idcompra="${idCompra}"
                  title="Editar"
                >
                  <i class="fas fa-edit fa-fw text-base"></i>
                </button>
                <button
                  class="eliminar-compra-btn text-red-600 hover:text-red-700 p-1 transition-colors duration-150"
                  data-idcompra="${idCompra}"
                  data-nro-compra="${nroCompra}"
                  title="Eliminar"
                >
                  <i class="fas fa-trash-alt fa-fw text-base"></i>
                </button>`;
            }

            return `
              <div class="inline-flex items-center space-x-1">
                <button
                  class="ver-compra-btn text-green-600 hover:text-green-800 p-1 transition-colors duration-150"
                  data-idcompra="${idCompra}"
                  title="Ver Detalle"
                >
                  <i class="fas fa-eye fa-fw text-base"></i>
                </button>
                ${botonesPrincipales}
                ${botonesEstado}
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
          '<div class="text-center py-4"><i class="fas fa-info-circle fa-2x text-gray-400 mb-2"></i><p class="text-gray-600">No hay compras disponibles.</p></div>',
        info: "Mostrando _START_ a _END_ de _TOTAL_ compras",
        infoEmpty: "Mostrando 0 compras",
        infoFiltered: "(filtrado de _MAX_ compras totales)",
        lengthMenu: "Mostrar _MENU_ compras",
        search: "_INPUT_",
        searchPlaceholder: "Buscar compra...",
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
      order: [[0, "desc"]],
      scrollX: true,
      fixedColumns: {
        left: 1,
      },
      className: "compact",
      initComplete: function (settings, json) {
        window.tablaCompras = this.api();
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

    $("#TablaCompras tbody").on("click", ".ver-compra-btn", function () {
      const idCompra = $(this).data("idcompra");
      if (idCompra && typeof verCompra === "function") {
        verCompra(idCompra);
      } else {
        console.error(
          "Función verCompra no definida o idCompra no encontrado."
        );
        alert("Error: No se pudo obtener el ID de la compra para verla.");
      }
    });

    $("#TablaCompras tbody").on("click", ".editar-compra-btn", function () {
      const idCompra = $(this).data("idcompra");
      if (idCompra && typeof editarCompra === "function") {
        editarCompra(idCompra);
      } else {
        console.error(
          "Función editarCompra no definida o idCompra no encontrado."
        );
        alert("Error: No se pudo obtener el ID de la compra para editarla.");
      }
    });

    $("#TablaCompras tbody").on("click", ".eliminar-compra-btn", function () {
      const idCompra = $(this).data("idcompra");
      const nroCompra = $(this).data("nro-compra");
      if (idCompra && typeof eliminarCompra === "function") {
        eliminarCompra(idCompra, nroCompra);
      } else {
        console.error(
          "Función eliminarCompra no definida o idCompra no encontrado."
        );
        alert("Error: No se pudo obtener el ID de la compra para eliminarla.");
      }
    });

    $("#TablaCompras tbody").on("click", ".cambiar-estado-btn", function () {
      const idCompra = $(this).data("idcompra");
      const nuevoEstado = $(this).data("nuevo-estado");
      if (
        idCompra &&
        nuevoEstado &&
        typeof cambiarEstadoCompra === "function"
      ) {
        cambiarEstadoCompra(idCompra, nuevoEstado);
      } else {
        console.error(
          "Función cambiarEstadoCompra no definida o faltan datos (idCompra, nuevoEstado)."
        );
        alert("Error: No se pudo cambiar el estado de la compra.");
      }
    });
  });

  const btnAbrirModalNuevaCompra = document.getElementById(
    "btnAbrirModalNuevaCompra"
  );
  const formNuevaCompraModal = document.getElementById("formNuevaCompraModal");
  const btnCerrarModalNuevaCompra = document.getElementById(
    "btnCerrarModalNuevaCompra"
  );
  const btnCancelarCompraModal = document.getElementById(
    "btnCancelarCompraModal"
  );
  const btnGuardarCompraModal = document.getElementById(
    "btnGuardarCompraModal"
  );

  const modalNuevaCompra = document.getElementById("modalNuevaCompra");
  const fechaCompraModal = document.getElementById("fecha_compra_modal");
  const selectMonedaGeneralModal = document.getElementById(
    "idmoneda_general_compra_modal"
  );
  const inputCriterioProveedorModal = document.getElementById(
    "inputCriterioProveedorModal"
  );
  const btnBuscarProveedorModal = document.getElementById(
    "btnBuscarProveedorModal"
  );
  const listaResultadosProveedorModal = document.getElementById(
    "listaResultadosProveedorModal"
  );
  const hiddenIdProveedorModal = document.getElementById(
    "idproveedor_seleccionado_modal"
  );
  const divInfoProveedorModal = document.getElementById(
    "proveedor_seleccionado_info_modal"
  );
  const selectProductoAgregarModal = document.getElementById(
    "select_producto_agregar_modal"
  );
  const btnAgregarProductoDetalleModal = document.getElementById(
    "btnAgregarProductoDetalleModal"
  );
  const cuerpoTablaDetalleCompraModal = document.getElementById(
    "cuerpoTablaDetalleCompraModal"
  );
  const totalGeneralDisplayModal = document.getElementById(
    "total_general_display_modal"
  );
  const totalGeneralInputModal = document.getElementById(
    "total_general_input_modal"
  );
  const mensajeErrorFormCompraModal = document.getElementById(
    "mensajeErrorFormCompraModal"
  );

  let tasasMonedas = {};
  let fechaActualCompra = null;

  function abrirModalNuevaCompra() {
    const ahora = new Date();
    const opcionesVenezuela = {
      timeZone: "America/Caracas",
    };
    const anioVE = parseInt(
      ahora.toLocaleString("en-US", {
        ...opcionesVenezuela,
        year: "numeric",
      })
    );
    const mesVE = parseInt(
      ahora.toLocaleString("en-US", {
        ...opcionesVenezuela,
        month: "numeric",
      })
    );
    const diaVE = parseInt(
      ahora.toLocaleString("en-US", {
        ...opcionesVenezuela,
        day: "numeric",
      })
    );

    if (!isNaN(anioVE) && !isNaN(mesVE) && !isNaN(diaVE)) {
      fechaCompraModal.valueAsDate = new Date(anioVE, mesVE - 1, diaVE);
    } else {
      console.error(
        "No se pudieron obtener los componentes de fecha para Venezuela. Usando fecha local del cliente."
      );
      fechaCompraModal.valueAsDate = new Date();
    }
    fechaActualCompra = fechaCompraModal.value;
    cargarTasasPorFecha(fechaActualCompra);

    formNuevaCompraModal.reset();

    if (!isNaN(anioVE) && !isNaN(mesVE) && !isNaN(diaVE)) {
      fechaCompraModal.valueAsDate = new Date(anioVE, mesVE - 1, diaVE);
      fechaActualCompra = fechaCompraModal.value;
    } else {
      fechaCompraModal.valueAsDate = new Date();
      fechaActualCompra = fechaCompraModal.value;
    }
    cargarTasasPorFecha(fechaActualCompra);

    detalleCompraItemsModal = [];
    renderizarTablaDetalleModal();
    hiddenIdProveedorModal.value = "";
    divInfoProveedorModal.innerHTML = "";
    divInfoProveedorModal.classList.add("hidden");
    mensajeErrorFormCompraModal.textContent = "";

    cargarMonedasParaModal();
    cargarProductosParaModal();

    modalNuevaCompra.classList.remove("opacity-0", "pointer-events-none");
    document.body.classList.add("overflow-hidden");
  }

  function cerrarModalNuevaCompra() {
    modalNuevaCompra.classList.add("opacity-0", "pointer-events-none");
    document.body.classList.remove("overflow-hidden");
  }

  async function cargarTasasPorFecha(fecha) {
    const divTasa = document.getElementById("tasaDelDiaInfo");
    divTasa.textContent = "Cargando tasas del día...";
    try {
      const response = await fetch(
        `Compras/getTasasMonedasPorFecha?fecha=${encodeURIComponent(fecha)}`
      );
      const data = await response.json();
      if (data.status && data.tasas && Object.keys(data.tasas).length > 0) {
        tasasMonedas = data.tasas;
        let texto = `Tasa del día (${fecha.split("-").reverse().join("/")})`;
        let tasasArr = [];
        for (const [moneda, tasa] of Object.entries(tasasMonedas)) {
          tasasArr.push(
            `1 ${moneda} = ${Number(tasa).toLocaleString("es-VE", {
              minimumFractionDigits: 2,
              maximumFractionDigits: 4,
            })} Bs.`
          );
        }
        texto += ": " + tasasArr.join(" | ");
        divTasa.textContent = texto;
        calcularTotalesGeneralesModal();
      } else {
        divTasa.textContent = "No hay tasas registradas para esta fecha.";
        tasasMonedas = {};
        calcularTotalesGeneralesModal();
        Swal.fire(
          "Atención",
          "No hay tasas registradas para la fecha seleccionada.",
          "warning"
        );
      }
    } catch (e) {
      divTasa.textContent = "Error al cargar tasas del día.";
      tasasMonedas = {};
      calcularTotalesGeneralesModal();
      Swal.fire(
        "Error",
        "Ocurrió un error al cargar las tasas del día.",
        "error"
      );
    }
  }

  if (btnAbrirModalNuevaCompra) {
    btnAbrirModalNuevaCompra.addEventListener("click", function () {
      abrirModalNuevaCompra();
      inicializarValidaciones(camposCompras, "formNuevaCompraModal");
    });
  }

  if (btnCerrarModalNuevaCompra) {
    btnCerrarModalNuevaCompra.addEventListener("click", function () {
      cerrarModalNuevaCompra();
      limpiarValidaciones(camposCompras, "formNuevaCompraModal");
      formNuevaCompraModal.reset();
    });
  }

  if (btnCancelarCompraModal) {
    btnCancelarCompraModal.addEventListener("click", function () {
      cerrarModalNuevaCompra();
      limpiarValidaciones(camposCompras, "formNuevaCompraModal");
      formNuevaCompraModal.reset();
    });
  }

  modalNuevaCompra.addEventListener("click", (e) => {
    if (e.target === modalNuevaCompra) {
      cerrarModalNuevaCompra();
      limpiarValidaciones(camposCompras, "formNuevaCompraModal");
      formNuevaCompraModal.reset();
    }
  });

  if (fechaCompraModal) {
    fechaCompraModal.addEventListener("change", function () {
      fechaActualCompra = this.value;
      if (fechaActualCompra) {
        cargarTasasPorFecha(fechaActualCompra);
      }
    });
  }

  async function cargarMonedasParaModal() {
    selectMonedaGeneralModal.innerHTML =
      '<option value="">Cargando...</option>';
    try {
      const response = await fetch("Compras/getListaMonedasParaFormulario");
      if (!response.ok) throw new Error("Error en respuesta de monedas");
      const monedas = await response.json();
      tasasMonedas = {};
      selectMonedaGeneralModal.innerHTML =
        '<option value="">Seleccione Moneda</option>';
      monedas.forEach((moneda) => {
        tasasMonedas[moneda.idmoneda] = parseFloat(moneda.valor);
        let simbolo = "";
        if (moneda.codigo_moneda === "USD") simbolo = "$";
        else if (moneda.codigo_moneda === "EUR") simbolo = "€";
        else if (moneda.codigo_moneda === "VES") simbolo = "Bs.";
        const option = document.createElement("option");
        option.value = moneda.idmoneda;
        option.textContent = `${moneda.codigo_moneda} (${simbolo})`;
        selectMonedaGeneralModal.appendChild(option);
      });
      selectMonedaGeneralModal.value = "3"; // VES por defecto
    } catch (error) {
      console.error("Error al cargar monedas:", error);
      selectMonedaGeneralModal.innerHTML =
        '<option value="">Error al cargar</option>';
    }
  }

  async function cargarProductosParaModal() {
    selectProductoAgregarModal.innerHTML =
      '<option value="">Cargando...</option>';
    try {
      const response = await fetch("Compras/getListaProductosParaFormulario");
      if (!response.ok) throw new Error("Error en respuesta de productos");
      const productos = await response.json();
      selectProductoAgregarModal.innerHTML =
        '<option value="">Seleccione producto...</option>';
      productos.forEach((producto) => {
        const option = document.createElement("option");
        option.value = producto.idproducto;
        option.dataset.idcategoria = producto.idcategoria;
        option.dataset.nombre = producto.nombre_producto;
        option.dataset.precio = producto.precio_referencia_compra || "0.00";
        option.dataset.idmoneda = producto.codigo_moneda || "";
        option.dataset.moneda = producto.idmoneda_producto || "";
        option.textContent = `${producto.nombre_producto} (${producto.nombre_categoria})`;
        selectProductoAgregarModal.appendChild(option);
      });
    } catch (error) {
      console.error("Error al cargar productos:", error);
      selectProductoAgregarModal.innerHTML =
        '<option value="">Error al cargar</option>';
    }
  }

  // Buscar proveedores
  if (btnBuscarProveedorModal && inputCriterioProveedorModal) {
    btnBuscarProveedorModal.addEventListener("click", async function () {
      const termino = inputCriterioProveedorModal.value.trim();
      if (termino.length < 2) {
        Swal.fire(
          "Atención",
          "Ingrese al menos 2 caracteres para buscar.",
          "warning"
        );
        return;
      }

      listaResultadosProveedorModal.innerHTML =
        '<div class="p-2 text-xs text-gray-500">Buscando...</div>';
      listaResultadosProveedorModal.classList.remove("hidden");

      try {
        const response = await fetch(
          `Compras/buscarProveedores?term=${encodeURIComponent(termino)}`
        );
        if (!response.ok) {
          throw new Error("Error en la respuesta del servidor");
        }
        const proveedores = await response.json();

        listaResultadosProveedorModal.innerHTML = "";
        if (proveedores && proveedores.length > 0) {
          proveedores.forEach((prov) => {
            const itemDiv = document.createElement("div");
            itemDiv.classList.add(
              "p-2",
              "text-xs",
              "hover:bg-gray-100",
              "cursor-pointer"
            );
            itemDiv.textContent = `${prov.nombre || ""} ${
              prov.apellido || ""
            } (${prov.identificacion || ""})`.trim();
            itemDiv.dataset.idproveedor = prov.idproveedor;
            itemDiv.dataset.nombre = `${prov.nombre || ""} ${
              prov.apellido || ""
            }`.trim();
            itemDiv.dataset.identificacion = prov.identificacion || "";

            itemDiv.addEventListener("click", function () {
              hiddenIdProveedorModal.value = this.dataset.idproveedor;
              divInfoProveedorModal.innerHTML = `Sel: <strong>${this.dataset.nombre}</strong> (ID: ${this.dataset.identificacion})`;
              divInfoProveedorModal.classList.remove("hidden");
              inputCriterioProveedorModal.value = this.textContent;
              listaResultadosProveedorModal.classList.add("hidden");
              listaResultadosProveedorModal.innerHTML = "";
            });
            listaResultadosProveedorModal.appendChild(itemDiv);
          });
        } else {
          listaResultadosProveedorModal.innerHTML =
            '<div class="p-2 text-xs text-gray-500">No se encontraron proveedores.</div>';
        }
      } catch (error) {
        console.error("Error al buscar proveedores:", error);
        listaResultadosProveedorModal.innerHTML =
          '<div class="p-2 text-xs text-red-500">Error al buscar. Intente de nuevo.</div>';
      }
    });
  }

  function calcularSubtotalLineaItemModal(item) {
    const precioUnitario = parseFloat(item.precio_unitario) || 0;
    let cantidadBase = 0;
    if (item.idcategoria === 2) {
      cantidadBase = calcularPesoNetoItemModal(item) || 0;
    } else {
      cantidadBase = parseFloat(item.cantidad_unidad) || 0;
    }
    const subtotalAntesDescuento = cantidadBase * precioUnitario;
    const porcentajeDescuento = parseFloat(item.descuento) || 0;
    let montoDescuento = 0;
    let subtotalConDescuento = subtotalAntesDescuento;
    if (porcentajeDescuento > 0 && porcentajeDescuento <= 100) {
      montoDescuento = subtotalAntesDescuento * (porcentajeDescuento / 100);
      subtotalConDescuento = subtotalAntesDescuento - montoDescuento;
    } else if (porcentajeDescuento > 100) {
      console.warn(
        `Porcentaje de descuento (${porcentajeDescuento}%) es mayor a 100. Se aplicará 0% o el máximo permitido.`
      );
    }
    item.subtotal_original_linea = subtotalAntesDescuento;
    item.monto_descuento_linea = montoDescuento;
    item.subtotal_linea = subtotalConDescuento;

    item.subtotal_linea_bs = convertirAMonedaBase(
      subtotalConDescuento,
      item.idmoneda_item
    );

    return item.subtotal_linea;
  }

  function calcularPesoNetoItemModal(item) {
    if (item.idcategoria === 2) {
      if (item.no_usa_vehiculo) {
        return parseFloat(item.peso_neto_directo) || 0;
      } else {
        const bruto = parseFloat(item.peso_bruto) || 0;
        const vehiculo = parseFloat(item.peso_vehiculo) || 0;
        return Math.max(0, bruto - vehiculo);
      }
    }
    return 0;
  }

  function convertirAMonedaBase(monto, idmoneda) {
    if (idmoneda == 3) return monto;
    const tasa = tasasMonedas[idmoneda] || 1;
    return monto * tasa;
  }

  function calcularTotalesGeneralesModal() {
    let subtotalGeneralBs = 0;
    let totalDescuentosBs = 0;

    // Calcular subtotal sumando los subtotales de cada línea ya convertidos a bolívares
    detalleCompraItemsModal.forEach((item) => {
      subtotalGeneralBs += parseFloat(item.subtotal_linea_bs) || 0;
    });

    // Calcular y mostrar el total general (subtotal - descuentos)
    const totalGeneral = subtotalGeneralBs - totalDescuentosBs;
    totalGeneralDisplayModal.value = `Bs. ${totalGeneral.toLocaleString(
      "es-VE",
      { minimumFractionDigits: 2, maximumFractionDigits: 2 }
    )}`;
    totalGeneralInputModal.value = totalGeneral.toFixed(2);
  }
  //DETALLE DE COMPRA
  if (btnAgregarProductoDetalleModal && selectProductoAgregarModal) {
    btnAgregarProductoDetalleModal.addEventListener("click", function () {
      const selectedOption =
        selectProductoAgregarModal.options[
          selectProductoAgregarModal.selectedIndex
        ];
      if (!selectedOption.value) {
        Swal.fire("Atención", "Seleccione un producto.", "warning");
        return;
      }

      const idproducto = selectedOption.value;
      if (
        detalleCompraItemsModal.find((item) => item.idproducto === idproducto)
      ) {
        Swal.fire("Atención", "Este producto ya ha sido agregado.", "warning");
        return;
      }

      const monedaGeneralSeleccionada =
        selectMonedaGeneralModal.options[
          selectMonedaGeneralModal.selectedIndex
        ];
      const simboloMonedaGeneral = monedaGeneralSeleccionada
        ? monedaGeneralSeleccionada.dataset.simbolo
        : "$";

      const item = {
        idproducto: idproducto,
        nombre: selectedOption.dataset.nombre,
        idcategoria: parseInt(selectedOption.dataset.idcategoria),
        precio_unitario: parseFloat(selectedOption.dataset.precio) || 0,
        idmoneda_item:
          selectedOption.dataset.idmoneda || selectMonedaGeneralModal.value,
        simbolo_moneda_item:
          selectedOption.dataset.monedaSimbolo || simboloMonedaGeneral,
        no_usa_vehiculo: false,
        peso_vehiculo: 0,
        peso_bruto: 0,
        peso_neto_directo: 0,
        cantidad_unidad: 1,
        descuento: 0,
        moneda: selectedOption.dataset.moneda,
      };

      detalleCompraItemsModal.push(item);
      renderizarTablaDetalleModal();
      selectProductoAgregarModal.value = "";
    });
  }

  function renderizarTablaDetalleModal() {
    cuerpoTablaDetalleCompraModal.innerHTML = "";
    detalleCompraItemsModal.forEach((item, index) => {
      const tr = document.createElement("tr");
      tr.classList.add("border-b", "hover:bg-gray-50");
      tr.dataset.index = index;

      let infoEspecificaHtml = "";
      if (item.idcategoria === 2) {
        infoEspecificaHtml = `
        <div class="space-y-1">
          <div>
            <label class="flex items-center text-xs">
              <input type="checkbox" class="form-checkbox h-3 w-3 mr-1 no_usa_vehiculo_cb_modal" ${
                item.no_usa_vehiculo ? "checked" : ""
              }> No usa vehículo
            </label>
          </div>
          <div class="campos_peso_vehiculo_modal ${
            item.no_usa_vehiculo ? "hidden" : ""
          }">
            P.Bru: 
            <input type="number" step="0.01" class="w-18 border rounded-md px-2 py-1 text-s focus:outline-none focus:ring-2 focus:ring-green-500 peso_bruto_modal" value="${
              item.peso_bruto || ""
            }" placeholder="0.00">
            <button type="button" class="btnUltimoPesoRomanaBruto bg-blue-100 text-blue-700 px-2 py-1 rounded ml-1" title="Traer último peso de romana"><i class="fas fa-balance-scale"></i></button>
            P.Veh: 
            <input type="number" step="0.01" class="w-18 border rounded-md px-2 py-1 text-s focus:outline-none focus:ring-2 focus:ring-green-500 peso_vehiculo_modal" value="${
              item.peso_vehiculo || ""
            }" placeholder="0.00">
            <button type="button" class="btnUltimoPesoRomanaVehiculo bg-blue-100 text-blue-700 px-2 py-1 rounded ml-1" title="Traer último peso de romana"><i class="fas fa-balance-scale"></i></button>
            Descuento %: 
            <input type="number" step="0.01" min="0" max="100" class="w-18 border rounded-md px-2 py-1 text-s focus:outline-none focus:ring-2 focus:ring-green-500 descuento_modal" value="${
              item.descuento || ""
            }" placeholder="0.00">
          </div>
          <div class="campo_peso_neto_directo_modal ${
            !item.no_usa_vehiculo ? "hidden" : ""
          }">
            P.Neto: <input type="number" step="0.01" class="w-18 border rounded-md py-1 text-s focus:outline-none focus:ring-2 focus:ring-green-500 peso_neto_directo_modal" value="${
              item.peso_neto_directo || ""
            }" placeholder="0.00">
            <button type="button" class="btnUltimoPesoRomanaBruto bg-blue-100 text-blue-700 px-2 py-1 rounded ml-1" title="Traer último peso de romana"><i class="fas fa-balance-scale"></i></button>
            Descuento %: 
            <input type="number" step="0.01" min="0" max="100" class="w-18 border rounded-md px-2 py-1 text-s focus:outline-none focus:ring-2 focus:ring-green-500 descuento_modal" value="${
              item.descuento || ""
            }" placeholder="0.00">
          </div>
          Neto Calc: <strong class="peso_neto_calculado_display_modal">${calcularPesoNetoItemModal(
            item
          ).toFixed(2)}</strong>
        </div>`;
      } else {
        infoEspecificaHtml = `
          <div>
            Cant: <input type="number" step="0.01" class="w-18 border rounded-md px-1 py-1 text-s focus:outline-none focus:ring-2 focus:ring-green-500 cantidad_unidad_modal" value="${
              item.cantidad_unidad || "1"
            }" placeholder="1">
          </div>`;
      }

      tr.innerHTML = `
        <td class="py-0.5 px-0.5 text-xs">${item.nombre}</td>
        <td class="py-0.5 px-0.5 text-xs">${infoEspecificaHtml}</td>
        <td class="py-0.5 px-0.5 text-xs">
            ${
              item.idmoneda_item
            } <input type="number" step="0.01" class="w-17 border rounded-md px-1 py-1 text-s focus:outline-none focus:ring-2 focus:ring-green-500 precio_unitario_item_modal" value="${item.precio_unitario.toFixed(
        2
      )}" placeholder="0.00">
        </td>
        <td class="py-0.5 px-0.5 text-xs subtotal_linea_display_modal">${
          item.idmoneda_item
        } ${calcularSubtotalLineaItemModal(item).toFixed(2)}</td>
        <td class="py-0.5 px-0.5 text-center"><button type="button" class="fa-solid fa-x text-red-500 hover:text-red-700 btnEliminarItemDetalleModal text-xs"></button></td>
      `;
      cuerpoTablaDetalleCompraModal.appendChild(tr);
    });
    addEventListenersToDetalleInputsModal();
    calcularTotalesGeneralesModal();
  }

  function addEventListenersToDetalleInputsModal() {
    document
      .querySelectorAll("#cuerpoTablaDetalleCompraModal tr")
      .forEach((row) => {
        const index = parseInt(row.dataset.index);
        if (isNaN(index) || index >= detalleCompraItemsModal.length) return;
        const item = detalleCompraItemsModal[index];

        // Event listeners para pesos de romana actualizados el 12-06-2025
        const btnUltimoPesoBruto = row.querySelector(
          ".btnUltimoPesoRomanaBruto"
        );
        if (btnUltimoPesoBruto) {
          btnUltimoPesoBruto.addEventListener("click", async function () {
            try {
              console.log("Consultando romana...");
              const response = await fetch("Compras/getUltimoPesoRomana");
              if (!response.ok)
                throw new Error(`HTTP error! status: ${response.status}`);
              const data = await response.json();
              if (data.status) {
                if (item.no_usa_vehiculo) {
                  item.peso_neto_directo = data.peso;
                  row.querySelector(".peso_neto_directo_modal").value =
                    data.peso;
                } else {
                  item.peso_bruto = data.peso;
                  row.querySelector(".peso_bruto_modal").value = data.peso;
                }
                actualizarCalculosFilaModal(row, item);

                // Guardar el peso en la base de datos
                await fetch("Compras/guardarPesoRomana", {
                  method: "POST",
                  headers: { "Content-Type": "application/json" },
                  body: JSON.stringify({
                    peso: data.peso,
                    fecha: new Date()
                      .toISOString()
                      .slice(0, 19)
                      .replace("T", " "),
                  }),
                });
              } else {
                Swal.fire(
                  "Atención",
                  data.message || "No se pudo obtener el peso.",
                  "warning"
                );
              }
            } catch (e) {
              console.error("Error completo:", e);
              Swal.fire(
                "Error",
                "Error al consultar la romana: " + e.message,
                "error"
              );
            }
          });
        }

        const btnUltimoPesoVehiculo = row.querySelector(
          ".btnUltimoPesoRomanaVehiculo"
        );
        if (btnUltimoPesoVehiculo) {
          btnUltimoPesoVehiculo.addEventListener("click", async function () {
            try {
              const response = await fetch("Compras/getUltimoPesoRomana");
              const data = await response.json();
              if (data.status) {
                item.peso_vehiculo = data.peso;
                row.querySelector(".peso_vehiculo_modal").value = data.peso;
                actualizarCalculosFilaModal(row, item);

                // Guardar el peso en la base de datos
                await fetch("Compras/guardarPesoRomana", {
                  method: "POST",
                  headers: { "Content-Type": "application/json" },
                  body: JSON.stringify({
                    peso: data.peso,
                    peso_texto: data.peso + " kg",
                    estado: data.estado || null,
                    variacion: data.variacion || null,
                    promedio: data.promedio || null,
                    observaciones: "Peso registrado desde interfaz de compra",
                    usuario_id: window.ID_USUARIO_ACTUAL || null, // Ajusta según tu sistema
                    fecha: new Date()
                      .toISOString()
                      .slice(0, 19)
                      .replace("T", " "),
                  }),
                });
              } else {
                Swal.fire(
                  "Atención",
                  data.message || "No se pudo obtener el peso.",
                  "warning"
                );
              }
            } catch (e) {
              Swal.fire("Error", "Error al consultar la romana.", "error");
            }
          });
        }

        // Checkbox no usa vehículo
        const cbNoUsaVehiculo = row.querySelector(".no_usa_vehiculo_cb_modal");
        if (cbNoUsaVehiculo) {
          cbNoUsaVehiculo.addEventListener("change", function (e) {
            item.no_usa_vehiculo = e.target.checked;
            const camposPesoVehiculo = row.querySelector(
              ".campos_peso_vehiculo_modal"
            );
            const campoPesoNetoDirecto = row.querySelector(
              ".campo_peso_neto_directo_modal"
            );
            if (e.target.checked) {
              camposPesoVehiculo.classList.add("hidden");
              campoPesoNetoDirecto.classList.remove("hidden");
              item.peso_vehiculo = 0;
              item.peso_bruto = 0;
            } else {
              camposPesoVehiculo.classList.remove("hidden");
              campoPesoNetoDirecto.classList.add("hidden");
              item.peso_neto_directo = 0;
            }
            actualizarCalculosFilaModal(row, item);
          });
        }

        row
          .querySelectorAll(
            ".peso_vehiculo_modal, .peso_bruto_modal, .peso_neto_directo_modal, .cantidad_unidad_modal, .precio_unitario_item_modal, .descuento_modal"
          )
          .forEach((input) => {
            input.addEventListener("input", function (e) {
              const fieldName = e.target.classList.contains(
                "peso_vehiculo_modal"
              )
                ? "peso_vehiculo"
                : e.target.classList.contains("peso_bruto_modal")
                ? "peso_bruto"
                : e.target.classList.contains("peso_neto_directo_modal")
                ? "peso_neto_directo"
                : e.target.classList.contains("cantidad_unidad_modal")
                ? "cantidad_unidad"
                : e.target.classList.contains("descuento_modal")
                ? "descuento"
                : "precio_unitario";

              let valor = parseFloat(e.target.value) || 0;

              // Validar que el descuento no sea mayor a 100%
              if (fieldName === "descuento" && valor > 100) {
                valor = 100;
                e.target.value = 100;
                Swal.fire(
                  "Atención",
                  "El descuento no puede ser mayor al 100%",
                  "warning"
                );
              }

              item[fieldName] = valor;
              actualizarCalculosFilaModal(row, item);
            });
          });

        // Botón eliminar item
        row
          .querySelector(".btnEliminarItemDetalleModal")
          .addEventListener("click", function () {
            detalleCompraItemsModal.splice(index, 1);
            renderizarTablaDetalleModal();
          });
      });
  }

  function actualizarCalculosFilaModal(rowElement, item) {
    const pesoNetoDisplay = rowElement.querySelector(
      ".peso_neto_calculado_display_modal"
    );
    if (pesoNetoDisplay) {
      pesoNetoDisplay.textContent = calcularPesoNetoItemModal(item).toFixed(2);
    }
    rowElement.querySelector(".subtotal_linea_display_modal").textContent = `${
      item.idmoneda_item
    } ${calcularSubtotalLineaItemModal(item).toFixed(2)}`;
    calcularTotalesGeneralesModal();
  }

  if (selectMonedaGeneralModal) {
    selectMonedaGeneralModal.addEventListener(
      "change",
      calcularTotalesGeneralesModal
    );
  }

  // GUARDAR COMPRA - ACTUALIZADO PARA ENVIAR DESCUENTOS
  if (btnGuardarCompraModal) {
    btnGuardarCompraModal.addEventListener("click", async function () {
      if (!validarCamposVacios(camposCompras, "formNuevaCompraModal")) return;
      mensajeErrorFormCompraModal.textContent = "";

      if (detalleCompraItemsModal.length === 0) {
        mensajeErrorFormCompraModal.textContent =
          "Debe agregar al menos un producto al detalle.";
        return;
      }

      if (!selectMonedaGeneralModal.value) {
        mensajeErrorFormCompraModal.textContent =
          "Debe seleccionar una moneda general para la compra.";
        return;
      }

      for (const item of detalleCompraItemsModal) {
        const precio = parseFloat(item.precio_unitario) || 0;
        let cantidadValida = false;
        if (item.idcategoria === 2) {
          cantidadValida = calcularPesoNetoItemModal(item) > 0;
        } else {
          cantidadValida = (parseFloat(item.cantidad_unidad) || 0) > 0;
        }
        if (precio <= 0 || !cantidadValida) {
          mensajeErrorFormCompraModal.textContent = `El producto "${item.nombre}" tiene precio o cantidad/peso inválido.`;
          return;
        }
      }

      const formData = new FormData(formNuevaCompraModal);
      // Mapear productos con todos los datos incluyendo descuento
      const productosDetalle = detalleCompraItemsModal.map((item) => ({
        idproducto: item.idproducto,
        nombre_producto: item.nombre,
        cantidad:
          item.idcategoria === 2
            ? calcularPesoNetoItemModal(item)
            : item.cantidad_unidad,
        precio_unitario_compra: item.precio_unitario,
        idmoneda_detalle: item.idmoneda_item,
        descuento: item.descuento || 0, // CAMPO DESCUENTO AGREGADO
        moneda: item.moneda,
        subtotal_original_linea: item.subtotal_original_linea || 0,
        monto_descuento_linea: item.monto_descuento_linea || 0,
        subtotal_linea: item.subtotal_linea,
        peso_vehiculo:
          item.idcategoria === 2 && !item.no_usa_vehiculo
            ? item.peso_vehiculo
            : null,
        peso_bruto:
          item.idcategoria === 2 && !item.no_usa_vehiculo
            ? item.peso_bruto
            : null,
        peso_neto:
          item.idcategoria === 2
            ? item.no_usa_vehiculo
              ? item.peso_neto_directo
              : calcularPesoNetoItemModal(item)
            : null,
      }));

      formData.append("productos_detalle", JSON.stringify(productosDetalle));
      formData.set("total_general_input", totalGeneralInputModal.value);

      btnGuardarCompraModal.disabled = true;
      btnGuardarCompraModal.textContent = "Guardando...";

      try {
        const response = await fetch(`Compras/setCompra`, {
          method: "POST",
          body: formData,
        });
        const data = await response.json();
        Swal.fire({
          title: data.status ? "¡Éxito!" : "Error",
          text: data.message,
          icon: data.status ? "success" : "error",
        });
        if (data.status) {
          cerrarModalNuevaCompra();
          $("#TablaCompras").DataTable().ajax.reload();
        } else {
          mensajeErrorFormCompraModal.textContent =
            data.message || "Error al guardar.";
        }
      } catch (error) {
        console.log(detalleCompraItemsModal);
        console.error("Error al guardar compra:", error);
        Swal.fire("Error", "Ocurrió un error de conexión al guardar.", "error");
        mensajeErrorFormCompraModal.textContent =
          "Ocurrió un error de conexión al guardar.";
      } finally {
        btnGuardarCompraModal.disabled = false;
        btnGuardarCompraModal.textContent = "Guardar Compra";
      }
    });
  }

  // MODAL REGISTRAR PROVEEDOR - CORREGIDO
  const btnAbrirModalRegistroProveedor = document.getElementById(
    "btnAbrirModalRegistrarProveedor"
  );
  const formRegistrarProveedor = document.getElementById(
    "formRegistrarProveedor"
  );
  const btnCerrarModalRegistroProveedor = document.getElementById(
    "btnCerrarModalRegistrar"
  );
  const btnCancelarModalRegistroProveedor = document.getElementById(
    "btnCancelarModalRegistrar"
  );
  const modalRegistrarProveedor = document.getElementById(
    "modalRegistrarProveedor"
  );

  // Función para abrir modal registrar proveedor
  function abrirModalRegistrarProveedor() {
    if (modalRegistrarProveedor) {
      if (formRegistrarProveedor) formRegistrarProveedor.reset();
      limpiarValidaciones(camposFormularioProveedor, "formRegistrarProveedor");
      inicializarValidaciones(
        camposFormularioProveedor,
        "formRegistrarProveedor"
      );
      abrirModal("modalRegistrarProveedor");
    }
  }

  // Función para cerrar modal registrar proveedor
  function cerrarModalRegistrarProveedor() {
    if (modalRegistrarProveedor) {
      limpiarValidaciones(camposFormularioProveedor, "formRegistrarProveedor");
      if (formRegistrarProveedor) formRegistrarProveedor.reset();
      cerrarModal("modalRegistrarProveedor");
    }
  }

  // Event listeners para modal registrar proveedor
  if (btnAbrirModalRegistroProveedor) {
    btnAbrirModalRegistroProveedor.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      abrirModalRegistrarProveedor();
    });
  }

  if (btnCerrarModalRegistroProveedor) {
    btnCerrarModalRegistroProveedor.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      cerrarModalRegistrarProveedor();
    });
  }

  if (btnCancelarModalRegistroProveedor) {
    btnCancelarModalRegistroProveedor.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      cerrarModalRegistrarProveedor();
    });
  }

  // Prevenir que el modal se cierre al hacer click dentro del contenido
  if (modalRegistrarProveedor) {
    const modalContent = modalRegistrarProveedor.querySelector(
      ".modal-content, .bg-white"
    );
    if (modalContent) {
      modalContent.addEventListener("click", function (e) {
        e.stopPropagation();
      });
    }
  }

  // SUBMIT FORM REGISTRAR PROVEEDOR
  if (formRegistrarProveedor) {
    formRegistrarProveedor.addEventListener("submit", function (e) {
      e.preventDefault();
      e.stopPropagation();
      registrarProveedor();
    });
  }

  function registrarProveedor() {
    const btnGuardarProveedor = document.getElementById("btnGuardarProveedor");

    if (btnGuardarProveedor) {
      btnGuardarProveedor.disabled = true;
      btnGuardarProveedor.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...`;
    }

    registrarEntidad({
      formId: "formRegistrarProveedor",
      endpoint: "Proveedores/createProveedor",
      campos: camposFormularioProveedor,
      mapeoNombres: {
        proveedorNombre: "nombre",
        proveedorApellido: "apellido",
        proveedorIdentificacion: "identificacion",
        proveedorTelefono: "telefono_principal",
        proveedorCorreo: "correo_electronico",
        proveedorDireccion: "direccion",
        proveedorFechaNacimiento: "fecha_nacimiento",
      },
      onSuccess: (result) => {
        const nombre = document.getElementById("proveedorNombre").value;
        const apellido = document.getElementById("proveedorApellido").value;
        const identificacion = document.getElementById(
          "proveedorIdentificacion"
        ).value;

        Swal.fire("¡Éxito!", result.message, "success").then(() => {
          cerrarModalRegistrarProveedor();
          hiddenIdProveedorModal.value = result.proveedor_id;
          divInfoProveedorModal.innerHTML = `Sel: <strong>${nombre} ${apellido}</strong> (ID: ${identificacion})`;
          divInfoProveedorModal.classList.remove("hidden");
          inputCriterioProveedorModal.value = `${nombre} ${apellido} (${identificacion})`;

          if (typeof recargarTablaProveedores === "function") {
            recargarTablaProveedores();
          }
        });
      },
      onError: (result) => {
        Swal.fire(
          "Error",
          result.message || "No se pudo registrar el proveedor.",
          "error"
        );
      },
    }).finally(() => {
      if (btnGuardarProveedor) {
        btnGuardarProveedor.disabled = false;
        btnGuardarProveedor.innerHTML = `<i class="fas fa-save mr-2"></i> Guardar Proveedor`;
      }
    });
  }
  const btnCerrarModalVer = document.getElementById("btnCerrarModalVer");
  const btnCerrarModalVer2 = document.getElementById("btnCerrarModalVer2");

  if (btnCerrarModalVer) {
    btnCerrarModalVer.addEventListener("click", function () {
      cerrarModal("modalVerCompra");
    });
  }

  if (btnCerrarModalVer2) {
    btnCerrarModalVer2.addEventListener("click", function () {
      cerrarModal("modalVerCompra");
    });
  }

  const btnCerrarModalEditarCompra = document.getElementById(
    "btnCerrarModalEditarCompra"
  );
  const btnCancelarCompraActualizar = document.getElementById(
    "btnCancelarCompraActualizar"
  );
  const modalEditarCompra = document.getElementById("modalEditarCompra");
  const formEditarCompraModal = document.getElementById(
    "formEditarCompraModal"
  );
  const fechaActualizar = document.getElementById("fechaActualizar");
  const selectMonedaGeneralActualizar = document.getElementById(
    "idmoneda_general_compra_actualizar"
  );
  const inputCriterioProveedorActualizar = document.getElementById(
    "inputCriterioProveedorActualizar"
  );
  const btnBuscarProveedorActualizar = document.getElementById(
    "btnBuscarProveedorActualizar"
  );
  const listaResultadosProveedorActualizar = document.getElementById(
    "listaResultadosProveedorActualizar"
  );
  const hiddenIdProveedorActualizar = document.getElementById(
    "idproveedor_seleccionado_actualizar"
  );
  const divInfoProveedorActualizar = document.getElementById(
    "proveedor_seleccionado_info_actualizar"
  );
  const selectProductoAgregarActualizar = document.getElementById(
    "select_producto_agregar_actualizar"
  );
  const btnAgregarProductoDetalleActualizar = document.getElementById(
    "btnAgregarProductoDetalleActualizar"
  );
  const cuerpoTablaDetalleCompraActualizar = document.getElementById(
    "cuerpoTablaDetalleCompraActualizar"
  );
  const totalGeneralDisplayActualizar = document.getElementById(
    "total_general_display_actualizar"
  );
  const totalGeneralInputActualizar = document.getElementById(
    "total_general_input_actualizar"
  );
  const mensajeErrorFormCompraActualizar = document.getElementById(
    "mensajeErrorFormCompraActualizar"
  );
  const btnActualizarCompraModal = document.getElementById(
    "btnActualizarCompraModal"
  );

  // Event listeners para cerrar modal
  if (btnCerrarModalEditarCompra) {
    btnCerrarModalEditarCompra.addEventListener("click", function () {
      cerrarModalEditarCompra();
    });
  }

  if (btnCancelarCompraActualizar) {
    btnCancelarCompraActualizar.addEventListener("click", function () {
      cerrarModalEditarCompra();
    });
  }

  // Cerrar modal al hacer click fuera
  if (modalEditarCompra) {
    modalEditarCompra.addEventListener("click", (e) => {
      if (e.target === modalEditarCompra) {
        cerrarModalEditarCompra();
      }
    });
  }

  // Event listener para cambio de fecha
  if (fechaActualizar) {
    fechaActualizar.addEventListener("change", function () {
      fechaActualCompraActualizar = this.value;
      if (fechaActualCompraActualizar) {
        cargarTasasPorFechaActualizar(fechaActualCompraActualizar);
      }
    });
  }

  // Event listener para cambio de moneda
  if (selectMonedaGeneralActualizar) {
    selectMonedaGeneralActualizar.addEventListener(
      "change",
      calcularTotalesGeneralesActualizar
    );
  }

  // Event listener para buscar proveedor
  if (btnBuscarProveedorActualizar && inputCriterioProveedorActualizar) {
    btnBuscarProveedorActualizar.addEventListener("click", async function () {
      const termino = inputCriterioProveedorActualizar.value.trim();
      if (termino.length < 2) {
        Swal.fire(
          "Atención",
          "Ingrese al menos 2 caracteres para buscar.",
          "warning"
        );
        return;
      }

      listaResultadosProveedorActualizar.innerHTML =
        '<div class="p-2 text-xs text-gray-500">Buscando...</div>';
      listaResultadosProveedorActualizar.classList.remove("hidden");

      try {
        const response = await fetch(
          `Compras/buscarProveedores?term=${encodeURIComponent(termino)}`
        );
        if (!response.ok) {
          throw new Error("Error en la respuesta del servidor");
        }
        const proveedores = await response.json();

        listaResultadosProveedorActualizar.innerHTML = "";
        if (proveedores && proveedores.length > 0) {
          proveedores.forEach((prov) => {
            const itemDiv = document.createElement("div");
            itemDiv.classList.add(
              "p-2",
              "text-xs",
              "hover:bg-gray-100",
              "cursor-pointer"
            );
            itemDiv.textContent = `${prov.nombre || ""} ${
              prov.apellido || ""
            } (${prov.identificacion || ""})`.trim();
            itemDiv.dataset.idproveedor = prov.idproveedor;
            itemDiv.dataset.nombre = `${prov.nombre || ""} ${
              prov.apellido || ""
            }`.trim();
            itemDiv.dataset.identificacion = prov.identificacion || "";

            itemDiv.addEventListener("click", function () {
              hiddenIdProveedorActualizar.value = this.dataset.idproveedor;
              divInfoProveedorActualizar.innerHTML = `Sel: <strong>${this.dataset.nombre}</strong> (ID: ${this.dataset.identificacion})`;
              divInfoProveedorActualizar.classList.remove("hidden");
              inputCriterioProveedorActualizar.value = this.textContent;
              listaResultadosProveedorActualizar.classList.add("hidden");
              listaResultadosProveedorActualizar.innerHTML = "";
            });
            listaResultadosProveedorActualizar.appendChild(itemDiv);
          });
        } else {
          listaResultadosProveedorActualizar.innerHTML =
            '<div class="p-2 text-xs text-gray-500">No se encontraron proveedores.</div>';
        }
      } catch (error) {
        console.error("Error al buscar proveedores:", error);
        listaResultadosProveedorActualizar.innerHTML =
          '<div class="p-2 text-xs text-red-500">Error al buscar. Intente de nuevo.</div>';
      }
    });
  }

  // Event listener para agregar producto
  if (btnAgregarProductoDetalleActualizar && selectProductoAgregarActualizar) {
    btnAgregarProductoDetalleActualizar.addEventListener("click", function () {
      const selectedOption =
        selectProductoAgregarActualizar.options[
          selectProductoAgregarActualizar.selectedIndex
        ];
      if (!selectedOption.value) {
        Swal.fire("Atención", "Seleccione un producto.", "warning");
        return;
      }

      const idproducto = selectedOption.value;
      if (
        detalleCompraItemsActualizar.find(
          (item) => item.idproducto === idproducto
        )
      ) {
        Swal.fire("Atención", "Este producto ya ha sido agregado.", "warning");
        return;
      }

      const monedaGeneralSeleccionada =
        selectMonedaGeneralActualizar.options[
          selectMonedaGeneralActualizar.selectedIndex
        ];
      const simboloMonedaGeneral = monedaGeneralSeleccionada
        ? monedaGeneralSeleccionada.dataset.simbolo
        : "$";

      const item = {
        idproducto: idproducto,
        nombre: selectedOption.dataset.nombre,
        idcategoria: parseInt(selectedOption.dataset.idcategoria),
        precio_unitario: parseFloat(selectedOption.dataset.precio) || 0,
        idmoneda_item:
          selectedOption.dataset.idmoneda ||
          selectMonedaGeneralActualizar.value,
        simbolo_moneda_item:
          selectedOption.dataset.monedaSimbolo || simboloMonedaGeneral,
        no_usa_vehiculo: false,
        peso_vehiculo: 0,
        peso_bruto: 0,
        peso_neto_directo: 0,
        cantidad_unidad: 1,
        descuento: 0,
        moneda: selectedOption.dataset.moneda,
      };

      detalleCompraItemsActualizar.push(item);
      renderizarTablaDetalleActualizar();
      selectProductoAgregarActualizar.value = "";
    });
  }

  // Event listener para actualizar compra
  if (btnActualizarCompraModal) {
    btnActualizarCompraModal.addEventListener("click", async function () {
      if (
        !validarCamposVacios(
          camposFormularioActualizarCompra,
          "formEditarCompraModal"
        )
      )
        return;
      mensajeErrorFormCompraActualizar.textContent = "";

      if (detalleCompraItemsActualizar.length === 0) {
        mensajeErrorFormCompraActualizar.textContent =
          "Debe agregar al menos un producto al detalle.";
        return;
      }

      if (!selectMonedaGeneralActualizar.value) {
        mensajeErrorFormCompraActualizar.textContent =
          "Debe seleccionar una moneda general para la compra.";
        return;
      }

      for (const item of detalleCompraItemsActualizar) {
        const precio = parseFloat(item.precio_unitario) || 0;
        let cantidadValida = false;
        if (item.idcategoria === 2) {
          cantidadValida = calcularPesoNetoItemActualizar(item) > 0;
        } else {
          cantidadValida = (parseFloat(item.cantidad_unidad) || 0) > 0;
        }
        if (precio <= 0 || !cantidadValida) {
          mensajeErrorFormCompraActualizar.textContent = `El producto "${item.nombre}" tiene precio o cantidad/peso inválido.`;
          return;
        }
      }

      const formData = new FormData(formEditarCompraModal);
      const productosDetalle = detalleCompraItemsActualizar.map((item) => ({
        idproducto: item.idproducto,
        nombre_producto: item.nombre,
        cantidad:
          item.idcategoria === 2
            ? calcularPesoNetoItemActualizar(item)
            : item.cantidad_unidad,
        precio_unitario_compra: item.precio_unitario,
        idmoneda_detalle: item.idmoneda_item,
        descuento: item.descuento || 0,
        moneda: item.moneda,
        subtotal_original_linea: item.subtotal_original_linea || 0,
        monto_descuento_linea: item.monto_descuento_linea || 0,
        subtotal_linea: item.subtotal_linea,
        peso_vehiculo:
          item.idcategoria === 2 && !item.no_usa_vehiculo
            ? item.peso_vehiculo
            : null,
        peso_bruto:
          item.idcategoria === 2 && !item.no_usa_vehiculo
            ? item.peso_bruto
            : null,
        peso_neto:
          item.idcategoria === 2
            ? item.no_usa_vehiculo
              ? item.peso_neto_directo
              : calcularPesoNetoItemActualizar(item)
            : null,
      }));

      formData.append("productos_detalle", JSON.stringify(productosDetalle));
      formData.set("total_general_input", totalGeneralInputActualizar.value);

      btnActualizarCompraModal.disabled = true;
      btnActualizarCompraModal.textContent = "Actualizando...";

      try {
        const response = await fetch(`Compras/updateCompra`, {
          method: "POST",
          body: formData,
        });
        const data = await response.json();
        Swal.fire({
          title: data.status ? "¡Éxito!" : "Error",
          text: data.message,
          icon: data.status ? "success" : "error",
        });
        if (data.status) {
          cerrarModalEditarCompra();
          $("#TablaCompras").DataTable().ajax.reload();
        } else {
          mensajeErrorFormCompraActualizar.textContent =
            data.message || "Error al actualizar.";
        }
      } catch (error) {
        console.error("Error al actualizar compra:", error);
        Swal.fire(
          "Error",
          "Ocurrió un error de conexión al actualizar.",
          "error"
        );
        mensajeErrorFormCompraActualizar.textContent =
          "Ocurrió un error de conexión al actualizar.";
      } finally {
        btnActualizarCompraModal.disabled = false;
        btnActualizarCompraModal.textContent = "Actualizar Compra";
      }
    });
  }
});

// Ver compra
function verCompra(idCompra) {
  fetch(`Compras/getCompraById/${idCompra}`, {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status && result.data) {
        const compra = result.data.compra;
        const detalles = result.data.detalles;

        let totalEuros = 0;
        let totalDolares = 0;

        const montoDescuentoBolivares = parseFloat(
          compra.monto_descuento_general || 0
        );
        const montoTotalBolivares = parseFloat(compra.total_general || 0);
        const subtotalGeneralBolivares = parseFloat(
          compra.subtotal_general || 0
        );

        if (detalles && detalles.length > 0) {
          detalles.forEach((detalle) => {
            const subtotalLinea = parseFloat(detalle.subtotal_linea || 0);
            if (isNaN(subtotalLinea)) {
              console.warn(
                "Subtotal de línea no es un número:",
                detalle.subtotal_linea
              );
              return;
            }
            switch (detalle.codigo_moneda) {
              case "EUR":
                totalEuros += subtotalLinea;
                break;
              case "USD":
                totalDolares += subtotalLinea;
                break;
            }
          });
        }

        mostrarModalVerCompra(
          compra,
          detalles,
          totalEuros,
          totalDolares,
          montoDescuentoBolivares,
          montoTotalBolivares,
          subtotalGeneralBolivares
        );
      } else {
        Swal.fire(
          "Error",
          result.message || "No se pudieron cargar los datos.",
          "error"
        );
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    });
}
// Mostrar modal de compra
function mostrarModalVerCompra(
  compra,
  detalles,
  totalEuros,
  totalDolares,
  montoDescuentoBolivares,
  montoTotalBolivares,
  subtotalGeneralBolivares
) {
  document.getElementById("verNroCompra").textContent =
    compra.nro_compra || "N/A";
  document.getElementById("verFecha").textContent = compra.fecha
    ? new Date(compra.fecha + "T00:00:00").toLocaleDateString("es-ES")
    : "N/A";
  document.getElementById("verProveedor").textContent =
    compra.proveedor_nombre || "N/A";
  document.getElementById("verEstado").textContent =
    compra.estatus_compra || "N/A";
  document.getElementById("verObservaciones").textContent =
    compra.observaciones_compra || "N/A";

  const contTotalEUR = document.getElementById("contenedorTotalProductosEUR");
  const elTotalProductosEUR = document.getElementById("verTotalProductosEUR");
  if (totalEuros > 0 && elTotalProductosEUR && contTotalEUR) {
    elTotalProductosEUR.textContent =
      "€ " +
      totalEuros.toLocaleString("es-ES", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });
    contTotalEUR.style.display = "block";
  } else if (contTotalEUR) {
    contTotalEUR.style.display = "none";
  }
  const contTotalUSD = document.getElementById("contenedorTotalProductosUSD");
  const elTotalProductosUSD = document.getElementById("verTotalProductosUSD");
  if (totalDolares > 0 && elTotalProductosUSD && contTotalUSD) {
    elTotalProductosUSD.textContent =
      "$ " +
      totalDolares.toLocaleString("es-ES", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });
    contTotalUSD.style.display = "block";
  } else if (contTotalUSD) {
    contTotalUSD.style.display = "none";
  }

  const contTasaEUR = document.getElementById("contenedorTasaEURVES");
  const elTasaEURVES = document.getElementById("verTasaEURVES");
  if (totalEuros > 0 && compra.tasa_eur_ves && elTasaEURVES && contTasaEUR) {
    elTasaEURVES.textContent = parseFloat(compra.tasa_eur_ves).toLocaleString(
      "es-ES",
      { minimumFractionDigits: 2, maximumFractionDigits: 4 }
    );
    contTasaEUR.style.display = "block";
  } else if (contTasaEUR) {
    contTasaEUR.style.display = "none";
  }

  const contTasaUSD = document.getElementById("contenedorTasaUSDVES");
  const elTasaUSDVES = document.getElementById("verTasaUSDVES");
  if (totalDolares > 0 && compra.tasa_usd_ves && elTasaUSDVES && contTasaUSD) {
    elTasaUSDVES.textContent = parseFloat(compra.tasa_usd_ves).toLocaleString(
      "es-ES",
      { minimumFractionDigits: 2, maximumFractionDigits: 4 }
    );
    contTasaUSD.style.display = "block";
  } else if (contTasaUSD) {
    contTasaUSD.style.display = "none";
  }

  const elSubtotalGeneralVES = document.getElementById("verSubtotalGeneralVES");
  if (elSubtotalGeneralVES) {
    elSubtotalGeneralVES.textContent =
      "Bs. " +
      subtotalGeneralBolivares.toLocaleString("es-ES", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });
  }

  const elMontoDescuentoVES = document.getElementById(
    "verMontoDescuentoGeneralVES"
  );
  if (elMontoDescuentoVES) {
    elMontoDescuentoVES.textContent =
      "Bs. " +
      montoDescuentoBolivares.toLocaleString("es-ES", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });
  }

  document.getElementById("verTotalGeneral").textContent =
    "Bs. " +
    montoTotalBolivares.toLocaleString("es-ES", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });

  const tbody = document.getElementById("verDetalleProductos");
  tbody.innerHTML = "";

  if (detalles && detalles.length > 0) {
    detalles.forEach((detalle) => {
      const tr = document.createElement("tr");
      const cantidad = parseFloat(detalle.cantidad || 0);
      const precioUnitario = parseFloat(detalle.precio_unitario_compra || 0);
      const subtotalLinea = parseFloat(detalle.subtotal_linea || 0);
      const descuentoValor = detalle.descuento
        ? parseFloat(detalle.descuento)
        : 0;

      tr.innerHTML = `
        <td class="px-4 py-2">${
          detalle.nombre_producto || detalle.producto_nombre || "N/A"
        }</td>
        <td class="px-4 py-2 text-right">${cantidad.toLocaleString("es-ES", {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        })}</td>
        <td class="px-4 py-2 text-right">${
          detalle.codigo_moneda || ""
        } ${precioUnitario.toLocaleString("es-ES", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })}</td>
        <td class="px-4 py-2 text-right">${descuentoValor.toLocaleString(
          "es-ES",
          { minimumFractionDigits: 0, maximumFractionDigits: 2 }
        )} %</td>
        <td class="px-4 py-2 text-right">${
          detalle.codigo_moneda || ""
        } ${subtotalLinea.toLocaleString("es-ES", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })}</td>
      `;
      tbody.appendChild(tr);
    });
  } else {
    tbody.innerHTML =
      '<tr><td colspan="5" class="px-4 py-2 text-center text-gray-500">No hay detalles disponibles</td></tr>';
  }

  abrirModal("modalVerCompra");
}
// Eliminar compra
function eliminarCompra(idCompra, nroCompra) {
  Swal.fire({
    title: "¿Estás seguro?",
    text: `¿Deseas eliminar la compra ${nroCompra}? Esta acción no se puede deshacer.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      const dataParaEnviar = {
        idcompra: idCompra,
      };

      fetch("Compras/deleteCompra", {
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
            if (typeof tablaCompras !== "undefined" && tablaCompras.ajax) {
              tablaCompras.ajax.reload(null, false);
            }
          } else {
            Swal.fire(
              "Error",
              result.message || "No se pudo eliminar la compra.",
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
// Cambiar estado de compra
function cambiarEstadoCompra(idCompra, nuevoEstado) {
  const mensajesEstado = {
    POR_AUTORIZAR: "enviar a autorización",
    AUTORIZADA: "autorizar",
    POR_PAGAR: "marcar para pago",
    PAGADA: "marcar como pagada",
    BORRADOR: "devolver a borrador",
  };

  const mensaje = mensajesEstado[nuevoEstado] || "cambiar estado de";

  Swal.fire({
    title: "¿Confirmar acción?",
    text: `¿Deseas ${mensaje} esta compra?`,
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Sí, confirmar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      const dataParaEnviar = {
        idcompra: idCompra,
        nuevo_estado: nuevoEstado,
      };

      fetch("Compras/cambiarEstadoCompra", {
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
            Swal.fire("¡Éxito!", result.message, "success");
            if (typeof tablaCompras !== "undefined" && tablaCompras.ajax) {
              tablaCompras.ajax.reload(null, false);
            }
          } else {
            Swal.fire(
              "Error",
              result.message || "No se pudo cambiar el estado de la compra.",
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

// Función para abrir modal de editar
async function editarCompra(idCompra) {
  try {
    const response = await fetch(`Compras/getCompraParaEditar/${idCompra}`);
    const result = await response.json();

    if (result.status && result.data) {
      const { compra, detalles } = result.data;
      await abrirModalEditarCompra(compra, detalles);
    } else {
      Swal.fire(
        "Error",
        result.message || "No se pudo cargar la compra para editar.",
        "error"
      );
    }
  } catch (error) {
    console.error("Error al cargar compra para editar:", error);
    Swal.fire("Error", "Error de conexión al cargar la compra.", "error");
  }
}

// Función para abrir modal de editar con datos
async function abrirModalEditarCompra(compra, detalles) {
  // Resetear formulario
  const formEditarCompraModal = document.getElementById(
    "formEditarCompraModal"
  );
  const modalEditarCompra = document.getElementById("modalEditarCompra");
  const fechaActualizar = document.getElementById("fechaActualizar");
  const selectMonedaGeneralActualizar = document.getElementById(
    "idmoneda_general_compra_actualizar"
  );
  const hiddenIdProveedorActualizar = document.getElementById(
    "idproveedor_seleccionado_actualizar"
  );
  const divInfoProveedorActualizar = document.getElementById(
    "proveedor_seleccionado_info_actualizar"
  );
  const inputCriterioProveedorActualizar = document.getElementById(
    "inputCriterioProveedorActualizar"
  );
  const mensajeErrorFormCompraActualizar = document.getElementById(
    "mensajeErrorFormCompraActualizar"
  );

  formEditarCompraModal.reset();
  detalleCompraItemsActualizar = [];
  mensajeErrorFormCompraActualizar.textContent = "";

  // Cargar datos básicos
  document.getElementById("idcompra_editar").value = compra.idcompra;
  fechaActualizar.value = compra.fecha;
  fechaActualCompraActualizar = compra.fecha;
  document.getElementById("observacionesActualizar").value =
    compra.observaciones_compra || "";

  // Cargar tasas por fecha
  await cargarTasasPorFechaActualizar(compra.fecha);

  // Cargar monedas y seleccionar la moneda general
  await cargarMonedasParaActualizar();
  selectMonedaGeneralActualizar.value = compra.idmoneda_general;

  // Cargar productos
  await cargarProductosParaActualizar();

  // Configurar proveedor
  hiddenIdProveedorActualizar.value = compra.idproveedor;
  divInfoProveedorActualizar.innerHTML = `Sel: <strong>${compra.proveedor_nombre}</strong>`;
  divInfoProveedorActualizar.classList.remove("hidden");
  inputCriterioProveedorActualizar.value = compra.proveedor_nombre;

  // Cargar detalles
  if (detalles && detalles.length > 0) {
    detalles.forEach((detalle) => {
      const item = {
        idproducto: detalle.idproducto,
        nombre:
          detalle.producto_nombre || detalle.descripcion_temporal_producto,
        idcategoria: parseInt(detalle.idcategoria),
        precio_unitario: parseFloat(detalle.precio_unitario_compra),
        idmoneda_item: detalle.codigo_moneda,
        simbolo_moneda_item: detalle.codigo_moneda,
        no_usa_vehiculo:
          detalle.peso_vehiculo === null && detalle.peso_bruto === null,
        peso_vehiculo: parseFloat(detalle.peso_vehiculo) || 0,
        peso_bruto: parseFloat(detalle.peso_bruto) || 0,
        peso_neto_directo:
          detalle.peso_vehiculo === null && detalle.peso_bruto === null
            ? parseFloat(detalle.peso_neto) || 0
            : 0,
        cantidad_unidad:
          parseInt(detalle.idcategoria) === 2
            ? 0
            : parseFloat(detalle.cantidad),
        descuento: parseFloat(detalle.descuento) || 0,
        moneda: detalle.idmoneda_detalle,
        subtotal_linea: parseFloat(detalle.subtotal_linea),
      };

      detalleCompraItemsActualizar.push(item);
    });
  }

  renderizarTablaDetalleActualizar();
  inicializarValidaciones(
    camposFormularioActualizarCompra,
    "formEditarCompraModal"
  );

  // Mostrar modal
  modalEditarCompra.classList.remove("opacity-0", "pointer-events-none");
  document.body.classList.add("overflow-hidden");
}

// Función para cerrar modal de editar
function cerrarModalEditarCompra() {
  const modalEditarCompra = document.getElementById("modalEditarCompra");
  const formEditarCompraModal = document.getElementById(
    "formEditarCompraModal"
  );

  modalEditarCompra.classList.add("opacity-0", "pointer-events-none");
  document.body.classList.remove("overflow-hidden");
  limpiarValidaciones(
    camposFormularioActualizarCompra,
    "formEditarCompraModal"
  );
  formEditarCompraModal.reset();
  detalleCompraItemsActualizar = [];
}

// Funciones auxiliares para el modal de actualizar (similares a las del modal de registro)
async function cargarTasasPorFechaActualizar(fecha) {
  const divTasa = document.getElementById("tasaDelDiaInfoActualizar");
  divTasa.textContent = "Cargando tasas del día...";
  try {
    const response = await fetch(
      `Compras/getTasasMonedasPorFecha?fecha=${encodeURIComponent(fecha)}`
    );
    const data = await response.json();
    if (data.status && data.tasas && Object.keys(data.tasas).length > 0) {
      tasasMonedasActualizar = data.tasas;
      let texto = `Tasa del día (${fecha.split("-").reverse().join("/")})`;
      let tasasArr = [];
      for (const [moneda, tasa] of Object.entries(tasasMonedasActualizar)) {
        tasasArr.push(
          `1 ${moneda} = ${Number(tasa).toLocaleString("es-VE", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 4,
          })} Bs.`
        );
      }
      texto += ": " + tasasArr.join(" | ");
      divTasa.textContent = texto;
      calcularTotalesGeneralesActualizar();
    } else {
      divTasa.textContent = "No hay tasas registradas para esta fecha.";
      tasasMonedasActualizar = {};
      calcularTotalesGeneralesActualizar();
    }
  } catch (e) {
    divTasa.textContent = "Error al cargar tasas del día.";
    tasasMonedasActualizar = {};
    calcularTotalesGeneralesActualizar();
  }
}

async function cargarMonedasParaActualizar() {
  const selectMonedaGeneralActualizar = document.getElementById(
    "idmoneda_general_compra_actualizar"
  );
  selectMonedaGeneralActualizar.innerHTML =
    '<option value="">Cargando...</option>';
  try {
    const response = await fetch("Compras/getListaMonedasParaFormulario");
    if (!response.ok) throw new Error("Error en respuesta de monedas");
    const monedas = await response.json();
    selectMonedaGeneralActualizar.innerHTML =
      '<option value="">Seleccione Moneda</option>';
    monedas.forEach((moneda) => {
      let simbolo = "";
      if (moneda.codigo_moneda === "USD") simbolo = "$";
      else if (moneda.codigo_moneda === "EUR") simbolo = "€";
      else if (moneda.codigo_moneda === "VES") simbolo = "Bs.";
      const option = document.createElement("option");
      option.value = moneda.idmoneda;
      option.textContent = `${moneda.codigo_moneda} (${simbolo})`;
      selectMonedaGeneralActualizar.appendChild(option);
    });
  } catch (error) {
    console.error("Error al cargar monedas:", error);
    selectMonedaGeneralActualizar.innerHTML =
      '<option value="">Error al cargar</option>';
  }
}

async function cargarProductosParaActualizar() {
  const selectProductoAgregarActualizar = document.getElementById(
    "select_producto_agregar_actualizar"
  );
  selectProductoAgregarActualizar.innerHTML =
    '<option value="">Cargando...</option>';
  try {
    const response = await fetch("Compras/getListaProductosParaFormulario");
    if (!response.ok) throw new Error("Error en respuesta de productos");
    const productos = await response.json();
    selectProductoAgregarActualizar.innerHTML =
      '<option value="">Seleccione producto...</option>';
    productos.forEach((producto) => {
      const option = document.createElement("option");
      option.value = producto.idproducto;
      option.dataset.idcategoria = producto.idcategoria;
      option.dataset.nombre = producto.nombre_producto;
      option.dataset.precio = producto.precio_referencia_compra || "0.00";
      option.dataset.idmoneda = producto.codigo_moneda || "";
      option.dataset.moneda = producto.idmoneda_producto || "";
      option.textContent = `${producto.nombre_producto} (${producto.nombre_categoria})`;
      selectProductoAgregarActualizar.appendChild(option);
    });
  } catch (error) {
    console.error("Error al cargar productos:", error);
    selectProductoAgregarActualizar.innerHTML =
      '<option value="">Error al cargar</option>';
  }
}

function calcularPesoNetoItemActualizar(item) {
  if (item.idcategoria === 2) {
    if (item.no_usa_vehiculo) {
      return parseFloat(item.peso_neto_directo) || 0;
    } else {
      const bruto = parseFloat(item.peso_bruto) || 0;
      const vehiculo = parseFloat(item.peso_vehiculo) || 0;
      return Math.max(0, bruto - vehiculo);
    }
  }
  return 0;
}

function calcularSubtotalLineaItemActualizar(item) {
  const precioUnitario = parseFloat(item.precio_unitario) || 0;
  let cantidadBase = 0;
  if (item.idcategoria === 2) {
    cantidadBase = calcularPesoNetoItemActualizar(item) || 0;
  } else {
    cantidadBase = parseFloat(item.cantidad_unidad) || 0;
  }
  const subtotalAntesDescuento = cantidadBase * precioUnitario;
  const porcentajeDescuento = parseFloat(item.descuento) || 0;
  let montoDescuento = 0;
  let subtotalConDescuento = subtotalAntesDescuento;
  if (porcentajeDescuento > 0 && porcentajeDescuento <= 100) {
    montoDescuento = subtotalAntesDescuento * (porcentajeDescuento / 100);
    subtotalConDescuento = subtotalAntesDescuento - montoDescuento;
  }
  item.subtotal_original_linea = subtotalAntesDescuento;
  item.monto_descuento_linea = montoDescuento;
  item.subtotal_linea = subtotalConDescuento;
  item.subtotal_linea_bs = convertirAMonedaBaseActualizar(
    subtotalConDescuento,
    item.idmoneda_item
  );
  return item.subtotal_linea;
}

function convertirAMonedaBaseActualizar(monto, idmoneda) {
  if (idmoneda == 3) return monto;
  const tasa = tasasMonedasActualizar[idmoneda] || 1;
  return monto * tasa;
}

function calcularTotalesGeneralesActualizar() {
  let subtotalGeneralBs = 0;
  detalleCompraItemsActualizar.forEach((item) => {
    subtotalGeneralBs += parseFloat(item.subtotal_linea_bs) || 0;
  });
  const totalGeneral = subtotalGeneralBs;
  const totalGeneralDisplayActualizar = document.getElementById(
    "total_general_display_actualizar"
  );
  const totalGeneralInputActualizar = document.getElementById(
    "total_general_input_actualizar"
  );
  totalGeneralDisplayActualizar.value = `Bs. ${totalGeneral.toLocaleString(
    "es-VE",
    { minimumFractionDigits: 2, maximumFractionDigits: 2 }
  )}`;
  totalGeneralInputActualizar.value = totalGeneral.toFixed(2);
}

function renderizarTablaDetalleActualizar() {
  const cuerpoTablaDetalleCompraActualizar = document.getElementById(
    "cuerpoTablaDetalleCompraActualizar"
  );
  cuerpoTablaDetalleCompraActualizar.innerHTML = "";
  detalleCompraItemsActualizar.forEach((item, index) => {
    const tr = document.createElement("tr");
    tr.classList.add("border-b", "hover:bg-gray-50");
    tr.dataset.index = index;

    let infoEspecificaHtml = "";
    if (item.idcategoria === 2) {
      infoEspecificaHtml = `
      <div class="space-y-1">
        <div>
          <label class="flex items-center text-xs">
            <input type="checkbox" class="form-checkbox h-3 w-3 mr-1 no_usa_vehiculo_cb_actualizar" ${
              item.no_usa_vehiculo ? "checked" : ""
            }> No usa vehículo
          </label>
        </div>
        <div class="campos_peso_vehiculo_actualizar ${
          item.no_usa_vehiculo ? "hidden" : ""
        }">
          P.Bru: 
          <input type="number" step="0.01" class="w-20 border rounded-md px-2 py-1 text-s focus:outline-none focus:ring-2 focus:ring-blue-500 peso_bruto_actualizar" value="${
            item.peso_bruto || ""
          }" placeholder="0.00">
          <button type="button" class="btnUltimoPesoRomanaBrutoActualizar bg-blue-100 text-blue-700 px-2 py-1 rounded ml-1" title="Traer último peso de romana"><i class="fas fa-balance-scale"></i></button>
          P.Veh: 
          <input type="number" step="0.01" class="w-20 border rounded-md px-2 py-1 text-s focus:outline-none focus:ring-2 focus:ring-blue-500 peso_vehiculo_actualizar" value="${
            item.peso_vehiculo || ""
          }" placeholder="0.00">
          <button type="button" class="btnUltimoPesoRomanaVehiculoActualizar bg-blue-100 text-blue-700 px-2 py-1 rounded ml-1" title="Traer último peso de romana"><i class="fas fa-balance-scale"></i></button>
          Descuento %: 
          <input type="number" step="0.01" min="0" max="100" class="w-20 border rounded-md px-2 py-1 text-s focus:outline-none focus:ring-2 focus:ring-blue-500 descuento_actualizar" value="${
            item.descuento || ""
          }" placeholder="0.00">
        </div>
        <div class="campo_peso_neto_directo_actualizar ${
          !item.no_usa_vehiculo ? "hidden" : ""
        }">
          P.Neto: <input type="number" step="0.01" class="w-20 border rounded-md py-1 text-s focus:outline-none focus:ring-2 focus:ring-blue-500 peso_neto_directo_actualizar" value="${
            item.peso_neto_directo || ""
          }" placeholder="0.00">
          <button type="button" class="btnUltimoPesoRomanaBrutoActualizar bg-blue-100 text-blue-700 px-2 py-1 rounded ml-1" title="Traer último peso de romana"><i class="fas fa-balance-scale"></i></button>
          Descuento %: 
          <input type="number" step="0.01" min="0" max="100" class="w-20 border rounded-md px-2 py-1 text-s focus:outline-none focus:ring-2 focus:ring-blue-500 descuento_actualizar" value="${
            item.descuento || ""
          }" placeholder="0.00">
        </div>
        Neto Calc: <strong class="peso_neto_calculado_display_actualizar">${calcularPesoNetoItemActualizar(
          item
        ).toFixed(2)}</strong>
      </div>`;
    } else {
      infoEspecificaHtml = `
        <div>
          Cant: <input type="number" step="0.01" class="w-20 border rounded-md px-1 py-1 text-s focus:outline-none focus:ring-2 focus:ring-blue-500 cantidad_unidad_actualizar" value="${
            item.cantidad_unidad || "1"
          }" placeholder="1">
        </div>`;
    }

    tr.innerHTML = `
      <td class="py-1 px-1 text-xs">${item.nombre}</td>
      <td class="py-1 px-1 text-xs">${infoEspecificaHtml}</td>
      <td class="py-1 px-1 text-xs">
          ${
            item.idmoneda_item
          } <input type="number" step="0.01" class="w-20 border rounded-md px-1 py-1 text-s focus:outline-none focus:ring-2 focus:ring-blue-500 precio_unitario_item_actualizar" value="${item.precio_unitario.toFixed(
      2
    )}" placeholder="0.00">
      </td>
      <td class="py-1 px-1 text-xs subtotal_linea_display_actualizar">${
        item.idmoneda_item
      } ${calcularSubtotalLineaItemActualizar(item).toFixed(2)}</td>
      <td class="py-1 px-1 text-center"><button type="button" class="fa-solid fa-x text-red-500 hover:text-red-700 btnEliminarItemDetalleActualizar text-xs"></button></td>
    `;
    cuerpoTablaDetalleCompraActualizar.appendChild(tr);
  });
  addEventListenersToDetalleInputsActualizar();
  calcularTotalesGeneralesActualizar();
}

function addEventListenersToDetalleInputsActualizar() {
  document
    .querySelectorAll("#cuerpoTablaDetalleCompraActualizar tr")
    .forEach((row) => {
      const index = parseInt(row.dataset.index);
      if (isNaN(index) || index >= detalleCompraItemsActualizar.length) return;
      const item = detalleCompraItemsActualizar[index];

      // Event listeners para pesos de romana
      const btnUltimoPesoBruto = row.querySelector(
        ".btnUltimoPesoRomanaBrutoActualizar"
      );
      if (btnUltimoPesoBruto) {
        btnUltimoPesoBruto.addEventListener("click", async function () {
          try {
            console.log("Consultando romana...");
            const response = await fetch("Compras/getUltimoPesoRomana");
            if (!response.ok)
              throw new Error(`HTTP error! status: ${response.status}`);
            const data = await response.json();
            if (data.status) {
              if (item.no_usa_vehiculo) {
                item.peso_neto_directo = data.peso;
                row.querySelector(".peso_neto_directo_actualizar").value =
                  data.peso;
              } else {
                item.peso_bruto = data.peso;
                row.querySelector(".peso_bruto_actualizar").value = data.peso;
              }
              actualizarCalculosFilaActualizar(row, item);

              // Guardar el peso en la base de datos
              await fetch("Compras/guardarPesoRomana", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                  peso: data.peso,
                  peso_texto: data.peso + " kg",
                  estado: data.estado || null,
                  variacion: data.variacion || null,
                  promedio: data.promedio || null,
                  observaciones: "Peso registrado desde interfaz de compra",
                  usuario_id: window.ID_USUARIO_ACTUAL || null, // Ajusta según tu sistema
                  fecha: new Date()
                    .toISOString()
                    .slice(0, 19)
                    .replace("T", " "),
                }),
              });
            } else {
              Swal.fire(
                "Atención",
                data.message || "No se pudo obtener el peso.",
                "warning"
              );
            }
          } catch (e) {
            console.error("Error completo:", e);
            Swal.fire(
              "Error",
              "Error al consultar la romana: " + e.message,
              "error"
            );
          }
        });
      }

      const btnUltimoPesoVehiculo = row.querySelector(
        ".btnUltimoPesoRomanaVehiculoActualizar"
      );
      if (btnUltimoPesoVehiculo) {
        btnUltimoPesoVehiculo.addEventListener("click", async function () {
          try {
            const response = await fetch("Compras/getUltimoPesoRomana");
            const data = await response.json();
            if (data.status) {
              item.peso_vehiculo = data.peso;
              row.querySelector(".peso_vehiculo_actualizar").value = data.peso;
              actualizarCalculosFilaActualizar(row, item);

              // Guardar el peso en la base de datos
              await fetch("Compras/guardarPesoRomana", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                  peso: data.peso,
                  peso_texto: data.peso + " kg",
                  estado: data.estado || null,
                  variacion: data.variacion || null,
                  promedio: data.promedio || null,
                  observaciones: "Peso registrado desde interfaz de compra",
                  usuario_id: window.ID_USUARIO_ACTUAL || null, // Ajusta según tu sistema
                  fecha: new Date()
                    .toISOString()
                    .slice(0, 19)
                    .replace("T", " "),
                }),
              });
            } else {
              Swal.fire(
                "Atención",
                data.message || "No se pudo obtener el peso.",
                "warning"
              );
            }
          } catch (e) {
            Swal.fire("Error", "Error al consultar la romana.", "error");
          }
        });
      }

      // Checkbox no usa vehículo
      const cbNoUsaVehiculo = row.querySelector(
        ".no_usa_vehiculo_cb_actualizar"
      );
      if (cbNoUsaVehiculo) {
        cbNoUsaVehiculo.addEventListener("change", function (e) {
          item.no_usa_vehiculo = e.target.checked;
          const camposPesoVehiculo = row.querySelector(
            ".campos_peso_vehiculo_actualizar"
          );
          const campoPesoNetoDirecto = row.querySelector(
            ".campo_peso_neto_directo_actualizar"
          );
          if (e.target.checked) {
            camposPesoVehiculo.classList.add("hidden");
            campoPesoNetoDirecto.classList.remove("hidden");
            item.peso_vehiculo = 0;
            item.peso_bruto = 0;
          } else {
            camposPesoVehiculo.classList.remove("hidden");
            campoPesoNetoDirecto.classList.add("hidden");
            item.peso_neto_directo = 0;
          }
          actualizarCalculosFilaActualizar(row, item);
        });
      }

      row
        .querySelectorAll(
          ".peso_vehiculo_actualizar, .peso_bruto_actualizar, .peso_neto_directo_actualizar, .cantidad_unidad_actualizar, .precio_unitario_item_actualizar, .descuento_actualizar"
        )
        .forEach((input) => {
          input.addEventListener("input", function (e) {
            const fieldName = e.target.classList.contains(
              "peso_vehiculo_actualizar"
            )
              ? "peso_vehiculo"
              : e.target.classList.contains("peso_bruto_actualizar")
              ? "peso_bruto"
              : e.target.classList.contains("peso_neto_directo_actualizar")
              ? "peso_neto_directo"
              : e.target.classList.contains("cantidad_unidad_actualizar")
              ? "cantidad_unidad"
              : e.target.classList.contains("descuento_actualizar")
              ? "descuento"
              : "precio_unitario";

            let valor = parseFloat(e.target.value) || 0;

            if (fieldName === "descuento" && valor > 100) {
              valor = 100;
              e.target.value = 100;
              Swal.fire(
                "Atención",
                "El descuento no puede ser mayor al 100%",
                "warning"
              );
            }

            item[fieldName] = valor;
            actualizarCalculosFilaActualizar(row, item);
          });
        });

      // Botón eliminar item
      row
        .querySelector(".btnEliminarItemDetalleActualizar")
        .addEventListener("click", function () {
          detalleCompraItemsActualizar.splice(index, 1);
          renderizarTablaDetalleActualizar();
        });
    });
}

function actualizarCalculosFilaActualizar(rowElement, item) {
  const pesoNetoDisplay = rowElement.querySelector(
    ".peso_neto_calculado_display_actualizar"
  );
  if (pesoNetoDisplay) {
    pesoNetoDisplay.textContent =
      calcularPesoNetoItemActualizar(item).toFixed(2);
  }
  rowElement.querySelector(
    ".subtotal_linea_display_actualizar"
  ).textContent = `${item.idmoneda_item} ${calcularSubtotalLineaItemActualizar(
    item
  ).toFixed(2)}`;
  calcularTotalesGeneralesActualizar();
}
