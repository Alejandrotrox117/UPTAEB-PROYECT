import { abrirModal, cerrarModal } from "./exporthelpers.js";
import { expresiones, inicializarValidaciones } from "./validaciones.js";
import { validarCampo } from "./validaciones.js";
document.addEventListener("DOMContentLoaded", function () {
  // Inicializar DataTable
  inicializarDataTable();

  // Definir campos y validaciones
  const campos = [
    { id: "cedula", regex: expresiones.cedula, mensaje: " La Cédula debe contener la estructura V-XXXXX No debe contener espacios y solo números." },
    { id: "nombre", regex: expresiones.nombre, mensaje: "El nombre debe tener entre 10 y 20 caracteres alfabéticos." },
    { id: "apellido", regex: expresiones.apellido, mensaje: "El apellido debe tener entre 10 y 20 caracteres alfabéticos." },
    { id: "telefono_principal", regex: expresiones.telefono_principal, mensaje: "El teléfono debe tener exactamente 11 dígitos. No debe contener letras." },
    { id: "direccion", regex: expresiones.direccion, mensaje: "La dirección debe tener entre 20 y 50 caracteres." },
    { id: "estatus", regex: expresiones.estatus, mensaje: "El estatus debe ser 'Activo' o 'Inactivo'." },
    { id: "observaciones", regex: expresiones.observaciones, mensaje: "Las observaciones no deben exceder los 200 caracteres." },
  ];

  inicializarValidaciones(campos);

  // Evento para el botón "Registrar"
  document.getElementById("registrarVentaBtn").addEventListener("click", function () {
    manejarRegistro(campos);
  });

  // Botón para abrir el modal de registro
  document.getElementById("abrirModalBtn").addEventListener("click", function () {
    abrirModal("ventaModal");
  });

  // Botón para cerrar el modal
  document.getElementById("cerrarModalBtn").addEventListener("click", function () {
    cerrarModal("ventaModal");
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

// Función para manejar el registro de ventas
function manejarRegistro(campos) {
  // Validar si hay campos vacíos
  const formularioValido = validarCamposVacios(campos);
  if (!formularioValido) {
    return; // Detener el proceso si hay campos vacíos
  }

  // Validar el formato de los campos
  let formatoValido = true;
  campos.forEach((campo) => {
    const input = document.getElementById(campo.id);
    if (input) {
      const valido = validarCampo(input, campo.regex, campo.mensaje);
      if (!valido) formatoValido = false;
    }
  });

  // Si el formato no es válido, mostrar alerta y detener el proceso
  if (!formatoValido) {
    Swal.fire({
      title: "¡Error!",
      text: "Por favor, corrige los errores en el formulario.",
      icon: "error",
      confirmButtonText: "Aceptar",
    });
    return;
  }

  // Si el formulario es válido, enviar los datos
  const formData = new FormData(document.getElementById("ventaForm"));
  const data = {};
  formData.forEach((value, key) => {
    data[key] = value;
  });

  const idventa = document.getElementById("idventa").value;
  const url = idventa ? "ventas/updateventa" : "ventas/createventa";
  const method = idventa ? "PUT" : "POST";

  fetch(url, {
    method: method,
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data),
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status) {
        Swal.fire({
          title: "¡Éxito!",
          text: result.message || "venta registrado correctamente.",
          icon: "success",
          confirmButtonText: "Aceptar",
        }).then(() => {
          $("#Tablaventas").DataTable().ajax.reload();
          cerrarModal("ventaModal");
        });
      } else {
        Swal.fire({
          title: "¡Error!",
          text: result.message || "No se pudo registrar el venta.",
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
