import { abrirModal, cerrarModal } from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  validarCamposVacios,
  validarDetalleVenta,
  validarSelect,
  validarFecha,
  limpiarValidaciones,
  cargarSelect,
  registrarEntidad,
  validarCampo,
} from "./validaciones.js";

document.addEventListener("DOMContentLoaded", function () {
  // --- Inicialización General ---
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
        fechaPosterior: "La fecha no puede ser posterior a hoy." 
      }
    },
    { 
      id: "idmoneda_general", 
      name_attr: "idmoneda_general", 
      tipo: "select", 
      mensajes: { 
        vacio: "Debe seleccionar una moneda." 
      }
    },
    { 
      id: "observaciones", 
      name_attr: "observaciones", 
      tipo: "textarea", 
      regex: expresiones.observaciones, 
      mensajes: { 
        formato: "Las observaciones no deben exceder los 50 caracteres." 
      }
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
        formato: "Formato de cédula inválido (ej: V-12345678)." 
      }
    },
    { 
      id: "nombre_nuevo", 
      name_attr: "nombre_nuevo_formulario", 
      backend_key: "nombre", 
      tipo: "input", 
      regex: expresiones.nombre, 
      mensajes: { 
        vacio: "El nombre es obligatorio.", 
        formato: "El nombre debe tener entre 2 y 30 caracteres." 
      }
    },
    { 
      id: "apellido_nuevo", 
      name_attr: "apellido_nuevo_formulario", 
      backend_key: "apellido", 
      tipo: "input", 
      regex: expresiones.apellido, 
      mensajes: { 
        vacio: "El apellido es obligatorio.", 
        formato: "El apellido debe tener entre 2 y 30 caracteres." 
      }
    },
    { 
      id: "telefono_principal_nuevo", 
      name_attr: "telefono_principal_nuevo_formulario", 
      backend_key: "telefono_principal", 
      tipo: "input", 
      regex: expresiones.telefono_principal, 
      mensajes: { 
        vacio: "El teléfono es obligatorio.", 
        formato: "El teléfono debe tener 11 dígitos." 
      }
    },
    { 
      id: "direccion_nuevo", 
      name_attr: "direccion_nuevo_formulario", 
      backend_key: "direccion", 
      tipo: "input", 
      regex: expresiones.direccion, 
      mensajes: { 
        vacio: "La dirección es obligatoria.", 
        formato: "La dirección debe tener entre 5 y 100 caracteres." 
      }
    },
    { 
      id: "observacionesCliente_nuevo", 
      name_attr: "observacionesCliente_nuevo_formulario", 
      backend_key: "observaciones", 
      tipo: "textarea", 
      regex: expresiones.observaciones, 
      mensajes: { 
        formato: "Las observaciones no deben exceder los 50 caracteres." 
      }
    },
    { 
      id: "estatus_nuevo", 
      name_attr: "estatus_nuevo_formulario", 
      backend_key: "estatus", 
      tipo: "select", 
      mensajes: { 
        vacio: "Debe seleccionar un estatus." 
      }
    },
  ];

  let listaProductosCargadosSelect = [];

  // --- Elementos del DOM ---
  const btnToggleNuevoCliente = document.getElementById("btnToggleNuevoCliente");
  const nuevoClienteContainer = document.getElementById("nuevoClienteContainer");
  const registrarClienteInlineBtn = document.getElementById("registrarClienteInlineBtn");
  const cancelarNuevoClienteBtn = document.getElementById("cancelarNuevoClienteBtn");
  const descuentoPorcentajeGeneralInput = document.getElementById("descuento_porcentaje_general");
  const detalleVentaBody = document.getElementById("detalleVentaBody");
  const noDetallesMsg = document.getElementById("noDetallesMensaje");

  // --- Funciones Auxiliares de UI y Estado ---
  function setCamposEmbebidosHabilitados(habilitar) {
    if (!nuevoClienteContainer) return;
    camposNuevoClienteEmbebido.forEach((campo) => {
      const input = nuevoClienteContainer.querySelector(`#${campo.id}`);
      if (input) input.disabled = !habilitar;
    });
    nuevoClienteContainer.querySelectorAll("button").forEach(b => b.disabled = !habilitar);
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
      btnToggleNuevoCliente.innerHTML = '<i class="mr-2 fas fa-user-plus"></i>Registrar Nuevo Cliente';
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
      'subtotal_general_display_modal', 'subtotal_general', 
      'monto_descuento_general_display', 'monto_descuento_general', 
      'total_general_display_modal', 'total_general'
    ].forEach(id => { 
      const el = document.getElementById(id); 
      if (el) el.value = "0.00"; 
    });
    
    if(descuentoPorcentajeGeneralInput) descuentoPorcentajeGeneralInput.value = "0";
    
    const selectProducto = document.getElementById("select_producto_agregar_modal");
    if (selectProducto) selectProducto.value = "";
    
    const inputCriterio = document.getElementById("inputCriterioClienteModal");
    const listaResultados = document.getElementById("listaResultadosClienteModal");
    const inputIdCliente = document.getElementById("idcliente");
    const divInfoCliente = document.getElementById("cliente_seleccionado_info_modal");
    
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
    
    document.querySelectorAll('[id^="error-"]').forEach((el) => el.classList.add("hidden"));
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
        this.innerHTML = '<i class="mr-2 fas fa-times"></i>Cancelar Nuevo Cliente';
        const primerCampo = nuevoClienteContainer.querySelector('input:not([type="hidden"]), select, textarea');
        if (primerCampo) primerCampo.focus();
        document.getElementById("idcliente").value = "";
        document.getElementById("inputCriterioClienteModal").value = "";
        document.getElementById("cliente_seleccionado_info_modal").classList.add("hidden");
      } else {
        resetYDeshabilitarFormClienteEmbebido();
      }
    });
  }

  if (registrarClienteInlineBtn && nuevoClienteContainer) {
    registrarClienteInlineBtn.addEventListener("click", function () {
      if (!validarCamposVacios(camposNuevoClienteEmbebido, "ventaForm")) return;
      
      let formClienteValido = true;
      camposNuevoClienteEmbebido.forEach((campo) => {
        const inputElement = ventaForm.querySelector(`#${campo.id}`);
        if (inputElement && inputElement.offsetParent !== null) {
          let esValidoEsteCampo = true;
          if (campo.tipo === "select") {
            esValidoEsteCampo = validarSelect(inputElement, campo.mensajes);
          } else {
            esValidoEsteCampo = validarCampo(inputElement, campo.regex, campo.mensajes);
          }
          if (!esValidoEsteCampo) formClienteValido = false;
        }
      });
      
      if (!formClienteValido) return;
      
      const datosParaBackend = {};
      camposNuevoClienteEmbebido.forEach(c => {
        const input = ventaForm.querySelector(`#${c.id}`);
        if (input) datosParaBackend[c.backend_key] = input.value;
      });
      
      registrarEntidad({
        formId: "ventaForm", 
        endpoint: "clientes/createcliente",
        campos: camposNuevoClienteEmbebido, 
        datosParaEnviar: datosParaBackend,
        onSuccess: (result) => {
          if (result.status && result.data && result.data.id) {
            Swal.fire("¡Éxito!", result.message || "Cliente registrado.", "success");
            resetYDeshabilitarFormClienteEmbebido();
            document.getElementById("idcliente").value = result.data.id;
            const nombreCompleto = `${result.data.nombre || ""} ${result.data.apellido || ""} (C.I.: ${result.data.cedula || ""})`.trim();
            document.getElementById("inputCriterioClienteModal").value = nombreCompleto;
            const divInfo = document.getElementById("cliente_seleccionado_info_modal");
            divInfo.innerHTML = `Sel: <strong>${nombreCompleto}</strong>`;
            divInfo.classList.remove("hidden");
          } else {
            Swal.fire("¡Error!", result.message || "No se pudo registrar el cliente.", "error");
          }
        },
        onError: (err) => Swal.fire("¡Error!", err.message || "Error registrando cliente.", "error"),
      });
    });
  }

  if (cancelarNuevoClienteBtn) {
    cancelarNuevoClienteBtn.addEventListener("click", resetYDeshabilitarFormClienteEmbebido);
  }

  // --- Lógica de Buscador de Clientes ---
  function inicializarBuscadorCliente() {
    const inputCriterio = document.getElementById("inputCriterioClienteModal");
    const btnBuscar = document.getElementById("btnBuscarClienteModal");
    const listaResultados = document.getElementById("listaResultadosClienteModal");
    const inputIdCliente = document.getElementById("idcliente");
    const divInfoCliente = document.getElementById("cliente_seleccionado_info_modal");
    
    if (!btnBuscar || !inputCriterio || !listaResultados || !inputIdCliente || !divInfoCliente) return;
    if (btnBuscar.dataset.listenerAttached === "true") return;
    
    btnBuscar.dataset.listenerAttached = "true";
    
    btnBuscar.addEventListener("click", async function () {
      if (this.disabled) return;
      
      const criterio = inputCriterio.value.trim();
      if (criterio.length < 2) { 
        Swal.fire("Atención", "Ingrese al menos 2 caracteres.", "warning"); 
        return; 
      }
      
      listaResultados.innerHTML = '<div class="p-2 text-xs text-gray-500">Buscando...</div>';
      listaResultados.classList.remove("hidden");
      
      try {
        const response = await fetch(`clientes/buscar?criterio=${encodeURIComponent(criterio)}`);
        if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
        
        const clientes = await response.json();
        listaResultados.innerHTML = "";
        
        if (clientes && clientes.length > 0) {
          clientes.forEach((cli) => {
            const itemDiv = document.createElement("div");
            itemDiv.className = "p-2 text-xs hover:bg-gray-100 cursor-pointer";
            itemDiv.textContent = `${cli.nombre || ""} ${cli.apellido || ""} (C.I.: ${cli.cedula || ""})`.trim();
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
          listaResultados.innerHTML = '<div class="p-2 text-xs text-gray-500">No se encontraron.</div>';
        }
      } catch (error) {
        console.error("Error al buscar clientes:", error);
        listaResultados.innerHTML = `<div class="p-2 text-xs text-red-500">Error: ${error.message}.</div>`;
      }
    });
    
    inputCriterio.addEventListener("input", function () {
      if (this.disabled) return;
      if (nuevoClienteContainer && nuevoClienteContainer.classList.contains("hidden")) {
        inputIdCliente.value = ""; 
        divInfoCliente.classList.add("hidden");
      }
    });
    
    document.addEventListener('click', function(event) {
      if (listaResultados && !listaResultados.contains(event.target) && 
          event.target !== inputCriterio && event.target !== btnBuscar) {
        listaResultados.classList.add('hidden');
      }
    });
  }

  // --- Lógica de Detalle de Venta ---
  document.getElementById("agregarDetalleBtn").addEventListener("click", function () {
    const selectProductoEl = document.getElementById("select_producto_agregar_modal");
    const idProductoSel = selectProductoEl.value;
    const errorDivSelect = document.getElementById("error-select_producto_agregar_modal-vacio") || selectProductoEl.nextElementSibling;
    
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
    const productoData = listaProductosCargadosSelect.find(p => 
      String(p.idproducto || p.id) === String(idProductoSel)
    );
    
    if (!productoData) { 
      Swal.fire("Error", "Producto no encontrado.", "error"); 
      return; 
    }
    
    // Verificar si el producto ya está agregado
    const yaAgregado = Array.from(detalleVentaBody.querySelectorAll("input[name='detalle_idproducto[]']"))
                            .some(input => input.value === String(productoData.idproducto || productoData.id));
    
    if (yaAgregado) { 
      Swal.fire("Atención", "Producto ya agregado.", "info"); 
      return; 
    }
    
    // Preparar datos del producto
    const idProd = productoData.idproducto || productoData.id;
    const nombreProd = `${productoData.nombre_producto} (${productoData.nombre_categoria || 'N/A'})`;
    const precioUnit = parseFloat(productoData.precio_unitario || 0).toFixed(2);
    
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
    detalleVentaBody.querySelectorAll(".cantidad-input").forEach(input => {
      input.oninput = function () {
        const fila = this.closest("tr");
        const precio = parseFloat(fila.querySelector(".precio-input").value) || 0;
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
    detalleVentaBody.querySelectorAll(".eliminar-detalle-btn").forEach(btn => {
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
    detalleVentaBody.querySelectorAll(".subtotal-input").forEach(input => {
      subtotalGeneral += parseFloat(input.value) || 0;
    });
    
    // Calcular descuento
    const descuentoP = parseFloat(descuentoPorcentajeGeneralInput?.value) || 0;
    const montoDesc = (subtotalGeneral * descuentoP) / 100;
    const totalGen = subtotalGeneral - montoDesc;
    
    // Actualizar campos de subtotal
    ['subtotal_general_display_modal', 'subtotal_general'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.value = subtotalGeneral.toFixed(2);
    });
    
    // Actualizar campos de descuento
    ['monto_descuento_general_display', 'monto_descuento_general'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.value = montoDesc.toFixed(2);
    });
    
    // Actualizar campos de total
    ['total_general_display_modal', 'total_general'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.value = totalGen.toFixed(2);
    });
  }

  if (descuentoPorcentajeGeneralInput) {
    descuentoPorcentajeGeneralInput.addEventListener("input", calcularTotalesGenerales);
  }

  // --- Eventos de Apertura/Cierre Modal y Registro de Venta Principal ---
  document.getElementById("abrirModalBtn").addEventListener("click", function () {
    abrirModal("ventaModal");
    limpiarFormularioVentaCompleto();
    
    // Establecer fecha actual
    const fechaInput = document.getElementById("fecha_venta_modal");
    if (fechaInput) {
      fechaInput.value = new Date().toISOString().split('T')[0];
    }
    
    cargarSelect({
      selectId: "idmoneda_general", 
      endpoint: "moneda/getMonedasActivas",
      optionTextFn: (m) => `${m.codigo_moneda} (${m.valor})`, 
      optionValueFn: (m) => m.idmoneda,
      placeholder: "Seleccione moneda...",
    });
    
    inicializarValidaciones(camposCabeceraVenta, "ventaForm");
    
    cargarSelect({
      selectId: "select_producto_agregar_modal", 
      endpoint: "productos/getListaProductosParaFormulario",
      optionTextFn: (p) => `${p.nombre_producto} (${p.nombre_categoria || "N/A"})`, 
      optionValueFn: (p) => p.idproducto || p.id || "",
      placeholder: "Seleccione producto...",
      onLoaded: (prods) => { 
        listaProductosCargadosSelect = prods || []; 
        console.log("Productos cargados:", listaProductosCargadosSelect);
      },
    });
    
    inicializarBuscadorCliente();
  });

  document.getElementById("btnCerrarModalNuevaVenta").addEventListener("click", function () {
    cerrarModal("ventaModal"); 
    limpiarFormularioVentaCompleto();
  });

  document.getElementById("cerrarModalBtn").addEventListener("click", function () {
    cerrarModal("ventaModal"); 
    limpiarFormularioVentaCompleto();
  });

  // --- REGISTRAR VENTA PRINCIPAL ---
  document.getElementById("registrarVentaBtn").addEventListener("click", function () {
    const idClienteSeleccionado = document.getElementById("idcliente").value;
    const nuevoClienteFormActivo = nuevoClienteContainer && !nuevoClienteContainer.classList.contains("hidden");

    // Validar cliente seleccionado
    if (!idClienteSeleccionado) {
      if (nuevoClienteFormActivo) {
        Swal.fire("Atención", "Guarde o cancele el nuevo cliente antes de proceder.", "warning");
        const btnGuardarCliente = document.getElementById("registrarClienteInlineBtn");
        if (btnGuardarCliente) btnGuardarCliente.focus();
      } else {
        Swal.fire("Atención", "Debe seleccionar un cliente para la venta.", "warning");
        document.getElementById("inputCriterioClienteModal").focus();
      }
      return;
    }

    // Validar campos de cabecera
    if (!validarCamposVacios(camposCabeceraVenta, "ventaForm")) return;
    
    let cabeceraValida = true;
    camposCabeceraVenta.forEach((campo) => {
      const inputEl = ventaForm.querySelector(`#${campo.id}`);
      if (inputEl && (inputEl.offsetParent !== null || inputEl.type === 'hidden')) {
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
      Swal.fire("Atención", "Debe agregar al menos un producto a la venta.", "warning");
      return;
    }

    // Preparar datos para envío - FORMATO COMPATIBLE CON EL BACKEND PHP
    const datosVentaFinal = {
      accion: 'crear',
      idcliente: parseInt(idClienteSeleccionado),
      fecha_venta: document.getElementById("fecha_venta_modal").value,
      total_venta: parseFloat(document.getElementById("total_general").value || 0),
      detalles: []
    };

    // Recopilar detalles en el formato esperado por el backend PHP
    filas.forEach(fila => {
      const idProducto = fila.querySelector("input[name='detalle_idproducto[]']").value;
      const cantidad = fila.querySelector("input[name='detalle_cantidad[]']").value;
      const precio = fila.querySelector("input[name='detalle_precio_unitario_venta[]']").value;
      const subtotal = fila.querySelector("input[name='detalle_subtotal[]']").value;

      // Validar que los datos no estén vacíos
      if (!idProducto || !cantidad || !precio || !subtotal) {
        console.warn("Detalle con datos incompletos encontrado:", {idProducto, cantidad, precio, subtotal});
        return;
      }

      datosVentaFinal.detalles.push({
        detalle_idproducto: parseInt(idProducto),
        detalle_cantidad: parseInt(cantidad),
        detalle_precio: parseFloat(precio),
        detalle_total: parseFloat(subtotal)
      });
    });

    // Validar que se agregaron detalles válidos
    if (datosVentaFinal.detalles.length === 0) {
      Swal.fire("Error", "No se encontraron productos válidos en el detalle.", "error");
      return;
    }

    console.log("Datos Finales de VENTA a enviar:", datosVentaFinal);

    // Enviar datos usando fetch directamente para compatibilidad con el backend PHP
    fetch('ventas.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(datosVentaFinal)
    })
    .then(response => response.json())
    .then(result => {
      if (result.success) {
        Swal.fire("¡Éxito!", result.message || "Venta registrada correctamente.", "success").then(() => {
          if (typeof $ !== 'undefined' && $("#Tablaventas").length) {
            $("#Tablaventas").DataTable().ajax.reload();
          }
          cerrarModal("ventaModal");
          limpiarFormularioVentaCompleto();
        });
      } else {
        Swal.fire("¡Error!", result.message || "No se pudo registrar la venta.", "error");
      }
    })
    .catch(error => {
      console.error("Error al registrar venta:", error);
      Swal.fire("¡Error!", "Error de comunicación con el servidor.", "error");
    });
  });

  // --- Eventos de Tabla y CRUD (Eliminar, Editar - placeholders) ---
  document.addEventListener("click", function (e) {
    const eliminarBtn = e.target.closest(".eliminar-btn");
    const editarBtn = e.target.closest(".editar-btn");
    
    if (eliminarBtn) {
      const idventa = eliminarBtn.getAttribute("data-idventa");
      if (idventa) confirmarEliminacion(idventa);
    }
    
    if (editarBtn) {
      const idventa = editarBtn.getAttribute("data-idventa");
      if (!idventa || isNaN(idventa)) { 
        Swal.fire("¡Error!", "ID de venta no válido.", "error"); 
        return; 
      }
      console.warn(`Funcionalidad Editar Venta para ID ${idventa} no implementada.`);
      // abrirModalventaParaEdicion(idventa);
    }
  });

  function confirmarEliminacion(idventa) {
    Swal.fire({
      title: "¿Estás seguro?", 
      text: "Esta acción podría cambiar el estatus.", 
      icon: "warning",
      showCancelButton: true, 
      confirmButtonColor: "#3085d6", 
      cancelButtonColor: "#d33",
      confirmButtonText: "Sí, continuar", 
      cancelButtonText: "Cancelar",
    }).then((result) => {
      if (result.isConfirmed) {
        fetch('ventas.php', {
          method: 'POST', 
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ 
            accion: 'eliminar',
            id: parseInt(idventa) 
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            Swal.fire('Actualizado', data.message || 'Venta actualizada.', 'success');
            if (typeof $ !== 'undefined' && $('#Tablaventas').length) {
              $('#Tablaventas').DataTable().ajax.reload();
            }
          } else {
            Swal.fire('Error', data.message || 'No se pudo procesar.', 'error');
          }
        })
        .catch(error => {
          Swal.fire('Error', 'Error de comunicación.', 'error');
          console.error('Error al eliminar/desactivar venta:', error);
        });
      }
    });
  }

  // --- Inicialización DataTable ---
  function inicializarDataTable() {
    if (typeof $ === 'undefined' || !$("#Tablaventas").length) {
      console.warn("jQuery o tabla de ventas no encontrada");
      return;
    }

    $("#Tablaventas").DataTable({
      processing: true, 
      serverSide: true,
      ajax: { 
        url: "ventas/getventasData", 
        type: "GET", 
        dataSrc: "data" 
      },
      columns: [
        { data: "nro_venta", title: "Nro Venta" },
        { data: "cliente_nombre", title: "Cliente" },
        { data: "fecha_venta", title: "Fecha" },
        { data: "total_general", title: "Total" },
        { data: "estatus", title: "Estatus" },
        { 
          data: null, 
          title: "Acciones", 
          orderable: false, 
          render: function (data, type, row) {
            return `
              <button class="editar-btn text-blue-500 hover:text-blue-700 p-1 rounded-full" 
                      data-idventa="${row.idventa}">
                <i class="fas fa-edit"></i>
              </button>
              <button class="eliminar-btn text-red-500 hover:text-red-700 p-1 rounded-full ml-2" 
                      data-idventa="${row.idventa}">
                <i class="fas fa-trash"></i>
              </button>
            `;
          },
        },
      ],
      language: {
        processing: "Procesando...",
        search: "Buscar:",
        lengthMenu: "Mostrar _MENU_ registros",
        info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
        infoEmpty: "Mostrando 0 a 0 de 0 registros",
        infoFiltered: "(filtrado de _MAX_ registros)",
        loadingRecords: "Cargando...",
        zeroRecords: "No se encontraron registros",
        emptyTable: "No hay datos disponibles",
        paginate: {
          first: "Primero",
          previous: "Anterior",
          next: "Siguiente",
          last: "Último"
        }
      },
      destroy: true, 
      responsive: true, 
      pageLength: 10, 
      order: [[0, "desc"]],
    });
  }
});
