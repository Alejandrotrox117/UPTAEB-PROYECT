document.addEventListener("DOMContentLoaded", function () {
  // --- DataTable (se mantiene igual que antes) ---
  $("#TablaCompras").DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "compras/getComprasDataTable",
      type: "GET",
      dataSrc: "data",
    },
    columns: [
      { data: "nro_compra", title: "Nro" },
      { data: "fecha", title: "Fecha" },
      { data: "proveedor_nombre", title: "Proveedor" },
      { data: "total_general", title: "Total" },
      {
        data: null,
        title: "Acciones",
        orderable: false,
        render: function (data, type, row) {
          // Generar botones con íconos de Font Awesome
          return `
                <button class="editar-btn text-blue-500 hover:text-blue-700 p-1 rounded-full" data-idpersona="${row.idcompra}">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="eliminar-btn text-red-500 hover:text-red-700 p-1 rounded-full ml-2" data-idpersona="${row.idcompra}">
                  <i class="fas fa-trash"></i>
                </button>
              `;
        },
      },
    ],
    language: {
      decimal: "",
      emptyTable: "No hay información",
      info: "Mostrando _START_ a _END_ de _TOTAL_ Entradas",
      infoEmpty: "Mostrando 0 to 0 of 0 Entradas",
      infoFiltered: "(Filtrado de _MAX_ total entradas)",
      infoPostFix: "",
      thousands: ",",
      lengthMenu: "Mostrar _MENU_ Entradas",
      loadingRecords: "Cargando...",
      processing: "Procesando...",
      search: "Buscar:",
      zeroRecords: "Sin resultados encontrados",
      paginate: {
        first: "Primero",
        last: "Último",
        next: "Siguiente",
        previous: "Anterior",
      },
    },
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

  let detalleCompraItemsModal = []; // Array para los ítems del detalle en el modal

  function abrirModalNuevaCompra() {
    // Resetear formulario y cargar datos iniciales
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

  async function cargarMonedasParaModal() {
    selectMonedaGeneralModal.innerHTML =
      '<option value="">Cargando...</option>';
    try {
      const response = await fetch(
        'compras/getListaMonedasParaFormulario'
      );
      if (!response.ok) throw new Error("Error en respuesta de monedas");
      const monedas = await response.json();
      selectMonedaGeneralModal.innerHTML =
        '<option value="">Seleccione Moneda</option>';
      monedas.forEach((moneda) => {
        const option = document.createElement("option");
        option.value = moneda.idmoneda;
        if(moneda.codigo_moneda === "USD") {
          $simbolo = "$";
        }else if(moneda.codigo_moneda === "EUR") {
          $simbolo = "€";
        }else if(moneda.codigo_moneda === "VES") {
          $simbolo = "Bs.";
        }
        option.textContent = `${moneda.codigo_moneda} (${$simbolo})`;
        selectMonedaGeneralModal.appendChild(option);
      });
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
            alert("Ingrese al menos 2 caracteres para buscar.");
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
        alert("Seleccione un producto.");
        return;
      }
      const idproducto = selectedOption.value;
      if (
        detalleCompraItemsModal.find((item) => item.idproducto === idproducto)
      ) {
        alert("Este producto ya ha sido agregado.");
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
              P.Bru: <input type="number" step="0.01" class="w-1/4 border rounded-md px-1 py-1 text-s focus:outline-none focus:ring-2 focus:ring-green-500 peso_bruto_modal" value="${item.peso_bruto || ""}" placeholder="0.00">
               P.Veh: <input type="number" step="0.01" class="w-1/4 border rounded-md px-1 py-1 text-s focus:outline-none focus:ring-2 focus:ring-green-500 peso_vehiculo_modal" value="${item.peso_vehiculo || ""}" placeholder="0.00">
            </div>
            <div class="campo_peso_neto_directo_modal ${!item.no_usa_vehiculo ? "hidden" : ""}">
              P.Neto: <input type="number" step="0.01" class="w-1/4 border rounded-md px-1 py-1 text-s focus:outline-none focus:ring-2 focus:ring-green-500 peso_neto_directo_modal" value="${item.peso_neto_directo || ""}" placeholder="0.00">
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
            ${item.idmoneda_item} <input type="number" step="0.01" class="w-1/4 border rounded-md px-1 py-1 text-s focus:outline-none focus:ring-2 focus:ring-green-500 precio_unitario_item_modal" value="${item.precio_unitario.toFixed(2)}" placeholder="0.00">
        </td>
        <td class="py-1 px-1 text-xs subtotal_linea_display_modal">${item.idmoneda_item} ${calcularSubtotalLineaItemModal(item).toFixed(2)}</td>
        <td class="py-1 px-1"><button type="button" class="text-red-500 hover:text-red-700 btnEliminarItemDetalleModal text-xs">X</button></td>
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

  function calcularSubtotalLineaItemModal(item) {
    const precioUnitario = parseFloat(item.precio_unitario) || 0;
    let cantidadBase = 0;
    if (item.idcategoria === 1) {
      cantidadBase = calcularPesoNetoItemModal(item);
    } else {
      cantidadBase = parseFloat(item.cantidad_unidad) || 0;
    }
    item.subtotal_linea = cantidadBase * precioUnitario;
    return item.subtotal_linea;
  }

  function calcularTotalesGeneralesModal() {
    let subtotalGeneral = 0;
    const monedaGeneralOption =
      selectMonedaGeneralModal.options[
        selectMonedaGeneralModal.selectedIndex
      ];
    const monedaGeneralSimbolo = monedaGeneralOption
      ? monedaGeneralOption.dataset.idmoneda_item
      : "$";

    detalleCompraItemsModal.forEach((item) => {
      // TODO: Implementar conversión de moneda si item.idmoneda_item es diferente a selectMonedaGeneralModal.value
      subtotalGeneral += parseFloat(item.subtotal_linea) || 0;
    });

    subtotalGeneralDisplayModal.value = `${monedaGeneralSimbolo} ${subtotalGeneral.toFixed(2)}`;
    subtotalGeneralInputModal.value = subtotalGeneral.toFixed(2);

    const descuentoPorcentaje =
      parseFloat(descuentoPorcentajeInputModal.value) || 0;
    const montoDescuento = (subtotalGeneral * descuentoPorcentaje) / 100;
    montoDescuentoDisplayModal.value = `${monedaGeneralSimbolo} ${montoDescuento.toFixed(2)}`;
    montoDescuentoInputModal.value = montoDescuento.toFixed(2);

    const totalGeneral = subtotalGeneral - montoDescuento;
    totalGeneralDisplayModal.value = `${monedaGeneralSimbolo} ${totalGeneral.toFixed(2)}`;
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
        const response = await fetch(`${URL_BASE}/Compras/setCompra`, {
          method: "POST",
          body: formData,
        });
        const data = await response.json();
        alert(data.message); // O usar un sistema de notificaciones más elegante
        if (data.status) {
          cerrarModalNuevaCompra();
          $("#TablaCompras").DataTable().ajax.reload(); // Recargar DataTable
        } else {
          mensajeErrorFormCompraModal.textContent =
            data.message || "Error al guardar.";
        }
      } catch (error) {
        console.error("Error al guardar compra:", error);
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
}); 

function verCompra(idcompra) {
  alert("Ver detalle de la compra ID: " + idcompra);
}
