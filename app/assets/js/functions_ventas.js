import { abrirModal, cerrarModal } from "./exporthelpers.js";
import { expresiones, inicializarValidaciones } from "./validaciones.js";
import { validarCampo } from "./validaciones.js";



//cargamos todo el en dom

document.addEventListener("DOMContentLoaded", function () {
  const abrirModalBtn = document.getElementById("abrirModalBtn");
  const cerrarModalBtn = document.getElementById("cerrarModalBtn");
  const ventaModal = document.getElementById("ventaModal");

  // Función para abrir el modal
  function abrirModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.remove("opacity-0", "pointer-events-none");
      modal.classList.add("opacity-100", "pointer-events-auto");
    }
  }

  // Función para cerrar el modal
  function cerrarModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.add("opacity-0", "pointer-events-none");
      modal.classList.remove("opacity-100", "pointer-events-auto");
    }
  }

  // Evento para abrir el modal
  if (abrirModalBtn) {
    abrirModalBtn.addEventListener("click", function () {
      abrirModal("ventaModal");
    });
  }

  // Evento para cerrar el modal
  if (cerrarModalBtn) {
    cerrarModalBtn.addEventListener("click", function () {
      cerrarModal("ventaModal");
    });
  }

  // Función para manejar el registro de la venta
  const registrarVentaBtn = document.getElementById("registrarVentaBtn");
  if (registrarVentaBtn) {
    registrarVentaBtn.addEventListener("click", function () {
      manejarRegistro();
    });
  }

  // Función para manejar el registro
  function manejarRegistro() {
    const form = document.getElementById("ventaForm");
    if (!form) return;

    const formData = new FormData(form);
    const data = {};
    formData.forEach((value, key) => {
      data[key] = value;
    });

 
    console.log("Datos enviados:", data);

   
    cerrarModal("ventaModal");
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

  // Inicializar DataTable
  inicializarDataTable();

  // Definir campos y validaciones
  const campos = [
    { id: "fecha_venta_modal", regex: expresiones.fecha, mensaje: "La fecha es obligatoria." },
    { id: "idmoneda_general", regex: expresiones.moneda, mensaje: "Debe seleccionar una moneda válida." },
    { id: "idcliente", regex: expresiones.id, mensaje: "Debe seleccionar un cliente válido." },
    { id: "subtotal_general", regex: expresiones.numero, mensaje: "El subtotal debe ser un número válido." },
    { id: "descuento_porcentaje_general", regex: expresiones.numero, mensaje: "El descuento debe ser un número válido." },
    { id: "monto_descuento_general", regex: expresiones.numero, mensaje: "El monto de descuento debe ser un número válido." },
    { id: "total_general", regex: expresiones.numero, mensaje: "El total debe ser un número válido." },
    { id: "observaciones", regex: expresiones.observaciones, mensaje: "Las observaciones no deben exceder los 200 caracteres." },
  ];

  inicializarValidaciones(campos);

  // Botón para abrir el modal de registro
  document.getElementById("abrirModalBtn").addEventListener("click", function () {
    abrirModal("ventaModal");
  });

  // Botón para cerrar el modal
  document.getElementById("cerrarModalBtn").addEventListener("click", function () {
    cerrarModal("ventaModal");
    limpiarFormulario();
  });

  // Botón para registrar la venta
  document.getElementById("registrarVentaBtn").addEventListener("click", function () {
    manejarRegistro(campos);
  });

  // Botón para agregar un producto al detalle
  document.getElementById("agregarDetalleBtn").addEventListener("click", function () {
    agregarProductoDetalle();
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

  // Función para manejar el registro de ventas
  function manejarRegistro(campos) {
    const formularioValido = validarCamposVacios(campos);
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
      .then(response => response.json())
      .then(result => {
        if (result.status) {
          Swal.fire({
            title: "¡Éxito!",
            text: result.message || "Venta registrada correctamente.",
            icon: "success",
            confirmButtonText: "Aceptar",
          }).then(() => {
            $("#Tablaventas").DataTable().ajax.reload();
            cerrarModal("ventaModal");
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
      .catch(error => {
        console.error("Error:", error);
        Swal.fire({
          title: "¡Error!",
          text: "Ocurrió un error al procesar la solicitud.",
          icon: "error",
          confirmButtonText: "Aceptar",
        });
      });
  }

  // Función para limpiar el formulario
  function limpiarFormulario() {
    document.getElementById("ventaForm").reset();
    document.getElementById("detalleVentaBody").innerHTML = "";
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
      { data: "idventa", title: "Nro" },
      { data: "nombre_producto", title: "Producto" },
      { data: "fecha", title: "Fecha" },
      { data: "cantidad", title: "Cantidad" },
      { data: "descuento", title: "Descuento" },
      { data: "total", title: "Total" },
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


// Función para confirmar la eliminación de un venta
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

// Función para eliminar un venta
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

// Función para abrir el modal de edición de venta
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


function validarCamposVacios(campos) {
  let formularioValido = true; // Variable para rastrear si el formulario es válido

  // Validar campos vacíos
  for (let campo of campos) {
    // Omitir la validación del campo idventa
    if (campo.id === "idventa") {
      continue;
    }

    // Obtener el valor del campo
    const input = document.getElementById(campo.id);
    if (!input) {
      console.warn(`El campo con ID "${campo.id}" no existe en el DOM.`);
      continue;
    }

    let valor = input.value.trim();
    if (valor === "") {
      Swal.fire({
        title: "¡Error!",
        text: `El campo "${campo.id}" no puede estar vacío.`,
        icon: "error",
        confirmButtonText: "Aceptar",
      });
      formularioValido = false; // Marcar el formulario como no válido
    }
  }

  return formularioValido; // Retornar true si todos los campos son válidos, false si no
}
