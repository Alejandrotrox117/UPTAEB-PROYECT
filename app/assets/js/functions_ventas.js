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
  const PERMISOS_USUARIO = obtenerPermisosUsuario();
  window.PERMISOS_USUARIO = PERMISOS_USUARIO;

  inicializarDataTable();
  const ventaForm = document.getElementById("ventaForm");

  const camposCabeceraVenta = [
    {
      id: "fecha_venta_modal",
      name_attr: "fecha_venta",
      tipo: "fecha",
      mensajes: {
        vacio: "La fecha es obligatoria.",
        fechaPosterior: "La fecha no puede ser posterior a hoy.",
      },
    },
    {
      id: "observaciones",
      name_attr: "observaciones",
      tipo: "textarea",
      opcional: true,
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
        formato: "El nombre debe tener entre 2 y 50 caracteres.",
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

  // Función auxiliar para limpiar la selección de cliente
  function limpiarSeleccionClienteCompleta() {
    const inputCriterio = document.getElementById("inputCriterioClienteModal");
    const inputIdCliente = document.getElementById("idcliente");
    const divInfoCliente = document.getElementById("cliente_seleccionado_info_modal");
    const listaResultados = document.getElementById("listaResultadosClienteModal");
    const btnLimpiarCliente = document.getElementById("btnLimpiarClienteModal");

    if (inputCriterio) inputCriterio.value = "";
    if (inputIdCliente) inputIdCliente.value = "";
    if (divInfoCliente) divInfoCliente.classList.add("hidden");
    if (listaResultados) listaResultados.classList.add("hidden");
    if (btnLimpiarCliente) btnLimpiarCliente.classList.add("hidden");
    
    // Limpiar validaciones relacionadas con el cliente si las hay
    const errorClienteDiv = document.getElementById("error-idcliente-vacio");
    if (errorClienteDiv) errorClienteDiv.classList.add("hidden");
  }

  function limpiarFormularioVentaCompleto() {
    if (ventaForm) {
      ventaForm.reset();
      limpiarValidaciones(camposCabeceraVenta, "ventaForm");
    }
    resetYDeshabilitarFormClienteEmbebido();
    
    // Limpiar selección de cliente
    limpiarSeleccionClienteCompleta();
    
    if (detalleVentaBody) detalleVentaBody.innerHTML = "";
    if (noDetallesMsg) noDetallesMsg.classList.remove("hidden");

    // Limpiar cliente original de edición
    clienteOriginalEdicion = null;

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

  function validarClienteNuevo() {
    if (
      !nuevoClienteContainer ||
      nuevoClienteContainer.classList.contains("hidden")
    ) {
      return { esValido: true, datos: null };
    }

    if (!validarCamposVacios(camposNuevoClienteEmbebido, "ventaForm")) {
      return { esValido: false, datos: null };
    }

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

    const datosCliente = {};
    camposNuevoClienteEmbebido.forEach((c) => {
      const input = ventaForm.querySelector(`#${c.id}`);
      if (input) datosCliente[c.backend_key] = input.value;
    });

    return { esValido: true, datos: datosCliente };
  }

  function inicializarBuscadorCliente() {
    const inputCriterio = document.getElementById("inputCriterioClienteModal");
    const btnBuscar = document.getElementById("btnBuscarClienteModal");
    const listaResultados = document.getElementById("listaResultadosClienteModal");
    const inputIdCliente = document.getElementById("idcliente");
    const divInfoCliente = document.getElementById("cliente_seleccionado_info_modal");

    if (!btnBuscar || !inputCriterio || !listaResultados || !inputIdCliente || !divInfoCliente)
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
        // Usar FormData para enviar como POST
        const formData = new FormData();
        formData.append('criterio', criterio);

        const response = await fetch('ventas/buscarClientes', {
          method: 'POST',
          body: formData
        });

        if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);

        const result = await response.json();
        listaResultados.innerHTML = "";

        // Verificar si la respuesta tiene la estructura esperada
        let clientes = [];
        if (result.status && result.data) {
          clientes = result.data;
        } else if (Array.isArray(result)) {
          clientes = result;
        }

        if (clientes && clientes.length > 0) {
          clientes.forEach((cli) => {
            const itemDiv = document.createElement("div");
            itemDiv.className = "p-2 text-xs hover:bg-gray-100 cursor-pointer";
            itemDiv.textContent = `${cli.nombre || ""} ${cli.apellido || ""} (C.I.: ${cli.cedula || ""})`.trim();
            
            // Usar los nombres de campos correctos del modelo
            itemDiv.dataset.idcliente = cli.idcliente || cli.id;
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
            '<div class="p-2 text-xs text-gray-500">No se encontraron clientes.</div>';
        }
      } catch (error) {
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

  function inicializarBotonesLimpiarCliente() {
    const btnLimpiarCliente = document.getElementById("btnLimpiarClienteModal");
    const btnEliminarClienteSeleccionado = document.getElementById("btnEliminarClienteSeleccionado");
    const inputCriterio = document.getElementById("inputCriterioClienteModal");

    // Evento para el botón limpiar en el buscador
    if (btnLimpiarCliente && !btnLimpiarCliente.dataset.listenerAttached) {
      btnLimpiarCliente.dataset.listenerAttached = "true";
      btnLimpiarCliente.addEventListener("click", function() {
        limpiarSeleccionClienteCompleta();
      });
    }

    // Evento para el botón eliminar en la información del cliente seleccionado
    if (btnEliminarClienteSeleccionado && !btnEliminarClienteSeleccionado.dataset.listenerAttached) {
      btnEliminarClienteSeleccionado.dataset.listenerAttached = "true";
      btnEliminarClienteSeleccionado.addEventListener("click", function() {
        limpiarSeleccionClienteCompleta();
      });
    }

    // Mostrar el botón limpiar cuando hay texto en el input
    if (inputCriterio && !inputCriterio.dataset.inputListenerAttached) {
      inputCriterio.dataset.inputListenerAttached = "true";
      inputCriterio.addEventListener("input", function() {
        if (btnLimpiarCliente) {
          if (this.value.trim().length > 0) {
            btnLimpiarCliente.classList.remove("hidden");
          } else {
            btnLimpiarCliente.classList.add("hidden");
          }
        }
      });
    }
  }

  const agregarDetalleBtn = document.getElementById("agregarDetalleBtn");
  if (agregarDetalleBtn) {
    agregarDetalleBtn.addEventListener("click", async function () {
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

      const productoData = listaProductosCargadosSelect.find(
        (p) => String(p.idproducto || p.id) === String(idProductoSel)
      );

      if (!productoData) {
        Swal.fire("Error", "Producto no encontrado.", "error");
        return;
      }

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

      const idProd = productoData.idproducto || productoData.id;
      const nombreProd = `${productoData.nombre_producto} (${
        productoData.nombre_categoria || "N/A"
      })`;
      
      // Mostrar loading mientras se calcula el precio
      Swal.fire({
        title: 'Calculando precio...',
        text: 'Obteniendo tasa de cambio actual',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
          Swal.showLoading();
        }
      });

      // Convertir precio según moneda
      const precioConvertido = await convertirPrecioSegunMoneda(productoData);
      const precioUnit = precioConvertido.toFixed(2);
      
      // Cerrar loading
      Swal.close();

      if (!idProd || idProd === "" || idProd === "0") {
        Swal.fire("Error", "ID de producto no válido.", "error");
        return;
      }

      const nuevaFilaHTML = `
      <tr>
        <td class="px-3 py-1.5">
          <input type="hidden" name="detalle_idproducto[]" value="${idProd}">
          <input type="hidden" name="detalle_moneda_original[]" value="${productoData.codigo_moneda_producto || ''}">
          <input type="hidden" name="detalle_precio_original[]" value="${productoData.precio_unitario || 0}">
          <span>${nombreProd}</span>
          <br><small class="text-gray-500">Precio original: ${productoData.codigo_moneda_producto || 'N/A'} ${parseFloat(productoData.precio_unitario || 0).toFixed(2)}</small>
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
  }

  function actualizarEventosDetalle() {
    detalleVentaBody.querySelectorAll(".cantidad-input").forEach((input) => {
      input.oninput = function () {
        const fila = this.closest("tr");
        const precio =
          parseFloat(fila.querySelector(".precio-input").value) || 0;
        let cantidad = parseFloat(this.value) || 0;

        if (cantidad < 1 && this.value !== "") {
          cantidad = 1;
          this.value = 1;
        }

        const subtotal = (cantidad * precio).toFixed(2);
        fila.querySelector(".subtotal-input").value = subtotal;
        calcularTotalesGenerales();
      };
    });

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

    detalleVentaBody.querySelectorAll(".subtotal-input").forEach((input) => {
      subtotalGeneral += parseFloat(input.value) || 0;
    });

    const descuentoP = parseFloat(descuentoPorcentajeGeneralInput?.value) || 0;
    const montoDesc = (subtotalGeneral * descuentoP) / 100;
    const totalGen = subtotalGeneral - montoDesc;

    ["subtotal_general_display_modal", "subtotal_general"].forEach((id) => {
      const el = document.getElementById(id);
      if (el) el.value = subtotalGeneral.toFixed(2);
    });

    ["monto_descuento_general_display", "monto_descuento_general"].forEach(
      (id) => {
        const el = document.getElementById(id);
        if (el) el.value = montoDesc.toFixed(2);
      }
    );

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

  const abrirModalBtn = document.getElementById("abrirModalBtn");
  if (abrirModalBtn) {
    abrirModalBtn.addEventListener("click", function () {
      abrirModal("ventaModal");
      limpiarFormularioVentaCompleto();

      // Resetear botón a modo de creación
      const submitBtn = document.getElementById("registrarVentaBtn");
      if (submitBtn) {
        const modalTitle = document.querySelector("#ventaModal h3");
        if (modalTitle) {
          modalTitle.innerHTML = '<i class="mr-1 text-green-600 fas fa-plus"></i>Nueva Venta';
        }
        submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Registrar Venta';
        submitBtn.removeAttribute('data-mode');
        submitBtn.removeAttribute('data-idventa');
      }

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
            const option = select.options[i + 1];
            if (option) option.dataset.codigo = m.codigo_moneda;
          });
          
          // Buscar y seleccionar automáticamente VES
          const vesMoneda = monedas.find(m => m.codigo_moneda === 'VES');
          if (vesMoneda) {
            select.value = vesMoneda.idmoneda;
          }
        },
      });

      inicializarValidaciones(camposCabeceraVenta, "ventaForm");

      cargarSelect({
        selectId: "select_producto_agregar_modal",
        endpoint: "ventas/getProductosDisponibles",
        optionTextFn: (p) =>
          `${p.nombre_producto} (${p.nombre_categoria || "N/A"}) - ${p.codigo_moneda_producto || 'N/A'} ${parseFloat(p.precio_unitario || 0).toFixed(2)}`,
        optionValueFn: (p) => p.idproducto || p.id || "",
        placeholder: "Seleccione producto...",
        onLoaded: (prods) => {
          listaProductosCargadosSelect = prods || [];
        },
      });

      inicializarBuscadorCliente();
      inicializarBotonesLimpiarCliente();
      inicializarModalRegistrarCliente();
    });
  }

  const btnCerrarModalNuevaVenta = document.getElementById("btnCerrarModalNuevaVenta");
  if (btnCerrarModalNuevaVenta) {
    btnCerrarModalNuevaVenta.addEventListener("click", function () {
      cerrarModal("ventaModal");
      limpiarFormularioVentaCompleto();
    });
  }

  const cerrarModalBtn = document.getElementById("cerrarModalBtn");
  if (cerrarModalBtn) {
    cerrarModalBtn.addEventListener("click", function () {
      cerrarModal("ventaModal");
      limpiarFormularioVentaCompleto();
    });
  }

  const registrarVentaBtn = document.getElementById("registrarVentaBtn");
  if (registrarVentaBtn) {
    registrarVentaBtn.addEventListener("click", async function () {
      // Determinar si estamos editando o creando
      const mode = this.getAttribute('data-mode') || 'create';
      const idVentaEditar = this.getAttribute('data-idventa');

      const idClienteSeleccionado = document.getElementById("idcliente").value;
      const nuevoClienteFormActivo =
        nuevoClienteContainer &&
        !nuevoClienteContainer.classList.contains("hidden");

      // Solo validar selección de cliente en modo de creación, no en modo de edición
      if (mode === 'create' && !idClienteSeleccionado && !nuevoClienteFormActivo) {
        Swal.fire(
          "Atención",
          "Debe seleccionar un cliente existente o completar los datos del nuevo cliente.",
          "warning"
        );
        document.getElementById("inputCriterioClienteModal").focus();
        return;
      }

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

      if (!validarCamposVacios(camposCabeceraVenta, "ventaForm")) return;

      // Validación específica para asegurar que VES esté seleccionado (solo en modo creación)
      if (mode === 'create') {
        const selectMoneda = document.getElementById("idmoneda_general");
        if (!selectMoneda.value) {
          Swal.fire("Error", "No se pudo seleccionar la moneda VES automáticamente. Contacte al administrador.", "error");
          return;
        }
      }

      let cabeceraValida = true;
      camposCabeceraVenta.forEach((campo) => {
        const inputEl = ventaForm.querySelector(`#${campo.id}`);
        if (
          inputEl &&
          (inputEl.offsetParent !== null || inputEl.type === "hidden")
        ) {
          let esValido = true;
          
       
          if (campo.opcional && (!inputEl.value || inputEl.value.trim() === "")) {
            return; 
          }
          
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

      if (!validarDetalleVenta()) return;

      const filas = detalleVentaBody.querySelectorAll("tr");
      if (filas.length === 0) {
        Swal.fire(
          "Atención",
          "Debe agregar al menos un producto a la venta.",
          "warning"
        );
        return;
      }

      const datosVentaFinal = {
        idcliente: idClienteSeleccionado
          ? parseInt(idClienteSeleccionado)
          : (mode === 'edit' && clienteOriginalEdicion ? parseInt(clienteOriginalEdicion) : null),
        cliente_nuevo: datosClienteNuevo,
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
        estatus: "POR_PAGAR",
        observaciones: document.getElementById("observaciones")?.value || "",
        detalles: [],
      };

      // Si estamos editando, agregar el ID de la venta
      if (mode === 'edit' && idVentaEditar) {
        datosVentaFinal.idventa = parseInt(idVentaEditar);
      }

      try {
        datosVentaFinal.tasa_usada = await obtenerTasaActualSeleccionada(
          datosVentaFinal.idmoneda_general,
          datosVentaFinal.fecha_venta
        );
      } catch (error) {
        datosVentaFinal.tasa_usada = 1;
      }

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

        if (!idProducto || !cantidad || !precio || !subtotal) {
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

      if (datosVentaFinal.detalles.length === 0) {
        Swal.fire(
          "Error",
          "No se encontraron productos válidos en el detalle.",
          "error"
        );
        return;
      }

      const btnRegistrar = document.getElementById("registrarVentaBtn");
      const textoOriginal = btnRegistrar.innerHTML;
      btnRegistrar.disabled = true;
      
      // Texto diferente para edición
      const textoLoading = mode === 'edit' 
        ? '<i class="fas fa-spinner fa-spin mr-2"></i>Actualizando...'
        : '<i class="fas fa-spinner fa-spin mr-2"></i>Procesando...';
      
      btnRegistrar.innerHTML = textoLoading;

      try {
        // URL diferente para edición
        const url = mode === 'edit' ? "ventas/updateVenta" : "ventas/setVenta";
        
        const response = await fetch(url, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify(datosVentaFinal),
        });

        if (!response.ok) {
          throw new Error(
            `Error HTTP: ${response.status} - ${response.statusText}`
          );
        }

        const result = await response.json();

        if (result.status === true) {
          const mensaje = result.message || (mode === 'edit' ? "Venta actualizada correctamente." : "Venta registrada correctamente.");
          let mensajeCompleto = mensaje;

          if (result.data && typeof result.data === "object") {
            const { nro_venta, idventa, idcliente } = result.data;

            if (nro_venta) {
              mensajeCompleto = mode === 'edit' 
                ? `Venta ${nro_venta} actualizada correctamente.`
                : `Venta ${nro_venta} registrada correctamente.`;
            }
          }

          await Swal.fire("¡Éxito!", mensajeCompleto, "success");

          if (typeof $ !== "undefined" && $("#Tablaventas").length) {
            $("#Tablaventas").DataTable().ajax.reload();
          }

          cerrarModal("ventaModal");
          limpiarFormularioVentaCompleto();
          
          // Restablecer el modo del botón
          btnRegistrar.removeAttribute('data-mode');
          btnRegistrar.removeAttribute('data-idventa');
          btnRegistrar.innerHTML = '<i class="fas fa-save mr-2"></i>Registrar Venta';
          
          // Restablecer el título del modal
          const modalTitle = document.querySelector("#ventaModal h3");
          if (modalTitle) {
            modalTitle.innerHTML = '<i class="mr-1 text-green-600 fas fa-shopping-cart"></i>Registrar Nueva Venta';
          }
        } else {
          const mensajeError =
            result.message || (mode === 'edit' ? "No se pudo actualizar la venta." : "No se pudo registrar la venta.");
          await Swal.fire("¡Error!", mensajeError, "error");
        }
      } catch (error) {

        let mensajeError = "Error de comunicación con el servidor.";
        if (error.message.includes("HTTP:")) {
          mensajeError = `Error del servidor: ${error.message}`;
        } else if (error.name === "SyntaxError") {
          mensajeError = "Error al procesar la respuesta del servidor.";
        }

        await Swal.fire("¡Error!", mensajeError, "error");
      } finally {
        btnRegistrar.disabled = false;
        btnRegistrar.innerHTML = textoOriginal;
      }
    });
  }

  async function obtenerTasaActualSeleccionada(idmoneda, fechaVenta) {
    try {
      const selectMoneda = document.getElementById("idmoneda_general");
      if (!selectMoneda || selectMoneda.selectedIndex === -1) {
        return 1;
      }

      const option = selectMoneda.options[selectMoneda.selectedIndex];
      const codigoMoneda = option.dataset.codigo || option.text.split(" ")[0];

      if (!codigoMoneda) {
        return 1;
      }

      const response = await fetch(
        `ventas/getTasa?codigo_moneda=${encodeURIComponent(
          codigoMoneda
        )}&fecha=${encodeURIComponent(fechaVenta)}`
      );

      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }

      const data = await response.json();
      return parseFloat(data.tasa) || 1;
    } catch (error) {
      return 1;
    }
  }

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
          const loadingSwal = Swal.fire({
            title: "Procesando...",
            text: "Desactivando venta",
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
              Swal.showLoading();
            },
          });

          const response = await fetch("ventas/deleteVenta", {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-Requested-With": "XMLHttpRequest",
            },
            body: JSON.stringify({
              id: parseInt(idventa),
            }),
          });

          loadingSwal.close();

          if (!response.ok) {
            throw new Error(
              `Error HTTP: ${response.status} - ${response.statusText}`
            );
          }

          const data = await response.json();

          if (data.status === true) {
            await Swal.fire({
              title: "¡Desactivada!",
              text: data.message || "Venta desactivada correctamente.",
              icon: "success",
              confirmButtonText: "Aceptar",
            });

            if (typeof $ !== "undefined" && $("#Tablaventas").length) {
              $("#Tablaventas").DataTable().ajax.reload(null, false);
            }
          } else {
            await Swal.fire({
              title: "Error",
              text: data.message || "No se pudo desactivar la venta.",
              icon: "error",
              confirmButtonText: "Aceptar",
            });
          }
        } catch (error) {
          const mensajeError = "Error de comunicación con el servidor.";
          await Swal.fire({
            title: "Error",
            text: mensajeError,
            icon: "error",
            confirmButtonText: "Aceptar",
          });
        }
      }
    });
  }

  document.addEventListener("click", function (e) {
    const eliminarBtn = e.target.closest(".eliminar-btn");
    if (eliminarBtn) {
      const idventa = eliminarBtn.getAttribute("data-idventa");

      if (!idventa || isNaN(parseInt(idventa))) {
        Swal.fire({
          title: "Error",
          text: "ID de venta no válido.",
          icon: "error",
          confirmButtonText: "Aceptar",
        });
        return;
      }

      confirmarEliminacion(idventa);
    }

    // Cambiar estado de venta
    const cambiarEstadoBtn = e.target.closest(".cambiar-estado-venta-btn");
    if (cambiarEstadoBtn) {
      const idVenta = cambiarEstadoBtn.getAttribute("data-idventa");
      const nuevoEstado = cambiarEstadoBtn.getAttribute("data-nuevo-estado");
      
      if (idVenta && nuevoEstado) {
        cambiarEstadoVenta(idVenta, nuevoEstado);
      }
    }

    // Ir a pagos
    const irPagosBtn = e.target.closest(".ir-pagos-venta-btn");
    if (irPagosBtn) {
      const idVenta = irPagosBtn.getAttribute("data-idventa");
      if (idVenta) {
        // Redirigir al módulo de pagos con filtro de venta
        window.location.href = `pagos?venta=${idVenta}`;
      }
    }

    // Nota de despacho
    const notaDespachoBtn = e.target.closest(".nota-despacho-btn");
    if (notaDespachoBtn) {
      const idVenta = notaDespachoBtn.getAttribute("data-idventa");
      if (idVenta) {
        // Redirigir a nota de despacho
        window.open(`ventas/notaDespacho/${idVenta}`, '_blank');
      }
    }

    // Editar venta
    const editarVentaBtn = e.target.closest(".editar-venta-btn");
    if (editarVentaBtn) {
      const idVenta = editarVentaBtn.getAttribute("data-idventa");
      if (idVenta) {
        cargarVentaParaEditar(idVenta);
      }
    }
  });

  document.addEventListener("click", async function (e) {
    const verDetalleBtn = e.target.closest(".ver-detalle-btn");
    if (verDetalleBtn) {
      const idventa = verDetalleBtn.getAttribute("data-idventa");
      if (!idventa || isNaN(parseInt(idventa))) {
        await Swal.fire("Error", "ID de venta no válido.", "error");
        return;
      }

      const modal = document.getElementById("modalDetalleVenta");
      if (!modal) {
        return;
      }

      modal.classList.remove("opacity-0", "pointer-events-none", "transparent");
      modal.classList.add("opacity-100");

      const contenido = document.getElementById("detalleVentaContenido");
      if (contenido) {
        contenido.innerHTML =
          '<div class="flex justify-center items-center py-8"><i class="fas fa-spinner fa-spin mr-2"></i>Cargando...</div>';
      }

      try {
        const response = await fetch(
          `ventas/getVentaDetalle?idventa=${encodeURIComponent(idventa)}`
        );

        if (!response.ok) {
          throw new Error(`Error HTTP: ${response.status}`);
        }

        const data = await response.json();

        if (!data.status) {
          throw new Error(data.message || "No se pudo obtener el detalle.");
        }

        if (!data.data || !data.data.venta || !data.data.detalle) {
          throw new Error("Estructura de datos incompleta.");
        }

        const venta = data.data.venta;
        const detalle = data.data.detalle;

        if (contenido) {
          contenido.innerHTML = `
            <div class="mb-4">
              <h4 class="font-semibold text-gray-700 mb-2">Datos Generales</h4>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <div><b>Nro Venta:</b> ${venta.nro_venta || "N/A"}</div>
                <div><b>Fecha:</b> ${venta.fecha_venta || "N/A"}</div>
                <div><b>Cliente:</b> ${
                  (venta.cliente_nombre || "") +
                  " " +
                  (venta.cliente_apellido || "")
                }</div>
                <div><b>Cédula:</b> ${venta.cliente_cedula || "N/A"}</div>
                <div><b>Tasa usada:</b> ${venta.tasa_usada || "-"}</div>
                <div><b>Estatus:</b> ${venta.estatus || "N/A"}</div>
                <div><b>Observaciones:</b> ${venta.observaciones || "-"}</div>
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
                  ${
                    Array.isArray(detalle)
                      ? detalle
                          .map(
                            (d) => `
                    <tr>
                      <td class="px-2 py-1 border">${
                        d.nombre_producto || "N/A"
                      }</td>
                      <td class="px-2 py-1 border">${d.cantidad || "0"}</td>
                      <td class="px-2 py-1 border">${
                        d.precio_unitario_venta || "0.00"
                      }</td>
                      <td class="px-2 py-1 border">${
                        d.subtotal_general || d.subtotal || "0.00"
                      }</td>
                      <td class="px-2 py-1 border">${
                        d.codigo_moneda ||  "N/A"
                      }</td>
                    </tr>
                  `
                          )
                          .join("")
                      : '<tr><td colspan="5" class="px-2 py-1 border text-center">No hay detalles</td></tr>'
                  }
                </tbody>
              </table>
            </div>
            <div class="mb-2">
              <b>Subtotal:</b> ${venta.subtotal_general || "0.00"} <br>
              <b>Descuento:</b> ${
                venta.descuento_porcentaje_general || "0"
              }% <br>
              <b>Monto Descuento:</b> ${
                venta.monto_descuento_general || "0.00"
              } <br>
              <b>Total General:</b> ${venta.total_general || "0.00"}
            </div>
          `;
        }
        
        // Debug para ver qué datos llegan
        console.log('Datos completos recibidos:', data);
        console.log('Detalle específico:', detalle);
        
      } catch (error) {
        console.error('Error al cargar detalle:', error);
        if (contenido) {
          contenido.innerHTML = `<div class="text-red-500 text-center py-4">Error: ${error.message}</div>`;
        }
      }
    }
  });

  ["cerrarModalDetalleVentaBtn", "cerrarModalDetalleVentaBtn2"].forEach(
    (id) => {
      const element = document.getElementById(id);
      if (element) {
        element.addEventListener("click", function () {
          const modal = document.getElementById("modalDetalleVenta");
          if (modal) {
            modal.classList.add("opacity-0", "pointer-events-none", "transparent");
            modal.classList.remove("opacity-100");
          }
          const contenido = document.getElementById("detalleVentaContenido");
          if (contenido) {
            contenido.innerHTML = "";
          }
        });
      }
    }
  );

  // Función para cambiar estado de venta
  function cambiarEstadoVenta(idVenta, nuevoEstado) {
    const mensajesEstado = {
      POR_PAGAR: "marcar para pago",
      PAGADA: "marcar como pagada",
      BORRADOR: "devolver a borrador",
    };

    const mensaje = mensajesEstado[nuevoEstado] || "cambiar estado de";
    
    // Mensaje especial para marcar como pagada
    let textoConfirmacion = `¿Deseas ${mensaje} esta venta?`;
    if (nuevoEstado === "PAGADA") {
      textoConfirmacion = `¿Deseas marcar esta venta como pagada?\n\nNOTA: Esta acción solo será exitosa si existen pagos registrados y conciliados que cubran el total de la venta.`;
    }

    Swal.fire({
      title: "¿Confirmar acción?",
      text: textoConfirmacion,
      icon: "question",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Sí, confirmar",
      cancelButtonText: "Cancelar",
    }).then((result) => {
      if (result.isConfirmed) {
        const dataParaEnviar = {
          idventa: idVenta,
          nuevo_estado: nuevoEstado,
        };

        fetch("ventas/cambiarEstadoVenta", {
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
              // Recargar la tabla de ventas
              if (typeof $ !== "undefined" && $("#Tablaventas").length) {
                $("#Tablaventas").DataTable().ajax.reload();
              }
            } else {
              Swal.fire(
                "Error",
                result.message || "No se pudo cambiar el estado de la venta.",
                "error"
              );
            }
          })
          .catch((error) => {
            Swal.fire("Error", "Error de conexión.", "error");
          });
      }
    });
  }

  // Función para cargar venta para editar
  async function cargarVentaParaEditar(idVenta) {
    try {
      // Mostrar el modal con mensaje de carga
      const modal = document.getElementById("ventaModal");
      const modalTitle = modal.querySelector("h3");
      const submitBtn = document.getElementById("registrarVentaBtn");
      
      // Cambiar el título del modal
      modalTitle.innerHTML = '<i class="mr-1 text-blue-600 fas fa-edit"></i>Editar Venta';
      
      // Cambiar el texto del botón
      submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Actualizar Venta';
      submitBtn.setAttribute('data-mode', 'edit');
      submitBtn.setAttribute('data-idventa', idVenta);
      
      // Abrir el modal
      abrirModal("ventaModal");
      
      // Mostrar mensaje de carga
      Swal.fire({
        title: 'Cargando...',
        text: 'Obteniendo datos de la venta',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      // Obtener los datos de la venta
      const response = await fetch(`ventas/getVentaDetalle?idventa=${encodeURIComponent(idVenta)}`);
      
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }

      const data = await response.json();

      if (!data.status) {
        throw new Error(data.message || "No se pudo obtener los datos de la venta.");
      }

      const venta = data.data.venta;
      const detalles = data.data.detalle;

      // Cerrar el loading
      Swal.close();

      // Llenar el formulario con los datos de la venta
      await llenarFormularioEdicion(venta, detalles);

    } catch (error) {
      console.error('Error al cargar venta para editar:', error);
      Swal.fire("Error", error.message || "No se pudo cargar la venta para editar.", "error");
      cerrarModal("ventaModal");
    }
  }

  let clienteOriginalEdicion = null; // Variable para almacenar el cliente original en modo edición

  // Función para llenar el formulario con los datos de la venta
  async function llenarFormularioEdicion(venta, detalles) {
    try {
      // Almacenar el cliente original
      clienteOriginalEdicion = venta.cliente_id || null;
      
      // Llenar campos básicos
      document.getElementById("fecha_venta_modal").value = venta.fecha_venta || "";
      document.getElementById("observaciones").value = venta.observaciones || "";
      
      // Seleccionar cliente
      if (venta.cliente_cedula) {
        const clienteInfo = {
          id: venta.cliente_id,
          cedula: venta.cliente_cedula,
          nombre: venta.cliente_nombre || "",
          apellido: venta.cliente_apellido || "",
          telefono_principal: venta.cliente_telefono || "",
          direccion: venta.cliente_direccion || "",
          observaciones: venta.cliente_observaciones || "",
          estatus: venta.cliente_estatus || "1"
        };
        
        // Llenar la información del cliente seleccionado
        const clienteInfoDiv = document.getElementById("cliente_seleccionado_info_modal");
        if (clienteInfoDiv) {
          clienteInfoDiv.innerHTML = `
            <strong>Cliente seleccionado:</strong><br>
            <span><strong>Nombre:</strong> ${clienteInfo.nombre} ${clienteInfo.apellido}</span><br>
            <span><strong>Cédula:</strong> ${clienteInfo.cedula}</span><br>
            <span><strong>Teléfono:</strong> ${clienteInfo.telefono_principal || 'No especificado'}</span>
          `;
          clienteInfoDiv.classList.remove("hidden");
          clienteInfoDiv.setAttribute("data-cliente-id", clienteInfo.id);
        }
        
        // Establecer el ID del cliente en el campo oculto
        const idClienteField = document.getElementById("idcliente");
        if (idClienteField) {
          idClienteField.value = clienteInfo.id;
        }
      }

      // Llenar descuento general
      if (venta.descuento_porcentaje_general) {
        document.getElementById("descuento_porcentaje_general").value = venta.descuento_porcentaje_general;
      }

      // Limpiar detalles actuales
      const detalleBody = document.getElementById("detalleVentaBody");
      const noDetallesMsg = document.getElementById("noDetallesMensaje");
      
      if (detalleBody) {
        detalleBody.innerHTML = "";
      }

      // Cargar productos para los selects
      await cargarProductosSelect();

      // Agregar los detalles existentes
      if (Array.isArray(detalles) && detalles.length > 0) {
        if (noDetallesMsg) {
          noDetallesMsg.classList.add("hidden");
        }

        detalles.forEach((detalle, index) => {
          agregarFilaDetalle({
            producto_id: detalle.idproducto,
            nombre_producto: detalle.nombre_producto,
            cantidad: detalle.cantidad,
            precio_unitario_venta: detalle.precio_unitario_venta,
            codigo_moneda: detalle.codigo_moneda || 'VES'
          });
        });
      } else {
        if (noDetallesMsg) {
          noDetallesMsg.classList.remove("hidden");
        }
      }

      // Recalcular totales
      calcularTotalesGenerales();

    } catch (error) {
      console.error('Error al llenar formulario:', error);
      throw error;
    }
  }

  // Función auxiliar para cargar productos en el select
  async function cargarProductosSelect() {
    try {
      const response = await fetch("ventas/getProductosDisponibles");
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }
      const data = await response.json();
      
      if (data.status && data.data) {
        listaProductosCargadosSelect = data.data;
        // Actualizar el select de productos
        const selectProducto = document.getElementById("select_producto_agregar_modal");
        if (selectProducto) {
          selectProducto.innerHTML = '<option value="">Seleccione un producto</option>';
          data.data.forEach(producto => {
            const option = document.createElement('option');
            option.value = producto.idproducto || producto.id;
            // Usar la misma lógica que en el modo de creación para mostrar moneda y precio
            option.textContent = `${producto.nombre_producto} (${producto.nombre_categoria || "N/A"}) - ${producto.codigo_moneda_producto || 'N/A'} ${parseFloat(producto.precio_unitario || 0).toFixed(2)}`;
            selectProducto.appendChild(option);
          });
        }
      }
    } catch (error) {
      console.error('Error al cargar productos:', error);
    }
  }

  // Función auxiliar para agregar una fila de detalle
  function agregarFilaDetalle(producto) {
    const idProd = producto.producto_id;
    const nombreProd = `${producto.nombre_producto}`;
    const cantidad = producto.cantidad || 1;
    const precioUnit = parseFloat(producto.precio_unitario_venta || 0).toFixed(2);
    const subtotal = (cantidad * parseFloat(precioUnit)).toFixed(2);
    const monedaCodigo = producto.codigo_moneda || 'VES';

    const nuevaFilaHTML = `
    <tr>
      <td class="px-3 py-1.5">
        <input type="hidden" name="detalle_idproducto[]" value="${idProd}">
        <input type="hidden" name="detalle_moneda_original[]" value="${monedaCodigo}">
        <input type="hidden" name="detalle_precio_original[]" value="${precioUnit}">
        <span>${nombreProd}</span>
        <br><small class="text-gray-500">Precio: ${monedaCodigo} ${precioUnit}</small>
      </td>
      <td class="px-3 py-1.5">
        <input type="number" name="detalle_cantidad[]" 
               class="w-full px-2 py-1 text-xs border rounded-md cantidad-input" 
               value="${cantidad}" min="1" step="1">
      </td>
      <td class="px-3 py-1.5">
        <input type="number" name="detalle_precio_unitario_venta[]" 
               class="w-full px-2 py-1 text-xs border rounded-md precio-input bg-gray-100" 
               value="${precioUnit}" readonly step="0.01">
      </td>
      <td class="px-3 py-1.5">
        <input type="number" name="detalle_subtotal[]" 
               class="w-full px-2 py-1 text-xs border rounded-md subtotal-input bg-gray-100" 
               value="${subtotal}" readonly step="0.01">
      </td>
      <td class="px-3 py-1.5 text-center">
        <button type="button" class="text-red-500 eliminar-detalle-btn hover:text-red-700">
          <i class="fas fa-trash"></i>
        </button>
      </td>
    </tr>`;

    detalleVentaBody.insertAdjacentHTML("beforeend", nuevaFilaHTML);
    actualizarEventosDetalle();
  }

  // Event listeners para los nuevos botones
  // Función para generar botones de acción con estados
  function generarBotonesAccionVentas(data, type, row) {
    var idVenta = row.idventa || "";
    var nroVenta = row.nro_venta || "Sin número";
    var estadoActual = row.estatus || "";
    var botones = [];

    // Botón Ver (disponible si tiene permisos de ver, editar o eliminar)
    if (window.PERMISOS_USUARIO && 
        (window.PERMISOS_USUARIO.puede_ver || 
         window.PERMISOS_USUARIO.puede_editar || 
         window.PERMISOS_USUARIO.puede_eliminar)) {
      botones.push(`
        <button
          class="ver-detalle-btn text-green-600 hover:text-green-700 p-1 transition-colors duration-150"
          data-idventa="${idVenta}"
          title="Ver detalles"
        >
          <i class="fas fa-eye fa-fw text-base"></i>
        </button>
      `);
    }

    // Botón Nota de Despacho (disponible solo para ventas en estado PAGADA)
    if (window.PERMISOS_USUARIO && window.PERMISOS_USUARIO.puede_ver && 
        estadoActual.toUpperCase() === "PAGADA") {
      botones.push(`
        <button
          class="nota-despacho-btn text-purple-600 hover:text-purple-700 p-1 transition-colors duration-150"
          data-idventa="${idVenta}"
          title="Generar Nota de Despacho"
        >
          <i class="fas fa-file-alt fa-fw text-base"></i>
        </button>
      `);
    }

    // Botón Editar (en estado BORRADOR y POR_PAGAR)
    if ((window.PERMISOS_USUARIO && window.PERMISOS_USUARIO.puede_editar) && 
        (estadoActual.toUpperCase() === "BORRADOR" || estadoActual.toUpperCase() === "POR_PAGAR")) {
      botones.push(`
        <button
          class="editar-venta-btn text-blue-600 hover:text-blue-700 p-1 transition-colors duration-150"
          data-idventa="${idVenta}"
          title="Modificar"
        >
          <i class="fas fa-edit fa-fw text-base"></i>
        </button>
      `);
    }

    // Botones de cambio de estado
    if (window.PERMISOS_USUARIO && (window.PERMISOS_USUARIO.puede_editar || window.PERMISOS_USUARIO.puede_eliminar)) {
      switch (estadoActual.toUpperCase()) {
        case "BORRADOR":
          // Enviar a pago
          botones.push(`
            <button
              class="cambiar-estado-venta-btn text-blue-500 hover:text-blue-700 p-1 transition-colors duration-150"
              data-idventa="${idVenta}"
              data-nuevo-estado="POR_PAGAR"
              title="Enviar a Pago"
            >
              <i class="fas fa-paper-plane fa-fw text-base"></i>
            </button>
          `);
          break;

        case "POR_PAGAR":
          // Marcar como pagada
          botones.push(`
            <button
              class="cambiar-estado-venta-btn text-green-500 hover:text-green-700 p-1 transition-colors duration-150"
              data-idventa="${idVenta}"
              data-nuevo-estado="PAGADA"
              title="Marcar como Pagada"
            >
              <i class="fas fa-check fa-fw text-base"></i>
            </button>
          `);
          break;

        case "PAGADA":
          // Ver pagos
          botones.push(`
            <button
              class="ir-pagos-venta-btn text-green-600 hover:text-green-800 p-1 transition-colors duration-150"
              data-idventa="${idVenta}"
              title="Ver Pagos"
            >
              <i class="fas fa-credit-card fa-fw text-base"></i>
            </button>
          `);
          break;
      }
    }

    // Botón Eliminar (en estado BORRADOR y POR_PAGAR)
    if ((window.PERMISOS_USUARIO && window.PERMISOS_USUARIO.puede_eliminar) && 
        (estadoActual.toUpperCase() === "BORRADOR" || estadoActual.toUpperCase() === "POR_PAGAR")) {
      botones.push(`
        <button
          class="eliminar-btn text-red-600 hover:text-red-700 p-1 transition-colors duration-150"
          data-idventa="${idVenta}"
          data-nro="${nroVenta}"
          title="Eliminar"
        >
          <i class="fas fa-trash-alt fa-fw text-base"></i>
        </button>
      `);
    }

    return `<div class="inline-flex items-center space-x-1">${botones.join('')}</div>`;
  }

  // Actualizar la función inicializarDataTable para usar los nuevos botones
  function inicializarDataTable() {
    let columnsConfig = [
      { data: "nro_venta", title: "Nro. Venta" },
      { data: "cliente_nombre", title: "Cliente" },
      { data: "fecha_venta", title: "Fecha" },
      {
        data: "estatus",
        title: "Estatus",
        render: function (data, type, row) {
          const estadoUpper = data.toUpperCase();
          if (estadoUpper === "ACTIVO" || estadoUpper === "PAGADA") {
            return `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">${data}</span>`;
          } else if (estadoUpper === "INACTIVO" || estadoUpper === "ANULADA") {
            return `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">${data}</span>`;
          } else if (estadoUpper === "BORRADOR") {
            return `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">${data}</span>`;
          } else if (estadoUpper === "POR_PAGAR") {
            return `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">${data}</span>`;
          } else {
            return `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">${data}</span>`;
          }
        },
      },
    ];

    // Agregar columna de acciones si tiene permisos
    if (
      window.PERMISOS_USUARIO &&
      (window.PERMISOS_USUARIO.puede_editar ||
        window.PERMISOS_USUARIO.puede_eliminar ||
        window.PERMISOS_USUARIO.puede_ver)
    ) {
      columnsConfig.push({
        data: null,
        title: "Acciones",
        orderable: false,
        searchable: false,
        render: generarBotonesAccionVentas,
      });
    }

    $("#Tablaventas").DataTable({
      processing: true,
      serverSide: false,
      ajax: {
        url: "ventas/getventasData",
        type: "GET",
        dataSrc: function(json) {
          return json.data || [];
        },
        error: function (xhr, status, error) {
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

  /**
   * Obtiene la tasa de cambio actual de una moneda
   */
  async function obtenerTasaMoneda(codigoMoneda) {
    try {
      const response = await fetch(`ventas/getTasaMoneda?codigo=${encodeURIComponent(codigoMoneda)}`);
      const data = await response.json();
      
      if (data.status && data.data) {
        return data.data.tasa_a_bs || 1;
      }
      return 1;
    } catch (error) {
      console.error('Error al obtener tasa de moneda:', error);
      return 1;
    }
  }

  /**
   * Convierte precio según la moneda del producto y la moneda general de la venta
   */
  async function convertirPrecioSegunMoneda(productoData) {
    const selectMoneda = document.getElementById("idmoneda_general");
    if (!selectMoneda || !selectMoneda.selectedOptions[0]) {
      return parseFloat(productoData.precio_unitario || 0);
    }
    
    const monedaGeneralVenta = selectMoneda.selectedOptions[0].dataset.codigo || '';
    const monedaProducto = productoData.codigo_moneda_producto;
    let precioFinal = parseFloat(productoData.precio_unitario || 0);

    // Si las monedas son diferentes, hacer conversión
    if (monedaProducto && monedaGeneralVenta && monedaProducto !== monedaGeneralVenta) {
      try {
        // Si el producto está en moneda extranjera y la venta en VES
        if (monedaProducto !== 'VES' && monedaGeneralVenta === 'VES') {
          const tasaProducto = await obtenerTasaMoneda(monedaProducto);
          precioFinal = precioFinal * tasaProducto;
        }
        // Si el producto está en VES y la venta en moneda extranjera  
        else if (monedaProducto === 'VES' && monedaGeneralVenta !== 'VES') {
          const tasaVenta = await obtenerTasaMoneda(monedaGeneralVenta);
          precioFinal = precioFinal / tasaVenta;
        }
        // Si ambas son monedas extranjeras diferentes
        else if (monedaProducto !== 'VES' && monedaGeneralVenta !== 'VES' && monedaProducto !== monedaGeneralVenta) {
          const tasaProducto = await obtenerTasaMoneda(monedaProducto);
          const tasaVenta = await obtenerTasaMoneda(monedaGeneralVenta);
          // Convertir primero a VES, luego a la moneda de venta
          precioFinal = (precioFinal * tasaProducto) / tasaVenta;
        }
      } catch (error) {
        console.error('Error en conversión de moneda:', error);
        // En caso de error, usar precio original
      }
    }

    return precioFinal;
  }

  /**
   * Recalcula todos los precios del detalle cuando cambia la moneda general
   */
  async function recalcularPreciosDetalle() {
    const filas = detalleVentaBody.querySelectorAll("tr");
    
    if (filas.length === 0) return;

    // Mostrar loading
    Swal.fire({
      title: 'Recalculando precios...',
      text: 'Actualizando cotizaciones',
      allowOutsideClick: false,
      showConfirmButton: false,
      willOpen: () => {
        Swal.showLoading();
      }
    });

    for (const fila of filas) {
      const idProducto = fila.querySelector("input[name='detalle_idproducto[]']").value;
      const monedaOriginal = fila.querySelector("input[name='detalle_moneda_original[]']").value;
      const precioOriginal = parseFloat(fila.querySelector("input[name='detalle_precio_original[]']").value || 0);
      
      // Encontrar datos del producto en la lista cargada
      const productoData = listaProductosCargadosSelect.find(
        (p) => String(p.idproducto || p.id) === String(idProducto)
      );
      
      if (productoData) {
        // Crear objeto temporal con los datos originales del producto
        const datosProductoOriginal = {
          ...productoData,
          codigo_moneda_producto: monedaOriginal,
          precio_unitario: precioOriginal
        };
        
        // Convertir precio según nueva moneda
        const precioConvertido = await convertirPrecioSegunMoneda(datosProductoOriginal);
        const nuevoPrecio = precioConvertido.toFixed(2);
        
        // Actualizar precio unitario
        const inputPrecio = fila.querySelector("input[name='detalle_precio_unitario_venta[]']");
        inputPrecio.value = nuevoPrecio;
        
        // Recalcular subtotal
        const cantidad = parseFloat(fila.querySelector("input[name='detalle_cantidad[]']").value || 1);
        const nuevoSubtotal = (precioConvertido * cantidad).toFixed(2);
        const inputSubtotal = fila.querySelector("input[name='detalle_subtotal[]']");
        inputSubtotal.value = nuevoSubtotal;
      }
    }
    
    // Cerrar loading y recalcular totales
    Swal.close();
    calcularTotalesGenerales();
  }

  // Agregar evento al cambio de moneda general (ambos selects)
  ["idmoneda_general", "idmoneda_general_modal"].forEach(selectId => {
    const selectElement = document.getElementById(selectId);
    if (selectElement) {
      selectElement.addEventListener("change", function() {
        const filasDetalle = detalleVentaBody.querySelectorAll("tr");
        if (filasDetalle.length > 0) {
          recalcularPreciosDetalle();
        }
      });
    }
  });

  // Event listener para botón de nota de despacho
  document.addEventListener("click", function (e) {
    // Verificar si el click fue en el botón o en su ícono
    const notaDespachoBtn = e.target.closest(".nota-despacho-btn");
    if (notaDespachoBtn) {
      e.preventDefault();
      e.stopPropagation();
      
      const idventa = notaDespachoBtn.getAttribute("data-idventa");
      if (!idventa || isNaN(parseInt(idventa))) {
        Swal.fire("Error", "ID de venta no válido.", "error");
        return;
      }

      // Navegar en la misma ventana
      window.location.href = `${base_url}ventas/notaDespacho/${idventa}`;
    }
  });

  // Funciones para el modal de registro de cliente
  const camposFormularioClienteModal = [
    {
      id: "cedula_cliente_modal",
      tipo: "input",
      regex: expresiones.cedula,
      mensajes: {
        vacio: "La cédula es obligatoria.",
        formato: "La Cédula debe contener la estructura V-/J-/E- No debe contener espacios y solo números.",
      },
    },
    {
      id: "nombre_cliente_modal",
      tipo: "input",
      regex: expresiones.nombre,
      mensajes: {
        vacio: "El nombre es obligatorio.",
        formato: "El nombre debe tener entre 3 y 50 caracteres alfabéticos.",
      },
    },
    {
      id: "apellido_cliente_modal",
      tipo: "input",
      regex: expresiones.apellido,
      mensajes: {
        vacio: "El apellido es obligatorio.",
        formato: "El apellido debe tener entre 3 y 50 caracteres alfabéticos.",
      },
    },
    {
      id: "telefono_principal_cliente_modal",
      tipo: "input",
      regex: expresiones.telefono_principal,
      mensajes: {
        vacio: "El teléfono es obligatorio.",
        formato: "El teléfono debe tener exactamente 11 dígitos. No debe contener letras. Debe comenzar con 0412, 0414, 0424 o 0416.",
      },
    },
    {
      id: "direccion_cliente_modal",
      tipo: "input",
      regex: expresiones.direccion,
      mensajes: {
        vacio: "La dirección es obligatoria.",
        formato: "La dirección debe tener entre 5 y 100 caracteres.",
      },
    },
    {
      id: "observaciones_cliente_modal",
      tipo: "input",
      regex: expresiones.observaciones,
      mensajes: {
        formato: "Las observaciones no deben exceder los 50 caracteres.",
      },
    },
  ];

  const camposObligatoriosClienteModal = [
    "cedula_cliente_modal",
    "nombre_cliente_modal", 
    "apellido_cliente_modal",
    "telefono_principal_cliente_modal",
    "direccion_cliente_modal"
  ];

  function inicializarModalRegistrarCliente() {
    const btnAbrirModal = document.getElementById("btnAbrirModalRegistrarCliente");
    const btnCerrarModal = document.getElementById("cerrarModalRegistrarClienteBtn");
    const btnCancelarModal = document.getElementById("cancelarRegistrarClienteBtn");
    const formRegistrarCliente = document.getElementById("formRegistrarCliente");

    if (btnAbrirModal) {
      btnAbrirModal.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        abrirModalRegistrarCliente();
      });
    }

    if (btnCerrarModal) {
      btnCerrarModal.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        cerrarModalRegistrarCliente();
      });
    }

    if (btnCancelarModal) {
      btnCancelarModal.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        cerrarModalRegistrarCliente();
      });
    }

    if (formRegistrarCliente) {
      formRegistrarCliente.addEventListener("submit", async function (e) {
        e.preventDefault();
        await registrarClienteModal();
      });
    }

    // Inicializar validaciones en tiempo real
    inicializarValidaciones(camposFormularioClienteModal, "formRegistrarCliente");
  }

  function abrirModalRegistrarCliente() {
    const modal = document.getElementById("modalRegistrarCliente");
    const form = document.getElementById("formRegistrarCliente");

    if (modal) {
      if (form) form.reset();
      limpiarValidaciones(camposFormularioClienteModal, "formRegistrarCliente");
      inicializarValidaciones(camposFormularioClienteModal, "formRegistrarCliente");
      abrirModal("modalRegistrarCliente");
    }
  }

  function cerrarModalRegistrarCliente() {
    const modal = document.getElementById("modalRegistrarCliente");
    const form = document.getElementById("formRegistrarCliente");

    if (modal) {
      limpiarValidaciones(camposFormularioClienteModal, "formRegistrarCliente");
      if (form) form.reset();
      cerrarModal("modalRegistrarCliente");
    }
  }

  async function registrarClienteModal() {
    try {
      // Validar campos obligatorios
      if (!validarCamposVacios(camposObligatoriosClienteModal, "formRegistrarCliente")) {
        return;
      }

      // Validar formato de campos
      let todosValidos = true;
      let camposConError = [];
      for (const campo of camposFormularioClienteModal) {
        const inputElement = document.getElementById(campo.id);
        if (inputElement) {
          const esValidoEsteCampo = validarCampo(inputElement, campo.regex, campo.mensajes);
          if (!esValidoEsteCampo) {
            todosValidos = false;
            camposConError.push(campo.mensajes.vacio || campo.mensajes.formato);
          }
        }
      }

      if (!todosValidos) {
        Swal.fire({
          title: 'Error de validación',
           html: `<ul style="text-align:left;">${camposConError.map(msg => `<li>• ${msg}</li>`).join('')}</ul>`,
          icon: 'error'
        });
        return;
      }

      // Mostrar loading en el botón
      const submitBtn = document.getElementById("registrarClienteBtn");
      const originalText = submitBtn.innerHTML;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Registrando...';
      submitBtn.disabled = true;

      // Recopilar datos del formulario
      const formData = {
        nombre: document.getElementById("nombre_cliente_modal").value.trim(),
        apellido: document.getElementById("apellido_cliente_modal").value.trim(),
        identificacion: document.getElementById("cedula_cliente_modal").value.trim(), // Mapear cedula a identificacion
        telefono_principal: document.getElementById("telefono_principal_cliente_modal").value.trim(),
        direccion: document.getElementById("direccion_cliente_modal").value.trim(),
        observaciones: document.getElementById("observaciones_cliente_modal").value.trim()
      };

      // Enviar datos al servidor
      const response = await fetch(`${base_url}clientes/registrarClienteModal`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
      });

      const result = await response.json();

      if (result.status) {
        Swal.fire({
          title: 'Éxito',
          text: result.message,
          icon: 'success',
          timer: 2000,
          showConfirmButton: false
        });

        // Cerrar modal
        cerrarModalRegistrarCliente();

        // Seleccionar automáticamente el cliente recién creado
        if (result.cliente_id) {
          seleccionarClienteRecienCreado(result.cliente_id, formData.nombre, formData.apellido, formData.identificacion);
        }
      } else {
        throw new Error(result.message || 'Error al registrar cliente');
      }

    } catch (error) {
      console.error('Error al registrar cliente:', error);
      Swal.fire({
        title: 'Error',
        text: error.message || 'Error al registrar el cliente',
        icon: 'error'
      });
    } finally {
      // Restaurar botón
      const submitBtn = document.getElementById("registrarClienteBtn");
      if (submitBtn) {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
      }
    }
  }

  function seleccionarClienteRecienCreado(id, nombre, apellido, identificacion) {
    // Seleccionar el cliente en el formulario principal
    const idClienteInput = document.getElementById("idcliente");
    const clienteInfoDiv = document.getElementById("cliente_seleccionado_info_modal");

    if (idClienteInput) {
      idClienteInput.value = id;
    }

    if (clienteInfoDiv) {
      clienteInfoDiv.innerHTML = `
        <strong>Cliente seleccionado:</strong><br>
        <span class="text-green-600">${nombre} ${apellido}</span><br>
        <span class="text-gray-600">CI: ${identificacion}</span>
      `;
      clienteInfoDiv.classList.remove("hidden");
    }

    // Limpiar el campo de búsqueda
    const criterioInput = document.getElementById("inputCriterioClienteModal");
    if (criterioInput) {
      criterioInput.value = "";
    }

    // Ocultar lista de resultados
    const listaResultados = document.getElementById("listaResultadosClienteModal");
    if (listaResultados) {
      listaResultados.classList.add("hidden");
    }
  }


});
