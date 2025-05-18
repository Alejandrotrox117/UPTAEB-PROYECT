document.addEventListener("DOMContentLoaded", function () {
  // --- DataTable (se mantiene igual que antes) ---
  let TablaCompras;
  tablaCompras = $("#TablaCompras").DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "compras/getComprasDataTable",
            type: "GET", 
            dataSrc: "data", 
        },
        columns: [
            { data: "nro_compra", title: "Nro. Compra" },
            { data: "fecha", title: "Fecha" },
            { data: "idproveedor", title: "Proveedor" },
            { data: "total_general", title: "Total" },
            
            {
                data: null,
                title: "Acciones",
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    return `
                        <button class="ver-compra-btn text-green-600 hover:text-green-800 p-1" data-idcompra="${row.idcompra}" title="Ver Detalle">
                            <i class="fas fa-eye fa-lg"></i>
                        </button>
                        <button class="editar-proveedor-btn text-blue-500 hover:text-blue-700 p-1" data-idcompra="${row.idcompra}" title="Editar">
                            <i class="fas fa-edit fa-lg"></i>
                        </button>
                        <button class="eliminar-proveedor-btn text-red-500 hover:text-red-700 p-1 ml-2" data-idcompra="${row.idcompra}" title="Eliminar">
                            <i class="fas fa-trash fa-lg"></i>
                        </button>
                    `;
                },
            },
        ],
        language: {  },
        destroy: true,
        responsive: true,
        pageLength: 10,
        order: [[0, "asc"]],
    });

  // --- Lógica del Modal de Nueva Compra ---
  const modalNuevaCompra = document.getElementById("modalNuevaCompra");
  const btnAbrirModalNuevaCompra = document.getElementById(
    "btnAbrirModalNuevaCompra",
  );
  const btnCerrarModalNuevaCompra = document.getElementById(
    "btnCerrarModalNuevaCompra",
  );
  const btnCancelarCompraModal = document.getElementById(
    "btnCancelarCompraModal",
  );
  const formNuevaCompraModal = document.getElementById("formNuevaCompraModal");
  const btnGuardarCompraModal = document.getElementById("btnGuardarCompraModal");

  // Elementos del formulario del modal
  const fechaCompraModal = document.getElementById("fecha_compra_modal");
  const selectMonedaGeneralModal = document.getElementById(
    "idmoneda_general_compra_modal",
  );
  const inputBuscarProveedorModal = document.getElementById(
    "buscar_proveedor_modal",
  );
  const hiddenIdProveedorModal = document.getElementById(
    "idproveedor_seleccionado_modal",
  );
  const divInfoProveedorModal = document.getElementById(
    "proveedor_seleccionado_info_modal",
  );
  const selectProductoAgregarModal = document.getElementById(
    "select_producto_agregar_modal",
  );
  const btnAgregarProductoDetalleModal = document.getElementById(
    "btnAgregarProductoDetalleModal",
  );
  const cuerpoTablaDetalleCompraModal = document.getElementById(
    "cuerpoTablaDetalleCompraModal",
  );
  const subtotalGeneralDisplayModal = document.getElementById(
    "subtotal_general_display_modal",
  );
  const subtotalGeneralInputModal = document.getElementById(
    "subtotal_general_input_modal",
  );
  const descuentoPorcentajeInputModal = document.getElementById(
    "descuento_porcentaje_input_modal",
  );
  const montoDescuentoDisplayModal = document.getElementById(
    "monto_descuento_display_modal",
  );
  const montoDescuentoInputModal = document.getElementById(
    "monto_descuento_input_modal",
  );
  const totalGeneralDisplayModal = document.getElementById(
    "total_general_display_modal",
  );
  const totalGeneralInputModal = document.getElementById(
    "total_general_input_modal",
  );
  const observacionesCompraModal = document.getElementById(
    "observaciones_compra_modal",
  );
  const mensajeErrorFormCompraModal = document.getElementById(
    "mensajeErrorFormCompraModal",
  );

  // Event listener para botones de Editar
    document.getElementById("TablaCompras").addEventListener("click", function (e) {
        const target = e.target;
        if (target.closest(".ver-compra-btn")) {
            const idCompra = target.closest(".ver-compra-btn").getAttribute("data-idcompra");
            if (idCompra) {
               verCompra(idCompra);
            }
        }else if (target.closest(".eliminar-compra-btn")) {
             const idCompra = target.closest(".eliminar-compra-btn").getAttribute("data-idcompra");
             if (idCompra) {
                editarCompra(idCompra);
            }
         }
    });

async function editarCompra(idcompra) {
    try {
        const response = await fetch(`compras/getDetalleCompra/${idcompra}`);
        const data = await response.json();

        if (!data.status) {
            Swal.fire("Error", data.message || "No se pudo obtener el detalle.", "error");
            return;
        }

        // 1. Llena los campos de la cabecera
        document.getElementById("fecha_compra_modal").value = data.compra.fecha;
        document.getElementById("idmoneda_general_compra_modal").value = data.compra.idmoneda_general;
        document.getElementById("observaciones_compra_modal").value = data.compra.observaciones_compra || "";

        // 2. Proveedor
        document.getElementById("idproveedor_seleccionado_modal").value = data.compra.idproveedor;
        document.getElementById("proveedor_seleccionado_info_modal").innerHTML = `Sel: <strong>${data.compra.proveedor}</strong>`;
        document.getElementById("proveedor_seleccionado_info_modal").classList.remove("hidden");

        // 3. Detalle de productos
        detalleCompraItemsModal = data.detalle.map(item => ({
            idproducto: item.idproducto,
            nombre: item.descripcion_temporal_producto,
            idcategoria: item.idcategoria || 1, // Ajusta según tu modelo
            precio_unitario: parseFloat(item.precio_unitario_compra),
            idmoneda_item: item.idmoneda_detalle,
            simbolo_moneda_item: "", // Puedes obtenerlo si lo necesitas
            no_usa_vehiculo: false, // Ajusta si tienes este dato
            peso_vehiculo: parseFloat(item.peso_vehiculo) || 0,
            peso_bruto: parseFloat(item.peso_bruto) || 0,
            peso_neto_directo: parseFloat(item.peso_neto) || 0,
            cantidad_unidad: parseFloat(item.cantidad) || 1,
            subtotal_linea: parseFloat(item.subtotal_linea) || 0,
            subtotal_linea_bs: parseFloat(item.subtotal_linea) || 0,
        }));

        renderizarTablaDetalleModal();
        calcularTotalesGeneralesModal();

        // 4. Cambia el modo del modal a "Editar"
        document.getElementById("btnGuardarCompraModal").textContent = "Actualizar Compra";
        document.getElementById("btnGuardarCompraModal").onclick = function () {
            guardarEdicionCompra(idcompra);
        };

        // 5. Abre el modal
        abrirModalNuevaCompra();

    } catch (error) {
        Swal.fire("Error", "Ocurrió un error al obtener la compra.", "error");
    }
}


// Mostrar el modal con los datos
  async function verCompra(idcompra) {
      try {
          const response = await fetch(`compras/getDetalleCompra/${idcompra}`);
          const data = await response.json();  
          console.log(data);  

          if (!data.status) {
              Swal.fire("Error", data.message || "No se pudo obtener el detalle.", "error");
              return;
          }

          let html = `
              <div class="mb-4">
                  <strong>Nro. Compra:</strong> ${data.compra.nro_compra}<br>
                  <strong>Fecha:</strong> ${data.compra.fecha}<br>
                  <strong>Proveedor:</strong> ${data.compra.proveedor}<br>
                  <strong>Total:</strong> ${data.compra.total_general}<br>
                  <strong>Observaciones:</strong> ${data.compra.observaciones_compra || '-'}
              </div>
              <hr>
              <div class="mt-4">
                  <strong>Detalle de Productos:</strong>
                  <div class="overflow-x-auto">
                  <table class="w-full text-xs mt-2 border">
                      <thead class="bg-gray-100">
                          <tr>
                              <th class="px-2 py-1 border">Producto</th>
                              <th class="px-2 py-1 border">Cantidad</th>
                              <th class="px-2 py-1 border">Precio U.</th>
                              <th class="px-2 py-1 border">Subtotal</th>
                          </tr>
                      </thead>
                      <tbody>
          `;
          data.detalle.forEach(item => {
              html += `
                  <tr>
                      <td class="px-2 py-1 border">${item.descripcion_temporal_producto}</td>
                      <td class="px-2 py-1 border">${item.cantidad}</td>
                      <td class="px-2 py-1 border">${item.precio_unitario_compra}</td>
                      <td class="px-2 py-1 border">${item.subtotal_linea}</td>
                  </tr>
              `;
          });
          html += `
                      </tbody>
                  </table>
                  </div>
              </div>
          `;

          document.getElementById("contenidoModalDetalleCompra").innerHTML = html;
          abrirModalDetalleCompra();
      } catch (error) {
          Swal.fire("Error", "Ocurrió un error al obtener el detalle.", "error");
      }
  }

  // Funciones para abrir/cerrar el modal
  function abrirModalDetalleCompra() {
      const modal = document.getElementById("modalDetalleCompra");
      modal.classList.remove("opacity-0", "pointer-events-none");
      document.body.classList.add("overflow-hidden");
  }
  function cerrarModalDetalleCompra() {
      const modal = document.getElementById("modalDetalleCompra");
      modal.classList.add("opacity-0", "pointer-events-none");
      document.body.classList.remove("overflow-hidden");
  }

  // Eventos para cerrar el modal
  document.getElementById("btnCerrarModalDetalleCompra").addEventListener("click", cerrarModalDetalleCompra);
  document.getElementById("btnCerrarModalDetalleCompra2").addEventListener("click", cerrarModalDetalleCompra);
  document.getElementById("modalDetalleCompra").addEventListener("click", function(e) {
      if (e.target === this) cerrarModalDetalleCompra();
  });


  let detalleCompraItemsModal = []; // Array para los ítems del detalle en el modal

  function abrirModalNuevaCompra() {
    // Resetear formulario y cargar datos iniciales
    fechaCompraModal.valueAsDate = new Date();
    fechaActualCompra = fechaCompraModal.value;
    cargarTasasPorFecha(fechaActualCompra);
    formNuevaCompraModal.reset();
    detalleCompraItemsModal = [];
    renderizarTablaDetalleModal(); // Limpia la tabla de detalles
    hiddenIdProveedorModal.value = "";
    divInfoProveedorModal.innerHTML = "";
    divInfoProveedorModal.classList.add("hidden");
    mensajeErrorFormCompraModal.textContent = "";
    fechaCompraModal.valueAsDate = new Date(); // Fecha actual por defecto

    cargarMonedasParaModal();
    cargarProductosParaModal();

    modalNuevaCompra.classList.remove("opacity-0", "pointer-events-none");
    document.body.classList.add("overflow-hidden"); // Evitar scroll del fondo
  }

  function cerrarModalNuevaCompra() {
    modalNuevaCompra.classList.add("opacity-0", "pointer-events-none");
    document.body.classList.remove("overflow-hidden");
  }

  let tasasMonedas = {}; // { USD: 93.58, EUR: 100.00, ... }
  let fechaActualCompra = null;

  async function cargarTasasPorFecha(fecha) {
  const divTasa = document.getElementById("tasaDelDiaInfo");
  divTasa.textContent = "Cargando tasas del día...";
  try {
    const response = await fetch(`compras/getTasasMonedasPorFecha?fecha=${encodeURIComponent(fecha)}`);
    const data = await response.json();
    if (data.status && data.tasas && Object.keys(data.tasas).length > 0) {
      tasasMonedas = data.tasas;
      // Mostrar texto
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


  // Al cambiar la fecha de compra
  fechaCompraModal.addEventListener("change", function () {
    fechaActualCompra = this.value;
    if (fechaActualCompra) {
      cargarTasasPorFecha(fechaActualCompra);
    }
  });

  if (btnAbrirModalNuevaCompra)
    btnAbrirModalNuevaCompra.addEventListener("click", abrirModalNuevaCompra);
  if (btnCerrarModalNuevaCompra)
    btnCerrarModalNuevaCompra.addEventListener("click", cerrarModalNuevaCompra);
  if (btnCancelarCompraModal)
    btnCancelarCompraModal.addEventListener("click", cerrarModalNuevaCompra);

  // Cerrar modal si se hace clic fuera del contenido (en el overlay)
  modalNuevaCompra.addEventListener("click", (e) => {
    if (e.target === modalNuevaCompra) {
      cerrarModalNuevaCompra();
    }
  });

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

  function calcularTotalesGeneralesModal() {
    let subtotalGeneralBs = 0;
    detalleCompraItemsModal.forEach((item) => {
      subtotalGeneralBs += parseFloat(item.subtotal_linea_bs) || 0;
    });

    subtotalGeneralDisplayModal.value = `Bs. ${subtotalGeneralBs.toFixed(2)}`;
    subtotalGeneralInputModal.value = subtotalGeneralBs.toFixed(2);

    const descuentoPorcentaje = parseFloat(descuentoPorcentajeInputModal.value) || 0;
    const montoDescuento = (subtotalGeneralBs * descuentoPorcentaje) / 100;
    montoDescuentoDisplayModal.value = `Bs. ${montoDescuento.toFixed(2)}`;
    montoDescuentoInputModal.value = montoDescuento.toFixed(2);

    const totalGeneral = subtotalGeneralBs - montoDescuento;
    totalGeneralDisplayModal.value = `Bs. ${totalGeneral.toFixed(2)}`;
    totalGeneralInputModal.value = totalGeneral.toFixed(2);
  }

  async function cargarProductosParaModal() {
    selectProductoAgregarModal.innerHTML =
      '<option value="">Cargando...</option>';
    try {
      const response = await fetch(
        `compras/getListaProductosParaFormulario`,
      ); // Necesitarás este endpoint
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
        option.dataset.idmoneda = producto.idmoneda_producto || "";
        option.textContent = `${producto.nombre_producto} (${producto.nombre_categoria})`;
        selectProductoAgregarModal.appendChild(option);
      });
    } catch (error) {
      console.error("Error al cargar productos:", error);
      selectProductoAgregarModal.innerHTML =
        '<option value="">Error al cargar</option>';
    }
  }

  // Autocompletar para buscar proveedor en el MODAL
  const inputCriterioProveedorModal = document.getElementById('inputCriterioProveedorModal');
  const btnBuscarProveedorModal = document.getElementById('btnBuscarProveedorModal');
  const listaResultadosProveedorModal = document.getElementById('listaResultadosProveedorModal');

  if (btnBuscarProveedorModal && inputCriterioProveedorModal) {
    btnBuscarProveedorModal.addEventListener('click', async function() {
      const termino = inputCriterioProveedorModal.value.trim();
      if (termino.length < 2) { // O la longitud mínima que desees
        Swal.fire("Atención", "Ingrese al menos 2 caracteres para buscar.", "warning");
        return;
      }

      listaResultadosProveedorModal.innerHTML = '<div class="p-2 text-xs text-gray-500">Buscando...</div>';
      listaResultadosProveedorModal.classList.remove('hidden');

      try {
        const response = await fetch(`compras/buscarProveedores?term=${encodeURIComponent(termino)}`);
        if (!response.ok) {
          throw new Error('Error en la respuesta del servidor');
        }
        const proveedores = await response.json();

        listaResultadosProveedorModal.innerHTML = ''; // Limpiar
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
              inputCriterioProveedorModal.value = this.textContent; // Opcional: llenar el input con el seleccionado
              listaResultadosProveedorModal.classList.add('hidden');
              listaResultadosProveedorModal.innerHTML = ''; // Limpiar después de seleccionar
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

  // Lógica para agregar productos al detalle en el MODAL
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
        precio_unitario:
          parseFloat(selectedOption.dataset.precio) || 0,
        idmoneda_item:
          selectedOption.dataset.idmoneda || selectMonedaGeneralModal.value,
        simbolo_moneda_item:
          selectedOption.dataset.monedaSimbolo || simboloMonedaGeneral,
        // Campos específicos se llenarán en la tabla
        no_usa_vehiculo: false, // Default
        peso_vehiculo: 0,
        peso_bruto: 0,
        peso_neto_directo: 0,
        cantidad_unidad: 1, // Default para productos por unidad
      };
      detalleCompraItemsModal.push(item);
      renderizarTablaDetalleModal();
      selectProductoAgregarModal.value = "";
    });
  }

  function renderizarTablaDetalleModal() {
    cuerpoTablaDetalleCompraModal.innerHTML = "";
    detalleCompraItemsModal.forEach((item, index) => {
      console.log(item);
      const tr = document.createElement("tr");
      tr.classList.add("border-b", "hover:bg-gray-50");
      tr.dataset.index = index;

      let infoEspecificaHtml = "";
      if (item.idcategoria === 1) {
        // Materiales por Peso
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
    document
      .querySelectorAll("#cuerpoTablaDetalleCompraModal tr")
      .forEach((row) => {
        const btnUltimoPesoBruto = row.querySelector(".btnUltimoPesoRomanaBruto");
        if (btnUltimoPesoBruto) {
          btnUltimoPesoBruto.addEventListener("click", async function () {
            try {
              const response = await fetch("compras/getUltimoPesoRomana");
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
              const response = await fetch("compras/getUltimoPesoRomana");
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

        const index = parseInt(row.dataset.index);
        if (isNaN(index) || index >= detalleCompraItemsModal.length) return; // Safety check
        const item = detalleCompraItemsModal[index];

        const cbNoUsaVehiculo = row.querySelector(
          ".no_usa_vehiculo_cb_modal",
        );
        if (cbNoUsaVehiculo) {
          cbNoUsaVehiculo.addEventListener("change", function (e) {
            item.no_usa_vehiculo = e.target.checked;
            const camposPesoVehiculo = row.querySelector(
              ".campos_peso_vehiculo_modal",
            );
            const campoPesoNetoDirecto = row.querySelector(
              ".campo_peso_neto_directo_modal",
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
            ".peso_vehiculo_modal, .peso_bruto_modal, .peso_neto_directo_modal, .cantidad_unidad_modal, .precio_unitario_item_modal",
          )
          .forEach((input) => {
            input.addEventListener("input", function (e) {
              const fieldName = e.target.classList.contains(
                "peso_vehiculo_modal",
              )
                ? "peso_vehiculo"
                : e.target.classList.contains("peso_bruto_modal")
                  ? "peso_bruto"
                  : e.target.classList.contains("peso_neto_directo_modal")
                    ? "peso_neto_directo"
                    : e.target.classList.contains("cantidad_unidad_modal")
                      ? "cantidad_unidad"
                      : "precio_unitario";
              item[fieldName] = parseFloat(e.target.value) || 0;
              actualizarCalculosFilaModal(row, item);
            });
          });

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
      ".peso_neto_calculado_display_modal",
    );
    if (pesoNetoDisplay) {
      pesoNetoDisplay.textContent = calcularPesoNetoItemModal(item).toFixed(2);
    }
    rowElement.querySelector(
      ".subtotal_linea_display_modal",
    ).textContent = `${item.idmoneda_item} ${calcularSubtotalLineaItemModal(item).toFixed(2)}`;
    calcularTotalesGeneralesModal();
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

  async function cargarMonedasParaModal() {
    selectMonedaGeneralModal.innerHTML = '<option value="">Cargando...</option>';
    try {
      const response = await fetch('compras/getListaMonedasParaFormulario');
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

  function convertirAMonedaBase(monto, idmoneda) {
    if (idmoneda == 3) return monto;
    const tasa = tasasMonedas[idmoneda] || 1;
    console.log("Convertir a moneda base: ", idmoneda);
    console.log("Monto: ", monto);  
    console.log("Tasas: ", tasasMonedas);
    console.log("Tasa de moneda: ", tasasMonedas[idmoneda]);
    console.log("Monto convertido: ", monto * tasa);
    return monto * tasa;
  }

  // (Eliminada la definición duplicada de calcularSubtotalLineaItemModal)

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

  if (descuentoPorcentajeInputModal)
    descuentoPorcentajeInputModal.addEventListener(
      "input",
      calcularTotalesGeneralesModal,
    );
  if (selectMonedaGeneralModal)
    selectMonedaGeneralModal.addEventListener(
      "change",
      calcularTotalesGeneralesModal,
    ); // También al cambiar moneda general

  // Guardar Compra desde el MODAL
  if (btnGuardarCompraModal) {
    btnGuardarCompraModal.addEventListener("click", async function () {
      // Validaciones
      mensajeErrorFormCompraModal.textContent = "";
      if (!hiddenIdProveedorModal.value) {
        mensajeErrorFormCompraModal.textContent =
          "Debe seleccionar un proveedor.";
        return;
      }
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

      const formData = new FormData(formNuevaCompraModal); // Recoge datos del form
      formData.append(
        "productos_detalle",
        JSON.stringify(detalleCompraItemsModal),
      );
      // Asegurarse que los inputs de totales también se envíen si no están en el form
      formData.set(
        "subtotal_general_input",
        subtotalGeneralInputModal.value,
      );
      formData.set(
        "descuento_porcentaje_input",
        descuentoPorcentajeInputModal.value,
      );
      formData.set(
        "monto_descuento_input",
        montoDescuentoInputModal.value,
      );
      formData.set("total_general_input", totalGeneralInputModal.value);

      btnGuardarCompraModal.disabled = true;
      btnGuardarCompraModal.textContent = "Guardando...";

      try {
        const response = await fetch(`compras/setCompra`, {
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
          $("#TablaCompras").DataTable().ajax.reload(); // Recargar DataTable
        } else {
          mensajeErrorFormCompraModal.textContent =
            data.message || "Error al guardar.";
        }
      } catch (error) {
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

  const modalProveedor = document.getElementById("proveedorModal");
  const formProveedor = document.getElementById("proveedorForm");
  const modalTitulo = document.getElementById("modalProveedorTitulo");
  const btnSubmitProveedor = document.getElementById("btnSubmitProveedor");
  const inputIdPersona = document.getElementById("idproveedor");

  window.abrirModalProveedor = function (titulo = "Registrar Proveedor", formAction = "proveedores/createProveedor") {
    formProveedor.reset(); // 
    inputIdPersona.value = ""; 
    modalTitulo.textContent = titulo;
    formProveedor.setAttribute("data-action", formAction); 
    btnSubmitProveedor.textContent = "Registrar";
    modalProveedor.classList.remove("opacity-0", "pointer-events-none");
  };

  // Cerrar modal de proveedor
window.cerrarModalProveedor = function () {
  modalProveedor.classList.add("opacity-0", "pointer-events-none");
  formProveedor.reset();
  inputIdPersona.value = "";
};

// Enviar formulario FORMULARIO (Crear o Actualizar)
formProveedor.addEventListener("submit", function (e) {
  e.preventDefault();
  const formData = new FormData(this);
  const actionUrl = "proveedores/createProveedorinCompras";
  const method = "POST";

  if (!actionUrl) {
    Swal.fire("Error Interno", "URL de acción no definida para el formulario.", "error");
    console.error("El atributo data-action del formulario está vacío o no existe.");
    return;
  }

  // Validaciones básicas
  const nombre = formData.get('nombre');
  const identificacion = formData.get('identificacion');
  const telefono_principal = formData.get('telefono_principal');

  if (!nombre || !identificacion || !telefono_principal) {
    Swal.fire("Atención", "Nombre, Identificación y Teléfono son obligatorios.", "warning");
    return;
  }

  fetch(actionUrl, {
    method: method,
    body: formData // Enviar FormData directamente
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
        // Obtener los datos completos del proveedor recién creado
        try {
          const response = await fetch(`proveedores/getProveedorById/${encodeURIComponent(result.idproveedor)}`);
          if (!response.ok) throw new Error('No se pudo obtener el proveedor');
          const proveedor = await response.json();
          // Llenar los campos como si se hubiera seleccionado desde el autocompletado
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

})
