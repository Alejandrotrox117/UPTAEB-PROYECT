import { abrirModal, cerrarModal } from "./exporthelpers.js";
import { expresiones, inicializarValidaciones,validarCamposVacios,validarFecha,validarSelect } from "./validaciones.js";

document.addEventListener("DOMContentLoaded", function () {
// Inicializar DataTable
  inicializarDataTable();
   // campos y validaciones importados del archivo validaciones.js
  // 
 const campos = [
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
      id: "nombre_cliente",
      tipo: "input",
      regex: expresiones.nombre,
      mensajes: {
        vacio: "El nombre es obligatorio.",
        formato: "El nombre debe tener entre 2 y 20 caracteres.",
      },
    },
    {
      id: "telefono_principal",
      tipo: "input",
      regex: expresiones.telefono_principal,
      mensajes: {
        vacio: "El teléfono es obligatorio.",
        formato: "El teléfono debe tener exactamente 11 dígitos. No debe contener letras.",
      },
    },
    {
      id: "direccion",
      tipo: "input",
      regex: expresiones.direccion,
      mensajes: {
        vacio: "La dirección es obligatoria.",
        formato: "La dirección debe tener entre 20 y 50 caracteres.",
      },
    },
    {
      id: "observaciones",
      tipo: "input",
      regex: expresiones.observaciones,
      mensajes: {
        vacio: "Las observaciones son obligatorias.",
        formato: "Las observaciones no deben exceder los 200 caracteres.",
      },
    },
    {
      id: "estatus",
      tipo: "select",
      mensajes: {
        vacio: "Debe seleccionar un estatus.",
      },
    },
  ];
  //inicializamos la funcion con los campos para validar cada input
  inicializarValidaciones(campos);




//FUNCIONES////
// CREAR VENTA NUEVA 
  function RegistrarNuevaVenta(campos) {
  let formularioValido = true;

  campos.forEach((campo) => {
    const input = document.getElementById(campo.id);
    if (input) {
      let esValido = false;
      if (campo.tipo === "date") {
        esValido = validarFecha(input, campo.mensajes);
      }

      if (!esValido) {
        formularioValido = false;
      }
    }
  });

  if (!formularioValido) return;

  const formData = new FormData(document.getElementById("ventaForm"));
  const data = {};
  formData.forEach((value, key) => {
    data[key] = value;
  });

  fetch("ventas/createventa", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data),
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status) {
        Swal.fire({
          title: "¡Éxito!",
          text: result.message || "Venta registrada correctamente.",
          icon: "success",
          confirmButtonText: "Aceptar",
        }).then(() => {
          $("#Tablaventas").DataTable().ajax.reload();
          cerrarModal("ventaModal");
          formData.reset();
          limpiarFormulario();
        });
      } else {
        Swal.fire({
          title: "¡Error!",
          text: result.message || "No se pudo registrar la venta.",
          icon: "error",
          confirmButtonText: "Aceptar",
        });
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire({
        title: "¡Error!",
        text: "Ocurrió un error al procesar la solicitud.",
        icon: "error",
        confirmButtonText: "Aceptar",
      });
    });
}


function inicializarBuscadorCliente() {
  // Buscar clientes al hacer clic en el botón
  const btnBuscar = document.getElementById("btnBuscarClienteModal");
  const inputCriterio = document.getElementById("inputCriterioClienteModal");
  const listaResultados = document.getElementById("listaResultadosClienteModal");
  const infoSeleccionado = document.getElementById("cliente_seleccionado_info_modal");
  const inputIdCliente = document.getElementById("idcliente");

  if (!btnBuscar || !inputCriterio || !listaResultados || !infoSeleccionado || !inputIdCliente) return;

  btnBuscar.addEventListener("click", function () {
    const criterio = inputCriterio.value.trim();
    listaResultados.innerHTML = "";
    infoSeleccionado.classList.add("hidden");
    inputIdCliente.value = "";

    if (criterio.length < 2) {
      listaResultados.innerHTML = "<div class='p-2 text-red-500'>Ingrese al menos 2 caracteres.</div>";
      listaResultados.classList.remove("hidden");
      return;
    }

    fetch(`/clientes/getclienteById=${encodeURIComponent(criterio)}`)
      .then(res => res.json())
      .then(data => {
        if (!Array.isArray(data) || data.length === 0) {
          listaResultados.innerHTML = "<div class='p-2 text-gray-500'>No se encontraron clientes.</div>";
        } else {
          listaResultados.innerHTML = data.map(cliente => `
            <div class="p-2 hover:bg-green-100 cursor-pointer border-b" data-id="${cliente.id}" data-nombre="${cliente.nombre}" data-apellido="${cliente.apellido}" data-cedula="${cliente.cedula}">
              <span class="font-semibold">${cliente.nombre} ${cliente.apellido}</span> - <span class="text-gray-600">${cliente.cedula}</span>
            </div>
          `).join('');
        }
        listaResultados.classList.remove("hidden");
      })
      .catch(() => {
        listaResultados.innerHTML = "<div class='p-2 text-red-500'>Error al buscar clientes.</div>";
        listaResultados.classList.remove("hidden");
      });
  });

  // Seleccionar cliente de la lista
  listaResultados.addEventListener("click", function (e) {
    const item = e.target.closest("[data-id]");
    if (item) {
      inputIdCliente.value = item.dataset.id;
      infoSeleccionado.innerHTML = `
        <span class="font-semibold">${item.dataset.nombre} ${item.dataset.apellido}</span>
        <span class="ml-2 text-gray-600">${item.dataset.cedula}</span>
      `;
      infoSeleccionado.classList.remove("hidden");
      listaResultados.classList.add("hidden");
    }
  });

  // Limpiar selección si el usuario cambia el criterio
  inputCriterio.addEventListener("input", function () {
    inputIdCliente.value = "";
    infoSeleccionado.classList.add("hidden");
    listaResultados.classList.add("hidden");
  });
}

// Llama a esta función cuando abras el modal de ventas








  //NOTIFICACION PARA CONFIRMACION DE ELIMINACION DE VENTA
function confirmarEliminacion(idventa) {
  Swal.fire({
    title: "¿Estás seguro?",
    text: "Esta acción desactivará al venta.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      eliminarventa(idventa);
    }
  });
}

// ELIMINAR VENTA
function eliminarventa(idventa) {
  fetch(`ventas/deleteventa`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ idventa }),
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status) {
        Swal.fire({
          title: "¡Éxito!",
          text: result.message || "venta eliminado correctamente.",
          icon: "success",
          confirmButtonText: "Aceptar",
        }).then(() => {
          $("#Tablaventas").DataTable().ajax.reload();
        });
      } else {
        Swal.fire({
          title: "¡Error!",
          text: result.message || "No se pudo eliminar el venta.",
          icon: "error",
          confirmButtonText: "Aceptar",
        });
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire({
        title: "¡Error!",
        text: "Ocurrió un error al intentar eliminar el venta.",
        icon: "error",
        confirmButtonText: "Aceptar",
      });
    });
}




// MODIFICAR VENTA 
function abrirModalventaParaEdicion(idventa) {
  fetch(`ventas/getventaById/${idventa}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      if (!data.status) {
        throw new Error(data.message || "Error al cargar los datos del venta.");
      }

      const venta = data.data;

      // Asignar los valores a los campos del formulario
      document.getElementById("idventa").value = venta.idventa || "";
      document.getElementById("nombre").value = venta.nombre || "";
      document.getElementById("apellido").value = venta.apellido || "";
      document.getElementById("cedula").value = venta.cedula || "";
      document.getElementById("telefono_principal").value = venta.telefono_principal || "";
      document.getElementById("direccion").value = venta.direccion || "";
      document.getElementById("observaciones").value = venta.observaciones || "";
      document.getElementById("estatus").value = venta.estatus || "";

      abrirModal("ventaModal");
    })
    .catch((error) => {
      console.error("Error capturado al cargar los datos:", error.message);
      Swal.fire({
        title: "¡Error!",
        text: "Ocurrió un error al cargar los datos del venta. Por favor, intenta nuevamente.",
        icon: "error",
        confirmButtonText: "Aceptar",
      });
    });
}








  // Función para manejar el registro de la venta
  const registrarVentaBtn = document.getElementById("registrarVentaBtn");
  if (registrarVentaBtn) {
    registrarVentaBtn.addEventListener("click", function () {
      RegistrarNuevaVenta(campos);
    });
  }

 
  // Función para calcular totales
  const descuentoInput = document.getElementById("descuento_porcentaje_general");
  if (descuentoInput) {
    descuentoInput.addEventListener("input", function () {
      calcularTotales();
    });
  }

  function calcularTotales() {
    const subtotalInput = document.getElementById("subtotal_general");
    const descuentoInput = document.getElementById("descuento_porcentaje_general");
    const montoDescuentoInput = document.getElementById("monto_descuento_general");
    const totalInput = document.getElementById("total_general");

    if (!subtotalInput || !descuentoInput || !montoDescuentoInput || !totalInput) return;

    const subtotal = parseFloat(subtotalInput.value) || 0;
    const descuento = parseFloat(descuentoInput.value) || 0;

    const montoDescuento = (subtotal * descuento) / 100;
    const total = subtotal - montoDescuento;

    montoDescuentoInput.value = montoDescuento.toFixed(2);
    totalInput.value = total.toFixed(2);
  }

  

 


  //Funciones de apertura y cierre de modal al igual que el formateo del mismo.
  
// Botón para abrir el modal de registro
  document.getElementById("abrirModalBtn").addEventListener("click", function () {
    abrirModal("ventaModal");
  });
// Botón buscar clietnte
  document.getElementById("btnBuscarClienteModal").addEventListener("click", function () {
     inicializarBuscadorCliente();

  });
  // Botón para cerrar el modal
  document.getElementById("cerrarModalBtn").addEventListener("click", function () {
    cerrarModal("ventaModal");
    limpiarValidaciones(campos);
    limpiarFormulario();
  });
 
 
 
  // Botón para registrar la venta
  document.getElementById("registrarVentaBtn").addEventListener("click", function () {
    RegistrarNuevaVenta(campos);
  });

  // Botón para agregar un producto al detalle
  document.getElementById("agregarDetalleBtn").addEventListener("click", function () {
    agregarProductoDetalle();
  });

  document.getElementById("registrarClienteVentaBtn").addEventListener("click", function (e) {
  
  });

  // Evento para recalcular totales cuando cambie el descuento
  document.getElementById("descuento_porcentaje_general").addEventListener("input", function () {
    calcularTotales();
  });

  // Evento para manejar el clic en el botón de eliminar
  document.addEventListener("click", function (e) {
    if (e.target.closest(".eliminar-btn")) {
      const idventa = e.target.closest(".eliminar-btn").getAttribute("data-idventa");
      confirmarEliminacion(idventa);
    }
  });

  // Evento para manejar el clic en el botón de editar
  document.addEventListener("click", function (e) {
    if (e.target.closest(".editar-btn")) {
      const idventa = e.target.closest(".editar-btn").getAttribute("data-idventa");
      if (!idventa || isNaN(idventa)) {
        Swal.fire({
          title: "¡Error!",
          text: "ID de venta no válido.",
          icon: "error",
          confirmButtonText: "Aceptar",
        });
        return;
      }
      abrirModalventaParaEdicion(idventa);
    }
  });

  // Función para agregar un producto al detalle
  function agregarProductoDetalle() {
    const detalleVentaBody = document.getElementById("detalleVentaBody");
    const nuevaFila = `
      <tr>
        <td><input type="text" name="producto[]" class="border rounded-lg px-2 py-1 w-full"></td>
        <td><input type="text" name="descripcion[]" class="border rounded-lg px-2 py-1 w-full"></td>
        <td><input type="number" name="cantidad[]" class="border rounded-lg px-2 py-1 w-full cantidad-input" value="1" min="1"></td>
        <td><input type="number" name="precio_unitario[]" class="border rounded-lg px-2 py-1 w-full precio-input" value="0" min="0"></td>
        <td><input type="number" name="subtotal[]" class="border rounded-lg px-2 py-1 w-full subtotal-input" value="0" readonly></td>
        <td>
          <button type="button" class="eliminar-detalle-btn text-red-500 hover:text-red-700">
            <i class="fas fa-trash"></i>
          </button>
        </td>
      </tr>
    `;
    detalleVentaBody.insertAdjacentHTML("beforeend", nuevaFila);
    actualizarEventosDetalle();
  }

  // Función para actualizar eventos en los inputs del detalle
  function actualizarEventosDetalle() {
    const detalleVentaBody = document.getElementById("detalleVentaBody");
    const cantidadInputs = detalleVentaBody.querySelectorAll(".cantidad-input");
    const precioInputs = detalleVentaBody.querySelectorAll(".precio-input");
    const eliminarBtns = detalleVentaBody.querySelectorAll(".eliminar-detalle-btn");

    cantidadInputs.forEach(input => {
      input.addEventListener("input", calcularSubtotal);
    });

    precioInputs.forEach(input => {
      input.addEventListener("input", calcularSubtotal);
    });

    eliminarBtns.forEach(btn => {
      btn.addEventListener("click", function (e) {
        e.target.closest("tr").remove();
        calcularTotales();
      });
    });
  }

  // Función para calcular el subtotal de cada fila
  function calcularSubtotal() {
    const fila = this.closest("tr");
    const cantidad = fila.querySelector(".cantidad-input").value || 0;
    const precio = fila.querySelector(".precio-input").value || 0;
    const subtotal = fila.querySelector(".subtotal-input");

    subtotal.value = (cantidad * precio).toFixed(2);
    calcularTotales();
  }

  // Función para calcular los totales generales
  function calcularTotales() {
    const detalleVentaBody = document.getElementById("detalleVentaBody");
    let subtotalGeneral = 0;

    // Sumar todos los subtotales
    detalleVentaBody.querySelectorAll(".subtotal-input").forEach(input => {
      subtotalGeneral += parseFloat(input.value) || 0;
    });

    // Calcular el descuento
    const descuentoPorcentaje = parseFloat(document.getElementById("descuento_porcentaje_general").value) || 0;
    const montoDescuento = (subtotalGeneral * descuentoPorcentaje) / 100;

    // Calcular el total general
    const totalGeneral = subtotalGeneral - montoDescuento;

    // Actualizar los campos
    document.getElementById("subtotal_general").value = subtotalGeneral.toFixed(2);
    document.getElementById("monto_descuento_general").value = montoDescuento.toFixed(2);
    document.getElementById("total_general").value = totalGeneral.toFixed(2);
  }

  

function limpiarFormulario() {
  // Reiniciar el formulario completo
  const formulario = document.getElementById("ventaForm");
  if (formulario) {
    formulario.reset();
  }

  // Reiniciar los selects
  const selects = formulario.querySelectorAll("select");
  selects.forEach((select) => {
    select.value = ""; // Reinicia el valor del select
    select.classList.remove("border-red-500", "focus:ring-red-500");
    select.classList.add("border-gray-300", "focus:ring-green-400");
  });

  // Reiniciar los inputs de tipo date
  const dateInputs = formulario.querySelectorAll('input[type="date"]');
  dateInputs.forEach((input) => {
    input.value = ""; // Reinicia el valor del input de tipo date
    input.classList.remove("border-red-500", "focus:ring-red-500");
    input.classList.add("border-gray-300", "focus:ring-green-400");
  });

  // Limpiar el cuerpo del detalle de la venta (si aplica)
  const detalleVentaBody = document.getElementById("detalleVentaBody");
  if (detalleVentaBody) {
    detalleVentaBody.innerHTML = ""; // Elimina todas las filas del detalle
  }
}



  
});

// Función para inicializar DataTable
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


function limpiarValidaciones(campos) {
  campos.forEach((campo) => {
    const input = document.getElementById(campo.id);
    if (input) {
      const errorDiv = input.nextElementSibling; // Div donde se muestra el mensaje de error

      // Limpiar mensajes de error
      if (errorDiv) {
        errorDiv.textContent = "";
        errorDiv.classList.add("hidden");
      }

      // Restaurar estilos del campo
      input.classList.remove("border-red-500", "focus:ring-red-500");
      input.classList.add("border-gray-300", "focus:ring-green-400");
    }
  });
}



