import { abrirModal, cerrarModal } from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  validarCamposVacios,
  validarCampo,
  validarSelect,
  limpiarValidaciones,
} from "./validaciones.js";

let tablaCompras;
let detalleCompraItemsModal = [];
let detalleCompraItemsActualizar = [];
let tasasMonedasActualizar = {};
let fechaActualCompraActualizar = null;

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

  $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
    if (settings.nTable.id !== 'TablaCompras') {
      return true;
    }
    var api     = new $.fn.dataTable.Api(settings);
    var rowData = api.row(dataIndex).data();
    return rowData.estatus_compra !== 'inactivo';
  });

  $(document).ready(function () {
    tablaCompras = $("#TablaCompras").DataTable({
      processing: true,
      ajax: {
        url: "Compras/getComprasDataTable",
        type: "GET",
        dataSrc: function (json) {
          if (json && json.data) {
            return json.data;
          } else {
            console.error("Error en respuesta del servidor:", json);
            $("#TablaCompras_processing").hide();
            alert("Error: No se pudieron cargar los datos de compras.");
            return [];
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.error("Error AJAX:", textStatus, errorThrown);
          $("#TablaCompras_processing").hide();
          alert("Error de comunicación al cargar los datos.");
        }
      },
      columns: [
        { data: "nro_compra", title: "Nro. Compra" },
        {
          data: "fecha",
          title: "Fecha",
          render: function (data) {
            return data
              ? new Date(data).toLocaleDateString("es-ES")
              : "N/A";
          }
        },
        { data: "proveedor", title: "Proveedor" },
        {
          data: "total_general",
          title: "Total",
          render: function (data) {
            return data
              ? "Bs. " +
                  parseFloat(data).toLocaleString("es-ES", {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                  })
              : "Bs. 0.00";
          }
        },
        {
          data: "estatus_compra",
          title: "Estado",
          render: function (data) {
            if (!data) return '<i style="color: silver;">N/A</i>';
            var estatusUpper = String(data).toUpperCase();
            var badgeClass   = "bg-gray-100 text-gray-800";
            switch (estatusUpper) {
              case "BORRADOR":
                badgeClass = "bg-yellow-100 text-yellow-800"; break;
              case "POR_AUTORIZAR":
                badgeClass = "bg-blue-100 text-blue-800";   break;
              case "AUTORIZADA":
                badgeClass = "bg-green-100 text-green-800"; break;
              case "POR_PAGAR":
                badgeClass = "bg-orange-100 text-orange-800"; break;
              case "PAGADA":
                badgeClass = "bg-purple-100 text-purple-800"; break;
            }
            return `<span class="${badgeClass} text-xs font-medium me-2 \
  px-2.5 py-0.5 rounded">${data}</span>`;
          }
        },
        {
          data: null,
          title: "Acciones",
          orderable: false,
          searchable: false,
          className: "text-center",
          width: "200px",
          render: function (_, __, row) {
            var nroCompra     = row.nro_compra || "Sin número";
            var estadoActual  = row.estatus_compra || "";
            var botonesEstado = "";

            switch (estadoActual.toUpperCase()) {
              case "BORRADOR":
                botonesEstado = `
                  <button
                    class="cambiar-estado-btn text-blue-500 hover:text-blue-700 p-1"
                    data-idcompra="${row.idcompra}"
                    data-nuevo-estado="POR_AUTORIZAR"
                    title="Enviar a Autorización"
                  >
                    <i class="fas fa-paper-plane fa-lg"></i>
                  </button>`;
                break;
              case "POR_AUTORIZAR":
                botonesEstado = `
                  <button
                    class="cambiar-estado-btn text-green-500 hover:text-green-700 p-1"
                    data-idcompra="${row.idcompra}"
                    data-nuevo-estado="AUTORIZADA"
                    title="Autorizar"
                  >
                    <i class="fas fa-check fa-lg"></i>
                  </button>
                  <button
                    class="cambiar-estado-btn text-yellow-500 hover:text-yellow-700 p-1"
                    data-idcompra="${row.idcompra}"
                    data-nuevo-estado="BORRADOR"
                    title="Devolver a Borrador"
                  >
                    <i class="fas fa-undo fa-lg"></i>
                  </button>`;
                break;
              case "AUTORIZADA":
                botonesEstado = `
                  <button
                    class="cambiar-estado-btn text-orange-500 hover:text-orange-700 p-1"
                    data-idcompra="${row.idcompra}"
                    data-nuevo-estado="POR_PAGAR"
                    title="Marcar para Pago"
                  >
                    <i class="fas fa-credit-card fa-lg"></i>
                  </button>`;
                break;
              case "POR_PAGAR":
                botonesEstado = `
                  <button
                    class="cambiar-estado-btn text-purple-500 hover:text-purple-700 p-1"
                    data-idcompra="${row.idcompra}"
                    data-nuevo-estado="PAGADA"
                    title="Marcar como Pagada"
                  >
                    <i class="fas fa-money-check-alt fa-lg"></i>
                  </button>
                  <button
                    class="cambiar-estado-btn text-orange-500 hover:text-orange-700 p-1"
                    data-idcompra="${row.idcompra}"
                    data-nuevo-estado="AUTORIZADA"
                    title="Devolver a Autorizada"
                  >
                    <i class="fas fa-undo fa-lg"></i>
                  </button>`;
                break;
              case "PAGADA":
                botonesEstado = `
                  <span class="text-green-600 font-semibold text-xs">
                    FINALIZADA
                  </span>`;
                break;
            }

            return `
              <button
                class="ver-compra-btn text-green-600 hover:text-green-800 p-1"
                data-idcompra="${row.idcompra}"
                title="Ver Detalle"
              >
                <i class="fas fa-eye fa-lg"></i>
              </button>
              ${
                estadoActual.toUpperCase() === "BORRADOR"
                  ? `
                <button
                  class="editar-compra-btn text-blue-500 hover:text-blue-700 p-1 ml-2"
                  data-idcompra="${row.idcompra}"
                  title="Editar"
                >
                  <i class="fas fa-edit fa-lg"></i>
                </button>
                <button
                  class="eliminar-compra-btn text-red-500 hover:text-red-700 p-1 ml-2"
                  data-idcompra="${row.idcompra}"
                  data-nro-compra="${nroCompra}"
                  title="Eliminar"
                >
                  <i class="fas fa-trash fa-lg"></i>
                </button>`
                  : ""
              }
              ${botonesEstado}
            `;
          }
        }
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
          previous: "Anterior"
        }
      },
      destroy: true,
      responsive: true,
      pageLength: 10,
      order: [[1, "desc"]]
    });

    // Aplicar filtro inicial
    tablaCompras.draw();

    // Event listeners para botones del DataTable
    $("#TablaCompras tbody").on("click", ".ver-compra-btn", function () {
      const idCompra = $(this).data("idcompra");
      verCompra(idCompra);
    });

    $("#TablaCompras tbody").on("click", ".editar-compra-btn", function () {
      const idCompra = $(this).data("idcompra");
      editarCompra(idCompra);
    });

    $("#TablaCompras tbody").on("click", ".eliminar-compra-btn", function () {
      const idCompra  = $(this).data("idcompra");
      const nroCompra = $(this).data("nro-compra");
      eliminarCompra(idCompra, nroCompra);
    });

    $("#TablaCompras tbody").on("click", ".cambiar-estado-btn", function () {
      const idCompra   = $(this).data("idcompra");
      const nuevoEstado = $(this).data("nuevo-estado");
      cambiarEstadoCompra(idCompra, nuevoEstado);
    });
  });


  const btnAbrirModalNuevaCompra = document.getElementById("btnAbrirModalNuevaCompra");
  const formNuevaCompraModal = document.getElementById("formNuevaCompraModal");
  const btnCerrarModalNuevaCompra = document.getElementById("btnCerrarModalNuevaCompra");
  const btnCancelarCompraModal = document.getElementById("btnCancelarCompraModal");
  const btnGuardarCompraModal = document.getElementById("btnGuardarCompraModal");

  const modalNuevaCompra = document.getElementById("modalNuevaCompra");
  const fechaCompraModal = document.getElementById("fecha_compra_modal");
  const selectMonedaGeneralModal = document.getElementById("idmoneda_general_compra_modal");
  const inputCriterioProveedorModal = document.getElementById('inputCriterioProveedorModal');
  const btnBuscarProveedorModal = document.getElementById('btnBuscarProveedorModal');
  const listaResultadosProveedorModal = document.getElementById('listaResultadosProveedorModal');
  const hiddenIdProveedorModal = document.getElementById("idproveedor_seleccionado_modal");
  const divInfoProveedorModal = document.getElementById("proveedor_seleccionado_info_modal");
  const selectProductoAgregarModal = document.getElementById("select_producto_agregar_modal");
  const btnAgregarProductoDetalleModal = document.getElementById("btnAgregarProductoDetalleModal");
  const cuerpoTablaDetalleCompraModal = document.getElementById("cuerpoTablaDetalleCompraModal");
  const subtotalGeneralDisplayModal = document.getElementById("subtotal_general_display_modal");
  const subtotalGeneralInputModal = document.getElementById("subtotal_general_input_modal");
  const descuentoPorcentajeInputModal = document.getElementById("descuento_porcentaje_input_modal");
  const montoDescuentoDisplayModal = document.getElementById("monto_descuento_display_modal");
  const montoDescuentoInputModal = document.getElementById("monto_descuento_input_modal");
  const totalGeneralDisplayModal = document.getElementById("total_general_display_modal");
  const totalGeneralInputModal = document.getElementById("total_general_input_modal");
  const observacionesCompraModal = document.getElementById("observaciones_compra_modal");
  const mensajeErrorFormCompraModal = document.getElementById("mensajeErrorFormCompraModal");

  let tasasMonedas = {};
  let fechaActualCompra = null;

  function abrirModalNuevaCompra() {
    fechaCompraModal.valueAsDate = new Date();
    fechaActualCompra = fechaCompraModal.value;
    cargarTasasPorFecha(fechaActualCompra);
    formNuevaCompraModal.reset();
    detalleCompraItemsModal = [];
    renderizarTablaDetalleModal();
    hiddenIdProveedorModal.value = "";
    divInfoProveedorModal.innerHTML = "";
    divInfoProveedorModal.classList.add("hidden");
    mensajeErrorFormCompraModal.textContent = "";
    fechaCompraModal.valueAsDate = new Date();

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
      const response = await fetch(`Compras/getTasasMonedasPorFecha?fecha=${encodeURIComponent(fecha)}`);
      const data = await response.json();
      if (data.status && data.tasas && Object.keys(data.tasas).length > 0) {
        tasasMonedas = data.tasas;
        let texto = `Tasa del día (${fecha.split('-').reverse().join('/')})`;
        let tasasArr = [];
        for (const [moneda, tasa] of Object.entries(tasasMonedas)) {
          tasasArr.push(`1 ${moneda} = ${Number(tasa).toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 4})} Bs.`);
        }
        texto += ": " + tasasArr.join(" | ");
        divTasa.textContent = texto;
        calcularTotalesGeneralesModal();
      } else {
        divTasa.textContent = "No hay tasas registradas para esta fecha.";
        tasasMonedas = {};
        calcularTotalesGeneralesModal();
        Swal.fire("Atención", "No hay tasas registradas para la fecha seleccionada.", "warning");
      }
    } catch (e) {
      divTasa.textContent = "Error al cargar tasas del día.";
      tasasMonedas = {};
      calcularTotalesGeneralesModal();
      Swal.fire("Error", "Ocurrió un error al cargar las tasas del día.", "error");
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
    selectMonedaGeneralModal.innerHTML = '<option value="">Cargando...</option>';
    try {
      const response = await fetch('Compras/getListaMonedasParaFormulario');
      if (!response.ok) throw new Error("Error en respuesta de monedas");
      const monedas = await response.json();
      tasasMonedas = {};
      selectMonedaGeneralModal.innerHTML = '<option value="">Seleccione Moneda</option>';
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
      selectMonedaGeneralModal.innerHTML = '<option value="">Error al cargar</option>';
    }
  }

  async function cargarProductosParaModal() {
    selectProductoAgregarModal.innerHTML = '<option value="">Cargando...</option>';
    try {
      const response = await fetch('Compras/getListaProductosParaFormulario');
      if (!response.ok) throw new Error("Error en respuesta de productos");
      const productos = await response.json();
      selectProductoAgregarModal.innerHTML = '<option value="">Seleccione producto...</option>';
      productos.forEach((producto) => {
        const option = document.createElement("option");
        option.value = producto.idproducto;
        option.dataset.idcategoria = producto.idcategoria;
        option.dataset.nombre = producto.nombre_producto;
        option.dataset.precio = producto.precio_referencia_compra || "0.00";
        option.dataset.idmoneda = producto.idmoneda_producto || "";
        option.textContent = `${producto.nombre_producto} (${producto.nombre_categoria})`;
        selectProductoAgregarModal.appendChild(option);
      });
    } catch (error) {
      console.error("Error al cargar productos:", error);
      selectProductoAgregarModal.innerHTML = '<option value="">Error al cargar</option>';
    }
  }

  // Buscar proveedores
  if (btnBuscarProveedorModal && inputCriterioProveedorModal) {
    btnBuscarProveedorModal.addEventListener('click', async function() {
      const termino = inputCriterioProveedorModal.value.trim();
      if (termino.length < 2) {
        Swal.fire("Atención", "Ingrese al menos 2 caracteres para buscar.", "warning");
        return;
      }

      listaResultadosProveedorModal.innerHTML = '<div class="p-2 text-xs text-gray-500">Buscando...</div>';
      listaResultadosProveedorModal.classList.remove('hidden');

      try {
        const response = await fetch(`Compras/buscarProveedores?term=${encodeURIComponent(termino)}`);
        if (!response.ok) {
          throw new Error('Error en la respuesta del servidor');
        }
        const proveedores = await response.json();

        listaResultadosProveedorModal.innerHTML = '';
        if (proveedores && proveedores.length > 0) {
          proveedores.forEach(prov => {
            const itemDiv = document.createElement('div');
            itemDiv.classList.add('p-2', 'text-xs', 'hover:bg-gray-100', 'cursor-pointer');
            itemDiv.textContent = `${prov.nombre || ""} ${prov.apellido || ""} (${prov.identificacion || ""})`.trim();
            itemDiv.dataset.idproveedor = prov.idproveedor;
            itemDiv.dataset.nombre = `${prov.nombre || ""} ${prov.apellido || ""}`.trim();
            itemDiv.dataset.identificacion = prov.identificacion || "";

            itemDiv.addEventListener('click', function() {
              hiddenIdProveedorModal.value = this.dataset.idproveedor;
              divInfoProveedorModal.innerHTML = `Sel: <strong>${this.dataset.nombre}</strong> (ID: ${this.dataset.identificacion})`;
              divInfoProveedorModal.classList.remove('hidden');
              inputCriterioProveedorModal.value = this.textContent;
              listaResultadosProveedorModal.classList.add('hidden');
              listaResultadosProveedorModal.innerHTML = '';
            });
            listaResultadosProveedorModal.appendChild(itemDiv);
          });
        } else {
          listaResultadosProveedorModal.innerHTML = '<div class="p-2 text-xs text-gray-500">No se encontraron proveedores.</div>';
        }
      } catch (error) {
        console.error("Error al buscar proveedores:", error);
        listaResultadosProveedorModal.innerHTML = '<div class="p-2 text-xs text-red-500">Error al buscar. Intente de nuevo.</div>';
      }
    });
  }

  function calcularSubtotalLineaItemModal(item) {
    const precioUnitario = parseFloat(item.precio_unitario) || 0;
    let cantidadBase = 0;
    if (item.idcategoria === 1) {
      cantidadBase = calcularPesoNetoItemModal(item);
    } else {
      cantidadBase = parseFloat(item.cantidad_unidad) || 0;
    }
    const subtotalOriginal = cantidadBase * precioUnitario;
    item.subtotal_linea = subtotalOriginal;
    item.subtotal_linea_bs = convertirAMonedaBase(subtotalOriginal, item.idmoneda_item);
    return item.subtotal_linea;
  }

  function calcularPesoNetoItemModal(item) {
    if (item.idcategoria === 1) {
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
    detalleCompraItemsModal.forEach((item) => {
      subtotalGeneralBs += parseFloat(item.subtotal_linea_bs) || 0;
    });
    
    subtotalGeneralDisplayModal.value = `Bs. ${subtotalGeneralBs.toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    subtotalGeneralInputModal.value = subtotalGeneralBs.toFixed(2);
    
    const descuentoPorcentaje = parseFloat(descuentoPorcentajeInputModal.value) || 0;
    const montoDescuento = (subtotalGeneralBs * descuentoPorcentaje) / 100;
    montoDescuentoDisplayModal.value = `Bs. ${montoDescuento.toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    montoDescuentoInputModal.value = montoDescuento.toFixed(2);
    
    const totalGeneral = subtotalGeneralBs - montoDescuento;
    totalGeneralDisplayModal.value = `Bs. ${totalGeneral.toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    totalGeneralInputModal.value = totalGeneral.toFixed(2);
  }

  // AGREGAR PRODUCTOS AL DETALLE Y RENDERIZAR TABLA (MANTENER COMO ESTÁ)
  if (btnAgregarProductoDetalleModal && selectProductoAgregarModal) {
    btnAgregarProductoDetalleModal.addEventListener("click", function () {
      const selectedOption = selectProductoAgregarModal.options[selectProductoAgregarModal.selectedIndex];
      if (!selectedOption.value) {
        Swal.fire("Atención", "Seleccione un producto.", "warning");
        return;
      }
      
      const idproducto = selectedOption.value;
      if (detalleCompraItemsModal.find((item) => item.idproducto === idproducto)) {
        Swal.fire("Atención", "Este producto ya ha sido agregado.", "warning");
        return;
      }

      const monedaGeneralSeleccionada = selectMonedaGeneralModal.options[selectMonedaGeneralModal.selectedIndex];
      const simboloMonedaGeneral = monedaGeneralSeleccionada ? monedaGeneralSeleccionada.dataset.simbolo : "$";

      const item = {
        idproducto: idproducto,
        nombre: selectedOption.dataset.nombre,
        idcategoria: parseInt(selectedOption.dataset.idcategoria),
        precio_unitario: parseFloat(selectedOption.dataset.precio) || 0,
        idmoneda_item: selectedOption.dataset.idmoneda || selectMonedaGeneralModal.value,
        simbolo_moneda_item: selectedOption.dataset.monedaSimbolo || simboloMonedaGeneral,
        no_usa_vehiculo: false,
        peso_vehiculo: 0,
        peso_bruto: 0,
        peso_neto_directo: 0,
        cantidad_unidad: 1,
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
      if (item.idcategoria === 1) {
        infoEspecificaHtml = `
        <div class="space-y-1">
          <div>
            <label class="flex items-center text-xs">
              <input type="checkbox" class="form-checkbox h-3 w-3 mr-1 no_usa_vehiculo_cb_modal" ${item.no_usa_vehiculo ? "checked" : ""}> No usa vehículo
            </label>
          </div>
          <div class="campos_peso_vehiculo_modal ${item.no_usa_vehiculo ? "hidden" : ""}">
            P.Bru: 
            <input type="number" step="0.01" class="w-1/4 border rounded-md px-2 py-1 text-s focus:outline-none focus:ring-2 focus:ring-green-500 peso_bruto_modal" value="${item.peso_bruto || ""}" placeholder="0.00">
            <button type="button" class="btnUltimoPesoRomanaBruto bg-blue-100 text-blue-700 px-2 py-1 rounded ml-1" title="Traer último peso de romana"><i class="fas fa-balance-scale"></i></button>
            P.Veh: 
            <input type="number" step="0.01" class="w-1/4 border rounded-md px-2 py-1 text-s focus:outline-none focus:ring-2 focus:ring-green-500 peso_vehiculo_modal" value="${item.peso_vehiculo || ""}" placeholder="0.00">
            <button type="button" class="btnUltimoPesoRomanaVehiculo bg-blue-100 text-blue-700 px-2 py-1 rounded ml-1" title="Traer último peso de romana"><i class="fas fa-balance-scale"></i></button>
          </div>
          <div class="campo_peso_neto_directo_modal ${!item.no_usa_vehiculo ? "hidden" : ""}">
            P.Neto: <input type="number" step="0.01" class="w-1/4 border rounded-md py-1 text-s focus:outline-none focus:ring-2 focus:ring-green-500 peso_neto_directo_modal" value="${item.peso_neto_directo || ""}" placeholder="0.00">
            <button type="button" class="btnUltimoPesoRomanaVehiculo bg-blue-100 text-blue-700 px-2 py-1 rounded ml-1" title="Traer último peso de romana"><i class="fas fa-balance-scale"></i></button>
          </div>
          Neto Calc: <strong class="peso_neto_calculado_display_modal">${calcularPesoNetoItemModal(item).toFixed(2)}</strong>
        </div>`;
      } else {
        infoEspecificaHtml = `
          <div>
            Cant: <input type="number" step="0.01" class="w-1/4 border rounded-md px-1 py-1 text-s focus:outline-none focus:ring-2 focus:ring-green-500 cantidad_unidad_modal" value="${item.cantidad_unidad || "1"}" placeholder="1">
          </div>`;
      }

      tr.innerHTML = `
        <td class="py-1 px-1 text-xs">${item.nombre}</td>
        <td class="py-1 px-1 text-xs">${infoEspecificaHtml}</td>
        <td class="py-1 px-1 text-xs">
            ${item.idmoneda_item} <input type="number" step="0.01" class="w-20 border rounded-md px-1 py-1 text-s focus:outline-none focus:ring-2 focus:ring-green-500 precio_unitario_item_modal" value="${item.precio_unitario.toFixed(2)}" placeholder="0.00">
        </td>
        <td class="py-1 px-1 text-xs subtotal_linea_display_modal">${item.idmoneda_item} ${calcularSubtotalLineaItemModal(item).toFixed(2)}</td>
        <td class="py-1 px-1 text-center"><button type="button" class="fa-solid fa-x text-red-500 hover:text-red-700 btnEliminarItemDetalleModal text-xs"></button></td>
      `;
      cuerpoTablaDetalleCompraModal.appendChild(tr);
    });
    addEventListenersToDetalleInputsModal();
    calcularTotalesGeneralesModal();
  }

  function addEventListenersToDetalleInputsModal() {
    document.querySelectorAll("#cuerpoTablaDetalleCompraModal tr").forEach((row) => {
      const index = parseInt(row.dataset.index);
      if (isNaN(index) || index >= detalleCompraItemsModal.length) return;
      const item = detalleCompraItemsModal[index];

      // Event listeners para pesos de romana
      const btnUltimoPesoBruto = row.querySelector(".btnUltimoPesoRomanaBruto");
      if (btnUltimoPesoBruto) {
        btnUltimoPesoBruto.addEventListener("click", async function () {
          try {
            const response = await fetch("Compras/getUltimoPesoRomana");
            const data = await response.json();
            if (data.status) {
              item.peso_bruto = data.peso;
              row.querySelector(".peso_bruto_modal").value = data.peso;
              actualizarCalculosFilaModal(row, item);
            } else {
              Swal.fire("Atención", data.message || "No se pudo obtener el peso.", "warning");
            }
          } catch (e) {
            Swal.fire("Error", "Error al consultar la romana.", "error");
          }
        });
      }

      const btnUltimoPesoVehiculo = row.querySelector(".btnUltimoPesoRomanaVehiculo");
      if (btnUltimoPesoVehiculo) {
        btnUltimoPesoVehiculo.addEventListener("click", async function () {
          try {
            const response = await fetch("Compras/getUltimoPesoRomana");
            const data = await response.json();
            if (data.status) {
              item.peso_vehiculo = data.peso;
              row.querySelector(".peso_vehiculo_modal").value = data.peso;
              actualizarCalculosFilaModal(row, item);
            } else {
              Swal.fire("Atención", data.message || "No se pudo obtener el peso.", "warning");
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
          const camposPesoVehiculo = row.querySelector(".campos_peso_vehiculo_modal");
          const campoPesoNetoDirecto = row.querySelector(".campo_peso_neto_directo_modal");
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

      // Event listeners para inputs de valores
      row.querySelectorAll(".peso_vehiculo_modal, .peso_bruto_modal, .peso_neto_directo_modal, .cantidad_unidad_modal, .precio_unitario_item_modal").forEach((input) => {
        input.addEventListener("input", function (e) {
          const fieldName = e.target.classList.contains("peso_vehiculo_modal") ? "peso_vehiculo"
            : e.target.classList.contains("peso_bruto_modal") ? "peso_bruto"
            : e.target.classList.contains("peso_neto_directo_modal") ? "peso_neto_directo"
            : e.target.classList.contains("cantidad_unidad_modal") ? "cantidad_unidad"
            : "precio_unitario";
          item[fieldName] = parseFloat(e.target.value) || 0;
          actualizarCalculosFilaModal(row, item);
        });
      });

      // Botón eliminar item
      row.querySelector(".btnEliminarItemDetalleModal").addEventListener("click", function () {
        detalleCompraItemsModal.splice(index, 1);
        renderizarTablaDetalleModal();
      });
    });
  }

  function actualizarCalculosFilaModal(rowElement, item) {
    const pesoNetoDisplay = rowElement.querySelector(".peso_neto_calculado_display_modal");
    if (pesoNetoDisplay) {
      pesoNetoDisplay.textContent = calcularPesoNetoItemModal(item).toFixed(2);
    }
    rowElement.querySelector(".subtotal_linea_display_modal").textContent = `${item.idmoneda_item} ${calcularSubtotalLineaItemModal(item).toFixed(2)}`;
    calcularTotalesGeneralesModal();
  }

  // Event listener para descuento
  if (descuentoPorcentajeInputModal) {
    descuentoPorcentajeInputModal.addEventListener("input", calcularTotalesGeneralesModal);
  }
  if (selectMonedaGeneralModal) {
    selectMonedaGeneralModal.addEventListener("change", calcularTotalesGeneralesModal);
  }

  // GUARDAR COMPRA (MANTENER COMO ESTÁ - FUNCIONAL)
  if (btnGuardarCompraModal) {
    btnGuardarCompraModal.addEventListener("click", async function () {
      if (!validarCamposVacios(camposCompras, "formNuevaCompraModal")) return;
      mensajeErrorFormCompraModal.textContent = "";
      
      if (detalleCompraItemsModal.length === 0) {
        mensajeErrorFormCompraModal.textContent = "Debe agregar al menos un producto al detalle.";
        return;
      }
      
      if (!selectMonedaGeneralModal.value) {
        mensajeErrorFormCompraModal.textContent = "Debe seleccionar una moneda general para la compra.";
        return;
      }
      
      for (const item of detalleCompraItemsModal) {
        const precio = parseFloat(item.precio_unitario) || 0;
        let cantidadValida = false;
        if (item.idcategoria === 1) {
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
      formData.append("productos_detalle", JSON.stringify(detalleCompraItemsModal));
      formData.set("subtotal_general_input", subtotalGeneralInputModal.value);
      formData.set("descuento_porcentaje_input", descuentoPorcentajeInputModal.value);
      formData.set("monto_descuento_input", montoDescuentoInputModal.value);
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
          mensajeErrorFormCompraModal.textContent = data.message || "Error al guardar.";
        }
      } catch (error) {
        console.error("Error al guardar compra:", error);
        Swal.fire("Error", "Ocurrió un error de conexión al guardar.", "error");
        mensajeErrorFormCompraModal.textContent = "Ocurrió un error de conexión al guardar.";
      } finally {
        btnGuardarCompraModal.disabled = false;
        btnGuardarCompraModal.textContent = "Guardar Compra";
      }
    });
  }

  // MODAL DE PROVEEDOR (MANTENER COMO ESTÁ)
  const modalProveedor = document.getElementById("proveedorModal");
  const formProveedor = document.getElementById("proveedorForm");
  const modalTitulo = document.getElementById("modalProveedorTitulo");
  const btnSubmitProveedor = document.getElementById("btnSubmitProveedor");
  const inputIdPersona = document.getElementById("idproveedor");

  window.abrirModalProveedor = function (titulo = "Registrar Proveedor", formAction = "Compras/createProveedorinCompras") {
    formProveedor.reset(); 
    inputIdPersona.value = ""; 
    modalTitulo.textContent = titulo;
    formProveedor.setAttribute("data-action", formAction); 
    btnSubmitProveedor.textContent = "Registrar";
    modalProveedor.classList.remove("opacity-0", "pointer-events-none");
  };

  window.cerrarModalProveedor = function () {
    modalProveedor.classList.add("opacity-0", "pointer-events-none");
    formProveedor.reset();
    inputIdPersona.value = "";
  };

  formProveedor.addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    const actionUrl = "Compras/createProveedorinCompras";
    const method = "POST";

    if (!actionUrl) {
      Swal.fire("Error Interno", "URL de acción no definida para el formulario.", "error");
      console.error("El atributo data-action del formulario está vacío o no existe.");
      return;
    }

    const nombre = formData.get('nombre');
    const identificacion = formData.get('identificacion');
    const telefono_principal = formData.get('telefono_principal');

    if (!nombre || !identificacion || !telefono_principal) {
      Swal.fire("Atención", "Nombre, Identificación y Teléfono son obligatorios.", "warning");
      return;
    }

    fetch(actionUrl, {
      method: method,
      body: formData
    })
      .then((response) => {
        if (!response.ok) {
          return response.json().then(errData => {
            const error = new Error(errData.message || `Error HTTP: ${response.status}`);
            error.data = errData;
            error.status = response.status;
            throw error;
          }).catch(() => {
            throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`);
          });
        }
        return response.json();
      })
      .then(async (result) => {
        if (result.status) {
          Swal.fire("¡Éxito!", result.message, "success");
          try {
            const response = await fetch(`Compras/getProveedorById/${encodeURIComponent(result.idproveedor)}`);
            if (!response.ok) throw new Error('No se pudo obtener el proveedor');
            const proveedor = await response.json();
            hiddenIdProveedorModal.value = proveedor.data.idproveedor;
            divInfoProveedorModal.innerHTML = `Sel: <strong>${proveedor.data.nombre} ${proveedor.data.apellido}</strong> (ID: ${proveedor.data.identificacion})`;
            divInfoProveedorModal.classList.remove('hidden');
            inputCriterioProveedorModal.value = `${proveedor.data.nombre} ${proveedor.data.apellido} (${proveedor.data.identificacion})`;
            cerrarModalProveedor();
          } catch (error) {
            console.error("Error al obtener proveedor:", error);
            Swal.fire("Error", "Proveedor registrado, pero no se pudo mostrar la información.", "warning");
            cerrarModalProveedor();
          }
        } else {
          Swal.fire("Error", result.message || "Respuesta no exitosa del servidor.", "error");
        }
      })
      .catch((error) => {
        console.error("Error en fetch:", error);
        let errorMessage = "Ocurrió un error al procesar la solicitud.";
        if (error.data && error.data.message) {
          errorMessage = error.data.message;
        } else if (error.message) {
          errorMessage = error.message;
        }
        Swal.fire("Error", errorMessage, "error");
      });
  });

  // MODALES PARA VER, EDITAR COMPRAS
  const btnCerrarModalVer = document.getElementById("btnCerrarModalVer");
  const btnCerrarModalVer2 = document.getElementById("btnCerrarModalVer2");
  const btnCerrarModalActualizar = document.getElementById("btnCerrarModalActualizar");
  const btnCancelarModalActualizar = document.getElementById("btnCancelarModalActualizar");
  const formActualizarCompra = document.getElementById("formActualizarCompra");
  const btnActualizarCompra = document.getElementById("btnActualizarCompra");

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

  if (btnCerrarModalActualizar) {
    btnCerrarModalActualizar.addEventListener("click", function () {
      cerrarModal("modalActualizarCompra");
    });
  }

  if (btnCancelarModalActualizar) {
    btnCancelarModalActualizar.addEventListener("click", function () {
      cerrarModal("modalActualizarCompra");
    });
  }

  if (formActualizarCompra) {
    formActualizarCompra.addEventListener("submit", function (e) {
      e.preventDefault();
      actualizarCompra();
    });
  }
const fechaCompraActualizar = document.getElementById("fecha_compra_actualizar");
  if (fechaCompraActualizar) {
    fechaCompraActualizar.addEventListener("change", function () {
      fechaActualCompraActualizar = this.value;
      if (fechaActualCompraActualizar) {
        cargarTasasPorFechaActualizar(fechaActualCompraActualizar);
      }
    });
  }

  const descuentoPorcentajeInputActualizar = document.getElementById("descuento_porcentaje_input_actualizar");
  if (descuentoPorcentajeInputActualizar) {
    descuentoPorcentajeInputActualizar.addEventListener("input", calcularTotalesGeneralesActualizar);
  }

  const selectMonedaGeneralActualizar = document.getElementById("idmoneda_general_compra_actualizar");
  if (selectMonedaGeneralActualizar) {
    selectMonedaGeneralActualizar.addEventListener("change", calcularTotalesGeneralesActualizar);
  }

  // Buscar proveedores en modal actualizar
  const btnBuscarProveedorActualizar = document.getElementById('btnBuscarProveedorActualizar');
  const inputCriterioProveedorActualizar = document.getElementById('inputCriterioProveedorActualizar');
  const listaResultadosProveedorActualizar = document.getElementById('listaResultadosProveedorActualizar');
  const hiddenIdProveedorActualizar = document.getElementById("idproveedor_seleccionado_actualizar");
  const divInfoProveedorActualizar = document.getElementById("proveedor_seleccionado_info_actualizar");

  if (btnBuscarProveedorActualizar && inputCriterioProveedorActualizar) {
    btnBuscarProveedorActualizar.addEventListener('click', async function() {
      const termino = inputCriterioProveedorActualizar.value.trim();
      if (termino.length < 2) {
        Swal.fire("Atención", "Ingrese al menos 2 caracteres para buscar.", "warning");
        return;
      }

      listaResultadosProveedorActualizar.innerHTML = '<div class="p-2 text-xs text-gray-500">Buscando...</div>';
      listaResultadosProveedorActualizar.classList.remove('hidden');

      try {
        const response = await fetch(`Compras/buscarProveedores?term=${encodeURIComponent(termino)}`);
        if (!response.ok) {
          throw new Error('Error en la respuesta del servidor');
        }
        const proveedores = await response.json();

        listaResultadosProveedorActualizar.innerHTML = '';
        if (proveedores && proveedores.length > 0) {
          proveedores.forEach(prov => {
            const itemDiv = document.createElement('div');
            itemDiv.classList.add('p-2', 'text-xs', 'hover:bg-gray-100', 'cursor-pointer');
            itemDiv.textContent = `${prov.nombre || ""} ${prov.apellido || ""} (${prov.identificacion || ""})`.trim();
            itemDiv.dataset.idproveedor = prov.idproveedor;
            itemDiv.dataset.nombre = `${prov.nombre || ""} ${prov.apellido || ""}`.trim();
            itemDiv.dataset.identificacion = prov.identificacion || "";

            itemDiv.addEventListener('click', function() {
              hiddenIdProveedorActualizar.value = this.dataset.idproveedor;
              divInfoProveedorActualizar.innerHTML = `Sel: <strong>${this.dataset.nombre}</strong> (ID: ${this.dataset.identificacion})`;
              divInfoProveedorActualizar.classList.remove('hidden');
              inputCriterioProveedorActualizar.value = this.textContent;
              listaResultadosProveedorActualizar.classList.add('hidden');
              listaResultadosProveedorActualizar.innerHTML = '';
            });
            listaResultadosProveedorActualizar.appendChild(itemDiv);
          });
        } else {
          listaResultadosProveedorActualizar.innerHTML = '<div class="p-2 text-xs text-gray-500">No se encontraron proveedores.</div>';
        }
      } catch (error) {
        console.error("Error al buscar proveedores:", error);
        listaResultadosProveedorActualizar.innerHTML = '<div class="p-2 text-xs text-red-500">Error al buscar. Intente de nuevo.</div>';
      }
    });
  }

  // Agregar producto al detalle en modal actualizar
  const btnAgregarProductoDetalleActualizar = document.getElementById("btnAgregarProductoDetalleActualizar");
  const selectProductoAgregarActualizar = document.getElementById("select_producto_agregar_actualizar");
  
  if (btnAgregarProductoDetalleActualizar && selectProductoAgregarActualizar) {
    btnAgregarProductoDetalleActualizar.addEventListener("click", function () {
      const selectedOption = selectProductoAgregarActualizar.options[selectProductoAgregarActualizar.selectedIndex];
      if (!selectedOption.value) {
        Swal.fire("Atención", "Seleccione un producto.", "warning");
        return;
      }
      
      const idproducto = selectedOption.value;
      if (detalleCompraItemsActualizar.find((item) => item.idproducto === idproducto)) {
        Swal.fire("Atención", "Este producto ya ha sido agregado.", "warning");
        return;
      }

      const monedaGeneralSeleccionada = selectMonedaGeneralActualizar.options[selectMonedaGeneralActualizar.selectedIndex];
      const simboloMonedaGeneral = monedaGeneralSeleccionada ? monedaGeneralSeleccionada.textContent.split('(')[1]?.split(')')[0] : "$";

      const item = {
        idproducto: idproducto,
        nombre: selectedOption.dataset.nombre,
        idcategoria: parseInt(selectedOption.dataset.idcategoria),
        precio_unitario: parseFloat(selectedOption.dataset.precio) || 0,
        idmoneda_item: selectedOption.dataset.idmoneda || selectMonedaGeneralActualizar.value,
        simbolo_moneda_item: simboloMonedaGeneral,
        no_usa_vehiculo: false,
        peso_vehiculo: 0,
        peso_bruto: 0,
        peso_neto_directo: 0,
        cantidad_unidad: 1,
      };
      
      detalleCompraItemsActualizar.push(item);
      renderizarTablaDetalleActualizar();
      selectProductoAgregarActualizar.value = "";
    });
  }
});

// FUNCIONES PRINCIPALES

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
        mostrarModalVerCompra(compra, detalles);
      } else {
        Swal.fire("Error", "No se pudieron cargar los datos.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    });
}

function mostrarModalVerCompra(compra, detalles) {
  document.getElementById("verNroCompra").textContent = compra.nro_compra || "N/A";
  document.getElementById("verFecha").textContent = compra.fecha ? new Date(compra.fecha).toLocaleDateString('es-ES') : "N/A";
  document.getElementById("verProveedor").textContent = compra.proveedor_nombre || "N/A";
  document.getElementById("verEstado").textContent = compra.estatus_compra || "N/A";
  document.getElementById("verTotalGeneral").textContent = compra.total_general ? 
    "Bs. " + parseFloat(compra.total_general).toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : "N/A";
  document.getElementById("verObservaciones").textContent = compra.observaciones_compra || "N/A";

  const tbody = document.getElementById("verDetalleProductos");
  tbody.innerHTML = "";
  
  if (detalles && detalles.length > 0) {
    detalles.forEach(detalle => {
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td class="px-4 py-2">${detalle.descripcion_temporal_producto || detalle.producto_nombre || "N/A"}</td>
        <td class="px-4 py-2 text-right">${parseFloat(detalle.cantidad || 0).toLocaleString('es-ES', {minimumFractionDigits: 2})}</td>
        <td class="px-4 py-2 text-right">${detalle.codigo_moneda || ""} ${parseFloat(detalle.precio_unitario_compra || 0).toLocaleString('es-ES', {minimumFractionDigits: 2})}</td>
        <td class="px-4 py-2 text-right">${detalle.codigo_moneda || ""} ${parseFloat(detalle.subtotal_linea || 0).toLocaleString('es-ES', {minimumFractionDigits: 2})}</td>
      `;
      tbody.appendChild(tr);
    });
  } else {
    tbody.innerHTML = '<tr><td colspan="4" class="px-4 py-2 text-center text-gray-500">No hay detalles disponibles</td></tr>';
  }

  abrirModal("modalVerCompra");
}

function editarCompra(idCompra) {
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
        mostrarModalEditarCompra(compra, detalles);
      } else {
        Swal.fire("Error", "No se pudieron cargar los datos.", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire("Error", "Error de conexión.", "error");
    });
}

function mostrarModalEditarCompra(compra, detalles) {
  // Cargar datos básicos
  document.getElementById("idCompraActualizar").value = compra.idcompra || "";
  document.getElementById("fecha_compra_actualizar").value = compra.fecha || "";
  document.getElementById("observaciones_compra_actualizar").value = compra.observaciones_compra || "";
  
  // Configurar fecha y tasas
  fechaActualCompraActualizar = compra.fecha;
  cargarTasasPorFechaActualizar(fechaActualCompraActualizar);
  
  // Cargar monedas y productos
  cargarMonedasParaActualizar();
  cargarProductosParaActualizar();
  
  // Configurar proveedor
  document.getElementById("idproveedor_seleccionado_actualizar").value = compra.idproveedor || "";
  document.getElementById("proveedor_seleccionado_info_actualizar").innerHTML = `Sel: <strong>${compra.proveedor_nombre}</strong>`;
  document.getElementById("proveedor_seleccionado_info_actualizar").classList.remove("hidden");
  document.getElementById("inputCriterioProveedorActualizar").value = compra.proveedor_nombre || "";
  
  // Configurar moneda general
  setTimeout(() => {
    document.getElementById("idmoneda_general_compra_actualizar").value = compra.idmoneda_general || "";
  }, 500);
  
  // Configurar descuentos y totales
  document.getElementById("descuento_porcentaje_input_actualizar").value = compra.descuento_porcentaje_general || "0";
  
  // Cargar detalles
  detalleCompraItemsActualizar = [];
  if (detalles && detalles.length > 0) {
    detalles.forEach(detalle => {
      const item = {
        idproducto: detalle.idproducto,
        nombre: detalle.descripcion_temporal_producto,
        idcategoria: 1, // Asumiendo categoría, ajustar según tus datos
        precio_unitario: parseFloat(detalle.precio_unitario_compra),
        idmoneda_item: detalle.idmoneda_detalle,
        simbolo_moneda_item: detalle.codigo_moneda || "",
        no_usa_vehiculo: !detalle.peso_vehiculo && !detalle.peso_bruto,
        peso_vehiculo: parseFloat(detalle.peso_vehiculo) || 0,
        peso_bruto: parseFloat(detalle.peso_bruto) || 0,
        peso_neto_directo: parseFloat(detalle.peso_neto) || 0,
        cantidad_unidad: parseFloat(detalle.cantidad) || 1,
        subtotal_linea: parseFloat(detalle.subtotal_linea) || 0,
        subtotal_linea_bs: parseFloat(detalle.subtotal_linea) || 0,
      };
      detalleCompraItemsActualizar.push(item);
    });
  }
  
  // Renderizar tabla y calcular totales
  setTimeout(() => {
    renderizarTablaDetalleActualizar();
    calcularTotalesGeneralesActualizar();
  }, 600);

  inicializarValidaciones(camposCompras, "formActualizarCompra");
  abrirModal("modalActualizarCompra");
}

async function cargarTasasPorFechaActualizar(fecha) {
  const divTasa = document.getElementById("tasaDelDiaInfoActualizar");
  divTasa.textContent = "Cargando tasas del día...";
  try {
    const response = await fetch(`Compras/getTasasMonedasPorFecha?fecha=${encodeURIComponent(fecha)}`);
    const data = await response.json();
    if (data.status && data.tasas && Object.keys(data.tasas).length > 0) {
      tasasMonedasActualizar = data.tasas;
      let texto = `Tasa del día (${fecha.split('-').reverse().join('/')})`;
      let tasasArr = [];
      for (const [moneda, tasa] of Object.entries(tasasMonedasActualizar)) {
        tasasArr.push(`1 ${moneda} = ${Number(tasa).toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 4})} Bs.`);
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
  const selectMonedaGeneralActualizar = document.getElementById("idmoneda_general_compra_actualizar");
  selectMonedaGeneralActualizar.innerHTML = '<option value="">Cargando...</option>';
  try {
    const response = await fetch('Compras/getListaMonedasParaFormulario');
    if (!response.ok) throw new Error("Error en respuesta de monedas");
    const monedas = await response.json();
    selectMonedaGeneralActualizar.innerHTML = '<option value="">Seleccione Moneda</option>';
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
    selectMonedaGeneralActualizar.innerHTML = '<option value="">Error al cargar</option>';
  }
}

async function cargarProductosParaActualizar() {
  const selectProductoAgregarActualizar = document.getElementById("select_producto_agregar_actualizar");
  selectProductoAgregarActualizar.innerHTML = '<option value="">Cargando...</option>';
  try {
    const response = await fetch('Compras/getListaProductosParaFormulario');
    if (!response.ok) throw new Error("Error en respuesta de productos");
    const productos = await response.json();
    selectProductoAgregarActualizar.innerHTML = '<option value="">Seleccione producto...</option>';
    productos.forEach((producto) => {
      const option = document.createElement("option");
      option.value = producto.idproducto;
      option.dataset.idcategoria = producto.idcategoria;
      option.dataset.nombre = producto.nombre_producto;
      option.dataset.precio = producto.precio_referencia_compra || "0.00";
      option.dataset.idmoneda = producto.idmoneda_producto || "";
      option.textContent = `${producto.nombre_producto} (${producto.nombre_categoria})`;
      selectProductoAgregarActualizar.appendChild(option);
    });
  } catch (error) {
    console.error("Error al cargar productos:", error);
    selectProductoAgregarActualizar.innerHTML = '<option value="">Error al cargar</option>';
  }
}

function renderizarTablaDetalleActualizar() {
  const cuerpoTablaDetalleCompraActualizar = document.getElementById("cuerpoTablaDetalleCompraActualizar");
  cuerpoTablaDetalleCompraActualizar.innerHTML = "";
  
  detalleCompraItemsActualizar.forEach((item, index) => {
    const tr = document.createElement("tr");
    tr.classList.add("border-b", "hover:bg-gray-50");
    tr.dataset.index = index;

    let infoEspecificaHtml = "";
    if (item.idcategoria === 1) {
      infoEspecificaHtml = `
      <div class="space-y-1">
        <div>
          <label class="flex items-center text-xs">
            <input type="checkbox" class="form-checkbox h-3 w-3 mr-1 no_usa_vehiculo_cb_actualizar" ${item.no_usa_vehiculo ? "checked" : ""}> No usa vehículo
          </label>
        </div>
        <div class="campos_peso_vehiculo_actualizar ${item.no_usa_vehiculo ? "hidden" : ""}">
          P.Bru: 
          <input type="number" step="0.01" class="w-1/4 border rounded-md px-2 py-1 text-s focus:outline-none focus:ring-2 focus:ring-blue-500 peso_bruto_actualizar" value="${item.peso_bruto || ""}" placeholder="0.00">
          P.Veh: 
          <input type="number" step="0.01" class="w-1/4 border rounded-md px-2 py-1 text-s focus:outline-none focus:ring-2 focus:ring-blue-500 peso_vehiculo_actualizar" value="${item.peso_vehiculo || ""}" placeholder="0.00">
        </div>
        <div class="campo_peso_neto_directo_actualizar ${!item.no_usa_vehiculo ? "hidden" : ""}">
          P.Neto: <input type="number" step="0.01" class="w-1/4 border rounded-md py-1 text-s focus:outline-none focus:ring-2 focus:ring-blue-500 peso_neto_directo_actualizar" value="${item.peso_neto_directo || ""}" placeholder="0.00">
        </div>
        Neto Calc: <strong class="peso_neto_calculado_display_actualizar">${calcularPesoNetoItemActualizar(item).toFixed(2)}</strong>
      </div>`;
    } else {
      infoEspecificaHtml = `
        <div>
          Cant: <input type="number" step="0.01" class="w-1/4 border rounded-md px-1 py-1 text-s focus:outline-none focus:ring-2 focus:ring-blue-500 cantidad_unidad_actualizar" value="${item.cantidad_unidad || "1"}" placeholder="1">
        </div>`;
    }

    tr.innerHTML = `
      <td class="py-1 px-1 text-xs">${item.nombre}</td>
      <td class="py-1 px-1 text-xs">${infoEspecificaHtml}</td>
      <td class="py-1 px-1 text-xs">
          ${item.simbolo_moneda_item} <input type="number" step="0.01" class="w-20 border rounded-md px-1 py-1 text-s focus:outline-none focus:ring-2 focus:ring-blue-500 precio_unitario_item_actualizar" value="${item.precio_unitario.toFixed(2)}" placeholder="0.00">
      </td>
      <td class="py-1 px-1 text-xs subtotal_linea_display_actualizar">${item.simbolo_moneda_item} ${calcularSubtotalLineaItemActualizar(item).toFixed(2)}</td>
      <td class="py-1 px-1 text-center"><button type="button" class="fa-solid fa-x text-red-500 hover:text-red-700 btnEliminarItemDetalleActualizar text-xs"></button></td>
    `;
    cuerpoTablaDetalleCompraActualizar.appendChild(tr);
  });
  
  addEventListenersToDetalleInputsActualizar();
  calcularTotalesGeneralesActualizar();
}

function calcularPesoNetoItemActualizar(item) {
  if (item.idcategoria === 1) {
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
  if (item.idcategoria === 1) {
    cantidadBase = calcularPesoNetoItemActualizar(item);
  } else {
    cantidadBase = parseFloat(item.cantidad_unidad) || 0;
  }
  const subtotalOriginal = cantidadBase * precioUnitario;
  item.subtotal_linea = subtotalOriginal;
  item.subtotal_linea_bs = convertirAMonedaBaseActualizar(subtotalOriginal, item.idmoneda_item);
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
  
  const subtotalGeneralDisplayActualizar = document.getElementById("subtotal_general_display_actualizar");
  const subtotalGeneralInputActualizar = document.getElementById("subtotal_general_input_actualizar");
  const descuentoPorcentajeInputActualizar = document.getElementById("descuento_porcentaje_input_actualizar");
  const montoDescuentoDisplayActualizar = document.getElementById("monto_descuento_display_actualizar");
  const montoDescuentoInputActualizar = document.getElementById("monto_descuento_input_actualizar");
  const totalGeneralDisplayActualizar = document.getElementById("total_general_display_actualizar");
  const totalGeneralInputActualizar = document.getElementById("total_general_input_actualizar");
  
  if (subtotalGeneralDisplayActualizar) {
    subtotalGeneralDisplayActualizar.value = `Bs. ${subtotalGeneralBs.toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    subtotalGeneralInputActualizar.value = subtotalGeneralBs.toFixed(2);
    
    const descuentoPorcentaje = parseFloat(descuentoPorcentajeInputActualizar.value) || 0;
    const montoDescuento = (subtotalGeneralBs * descuentoPorcentaje) / 100;
    montoDescuentoDisplayActualizar.value = `Bs. ${montoDescuento.toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    montoDescuentoInputActualizar.value = montoDescuento.toFixed(2);
    
    const totalGeneral = subtotalGeneralBs - montoDescuento;
    totalGeneralDisplayActualizar.value = `Bs. ${totalGeneral.toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    totalGeneralInputActualizar.value = totalGeneral.toFixed(2);
  }
}

function addEventListenersToDetalleInputsActualizar() {
  document.querySelectorAll("#cuerpoTablaDetalleCompraActualizar tr").forEach((row) => {
    const index = parseInt(row.dataset.index);
    if (isNaN(index) || index >= detalleCompraItemsActualizar.length) return;
    const item = detalleCompraItemsActualizar[index];

    // Checkbox no usa vehículo
    const cbNoUsaVehiculo = row.querySelector(".no_usa_vehiculo_cb_actualizar");
    if (cbNoUsaVehiculo) {
      cbNoUsaVehiculo.addEventListener("change", function (e) {
        item.no_usa_vehiculo = e.target.checked;
        const camposPesoVehiculo = row.querySelector(".campos_peso_vehiculo_actualizar");
        const campoPesoNetoDirecto = row.querySelector(".campo_peso_neto_directo_actualizar");
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

    // Event listeners para inputs de valores
    row.querySelectorAll(".peso_vehiculo_actualizar, .peso_bruto_actualizar, .peso_neto_directo_actualizar, .cantidad_unidad_actualizar, .precio_unitario_item_actualizar").forEach((input) => {
      input.addEventListener("input", function (e) {
        const fieldName = e.target.classList.contains("peso_vehiculo_actualizar") ? "peso_vehiculo"
          : e.target.classList.contains("peso_bruto_actualizar") ? "peso_bruto"
          : e.target.classList.contains("peso_neto_directo_actualizar") ? "peso_neto_directo"
          : e.target.classList.contains("cantidad_unidad_actualizar") ? "cantidad_unidad"
          : "precio_unitario";
        item[fieldName] = parseFloat(e.target.value) || 0;
        actualizarCalculosFilaActualizar(row, item);
      });
    });

    // Botón eliminar item
    row.querySelector(".btnEliminarItemDetalleActualizar").addEventListener("click", function () {
      detalleCompraItemsActualizar.splice(index, 1);
      renderizarTablaDetalleActualizar();
    });
  });
}

function actualizarCalculosFilaActualizar(rowElement, item) {
  const pesoNetoDisplay = rowElement.querySelector(".peso_neto_calculado_display_actualizar");
  if (pesoNetoDisplay) {
    pesoNetoDisplay.textContent = calcularPesoNetoItemActualizar(item).toFixed(2);
  }
  rowElement.querySelector(".subtotal_linea_display_actualizar").textContent = `${item.simbolo_moneda_item} ${calcularSubtotalLineaItemActualizar(item).toFixed(2)}`;
  calcularTotalesGeneralesActualizar();
}


function actualizarCompra() {
  const formActualizar = document.getElementById("formActualizarCompra");
  const btnActualizarCompra = document.getElementById("btnActualizarCompra");
  const idCompra = document.getElementById("idCompraActualizar").value;
  const mensajeErrorFormCompraActualizar = document.getElementById("mensajeErrorFormCompraActualizar");

  // Validaciones
  if (!validarCamposVacios(camposCompras, "formActualizarCompra")) {
    return;
  }

  mensajeErrorFormCompraActualizar.textContent = "";
  
  if (detalleCompraItemsActualizar.length === 0) {
    mensajeErrorFormCompraActualizar.textContent = "Debe tener al menos un producto en el detalle.";
    return;
  }

  const formData = new FormData(formActualizar);
  const dataParaEnviar = {
    idcompra: idCompra,
    fecha_compra: formData.get("fecha_compra") || "",
    idproveedor: parseInt(document.getElementById("idproveedor_seleccionado_actualizar").value || "0"),
    idmoneda_general: parseInt(document.getElementById("idmoneda_general_compra_actualizar").value || "0"),
    observaciones_compra: formData.get("observaciones_compra") || "",
    subtotal_general: parseFloat(document.getElementById("subtotal_general_input_actualizar").value || "0"),
    descuento_porcentaje: parseFloat(document.getElementById("descuento_porcentaje_input_actualizar").value || "0"),
    monto_descuento: parseFloat(document.getElementById("monto_descuento_input_actualizar").value || "0"),
    total_general: parseFloat(document.getElementById("total_general_input_actualizar").value || "0"),
    detalles: detalleCompraItemsActualizar.map(item => ({
      idproducto: item.idproducto,
      descripcion_temporal_producto: item.nombre,
      cantidad: item.idcategoria === 1 ? calcularPesoNetoItemActualizar(item) : item.cantidad_unidad,
      precio_unitario_compra: item.precio_unitario,
      idmoneda_detalle: item.idmoneda_item,
      subtotal_linea: item.subtotal_linea,
      peso_vehiculo: item.idcategoria === 1 && !item.no_usa_vehiculo ? item.peso_vehiculo : null,
      peso_bruto: item.idcategoria === 1 && !item.no_usa_vehiculo ? item.peso_bruto : null,
      peso_neto: item.idcategoria === 1 ? (item.no_usa_vehiculo ? item.peso_neto_directo : calcularPesoNetoItemActualizar(item)) : null,
    }))
  };

  if (btnActualizarCompra) {
    btnActualizarCompra.disabled = true;
    btnActualizarCompra.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Actualizando...`;
  }

  fetch("Compras/updateCompra", {
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
        cerrarModal("modalActualizarCompra");
        if (typeof tablaCompras !== "undefined" && tablaCompras.ajax) {
          tablaCompras.ajax.reload(null, false);
        }
      } else {
        Swal.fire("Error", result.message || "No se pudo actualizar la compra.", "error");
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
      if (btnActualizarCompra) {
        btnActualizarCompra.disabled = false;
        btnActualizarCompra.innerHTML = `<i class="fas fa-save mr-2"></i> Actualizar Compra`;
      }
    });
  }

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
            Swal.fire("Error", result.message || "No se pudo eliminar la compra.", "error");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          Swal.fire("Error", "Error de conexión.", "error");
        });
    }
  });
}

function cambiarEstadoCompra(idCompra, nuevoEstado) {
  const mensajesEstado = {
    'POR_AUTORIZAR': 'enviar a autorización',
    'AUTORIZADA': 'autorizar',
    'POR_PAGAR': 'marcar para pago',
    'PAGADA': 'marcar como pagada',
    'BORRADOR': 'devolver a borrador'
  };

  const mensaje = mensajesEstado[nuevoEstado] || 'cambiar estado de';

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
            Swal.fire("Error", result.message || "No se pudo cambiar el estado de la compra.", "error");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          Swal.fire("Error", "Error de conexión.", "error");
        });
    }
  });
}
