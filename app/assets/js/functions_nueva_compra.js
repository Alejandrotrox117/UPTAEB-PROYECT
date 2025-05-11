document.addEventListener("DOMContentLoaded", function () {
  // Autocompletar para buscar proveedor
  $("#buscar_proveedor").autocomplete({
    source: function (request, response) {
      $.ajax({
        url: "/Compras/buscarProveedores", // Ajusta la URL base si es necesario
        dataType: "json",
        data: {
          term: request.term,
        },
        success: function (data) {
          response(
            data.map((item) => ({
              label: `${item.nombre} ${item.apellido || ""} (${
                item.identificacion
              })`,
              value: item.idproveedor,
              nombre: `${item.nombre} ${item.apellido || ""}`,
              identificacion: item.identificacion,
            })),
          );
        },
      });
    },
    minLength: 2,
    select: function (event, ui) {
      $("#idproveedor_seleccionado").val(ui.item.value);
      $("#proveedor_seleccionado_info")
        .html(
          `Proveedor: <strong>${ui.item.nombre}</strong> (ID: ${ui.item.identificacion})`,
        )
        .removeClass("hidden");
      $("#buscar_proveedor").val(ui.item.label); // Muestra el label en el input
      return false; // Previene que se ponga el value (ID) en el input
    },
    change: function (event, ui) {
      // Si se borra el input o no se selecciona nada, limpiar
      if (!ui.item) {
        $("#idproveedor_seleccionado").val("");
        $("#proveedor_seleccionado_info").html("").addClass("hidden");
        // Opcional: $("#buscar_proveedor").val("");
      }
    },
  });

  // Modal Nuevo Proveedor
  const modalNuevoProveedor = document.getElementById("modalNuevoProveedor");
  const btnAbrirModalNuevoProveedor = document.getElementById(
    "btnAbrirModalNuevoProveedor",
  );
  const btnCerrarModalNuevoProveedor = document.getElementById(
    "btnCerrarModalNuevoProveedor",
  );
  const btnCancelarModalNuevoProveedor = document.getElementById(
    "btnCancelarModalNuevoProveedor",
  );

  if (btnAbrirModalNuevoProveedor) {
    btnAbrirModalNuevoProveedor.addEventListener("click", () =>
      modalNuevoProveedor.classList.remove(
        "opacity-0",
        "pointer-events-none",
      ),
    );
  }
  const cerrarModalProv = () =>
    modalNuevoProveedor.classList.add("opacity-0", "pointer-events-none");
  if (btnCerrarModalNuevoProveedor)
    btnCerrarModalNuevoProveedor.addEventListener("click", cerrarModalProv);
  if (btnCancelarModalNuevoProveedor)
    btnCancelarModalNuevoProveedor.addEventListener("click", cerrarModalProv);

  // Registrar Nuevo Proveedor (AJAX)
  const formNuevoProveedor = document.getElementById("formNuevoProveedor");
  if (formNuevoProveedor) {
    formNuevoProveedor.addEventListener("submit", function (e) {
      e.preventDefault();
      const formData = new FormData(formNuevoProveedor);
      fetch("/Compras/registrarNuevoProveedor", {
        // Ajusta la URL base
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          alert(data.message);
          if (data.status) {
            $("#idproveedor_seleccionado").val(data.idproveedor);
            $("#proveedor_seleccionado_info")
              .html(
                `Proveedor: <strong>${formData.get(
                  "nombre_proveedor_nuevo",
                )} ${formData.get(
                  "apellido_proveedor_nuevo",
                )}</strong> (ID: ${formData.get(
                  "identificacion_proveedor_nuevo",
                )})`,
              )
              .removeClass("hidden");
            $("#buscar_proveedor").val(
              `${formData.get("nombre_proveedor_nuevo")} ${formData.get(
                "apellido_proveedor_nuevo",
              )} (${formData.get("identificacion_proveedor_nuevo")})`,
            );
            formNuevoProveedor.reset();
            cerrarModalProv();
          }
        })
        .catch((error) => console.error("Error:", error));
    });
  }

  // Lógica para agregar productos al detalle
  const btnAgregarProductoDetalle = document.getElementById(
    "btnAgregarProductoDetalle",
  );
  const selectProductoAgregar = document.getElementById(
    "select_producto_agregar",
  );
  const cuerpoTablaDetalleCompra = document.getElementById(
    "cuerpoTablaDetalleCompra",
  );
  let detalleCompraItems = []; // Array para guardar los items del detalle

  if (btnAgregarProductoDetalle && selectProductoAgregar) {
    btnAgregarProductoDetalle.addEventListener("click", function () {
      const selectedOption =
        selectProductoAgregar.options[selectProductoAgregar.selectedIndex];
      if (!selectedOption.value) {
        alert("Seleccione un producto.");
        return;
      }

      const idproducto = selectedOption.value;
      // Evitar duplicados (simple check por idproducto)
      if (detalleCompraItems.find((item) => item.idproducto === idproducto)) {
        alert("Este producto ya ha sido agregado.");
        return;
      }

      const nombreProducto = selectedOption.dataset.nombre;
      const idcategoria = parseInt(selectedOption.dataset.idcategoria);
      const precioReferencia = parseFloat(selectedOption.dataset.precio) || 0;
      const idMonedaProducto = selectedOption.dataset.idmoneda || "";
      const simboloMonedaProducto =
        selectedOption.dataset.monedaSimbolo ||
        $("#idmoneda_general_compra option:selected").data("simbolo") ||
        "$";

      const item = {
        idproducto: idproducto,
        nombre: nombreProducto,
        idcategoria: idcategoria,
        precio_unitario: precioReferencia,
        idmoneda_item:
          idMonedaProducto || $("#idmoneda_general_compra").val(),
        simbolo_moneda_item: simboloMonedaProducto,
        // Campos específicos se llenarán en la tabla
      };
      detalleCompraItems.push(item);
      renderizarTablaDetalle();
      selectProductoAgregar.value = ""; // Reset select
    });
  }

  function renderizarTablaDetalle() {
    cuerpoTablaDetalleCompra.innerHTML = ""; // Limpiar tabla
    detalleCompraItems.forEach((item, index) => {
      const tr = document.createElement("tr");
      tr.classList.add("border-b");
      tr.dataset.index = index; // Para identificar la fila

      let infoEspecificaHtml = "";
      if (item.idcategoria === 1) {
        // Materiales por Peso
        infoEspecificaHtml = `
          <div class="space-y-1 text-xs">
            <div>
              <label class="flex items-center">
                <input type="checkbox" class="form-checkbox h-4 w-4 mr-1 no_usa_vehiculo_cb" ${item.no_usa_vehiculo ? "checked" : ""}> No usa vehículo
              </label>
            </div>
            <div class="campos_peso_vehiculo ${item.no_usa_vehiculo ? "hidden" : ""}">
              P. Vehículo: <input type="number" step="0.01" class="input-xs peso_vehiculo" value="${item.peso_vehiculo || ""}" placeholder="0.00">
              P. Bruto: <input type="number" step="0.01" class="input-xs peso_bruto" value="${item.peso_bruto || ""}" placeholder="0.00">
            </div>
            <div class="campo_peso_neto_directo ${!item.no_usa_vehiculo ? "hidden" : ""}">
              P. Neto: <input type="number" step="0.01" class="input-xs peso_neto_directo" value="${item.peso_neto_directo || ""}" placeholder="0.00">
            </div>
            P. Neto Calc: <strong class="peso_neto_calculado_display">${calcularPesoNetoItem(item).toFixed(2)}</strong>
          </div>`;
      } else {
        // Productos por Unidad
        infoEspecificaHtml = `
          <div class="text-xs">
            Cantidad: <input type="number" step="0.01" class="input-xs cantidad_unidad" value="${item.cantidad_unidad || "1"}" placeholder="1">
          </div>`;
      }

      tr.innerHTML = `
        <td class="py-2 pr-2">${item.nombre}</td>
        <td class="py-2 pr-2">${infoEspecificaHtml}</td>
        <td class="py-2 pr-2">
            ${item.simbolo_moneda_item} <input type="number" step="0.01" class="input-sm precio_unitario_item" value="${item.precio_unitario.toFixed(2)}" placeholder="0.00">
        </td>
        <td class="py-2 pr-2 subtotal_linea_display">${item.simbolo_moneda_item} ${calcularSubtotalLineaItem(item).toFixed(2)}</td>
        <td class="py-2"><button type="button" class="text-red-500 hover:text-red-700 btnEliminarItemDetalle">Eliminar</button></td>
      `;
      cuerpoTablaDetalleCompra.appendChild(tr);
    });
    // Añadir listeners a los nuevos inputs y botones
    addEventListenersToDetalleInputs();
    calcularTotalesGenerales();
  }

  function addEventListenersToDetalleInputs() {
    document.querySelectorAll("#cuerpoTablaDetalleCompra tr").forEach((row) => {
      const index = parseInt(row.dataset.index);
      const item = detalleCompraItems[index];

      // Checkbox no_usa_vehiculo
      const cbNoUsaVehiculo = row.querySelector(".no_usa_vehiculo_cb");
      if (cbNoUsaVehiculo) {
        cbNoUsaVehiculo.addEventListener("change", function (e) {
          item.no_usa_vehiculo = e.target.checked;
          // Mostrar/ocultar campos correspondientes
          const camposPesoVehiculo = row.querySelector(".campos_peso_vehiculo");
          const campoPesoNetoDirecto = row.querySelector(
            ".campo_peso_neto_directo",
          );
          if (e.target.checked) {
            camposPesoVehiculo.classList.add("hidden");
            campoPesoNetoDirecto.classList.remove("hidden");
            item.peso_vehiculo = 0; // Resetear si se ocultan
            item.peso_bruto = 0;
          } else {
            camposPesoVehiculo.classList.remove("hidden");
            campoPesoNetoDirecto.classList.add("hidden");
            item.peso_neto_directo = 0;
          }
          actualizarCalculosFila(row, item);
        });
      }

      // Inputs de peso y cantidad
      row.querySelectorAll(".peso_vehiculo, .peso_bruto, .peso_neto_directo, .cantidad_unidad, .precio_unitario_item").forEach((input) => {
        input.addEventListener("input", function (e) {
          const fieldName = e.target.classList.contains("peso_vehiculo")
            ? "peso_vehiculo"
            : e.target.classList.contains("peso_bruto")
              ? "peso_bruto"
              : e.target.classList.contains("peso_neto_directo")
                ? "peso_neto_directo"
                : e.target.classList.contains("cantidad_unidad")
                  ? "cantidad_unidad"
                  : "precio_unitario";
          item[fieldName] = parseFloat(e.target.value) || 0;
          actualizarCalculosFila(row, item);
        });
      });

      // Botón eliminar
      row.querySelector(".btnEliminarItemDetalle").addEventListener("click", function () {
          detalleCompraItems.splice(index, 1);
          renderizarTablaDetalle(); // Re-renderizar para actualizar índices y todo
        });
    });
  }

  function actualizarCalculosFila(rowElement, item) {
    const pesoNetoDisplay = rowElement.querySelector(
      ".peso_neto_calculado_display",
    );
    if (pesoNetoDisplay) {
      pesoNetoDisplay.textContent = calcularPesoNetoItem(item).toFixed(2);
    }
    rowElement.querySelector(".subtotal_linea_display").textContent = `${item.simbolo_moneda_item} ${calcularSubtotalLineaItem(item).toFixed(2)}`;
    calcularTotalesGenerales();
  }

  function calcularPesoNetoItem(item) {
    if (item.idcategoria === 1) {
      // Materiales por Peso
      if (item.no_usa_vehiculo) {
        return parseFloat(item.peso_neto_directo) || 0;
      } else {
        const bruto = parseFloat(item.peso_bruto) || 0;
        const vehiculo = parseFloat(item.peso_vehiculo) || 0;
        return Math.max(0, bruto - vehiculo);
      }
    }
    return 0; // No aplica para otras categorías
  }

  function calcularSubtotalLineaItem(item) {
    const precioUnitario = parseFloat(item.precio_unitario) || 0;
    let cantidadBase = 0;
    if (item.idcategoria === 1) {
      // Materiales por Peso
      cantidadBase = calcularPesoNetoItem(item);
    } else {
      // Productos por Unidad
      cantidadBase = parseFloat(item.cantidad_unidad) || 0;
    }
    item.subtotal_linea = cantidadBase * precioUnitario; // Guardar en el item
    return item.subtotal_linea;
  }

  function calcularTotalesGenerales() {
    let subtotalGeneral = 0;
    const monedaGeneralSimbolo =
      $("#idmoneda_general_compra option:selected").data("simbolo") || "$";

    detalleCompraItems.forEach((item) => {
      // Aquí necesitarías conversión de moneda si los items tienen monedas diferentes a la general
      // Por simplicidad, asumimos que todos los subtotales de línea ya están en la moneda general o se convierten antes.
      // Para este ejemplo, sumamos directamente. Si hay diferentes monedas, esto es incorrecto.
      // Deberías tener un campo idmoneda_detalle en cada item y convertir a idmoneda_general.
      subtotalGeneral += parseFloat(item.subtotal_linea) || 0;
    });

    $("#subtotal_general_display").val(
      `${monedaGeneralSimbolo} ${subtotalGeneral.toFixed(2)}`,
    );
    $("#subtotal_general_input").val(subtotalGeneral.toFixed(2));

    const descuentoPorcentaje =
      parseFloat($("#descuento_porcentaje_input").val()) || 0;
    const montoDescuento = (subtotalGeneral * descuentoPorcentaje) / 100;
    $("#monto_descuento_display").val(
      `${monedaGeneralSimbolo} ${montoDescuento.toFixed(2)}`,
    );
    $("#monto_descuento_input").val(montoDescuento.toFixed(2));

    const totalGeneral = subtotalGeneral - montoDescuento;
    $("#total_general_display").val(
      `${monedaGeneralSimbolo} ${totalGeneral.toFixed(2)}`,
    );
    $("#total_general_input").val(totalGeneral.toFixed(2));
  }

  // Recalcular totales si cambia el descuento
  $("#descuento_porcentaje_input, #idmoneda_general_compra").on(
    "input change",
    calcularTotalesGenerales,
  );

  // Guardar Compra
  const formNuevaCompra = document.getElementById("formNuevaCompra");
  if (formNuevaCompra) {
    formNuevaCompra.addEventListener("submit", function (e) {
      e.preventDefault();

      if (!$("#idproveedor_seleccionado").val()) {
        alert("Debe seleccionar un proveedor.");
        return;
      }
      if (detalleCompraItems.length === 0) {
        alert("Debe agregar al menos un producto al detalle.");
        return;
      }
      if (!$("#idmoneda_general_compra").val()) {
        alert("Debe seleccionar una moneda general para la compra.");
        return;
      }

      // Validar que todos los items tengan datos necesarios (ej. precio > 0, cantidad/peso > 0)
      for (const item of detalleCompraItems) {
        const precio = parseFloat(item.precio_unitario) || 0;
        let cantidadValida = false;
        if (item.idcategoria === 1) { // Peso
            cantidadValida = calcularPesoNetoItem(item) > 0;
        } else { // Unidad
            cantidadValida = (parseFloat(item.cantidad_unidad) || 0) > 0;
        }
        if (precio <= 0 || !cantidadValida) {
            alert(`El producto "${item.nombre}" tiene precio o cantidad/peso inválido.`);
            return;
        }
      }


      const formData = new FormData(formNuevaCompra);
      // Agregar el detalle de compra como JSON string
      formData.append("productos_detalle", JSON.stringify(detalleCompraItems));

      // Deshabilitar botón para evitar doble envío
      const btnGuardar = document.getElementById("btnGuardarCompra");
      btnGuardar.disabled = true;
      btnGuardar.textContent = "Guardando...";

      fetch("/Compras/setCompra", {
        // Ajusta la URL base
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          alert(data.message);
          if (data.status) {
            // Limpiar formulario o redirigir
            formNuevaCompra.reset();
            detalleCompraItems = [];
            renderizarTablaDetalle();
            $("#proveedor_seleccionado_info").html("").addClass("hidden");
            $("#buscar_proveedor").val("");
            // Opcional: redirigir a la lista de compras o a ver la compra creada
            // window.location.href = "/Compras";
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("Ocurrió un error al guardar la compra.");
        })
        .finally(() => {
          btnGuardar.disabled = false;
          btnGuardar.textContent = "Guardar Compra";
        });
    });
  }

  // Botón Cancelar
  const btnCancelarCompra = document.getElementById("btnCancelarCompra");
  if (btnCancelarCompra) {
    btnCancelarCompra.addEventListener("click", () => {
      if (confirm("¿Está seguro de cancelar y perder los datos ingresados?")) {
        formNuevaCompra.reset();
        detalleCompraItems = [];
        renderizarTablaDetalle();
        $("#proveedor_seleccionado_info").html("").addClass("hidden");
        $("#buscar_proveedor").val("");
        // Opcional: redirigir
        // window.location.href = "/Compras";
      }
    });
  }
});

// Helper para inputs con clases específicas (ej. Tailwind)
// $(".input-xs").addClass("border border-gray-300 rounded px-1 py-0.5 text-xs w-20");
// $(".input-sm").addClass("border border-gray-300 rounded px-2 py-1 text-sm w-24");
// Asegúrate de tener estilos para estas clases o ajústalas.
