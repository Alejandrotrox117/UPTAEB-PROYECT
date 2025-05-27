import { abrirModal, cerrarModal } from "./exporthelpers.js";
// import { } from "./functions_entidades.js"; // Comentado si no se usa directamente aquí
import {
  expresiones,
  inicializarValidaciones,
  validarCamposVacios,
  validarDetalleVenta,
  validarSelect,
  validarFecha,
  limpiarValidaciones,
  cargarSelect,
  registrarEntidad, // Asegúrate que esta es la función correcta
  validarCampo, // Importado para validación más granular
} from "./validaciones.js";

document.addEventListener("DOMContentLoaded", function () {
  inicializarDataTable();

  const camposVentasOriginal = [
    {
      id: "fecha_venta_modal",
      tipo: "date",
      mensajes: {
        vacio: "La fecha es obligatoria.",
        fechaPosterior: "La fecha no puede ser posterior a hoy.",
      },
    },
    {
      id: "idmoneda_general",
      tipo: "select",
      mensajes: {
        vacio: "Debe seleccionar una moneda.",
      },
    },
    {
      id: "observaciones",
      tipo: "textarea",
      regex: expresiones.observaciones,
      mensajes: {
        formato: "Las observaciones no deben exceder los 50 caracteres.",
      },
    },
    {
      id: "select_producto_agregar_modal",
      tipo: "select",
      mensajes: {
        vacio: "Debe seleccionar un producto para agregar.",
      },
    },
  ];

  const camposNuevoCliente = [
    {
      id: "cedula_nuevo",
      name_attr: "cedula_nuevo_formulario", // Atributo 'name' del HTML
      backend_key: "cedula", // Clave que espera el backend
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
        formato: "El nombre debe tener entre 2 y 30 caracteres.",
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
      backend_key: "observaciones", // O 'observaciones_cliente' si es diferente en backend
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

  let listaProductos = [];
  const ventaForm = document.getElementById("ventaForm");

  // --- Elementos del DOM para Cliente Embebido ---
  const btnToggleNuevoCliente = document.getElementById("btnToggleNuevoCliente");
  const nuevoClienteContainer = document.getElementById("nuevoClienteContainer");
  const registrarClienteInlineBtn = document.getElementById("registrarClienteInlineBtn");
  const cancelarNuevoClienteBtn = document.getElementById("cancelarNuevoClienteBtn");

  // --- Funciones Auxiliares ---
  function setCamposHabilitados(containerElement, habilitar, camposArray) {
    if (!containerElement) return;
    camposArray.forEach((campo) => {
      const input = containerElement.querySelector(`#${campo.id}`);
      if (input) {
        input.disabled = !habilitar;
      }
    });
    const botones = containerElement.querySelectorAll("button");
    botones.forEach((boton) => {
      boton.disabled = !habilitar;
    });
  }

  function resetYDeshabilitarFormClienteEmbebido() {
    if (nuevoClienteContainer) {
      camposNuevoCliente.forEach((campo) => {
        const input = nuevoClienteContainer.querySelector(`#${campo.id}`);
        if (input) {
          input.value = campo.id === "estatus_nuevo" ? "Activo" : "";
          const errorDivVacio = document.getElementById(`error-${campo.id}-vacio`);
          const errorDivFormato = document.getElementById(`error-${campo.id}-formato`);
          if (errorDivVacio) errorDivVacio.classList.add("hidden");
          if (errorDivFormato) errorDivFormato.classList.add("hidden");
          input.classList.remove("border-red-500");
        }
      });
      setCamposHabilitados(nuevoClienteContainer, false, camposNuevoCliente);
      nuevoClienteContainer.classList.add("hidden");
    }
    if (btnToggleNuevoCliente) {
      btnToggleNuevoCliente.innerHTML = '<i class="mr-2 fas fa-user-plus"></i>Registrar Nuevo Cliente';
    }
    const inputCriterio = document.getElementById("inputCriterioClienteModal");
    const btnBuscar = document.getElementById("btnBuscarClienteModal");
    if (inputCriterio) inputCriterio.disabled = false;
    if (btnBuscar) btnBuscar.disabled = false;
  }

  function limpiarFormularioVentaCompleto() {
    if (ventaForm) {
      ventaForm.reset();
      limpiarValidaciones(camposVentasOriginal, "ventaForm"); // Limpia validaciones de campos de venta
    }
    resetYDeshabilitarFormClienteEmbebido();

    const detalleVentaBody = document.getElementById("detalleVentaBody");
    if (detalleVentaBody) detalleVentaBody.innerHTML = "";
    const noDetallesMsg = document.getElementById("noDetallesMensaje");
    if (noDetallesMsg) noDetallesMsg.classList.remove("hidden"); // O .add('hidden') si debe ocultarse

    document.getElementById("subtotal_general_display_modal").value = "0.00";
    document.getElementById("subtotal_general").value = "0.00";
    // document.getElementById("descuento_porcentaje_general").value = "0"; // reset() debería hacerlo
    document.getElementById("monto_descuento_general_display").value = "0.00";
    document.getElementById("monto_descuento_general").value = "0.00";
    document.getElementById("total_general_display_modal").value = "0.00";
    document.getElementById("total_general").value = "0.00";

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
  if (nuevoClienteContainer) {
    resetYDeshabilitarFormClienteEmbebido(); // Estado inicial
  }

  if (btnToggleNuevoCliente && nuevoClienteContainer) {
    btnToggleNuevoCliente.addEventListener("click", function () {
      const isHidden = nuevoClienteContainer.classList.contains("hidden");
      if (isHidden) {
        nuevoClienteContainer.classList.remove("hidden");
        setCamposHabilitados(nuevoClienteContainer, true, camposNuevoCliente);
        inicializarValidaciones(camposNuevoCliente, "ventaForm"); // Validaciones para campos de cliente
        this.innerHTML = '<i class="mr-2 fas fa-times"></i>Cancelar Nuevo Cliente';
        const primerCampo = nuevoClienteContainer.querySelector('input:not([type="hidden"]), select, textarea');
        if (primerCampo) primerCampo.focus();

        document.getElementById("inputCriterioClienteModal").disabled = true;
        document.getElementById("btnBuscarClienteModal").disabled = true;
        document.getElementById("idcliente").value = "";
        document.getElementById("cliente_seleccionado_info_modal").classList.add("hidden");
      } else {
        resetYDeshabilitarFormClienteEmbebido();
      }
    });
  }

  if (registrarClienteInlineBtn && nuevoClienteContainer) {
    registrarClienteInlineBtn.addEventListener("click", function () {
      if (!validarCamposVacios(camposNuevoCliente, "ventaForm")) return;

      let formClienteValido = true;
      camposNuevoCliente.forEach((campo) => {
        const inputElement = ventaForm.querySelector(`#${campo.id}`);
        if (inputElement && inputElement.offsetParent !== null) {
          let esValidoEsteCampo = true;
          if (campo.tipo === "select") {
            esValidoEsteCampo = validarSelect(inputElement, campo.mensajes);
          } else if (campo.tipo === "date") { // Si tuvieras fechas
            // esValidoEsteCampo = validarFecha(inputElement, campo.mensajes);
          } else { // input o textarea
            esValidoEsteCampo = validarCampo(inputElement, campo.regex, campo.mensajes);
          }
          if (!esValidoEsteCampo) formClienteValido = false;
        }
      });

      if (!formClienteValido) return;

      const datosParaBackend = {};
      camposNuevoCliente.forEach((campo) => {
        const input = ventaForm.querySelector(`#${campo.id}`);
        if (input) {
          datosParaBackend[campo.backend_key] = input.value;
        }
      });

      fetch("clientes/createcliente", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(datosParaBackend),
      })
        .then((response) => response.json())
        .then((result) => {
          if (result.status && result.data && result.data.id) {
            Swal.fire("¡Éxito!", result.message || "Cliente registrado correctamente.", "success");
            resetYDeshabilitarFormClienteEmbebido();

            document.getElementById("idcliente").value = result.data.id;
            const nombreCompleto = `${result.data.nombre || ""} ${result.data.apellido || ""} (C.I.: ${result.data.cedula || ""})`.trim();
            document.getElementById("inputCriterioClienteModal").value = nombreCompleto;
            const divInfoClienteModal = document.getElementById("cliente_seleccionado_info_modal");
            divInfoClienteModal.innerHTML = `Sel: <strong>${nombreCompleto}</strong>`;
            divInfoClienteModal.classList.remove("hidden");
          } else {
            Swal.fire("¡Error!", result.message || "No se pudo registrar el cliente.", "error");
          }
        })
        .catch((error) => {
          console.error("Error al registrar cliente:", error);
          Swal.fire("¡Error!", "Ocurrió un error al procesar la solicitud del cliente.", "error");
        });
    });
  }

  if (cancelarNuevoClienteBtn) {
    cancelarNuevoClienteBtn.addEventListener("click", function () {
      resetYDeshabilitarFormClienteEmbebido();
      limpiarValidaciones(camposNuevoCliente, "ventaForm");
    });
  }

  // --- Lógica de Ventas (Buscador, Detalle, Totales, Registro) ---
  function inicializarBuscadorCliente() {
    const inputCriterio = document.getElementById("inputCriterioClienteModal");
    const btnBuscar = document.getElementById("btnBuscarClienteModal");
    const listaResultados = document.getElementById("listaResultadosClienteModal");
    const inputIdCliente = document.getElementById("idcliente"); // Para la venta
    const divInfoCliente = document.getElementById("cliente_seleccionado_info_modal");

    if (!btnBuscar || !inputCriterio || !listaResultados || !inputIdCliente || !divInfoCliente) return;

    // Evitar añadir listeners múltiples si se llama varias veces
    if (btnBuscar.dataset.listenerAttached === "true") return;
    btnBuscar.dataset.listenerAttached = "true";


    btnBuscar.addEventListener("click", async function () {
      if (this.disabled) return; // No buscar si el botón está deshabilitado
      const criterio = inputCriterio.value.trim();
      if (criterio.length < 2) {
        Swal.fire("Atención", "Ingrese al menos 2 caracteres para buscar.", "warning");
        return;
      }
      listaResultados.innerHTML = '<div class="p-2 text-xs text-gray-500">Buscando...</div>';
      listaResultados.classList.remove("hidden");

      try {
        const response = await fetch(`clientes/buscar?criterio=${encodeURIComponent(criterio)}`);
        if (!response.ok) throw new Error("Error en la respuesta del servidor");
        const clientes = await response.json();
        listaResultados.innerHTML = "";
        if (clientes && clientes.length > 0) {
          clientes.forEach((cli) => {
            const itemDiv = document.createElement("div");
            // Aplicar estilos para que parezca un item de lista seleccionable
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
              inputCriterio.value = this.textContent; // Opcional: llenar el input de búsqueda
              listaResultados.classList.add("hidden");
              listaResultados.innerHTML = "";
            });
            listaResultados.appendChild(itemDiv);
          });
        } else {
          listaResultados.innerHTML = '<div class="p-2 text-xs text-gray-500">No se encontraron clientes.</div>';
        }
      } catch (error) {
        console.error("Error al buscar clientes:", error);
        listaResultados.innerHTML = '<div class="p-2 text-xs text-red-500">Error al buscar. Intente de nuevo.</div>';
      }
    });

    inputCriterio.addEventListener("input", function () {
        if (this.disabled) return;
      // Solo limpiar si el formulario de nuevo cliente NO está activo
      if (nuevoClienteContainer && nuevoClienteContainer.classList.contains("hidden")) {
        inputIdCliente.value = "";
        divInfoCliente.classList.add("hidden");
        // No ocultar listaResultados aquí, podría estar mostrando resultados de una búsqueda anterior
        // o el usuario podría estar escribiendo para una nueva búsqueda.
      }
    });
     // Ocultar lista de resultados si se hace clic fuera
    document.addEventListener('click', function(event) {
        if (!listaResultados.contains(event.target) && event.target !== inputCriterio && event.target !== btnBuscar) {
            listaResultados.classList.add('hidden');
        }
    });
  }


  document.getElementById("agregarDetalleBtn").addEventListener("click", function () {
      const selectProducto = document.getElementById("select_producto_agregar_modal");
      const idProducto = selectProducto.value;
      if (!idProducto) {
        // En lugar de Swal, mostrar error junto al campo
        const errorDiv = document.getElementById("error-select_producto_agregar_modal-vacio") || selectProducto.nextElementSibling;
        if (errorDiv && errorDiv.id.startsWith("error-")) {
            errorDiv.textContent = "Seleccione un producto.";
            errorDiv.classList.remove("hidden");
            selectProducto.classList.add("border-red-500");
        } else {
            Swal.fire("Atención", "Seleccione un producto para agregar al detalle.", "warning");
        }
        return;
      } else {
        const errorDiv = document.getElementById("error-select_producto_agregar_modal-vacio") || selectProducto.nextElementSibling;
        if (errorDiv && errorDiv.id.startsWith("error-")) {
            errorDiv.classList.add("hidden");
            selectProducto.classList.remove("border-red-500");
        }
      }


      const producto = listaProductos.find((p) => String(p.idproducto || p.id) === String(idProducto));
      if (!producto) {
        Swal.fire("Error", "Producto no encontrado en la lista cargada.", "error");
        return;
      }

      const detalleVentaBody = document.getElementById("detalleVentaBody");
      // Verificar si el producto ya está en el detalle
      const filasExistentes = detalleVentaBody.querySelectorAll("tr");
      for (let fila of filasExistentes) {
          const selectEnFila = fila.querySelector(".select-producto-detalle");
          if (selectEnFila && selectEnFila.value === (producto.idproducto || producto.id)) {
              Swal.fire("Atención", "Este producto ya ha sido agregado al detalle.", "info");
              return; // Evitar agregar duplicados
          }
      }


      const idProd = producto.idproducto || producto.id;
      const nombreProd = `${producto.nombre_producto} (${producto.nombre_categoria || 'N/A'})`;
      const precioUnit = parseFloat(producto.precio_unitario || 0).toFixed(2);

      // Usar un input hidden para el ID del producto y un span/div para el nombre
      const nuevaFilaHTML = `
        <tr>
          <td class="px-3 py-1.5">
            <input type="hidden" name="detalle_idproducto[]" value="${idProd}" class="select-producto-detalle">
            <span>${nombreProd}</span>
          </td>
          <td class="px-3 py-1.5"><input type="number" name="detalle_cantidad[]" class="w-full px-2 py-1 text-xs border rounded-md cantidad-input" value="1" min="1"></td>
          <td class="px-3 py-1.5"><input type="number" name="detalle_precio_unitario[]" class="w-full px-2 py-1 text-xs border rounded-md precio-input bg-gray-100" value="${precioUnit}" min="0" readonly></td>
          <td class="px-3 py-1.5"><input type="number" name="detalle_subtotal[]" class="w-full px-2 py-1 text-xs border rounded-md subtotal-input bg-gray-100" value="${precioUnit}" readonly></td>
          <td class="px-3 py-1.5 text-center">
            <button type="button" class="text-red-500 eliminar-detalle-btn hover:text-red-700">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
      `;
      detalleVentaBody.insertAdjacentHTML("beforeend", nuevaFilaHTML);
      document.getElementById("noDetallesMensaje").classList.add("hidden");
      actualizarEventosDetalle();
      calcularTotalesGenerales();
      selectProducto.value = ""; // Resetear el select de agregar producto
    });

  function actualizarEventosDetalle() {
    const detalleVentaBody = document.getElementById("detalleVentaBody");
    detalleVentaBody.querySelectorAll(".cantidad-input").forEach((input) => {
      input.oninput = function () { // Usar oninput para respuesta inmediata
        const fila = this.closest("tr");
        const precio = parseFloat(fila.querySelector(".precio-input").value) || 0;
        const cantidad = parseFloat(this.value) || 0;
        const subtotalInput = fila.querySelector(".subtotal-input");
        if (cantidad < 1 && this.value !== "") { // Evitar cantidades menores a 1 pero permitir campo vacío temporalmente
            this.value = 1; // Corregir a 1
            subtotalInput.value = (1 * precio).toFixed(2);
        } else {
            subtotalInput.value = (cantidad * precio).toFixed(2);
        }
        calcularTotalesGenerales();
      };
    });
    detalleVentaBody.querySelectorAll(".eliminar-detalle-btn").forEach((btn) => {
      btn.onclick = function (e) { // onclick está bien aquí
        this.closest("tr").remove();
        calcularTotalesGenerales();
        if (detalleVentaBody.rows.length === 0) {
            document.getElementById("noDetallesMensaje").classList.remove("hidden");
        }
      };
    });
  }

  function calcularTotalesGenerales() { // Renombrada para claridad
    const detalleVentaBody = document.getElementById("detalleVentaBody");
    let subtotalGeneral = 0;
    detalleVentaBody.querySelectorAll(".subtotal-input").forEach((input) => {
      subtotalGeneral += parseFloat(input.value) || 0;
    });

    const descuentoPorcentaje = parseFloat(document.getElementById("descuento_porcentaje_general").value) || 0;
    const montoDescuento = (subtotalGeneral * descuentoPorcentaje) / 100;
    const totalGeneral = subtotalGeneral - montoDescuento;

    document.getElementById("subtotal_general_display_modal").value = subtotalGeneral.toFixed(2);
    document.getElementById("subtotal_general").value = subtotalGeneral.toFixed(2);
    document.getElementById("monto_descuento_general_display").value = montoDescuento.toFixed(2);
    document.getElementById("monto_descuento_general").value = montoDescuento.toFixed(2);
    document.getElementById("total_general_display_modal").value = totalGeneral.toFixed(2);
    document.getElementById("total_general").value = totalGeneral.toFixed(2);
  }
  
 const descuentoPorcentajeGeneralInput = document.getElementById("descuento_porcentaje_general");
  if (descuentoPorcentajeGeneralInput) {
    descuentoPorcentajeGeneralInput.addEventListener("input", calcularTotalesGenerales);
  }


  // --- Eventos de Apertura/Cierre Modal y Registro de Venta ---
  document.getElementById("abrirModalBtn").addEventListener("click", function () {
    abrirModal("ventaModal");
    limpiarFormularioVentaCompleto(); // Limpia todo al abrir
    resetYDeshabilitarFormClienteEmbebido(); // Asegura estado limpio del form cliente

    cargarSelect({
      selectId: "idmoneda_general",
      endpoint: "moneda/getMonedasActivas",
      optionTextFn: (m) => `${m.codigo_moneda} (${m.valor})`,
      optionValueFn: (m) => m.idmoneda,
      placeholder: "Seleccione una moneda...",
    });
    // Inicializar validaciones para campos de venta que son siempre relevantes
    inicializarValidaciones(camposVentasOriginal, "ventaForm");

    cargarSelect({
      selectId: "select_producto_agregar_modal",
      endpoint: "productos/getListaProductosParaFormulario",
      optionTextFn: (p) => `${p.nombre_producto} (${p.nombre_categoria || "Sin categoría"})`,
      optionValueFn: (p) => p.idproducto || p.id || "",
      placeholder: "Seleccione un producto...",
      onLoaded: (productos) => {
        listaProductos = productos || []; // Asegurar que sea un array
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

  document.getElementById("registrarVentaBtn").addEventListener("click", function () {
    const idClienteSeleccionado = document.getElementById("idcliente").value;
    const nuevoClienteFormActivo = !nuevoClienteContainer.classList.contains("hidden");

    if (!idClienteSeleccionado && !nuevoClienteFormActivo) {
      Swal.fire("Atención", "Debe seleccionar un cliente existente o completar el registro de un nuevo cliente.", "warning");
      document.getElementById("inputCriterioClienteModal").focus();
      return;
    }
     if (nuevoClienteFormActivo && !idClienteSeleccionado) {
      Swal.fire("Atención", "Por favor, guarde o cancele el registro del nuevo cliente antes de proceder con la venta.", "warning");
      // Opcionalmente, hacer focus en el botón de guardar cliente inline
      const btnGuardarCliente = document.getElementById("registrarClienteInlineBtn");
      if(btnGuardarCliente) btnGuardarCliente.focus();
      return;
    }


    // Validar campos generales de la venta
    if (!validarCamposVacios(camposVentasOriginal, "ventaForm")) return;

    let todoValidoVenta = true;
    camposVentasOriginal.forEach((campo) => {
      const inputElement = ventaForm.querySelector(`#${campo.id}`);
      if (inputElement && inputElement.offsetParent !== null) {
        let esValidoEsteCampo = true;
        if (campo.tipo === "select") {
          esValidoEsteCampo = validarSelect(inputElement, campo.mensajes);
        } else if (campo.tipo === "date") {
          esValidoEsteCampo = validarFecha(inputElement, campo.mensajes);
        } else if (campo.regex) { // Para input/textarea con regex
          esValidoEsteCampo = validarCampo(inputElement, campo.regex, campo.mensajes);
        }
        if (!esValidoEsteCampo) todoValidoVenta = false;
      }
    });

    if (!todoValidoVenta) return;
    if (!validarDetalleVenta()) return; // Valida la tabla de productos

    const formData = new FormData(ventaForm);
    const datosVenta = {};
    formData.forEach((value, key) => {
      datosVenta[key] = value;
    });

    // Eliminar campos del formulario de cliente embebido del payload de la venta
    camposNuevoCliente.forEach((campoCliente) => {
      delete datosVenta[campoCliente.name_attr]; // Usa el 'name' del HTML
    });
     delete datosVenta["idcliente_nuevo_formulario_hidden"]; // Eliminar el hidden input específico


    registrarEntidad({
      formId: "ventaForm", // Aunque construimos datosVenta, registrarEntidad podría usarlo para algo más
      endpoint: "ventas/createventa",
      // campos: camposVentasOriginal, // Opcional si registrarEntidad valida con esto
      datosParaEnviar: datosVenta, // Pasar el objeto ya procesado
      onSuccess: (result) => {
        Swal.fire("¡Éxito!", result.message || "Venta registrada correctamente.", "success")
          .then(() => {
            $("#Tablaventas").DataTable().ajax.reload();
            cerrarModal("ventaModal");
            limpiarFormularioVentaCompleto();
          });
      },
      onError: (errorResult) => {
         Swal.fire("¡Error!", errorResult.message || "No se pudo registrar la venta.", "error");
      }
    });
  });


  // --- Eventos de Tabla (Editar/Eliminar Venta) ---
  document.addEventListener("click", function (e) {
    if (e.target.closest(".eliminar-btn")) {
      const idventa = e.target.closest(".eliminar-btn").getAttribute("data-idventa");
      if (idventa) confirmarEliminacion(idventa);
    }
    if (e.target.closest(".editar-btn")) {
      const idventa = e.target.closest(".editar-btn").getAttribute("data-idventa");
      if (!idventa || isNaN(idventa)) {
        Swal.fire("¡Error!", "ID de venta no válido.", "error");
        return;
      }
      // abrirModalventaParaEdicion(idventa); // Implementar si es necesario
      console.warn("Funcionalidad Editar Venta no implementada completamente en este ejemplo.");
    }
  });


  // --- Inicialización DataTable ---
  function inicializarDataTable() {
  $("#Tablaventas").DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "ventas/getventasData",
      type: "GET",
      dataSrc: "data",
    },
    columns: [
      { data: "nro_venta", title: "Nro" },
      { data: "nombre_producto", title: "Producto" },
      { data: "fecha", title: "Fecha" },
      { data: "total_general", title: "Total" },
      { data: "observaciones", title: "Observaciones" },
      { data: "estatus", title: "Estatus" },
      {
        data: null,
        title: "Acciones",
        orderable: false,
        render: function (data, type, row) {
          return `
            <button class="editar-btn text-blue-500 hover:text-blue-700 p-1 rounded-full" data-idventa="${row.idventa}">
              <i class="fas fa-edit"></i>
            </button>
            <button class="eliminar-btn text-red-500 hover:text-red-700 p-1 rounded-full ml-2" data-idventa="${row.idventa}">
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
      infoEmpty: "Mostrando 0 a 0 de 0 Entradas",
      infoFiltered: "(Filtrado de _MAX_ total entradas)",
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
}
});
