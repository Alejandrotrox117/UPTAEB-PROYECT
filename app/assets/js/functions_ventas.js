import {
  abrirModal,
  cerrarModal,
  obtenerPermisosUsuario,
} from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  validarCamposVacios,
  validarDetalleVenta,
  validarSelect,
  validarFecha,
  limpiarValidaciones,
  cargarSelect,
  validarCampo,
} from "./validaciones.js";

document.addEventListener("DOMContentLoaded", function () {
  // --- Inicialización General ---
  const PERMISOS_USUARIO = obtenerPermisosUsuario();
  window.PERMISOS_USUARIO = PERMISOS_USUARIO;

  inicializarDataTable();
  const ventaForm = document.getElementById("ventaForm");

  // --- Definición de Campos para Validación ---
  const camposCabeceraVenta = [
    {
      id: "fecha_venta_modal",
      name_attr: "fecha_venta",
      tipo: "date",
      mensajes: {
        vacio: "La fecha es obligatoria.",
        fechaPosterior: "La fecha no puede ser posterior a hoy.",
      },
    },
    {
      id: "idmoneda_general",
      name_attr: "idmoneda_general",
      tipo: "select",
      mensajes: {
        vacio: "Debe seleccionar una moneda.",
      },
    },
    {
      id: "observaciones",
      name_attr: "observaciones",
      tipo: "textarea",
      regex: expresiones.observaciones,
      mensajes: {
        formato: "Las observaciones no deben exceder los 50 caracteres.",
      },
    },
  ];

  const camposNuevoClienteEmbebido = [
    {
      id: "cedula_nuevo",
      name_attr: "cedula_nuevo_formulario",
      backend_key: "cedula",
      tipo: "input",
      regex: expresiones.cedula,
      mensajes: {
        vacio: "La cédula es obligatoria.",
        formato: "Formato de cédula inválido (ej: V-12345678).",
      },
    },
    {
      id: "nombre_nuevo",
      name_attr: "nombre_nuevo_formulario",
      backend_key: "nombre",
      tipo: "input",
      regex: expresiones.nombre,
      mensajes: {
        vacio: "El nombre es obligatorio.",
        formato: "El nombre debe tener entre 2 y 50 caracteres.", // Inconsistente con regex
      },
    },
    {
      id: "apellido_nuevo",
      name_attr: "apellido_nuevo_formulario",
      backend_key: "apellido",
      tipo: "input",
      regex: expresiones.apellido,
      mensajes: {
        vacio: "El apellido es obligatorio.",
        formato: "El apellido debe tener entre 2 y 30 caracteres.",
      },
    },
    {
      id: "telefono_principal_nuevo",
      name_attr: "telefono_principal_nuevo_formulario",
      backend_key: "telefono_principal",
      tipo: "input",
      regex: expresiones.telefono_principal,
      mensajes: {
        vacio: "El teléfono es obligatorio.",
        formato: "El teléfono debe tener 11 dígitos.",
      },
    },
    {
      id: "direccion_nuevo",
      name_attr: "direccion_nuevo_formulario",
      backend_key: "direccion",
      tipo: "input",
      regex: expresiones.direccion,
      mensajes: {
        vacio: "La dirección es obligatoria.",
        formato: "La dirección debe tener entre 5 y 100 caracteres.",
      },
    },
    {
      id: "observacionesCliente_nuevo",
      name_attr: "observacionesCliente_nuevo_formulario",
      backend_key: "observaciones",
      tipo: "textarea",
      regex: expresiones.observaciones,
      mensajes: {
        formato: "Las observaciones no deben exceder los 50 caracteres.",
      },
    },
    {
      id: "estatus_nuevo",
      name_attr: "estatus_nuevo_formulario",
      backend_key: "estatus",
      tipo: "select",
      mensajes: {
        vacio: "Debe seleccionar un estatus.",
      },
    },
  ];

  let listaProductosCargadosSelect = [];

  // --- Elementos del DOM ---
  const btnToggleNuevoCliente = document.getElementById(
    "btnToggleNuevoCliente"
  );
  const nuevoClienteContainer = document.getElementById(
    "nuevoClienteContainer"
  );
  const descuentoPorcentajeGeneralInput = document.getElementById(
    "descuento_porcentaje_general"
  );
  const detalleVentaBody = document.getElementById("detalleVentaBody");
  const noDetallesMsg = document.getElementById("noDetallesMensaje");

  // --- Funciones Auxiliares de UI y Estado ---
  function setCamposEmbebidosHabilitados(habilitar) {
    if (!nuevoClienteContainer) return;
    camposNuevoClienteEmbebido.forEach((campo) => {
      const input = nuevoClienteContainer.querySelector(`#${campo.id}`);
      if (input) input.disabled = !habilitar;
    });
    const inputCriterio = document.getElementById("inputCriterioClienteModal");
    const btnBuscar = document.getElementById("btnBuscarClienteModal");
    if (inputCriterio) inputCriterio.disabled = habilitar;
    if (btnBuscar) btnBuscar.disabled = habilitar;
  }

  function resetYDeshabilitarFormClienteEmbebido() {
    if (nuevoClienteContainer) {
      camposNuevoClienteEmbebido.forEach((campo) => {
        const input = nuevoClienteContainer.querySelector(`#${campo.id}`);
        if (input) {
          input.value = campo.id === "estatus_nuevo" ? "Activo" : "";
          input.classList.remove("border-red-500");
        }
      });
      limpiarValidaciones(camposNuevoClienteEmbebido, "ventaForm");
      setCamposEmbebidosHabilitados(false);
      nuevoClienteContainer.classList.add("hidden");
    }
    if (btnToggleNuevoCliente) {
      btnToggleNuevoCliente.innerHTML =
        '<i class="mr-2 fas fa-user-plus"></i>Registrar Nuevo Cliente';
    }
  }

  function limpiarFormularioVentaCompleto() {
    if (ventaForm) {
      ventaForm.reset();
      limpiarValidaciones(camposCabeceraVenta, "ventaForm");
    }
    resetYDeshabilitarFormClienteEmbebido();
    if (detalleVentaBody) detalleVentaBody.innerHTML = "";
    if (noDetallesMsg) noDetallesMsg.classList.remove("hidden");

    // Limpiar campos de totales
    [
      "subtotal_general_display_modal",
      "subtotal_general",
      "monto_descuento_general_display",
      "monto_descuento_general",
      "total_general_display_modal",
      "total_general",
    ].forEach((id) => {
      const el = document.getElementById(id);
      if (el) el.value = "0.00";
    });

    if (descuentoPorcentajeGeneralInput)
      descuentoPorcentajeGeneralInput.value = "0";

    const selectProducto = document.getElementById(
      "select_producto_agregar_modal"
    );
    if (selectProducto) selectProducto.value = "";

    const inputCriterio = document.getElementById("inputCriterioClienteModal");
    const listaResultados = document.getElementById(
      "listaResultadosClienteModal"
    );
    const inputIdCliente = document.getElementById("idcliente");
    const divInfoCliente = document.getElementById(
      "cliente_seleccionado_info_modal"
    );

    if (inputCriterio) inputCriterio.value = "";
    if (listaResultados) {
      listaResultados.innerHTML = "";
      listaResultados.classList.add("hidden");
    }
    if (inputIdCliente) inputIdCliente.value = "";
    if (divInfoCliente) {
      divInfoCliente.innerHTML = "";
      divInfoCliente.classList.add("hidden");
    }

    document
      .querySelectorAll('[id^="error-"]')
      .forEach((el) => el.classList.add("hidden"));
    const msgErrorForm = document.getElementById("mensajeErrorFormVentaModal");
    if (msgErrorForm) msgErrorForm.classList.add("hidden");
  }

  // --- Lógica para Cliente Embebido ---
  if (nuevoClienteContainer) resetYDeshabilitarFormClienteEmbebido();

  if (btnToggleNuevoCliente && nuevoClienteContainer) {
    btnToggleNuevoCliente.addEventListener("click", function () {
      const isHidden = nuevoClienteContainer.classList.contains("hidden");
      if (isHidden) {
        nuevoClienteContainer.classList.remove("hidden");
        setCamposEmbebidosHabilitados(true);
        inicializarValidaciones(camposNuevoClienteEmbebido, "ventaForm");
        this.innerHTML =
          '<i class="mr-2 fas fa-times"></i>Cancelar Nuevo Cliente';
        const primerCampo = nuevoClienteContainer.querySelector(
          'input:not([type="hidden"]), select, textarea'
        );
        if (primerCampo) primerCampo.focus();
        document.getElementById("idcliente").value = "";
        document.getElementById("inputCriterioClienteModal").value = "";
        document
          .getElementById("cliente_seleccionado_info_modal")
          .classList.add("hidden");
      } else {
        resetYDeshabilitarFormClienteEmbebido();
      }
    });
  }

  // --- Función para validar cliente nuevo (será usada en el registro de venta) ---
  function validarClienteNuevo() {
    if (!nuevoClienteContainer || nuevoClienteContainer.classList.contains("hidden")) {
      return { esValido: true, datos: null };
    }

    // Validar campos vacíos
    if (!validarCamposVacios(camposNuevoClienteEmbebido, "ventaForm")) {
      return { esValido: false, datos: null };
    }

    // Validar formato de campos
    let formClienteValido = true;
    camposNuevoClienteEmbebido.forEach((campo) => {
      const inputElement = ventaForm.querySelector(`#${campo.id}`);
      if (inputElement && inputElement.offsetParent !== null) {
        let esValidoEsteCampo = true;
        if (campo.tipo === "select") {
          esValidoEsteCampo = validarSelect(inputElement, campo.mensajes);
        } else {
          esValidoEsteCampo = validarCampo(
            inputElement,
            campo.regex,
            campo.mensajes
          );
        }
        if (!esValidoEsteCampo) formClienteValido = false;
      }
    });

    if (!formClienteValido) {
      return { esValido: false, datos: null };
    }

    // Recopilar datos del cliente
    const datosCliente = {};
    camposNuevoClienteEmbebido.forEach((c) => {
      const input = ventaForm.querySelector(`#${c.id}`);
      if (input) datosCliente[c.backend_key] = input.value;
    });

    return { esValido: true, datos: datosCliente };
  }

  // --- Lógica de Buscador de Clientes ---
  function inicializarBuscadorCliente() {
    const inputCriterio = document.getElementById("inputCriterioClienteModal");
    const btnBuscar = document.getElementById("btnBuscarClienteModal");
    const listaResultados = document.getElementById(
      "listaResultadosClienteModal"
    );
    const inputIdCliente = document.getElementById("idcliente");
    const divInfoCliente = document.getElementById(
      "cliente_seleccionado_info_modal"
    );

    if (
      !btnBuscar ||
      !inputCriterio ||
      !listaResultados ||
      !inputIdCliente ||
      !divInfoCliente
    )
      return;
    if (btnBuscar.dataset.listenerAttached === "true") return;

    btnBuscar.dataset.listenerAttached = "true";

    btnBuscar.addEventListener("click", async function () {
      if (this.disabled) return;

      const criterio = inputCriterio.value.trim();
      if (criterio.length < 2) {
        Swal.fire("Atención", "Ingrese al menos 2 caracteres.", "warning");
        return;
      }

      listaResultados.innerHTML =
        '<div class="p-2 text-xs text-gray-500">Buscando...</div>';
      listaResultados.classList.remove("hidden");

      try {
        const response = await fetch(
          `clientes/buscar?criterio=${encodeURIComponent(criterio)}`
        );
        if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);

        const clientes = await response.json();
        listaResultados.innerHTML = "";

        if (clientes && clientes.length > 0) {
          clientes.forEach((cli) => {
            const itemDiv = document.createElement("div");
            itemDiv.className = "p-2 text-xs hover:bg-gray-100 cursor-pointer";
            itemDiv.textContent = `${cli.nombre || ""} ${
              cli.apellido || ""
            } (C.I.: ${cli.cedula || ""})`.trim();
            itemDiv.dataset.idcliente = cli.id;
            itemDiv.dataset.nombre = cli.nombre || "";
            itemDiv.dataset.apellido = cli.apellido || "";
            itemDiv.dataset.cedula = cli.cedula || "";

            itemDiv.addEventListener("click", function () {
              inputIdCliente.value = this.dataset.idcliente;
              divInfoCliente.innerHTML = `Sel: <strong>${this.dataset.nombre} ${this.dataset.apellido}</strong> (C.I.: ${this.dataset.cedula})`;
              divInfoCliente.classList.remove("hidden");
              inputCriterio.value = this.textContent;
              listaResultados.classList.add("hidden");
            });

            listaResultados.appendChild(itemDiv);
          });
        } else {
          listaResultados.innerHTML =
            '<div class="p-2 text-xs text-gray-500">No se encontraron.</div>';
        }
      } catch (error) {
        console.error("Error al buscar clientes:", error);
        listaResultados.innerHTML = `<div class="p-2 text-xs text-red-500">Error: ${error.message}.</div>`;
      }
    });

    inputCriterio.addEventListener("input", function () {
      if (this.disabled) return;
      if (
        nuevoClienteContainer &&
        nuevoClienteContainer.classList.contains("hidden")
      ) {
        inputIdCliente.value = "";
        divInfoCliente.classList.add("hidden");
      }
    });

    document.addEventListener("click", function (event) {
      if (
        listaResultados &&
        !listaResultados.contains(event.target) &&
        event.target !== inputCriterio &&
        event.target !== btnBuscar
      ) {
        listaResultados.classList.add("hidden");
      }
    });
  }

  // --- Lógica de Detalle de Venta ---
  document
    .getElementById("agregarDetalleBtn")
    .addEventListener("click", function () {
      const selectProductoEl = document.getElementById(
        "select_producto_agregar_modal"
      );
      const idProductoSel = selectProductoEl.value;
      const errorDivSelect =
        document.getElementById("error-select_producto_agregar_modal-vacio") ||
        selectProductoEl.nextElementSibling;

      if (!idProductoSel) {
        if (errorDivSelect && errorDivSelect.id.startsWith("error-")) {
          errorDivSelect.textContent = "Seleccione un producto.";
          errorDivSelect.classList.remove("hidden");
          selectProductoEl.classList.add("border-red-500");
        } else {
          Swal.fire("Atención", "Seleccione un producto.", "warning");
        }
        return;
      } else {
        if (errorDivSelect && errorDivSelect.id.startsWith("error-")) {
          errorDivSelect.classList.add("hidden");
          selectProductoEl.classList.remove("border-red-500");
        }
      }

      // Buscar el producto en la lista cargada
      const productoData = listaProductosCargadosSelect.find(
        (p) => String(p.idproducto || p.id) === String(idProductoSel)
      );

      if (!productoData) {
        Swal.fire("Error", "Producto no encontrado.", "error");
        return;
      }

      // Verificar si el producto ya está agregado
      const yaAgregado = Array.from(
        detalleVentaBody.querySelectorAll("input[name='detalle_idproducto[]']")
      ).some(
        (input) =>
          input.value === String(productoData.idproducto || productoData.id)
      );

      if (yaAgregado) {
        Swal.fire("Atención", "Producto ya agregado.", "info");
        return;
      }

      // Preparar datos del producto
      const idProd = productoData.idproducto || productoData.id;
      const nombreProd = `${productoData.nombre_producto} (${
        productoData.nombre_categoria || "N/A"
      })`;
      const precioUnit = parseFloat(productoData.precio_unitario || 0).toFixed(
        2
      );

      // Validar que el ID del producto sea válido
      if (!idProd || idProd === "" || idProd === "0") {
        Swal.fire("Error", "ID de producto no válido.", "error");
        return;
      }

      const nuevaFilaHTML = `
      <tr>
        <td class="px-3 py-1.5">
          <input type="hidden" name="detalle_idproducto[]" value="${idProd}">
          <span>${nombreProd}</span>
        </td>
        <td class="px-3 py-1.5">
          <input type="number" name="detalle_cantidad[]" 
                 class="w-full px-2 py-1 text-xs border rounded-md cantidad-input" 
                 value="1" min="1" step="1">
        </td>
        <td class="px-3 py-1.5">
          <input type="number" name="detalle_precio_unitario_venta[]" 
                 class="w-full px-2 py-1 text-xs border rounded-md precio-input bg-gray-100" 
                 value="${precioUnit}" readonly step="0.01">
        </td>
        <td class="px-3 py-1.5">
          <input type="number" name="detalle_subtotal[]" 
                 class="w-full px-2 py-1 text-xs border rounded-md subtotal-input bg-gray-100" 
                 value="${precioUnit}" readonly step="0.01">
        </td>
        <td class="px-3 py-1.5 text-center">
          <button type="button" class="text-red-500 eliminar-detalle-btn hover:text-red-700">
            <i class="fas fa-trash"></i>
          </button>
        </td>
      </tr>`;

      detalleVentaBody.insertAdjacentHTML("beforeend", nuevaFilaHTML);
      if (noDetallesMsg) noDetallesMsg.classList.add("hidden");

      actualizarEventosDetalle();
      calcularTotalesGenerales();
      selectProductoEl.value = "";
    });

  function actualizarEventosDetalle() {
    // Eventos para cambio de cantidad
    detalleVentaBody.querySelectorAll(".cantidad-input").forEach((input) => {
      input.oninput = function () {
        const fila = this.closest("tr");
        const precio =
          parseFloat(fila.querySelector(".precio-input").value) || 0;
        let cantidad = parseFloat(this.value) || 0;

        // Validar cantidad mínima
        if (cantidad < 1 && this.value !== "") {
          cantidad = 1;
          this.value = 1;
        }

        const subtotal = (cantidad * precio).toFixed(2);
        fila.querySelector(".subtotal-input").value = subtotal;
        calcularTotalesGenerales();
      };
    });

    // Eventos para eliminar detalle
    detalleVentaBody
      .querySelectorAll(".eliminar-detalle-btn")
      .forEach((btn) => {
        btn.onclick = function () {
          this.closest("tr").remove();
          calcularTotalesGenerales();

          if (detalleVentaBody.rows.length === 0 && noDetallesMsg) {
            noDetallesMsg.classList.remove("hidden");
          }
        };
      });
  }

  function calcularTotalesGenerales() {
    let subtotalGeneral = 0;

    // Sumar todos los subtotales
    detalleVentaBody.querySelectorAll(".subtotal-input").forEach((input) => {
      subtotalGeneral += parseFloat(input.value) || 0;
    });

    // Calcular descuento
    const descuentoP = parseFloat(descuentoPorcentajeGeneralInput?.value) || 0;
    const montoDesc = (subtotalGeneral * descuentoP) / 100;
    const totalGen = subtotalGeneral - montoDesc;

    // Actualizar campos de subtotal
    ["subtotal_general_display_modal", "subtotal_general"].forEach((id) => {
      const el = document.getElementById(id);
      if (el) el.value = subtotalGeneral.toFixed(2);
    });

    // Actualizar campos de descuento
    ["monto_descuento_general_display", "monto_descuento_general"].forEach(
      (id) => {
        const el = document.getElementById(id);
        if (el) el.value = montoDesc.toFixed(2);
      }
    );

    // Actualizar campos de total
    ["total_general_display_modal", "total_general"].forEach((id) => {
      const el = document.getElementById(id);
      if (el) el.value = totalGen.toFixed(2);
    });
  }

  if (descuentoPorcentajeGeneralInput) {
    descuentoPorcentajeGeneralInput.addEventListener(
      "input",
      calcularTotalesGenerales
    );
  }

  // --- Eventos de Apertura/Cierre Modal y Registro de Venta Principal ---
  document
    .getElementById("abrirModalBtn")
    .addEventListener("click", function () {
      abrirModal("ventaModal");
      limpiarFormularioVentaCompleto();

      // Establecer fecha actual
      const fechaInput = document.getElementById("fecha_venta_modal");
      if (fechaInput) {
        fechaInput.value = new Date().toISOString().split("T")[0];
      }

      cargarSelect({
        selectId: "idmoneda_general",
        endpoint: "ventas/getMonedas",
        optionTextFn: (m) => `${m.codigo_moneda} (${m.valor})`,
        optionValueFn: (m) => m.idmoneda,
        placeholder: "Seleccione moneda...",
        onLoaded: (monedas) => {
          const select = document.getElementById("idmoneda_general");
          monedas.forEach((m, i) => {
            const option = select.options[i + 1]; // +1 si tienes placeholder
            if (option) option.dataset.codigo = m.codigo_moneda;
          });
        },
      });

      inicializarValidaciones(camposCabeceraVenta, "ventaForm");

      cargarSelect({
        selectId: "select_producto_agregar_modal",
        endpoint: "ventas/getProductosDisponibles",
        optionTextFn: (p) =>
          `${p.nombre_producto} (${p.nombre_categoria || "N/A"})`,
        optionValueFn: (p) => p.idproducto || p.id || "",
        placeholder: "Seleccione producto...",
        onLoaded: (prods) => {
          listaProductosCargadosSelect = prods || [];
          console.log("Productos cargados:", listaProductosCargadosSelect);
        },
      });

      inicializarBuscadorCliente();
    });

  document
    .getElementById("btnCerrarModalNuevaVenta")
    .addEventListener("click", function () {
      cerrarModal("ventaModal");
      limpiarFormularioVentaCompleto();
    });

  document
    .getElementById("cerrarModalBtn")
    .addEventListener("click", function () {
      cerrarModal("ventaModal");
      limpiarFormularioVentaCompleto();
    });

  // --- REGISTRAR VENTA PRINCIPAL CON CLIENTE AUTOMÁTICO ---
  document
    .getElementById("registrarVentaBtn")
    .addEventListener("click", async function () {
      const idClienteSeleccionado = document.getElementById("idcliente").value;
      const nuevoClienteFormActivo =
        nuevoClienteContainer &&
        !nuevoClienteContainer.classList.contains("hidden");

      // Validar cliente: debe haber un cliente seleccionado O datos de cliente nuevo
      if (!idClienteSeleccionado && !nuevoClienteFormActivo) {
        Swal.fire(
          "Atención",
          "Debe seleccionar un cliente existente o completar los datos del nuevo cliente.",
          "warning"
        );
        document.getElementById("inputCriterioClienteModal").focus();
        return;
      }

      // Si se está registrando un nuevo cliente, validar sus datos
      let datosClienteNuevo = null;
      if (nuevoClienteFormActivo) {
        const validacionCliente = validarClienteNuevo();
        if (!validacionCliente.esValido) {
          Swal.fire(
            "Atención",
            "Debe completar correctamente todos los datos del cliente.",
            "warning"
          );
          return;
        }
        datosClienteNuevo = validacionCliente.datos;
      }

      // Validar campos de cabecera
      if (!validarCamposVacios(camposCabeceraVenta, "ventaForm")) return;

      let cabeceraValida = true;
      camposCabeceraVenta.forEach((campo) => {
        const inputEl = ventaForm.querySelector(`#${campo.id}`);
        if (
          inputEl &&
          (inputEl.offsetParent !== null || inputEl.type === "hidden")
        ) {
          let esValido = true;
          if (campo.tipo === "select") {
            esValido = validarSelect(inputEl, campo.mensajes);
          } else if (campo.tipo === "date") {
            esValido = validarFecha(inputEl, campo.mensajes);
          } else if (campo.regex) {
            esValido = validarCampo(inputEl, campo.regex, campo.mensajes);
          }
          if (!esValido) cabeceraValida = false;
        }
      });

      if (!cabeceraValida) return;

      // Validar detalle de venta
      if (!validarDetalleVenta()) return;

      // Validar que haya al menos un producto
      const filas = detalleVentaBody.querySelectorAll("tr");
      if (filas.length === 0) {
        Swal.fire(
          "Atención",
          "Debe agregar al menos un producto a la venta.",
          "warning"
        );
        return;
      }

      // Preparar datos para envío - FORMATO COMPATIBLE CON EL BACKEND PHP
      const datosVentaFinal = {
        // Si hay cliente nuevo, se enviará null y se creará automáticamente
        idcliente: idClienteSeleccionado ? parseInt(idClienteSeleccionado) : null,
        cliente_nuevo: datosClienteNuevo, // Datos del cliente nuevo si aplica
        fecha_venta: document.getElementById("fecha_venta_modal").value,
        idmoneda_general: parseInt(
          document.getElementById("idmoneda_general").value
        ),
        subtotal_general: parseFloat(
          document.getElementById("subtotal_general").value || 0
        ),
        descuento_porcentaje_general: parseFloat(
          document.getElementById("descuento_porcentaje_general").value || 0
        ),
        monto_descuento_general: parseFloat(
          document.getElementById("monto_descuento_general").value || 0
        ),
        total_general: parseFloat(
          document.getElementById("total_general").value || 0
        ),
        estatus: "activo",
        observaciones: document.getElementById("observaciones")?.value || "",
        detalles: [],
      };

      // Obtener la tasa actual de la moneda seleccionada
      try {
        datosVentaFinal.tasa_usada = await obtenerTasaActualSeleccionada(
          datosVentaFinal.idmoneda_general,
          datosVentaFinal.fecha_venta
        );
      } catch (error) {
        console.warn("Error al obtener tasa, usando valor por defecto:", error);
        datosVentaFinal.tasa_usada = 1;
      }

      // Recopilar detalles en el formato esperado por el backend PHP
      filas.forEach((fila) => {
        const idProducto = fila.querySelector(
          "input[name='detalle_idproducto[]']"
        ).value;
        const cantidad = fila.querySelector(
          "input[name='detalle_cantidad[]']"
        ).value;
        const precio = fila.querySelector(
          "input[name='detalle_precio_unitario_venta[]']"
        ).value;
        const subtotal = fila.querySelector(
          "input[name='detalle_subtotal[]']"
        ).value;
        const tasa = datosVentaFinal.tasa_usada || 1;

        // Validar que los datos no estén vacíos
        if (!idProducto || !cantidad || !precio || !subtotal) {
          console.warn("Detalle con datos incompletos encontrado:", {
            idProducto,
            cantidad,
            precio,
            subtotal,
          });
          return;
        }

        datosVentaFinal.detalles.push({
          idproducto: parseInt(idProducto),
          cantidad: parseFloat(cantidad),
          precio_unitario_venta: parseFloat(precio),
          subtotal_general: parseFloat(subtotal),
          descuento_porcentaje_general: 0,
          id_moneda_detalle: datosVentaFinal.idmoneda_general,
          peso_vehiculo: 0,
          peso_bruto: 0,
          peso_neto: 0,
          tasa_usada: tasa,
        });
      });

      // Validar que se agregaron detalles válidos
      if (datosVentaFinal.detalles.length === 0) {
        Swal.fire(
          "Error",
          "No se encontraron productos válidos en el detalle.",
          "error"
        );
        return;
      }

      // Deshabilitar botón para evitar múltiples envíos
      const btnRegistrar = document.getElementById("registrarVentaBtn");
      const textoOriginal = btnRegistrar.innerHTML;
      btnRegistrar.disabled = true;
      btnRegistrar.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Procesando...';

      // Enviar datos usando fetch directamente para compatibilidad con el backend PHP
      try {
        const response = await fetch("ventas/setVenta", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify(datosVentaFinal),
        });

        // Verificar que la respuesta sea válida
        if (!response.ok) {
          throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`);
        }

        const result = await response.json();

        // Manejar la respuesta correctamente
        if (result.status === true) {
          // Extraer datos de la respuesta de forma segura
          const mensaje = result.message || "Venta registrada correctamente.";
          let mensajeCompleto = mensaje;
          
          // Verificar si existen los datos de la venta
          if (result.data && typeof result.data === 'object') {
            const { nro_venta, idventa, idcliente } = result.data;
            
            if (nro_venta) {
              mensajeCompleto = `Venta ${nro_venta} registrada correctamente.`;
            }
            
            // Log para debugging (opcional)
            console.log("Venta creada exitosamente:", {
              idventa: idventa || 'No disponible',
              nro_venta: nro_venta || 'No disponible',
              idcliente: idcliente || 'No disponible'
            });
          } else {
            console.warn("Datos de respuesta no disponibles o estructura incorrecta:", result);
          }

          await Swal.fire("¡Éxito!", mensajeCompleto, "success");
          
          // Recargar tabla si existe
          if (typeof $ !== "undefined" && $("#Tablaventas").length) {
            $("#Tablaventas").DataTable().ajax.reload();
          }
          
          // Cerrar modal y limpiar formulario
          cerrarModal("ventaModal");
          limpiarFormularioVentaCompleto();

        } else {
          // Manejar errores del servidor
          const mensajeError = result.message || "No se pudo registrar la venta.";
          await Swal.fire("¡Error!", mensajeError, "error");
        }

      } catch (error) {
        console.error("Error al registrar venta:", error);
        
        // Determinar mensaje de error apropiado
        let mensajeError = "Error de comunicación con el servidor.";
        if (error.message.includes("HTTP:")) {
          mensajeError = `Error del servidor: ${error.message}`;
        } else if (error.name === "SyntaxError") {
          mensajeError = "Error al procesar la respuesta del servidor.";
        }
        
        await Swal.fire("¡Error!", mensajeError, "error");
      } finally {
        // Rehabilitar botón
        btnRegistrar.disabled = false;
        btnRegistrar.innerHTML = textoOriginal;
      }
    });

  // Función mejorada para obtener tasa
  async function obtenerTasaActualSeleccionada(idmoneda, fechaVenta) {
    try {
      // Obtén el código de moneda desde el select
      const selectMoneda = document.getElementById("idmoneda_general");
      if (!selectMoneda || selectMoneda.selectedIndex === -1) {
        console.warn("Select de moneda no encontrado o sin selección");
        return 1;
      }

      const option = selectMoneda.options[selectMoneda.selectedIndex];
      const codigoMoneda = option.dataset.codigo || option.text.split(" ")[0];

      if (!codigoMoneda) {
        console.warn("Código de moneda no encontrado");
        return 1;
      }

      // Llama al endpoint pasando código y fecha
      const response = await fetch(
        `ventas/getTasa?codigo_moneda=${encodeURIComponent(codigoMoneda)}&fecha=${encodeURIComponent(fechaVenta)}`
      );

      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }

      const data = await response.json();
      return parseFloat(data.tasa) || 1;

    } catch (error) {
      console.error("Error al obtener tasa:", error);
      return 1; // Valor por defecto
    }
  }

  // Función para confirmar eliminación
  function confirmarEliminacion(idventa) {
    Swal.fire({
      title: "¿Estás seguro?",
      text: "Esta acción cambiará el estatus de la venta a inactivo.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Sí, desactivar",
      cancelButtonText: "Cancelar",
    }).then(async (result) => {
      if (result.isConfirmed) {
        try {
          // Mostrar loading
          const loadingSwal = Swal.fire({
            title: 'Procesando...',
            text: 'Desactivando venta',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
              Swal.showLoading();
            }
          });

          const response = await fetch("ventas/deleteVenta", {
            method: "POST",
            headers: { 
              "Content-Type": "application/json",
              "X-Requested-With": "XMLHttpRequest"
            },
            body: JSON.stringify({
              id: parseInt(idventa),
            }),
          });

          // Cerrar loading
          loadingSwal.close();

          if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`);
          }

          const data = await response.json();

          if (data.status === true) {
            await Swal.fire({
              title: "¡Desactivada!",
              text: data.message || "Venta desactivada correctamente.",
              icon: "success",
              confirmButtonText: "Aceptar"
            });
            
            // Recargar tabla si existe
            if (typeof $ !== "undefined" && $("#Tablaventas").length) {
              $("#Tablaventas").DataTable().ajax.reload(null, false);
            }
          } else {
            await Swal.fire({
              title: "Error",
              text: data.message || "No se pudo desactivar la venta.",
              icon: "error",
              confirmButtonText: "Aceptar"
            });
          }

        } catch (error) {
          console.error("Error al desactivar venta:", error);
          
          let mensajeError = "Error de comunicación con el servidor.";
          if (error.message.includes("HTTP:")) {
            mensajeError = `Error del servidor: ${error.message}`;
          } else if (error.name === "SyntaxError") {
            mensajeError = "Error al procesar la respuesta del servidor.";
          }
          
          await Swal.fire({
            title: "Error", 
            text: mensajeError,
            icon: "error",
            confirmButtonText: "Aceptar"
          });
        }
      }
    });
  }

  // Event listener para el botón eliminar
  document.addEventListener("click", function (e) {
    const eliminarBtn = e.target.closest(".eliminar-btn");
    if (eliminarBtn) {
      const idventa = eliminarBtn.getAttribute("data-idventa");
      
      if (!idventa || isNaN(parseInt(idventa))) {
        Swal.fire({
          title: "Error",
          text: "ID de venta no válido.",
          icon: "error",
          confirmButtonText: "Aceptar"
        });
        return;
      }

      confirmarEliminacion(idventa);
    }
  });

  // DETALLE DE VENTA - Mejorado
  document.addEventListener("click", async function (e) {
    const verDetalleBtn = e.target.closest(".ver-detalle-btn");
    if (verDetalleBtn) {
      const idventa = verDetalleBtn.getAttribute("data-idventa");
      if (!idventa || isNaN(parseInt(idventa))) {
        await Swal.fire("Error", "ID de venta no válido.", "error");
        return;
      }

      // Mostrar el modal
      const modal = document.getElementById("modalDetalleVenta");
      if (!modal) {
        console.error("Modal de detalle no encontrado");
        return;
      }

      modal.classList.remove("opacity-0", "pointer-events-none", "transparent");
      modal.classList.add("opacity-100");

      // Mostrar loading
      const contenido = document.getElementById("detalleVentaContenido");
      if (contenido) {
        contenido.innerHTML = '<div class="flex justify-center items-center py-8"><i class="fas fa-spinner fa-spin mr-2"></i>Cargando...</div>';
      }

      try {
        const response = await fetch(`ventas/getVentaDetalle?idventa=${encodeURIComponent(idventa)}`);
        
        if (!response.ok) {
          throw new Error(`Error HTTP: ${response.status}`);
        }

        const data = await response.json();
        
        if (!data.status) {
          throw new Error(data.message || "No se pudo obtener el detalle.");
        }

        // Verificar que existan los datos necesarios
        if (!data.data || !data.data.venta || !data.data.detalle) {
          throw new Error("Estructura de datos incompleta.");
        }

        const venta = data.data.venta;
        const detalle = data.data.detalle;

        // Renderizar los datos en el modal de forma segura
        if (contenido) {
          contenido.innerHTML = `
            <div class="mb-4">
              <h4 class="font-semibold text-gray-700 mb-2">Datos Generales</h4>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <div><b>Nro Venta:</b> ${venta.nro_venta || 'N/A'}</div>
                <div><b>Fecha:</b> ${venta.fecha_venta || 'N/A'}</div>
                <div><b>Cliente:</b> ${(venta.cliente_nombre || '') + ' ' + (venta.cliente_apellido || '')}</div>
                <div><b>Cédula:</b> ${venta.cliente_cedula || 'N/A'}</div>
                <div><b>Tasa usada:</b> ${venta.tasa_usada || '-'}</div>
                <div><b>Estatus:</b> ${venta.estatus || 'N/A'}</div>
                <div><b>Observaciones:</b> ${venta.observaciones || '-'}</div>
              </div>
            </div>
            <div class="mb-4">
              <h4 class="font-semibold text-gray-700 mb-2">Detalle de Productos</h4>
              <table class="w-full text-xs border border-collapse border-gray-300">
                <thead class="bg-gray-100">
                  <tr>
                    <th class="px-2 py-1 border">Producto</th>
                    <th class="px-2 py-1 border">Cantidad</th>
                    <th class="px-2 py-1 border">Precio U.</th>
                    <th class="px-2 py-1 border">Subtotal</th>
                    <th class="px-2 py-1 border">Moneda</th>
                  </tr>
                </thead>
                <tbody>
                  ${Array.isArray(detalle) ? detalle.map(d => `
                    <tr>
                      <td class="px-2 py-1 border">${d.producto_nombre || 'N/A'}</td>
                      <td class="px-2 py-1 border">${d.cantidad || '0'}</td>
                      <td class="px-2 py-1 border">${d.precio_unitario_venta || '0.00'}</td>
                      <td class="px-2 py-1 border">${d.subtotal_general || d.subtotal || '0.00'}</td>
                      <td class="px-2 py-1 border">${d.codigo_moneda || 'N/A'}</td>
                    </tr>
                  `).join("") : '<tr><td colspan="5" class="px-2 py-1 border text-center">No hay detalles</td></tr>'}
                </tbody>
              </table>
            </div>
            <div class="mb-2">
              <b>Subtotal:</b> ${venta.subtotal_general || '0.00'} <br>
              <b>Descuento:</b> ${venta.descuento_porcentaje_general || '0'}% <br>
              <b>Monto Descuento:</b> ${venta.monto_descuento_general || '0.00'} <br>
              <b>Total General:</b> ${venta.total_general || '0.00'}
            </div>
          `;
        }

      } catch (error) {
        console.error("Error al cargar detalle de venta:", error);
        if (contenido) {
          contenido.innerHTML = `<div class="text-red-500 text-center py-4">Error: ${error.message}</div>`;
        }
      }
    }
  });

  // Cerrar el modal
  ["cerrarModalDetalleVentaBtn", "cerrarModalDetalleVentaBtn2"].forEach(
    (id) => {
      document.getElementById(id).addEventListener("click", function () {
        const modal = document.getElementById("modalDetalleVenta");
        modal.classList.add("opacity-0", "pointer-events-none", "transparent");
        modal.classList.remove("opacity-100");
        document.getElementById("detalleVentaContenido").innerHTML = "";
      });
    }
  );

  function inicializarDataTable() {
    let columnsConfig = [
      { data: "nro_venta", title: "Nro. Venta" },
      { data: "cliente_nombre", title: "Cliente" },
      { data: "fecha_venta", title: "Fecha" },
      {
        data: "estatus",
        title: "Estatus",
        render: function (data, type, row) {
          if (data === "Activo" || data === "Pagada") {
            return `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">${data}</span>`;
          } else if (data === "Inactivo" || data === "Anulada") {
            return `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">${data}</span>`;
          } else {
            return `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">${data}</span>`;
          }
        },
      },
    ];

    const tienePermisoEditar =
      typeof window.PERMISOS_USUARIO !== "undefined" &&
      window.PERMISOS_USUARIO.puede_editar;
    const tienePermisoEliminar =
      typeof window.PERMISOS_USUARIO !== "undefined" &&
      window.PERMISOS_USUARIO.puede_eliminar;

    if (tienePermisoEditar || tienePermisoEliminar) {
      columnsConfig.push({
        data: null,
        title: "Acciones",
        orderable: false,
        searchable: false,
        render: function (data, type, row) {
          let botonesHtml =
            '<div class="flex justify-center items-center gap-x-2">';

          if (tienePermisoEditar) {
            botonesHtml += `
              <button 
      type="button"
      class="ver-detalle-btn text-green-500 hover:text-indigo-700 p-1 rounded-full focus:outline-none" 
      data-idventa="${row.idventa}" 
      title="Ver Detalle">
      <i class="fas fa-eye"></i>
    </button>
              <button 
                type="button"
                class="editar-btn text-blue-500 hover:text-blue-700 p-1 rounded-full focus:outline-none" 
                data-idventa="${row.idventa}" 
                title="Editar Venta">
                <i class="fas fa-edit"></i>
              </button>
             
            `;
          }

          if (tienePermisoEliminar) {
            botonesHtml += `
              <button 
                type="button"
                class="eliminar-btn text-red-500 hover:text-red-700 p-1 rounded-full focus:outline-none" 
                data-idventa="${row.idventa}" 
                title="Eliminar Venta">
                <i class="fas fa-trash"></i>
              </button>
            `;
          }
          botonesHtml += "</div>";
          return botonesHtml;
        },
      });
    }

    $("#Tablaventas").DataTable({
      processing: true,
      serverSide: false,
      ajax: {
        url: "ventas/getventasData",
        type: "GET",
        dataSrc: "data",
        error: function (xhr, status, error) {
          console.error(
            "Error al cargar datos para DataTable:",
            status,
            error,
            xhr.responseText
          );
          $("#Tablaventas_processing").hide();
          var table = $("#Tablaventas").DataTable();
          table.clear().draw();
        },
      },
      columns: columnsConfig,
      language: {
        decimal: "",
        emptyTable: "No hay información disponible en la tabla.",
        info: "Mostrando _START_ a _END_ de _TOTAL_ Entradas",
        infoEmpty: "Mostrando 0 a 0 de 0 Entradas",
        infoFiltered: "(Filtrado de _MAX_ total entradas)",
        lengthMenu: "Mostrar _MENU_ Entradas",
        loadingRecords: "Cargando...",
        processing: "Procesando...",
        search: "Buscar:",
        zeroRecords: "No se encontraron registros coincidentes.",
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
      order: [[0, "desc"]],
    });
  }
});
