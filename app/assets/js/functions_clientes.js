import { abrirModal, cerrarModal } from "./exporthelpers.js";
import { expresiones, inicializarValidaciones } from "./validaciones.js";
import { validarCampo } from "./validaciones.js";
document.addEventListener("DOMContentLoaded", function () {
  // Inicializar DataTable
  inicializarDataTable();

  // Definir campos y validaciones
  const campos = [
    { id: "cedula", regex: expresiones.cedula, mensaje: " La Cédula debe contener la estructura V-XXXXX No debe contener espacios y solo números." },
    { id: "nombre", regex: expresiones.nombre, mensaje: "El nombre debe tener entre 2 y 50 caracteres alfabéticos." },
    { id: "apellido", regex: expresiones.apellido, mensaje: "El apellido debe tener entre 2 y 50 caracteres alfabéticos." },
    { id: "telefono_principal", regex: expresiones.telefono_principal, mensaje: "El teléfono debe tener exactamente 10 dígitos." },
    { id: "direccion", regex: expresiones.direccion, mensaje: "La dirección debe tener entre 5 y 100 caracteres." },
    { id: "estatus", regex: expresiones.estatus, mensaje: "El estatus debe ser 'Activo' o 'Inactivo'." },
    { id: "observaciones", regex: expresiones.observaciones, mensaje: "Las observaciones no deben exceder los 200 caracteres." },
  ];

  inicializarValidaciones(campos);

  // Evento para el botón "Registrar"
  document.getElementById("registrarClienteBtn").addEventListener("click", function () {
    manejarRegistro(campos);
  });

  // Botón para abrir el modal de registro
  document.getElementById("abrirModalBtn").addEventListener("click", function () {
    abrirModal("clienteModal");
  });

  // Botón para cerrar el modal
  document.getElementById("cerrarModalBtn").addEventListener("click", function () {
    cerrarModal("clienteModal");
  });

  // Evento para manejar el clic en el botón de eliminar
  document.addEventListener("click", function (e) {
    if (e.target.closest(".eliminar-btn")) {
      const idcliente = e.target.closest(".eliminar-btn").getAttribute("data-idcliente");
      confirmarEliminacion(idcliente);
    }
  });

  // Evento para manejar el clic en el botón de editar
  document.addEventListener("click", function (e) {
    if (e.target.closest(".editar-btn")) {
      const idcliente = e.target.closest(".editar-btn").getAttribute("data-idcliente");
      if (!idcliente || isNaN(idcliente)) {
        Swal.fire({
          title: "¡Error!",
          text: "ID de cliente no válido.",
          icon: "error",
          confirmButtonText: "Aceptar",
        });
        return;
      }
      abrirModalclienteParaEdicion(idcliente);
    }
  });
});

// Función para inicializar DataTable
function inicializarDataTable() {
  $("#Tablaclientes").DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "clientes/getclientesData",
      type: "GET",
      dataSrc: "data",
    },
    columns: [
      { data: "idcliente", title: "Nro" },
      { data: "cedula", title: "Cédula" },
      { data: "nombre", title: "Nombre" },
      { data: "apellido", title: "Apellido" },
      { data: "telefono_principal", title: "Teléfono" },
      { data: "direccion", title: "Dirección" },
      { data: "estatus", title: "Estatus" },
      { data: "observaciones", title: "Observaciones" },
      {
        data: null,
        title: "Acciones",
        orderable: false,
        render: function (data, type, row) {
          return `
            <button class="editar-btn text-blue-500 hover:text-blue-700 p-1 rounded-full" data-idcliente="${row.idcliente}">
              <i class="fas fa-edit"></i>
            </button>
            <button class="eliminar-btn text-red-500 hover:text-red-700 p-1 rounded-full ml-2" data-idcliente="${row.idcliente}">
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

// Función para manejar el registro de clientes
function manejarRegistro(campos) {
  let formularioValido = true;

  campos.forEach((campo) => {
    const input = document.getElementById(campo.id);
    if (input) {
      const valido = validarCampo(input, campo.regex, campo.mensaje);
      if (!valido) formularioValido = false;
    }
  });

  if (!formularioValido) {
    Swal.fire({
      title: "¡Error!",
      text: "Por favor, corrige los errores en el formulario.",
      icon: "error",
      confirmButtonText: "Aceptar",
    });
    return;
  }

  // Si el formulario es válido, enviar los datos
  const formData = new FormData(document.getElementById("clienteForm"));
  const data = {};
  formData.forEach((value, key) => {
    data[key] = value;
  });

  const idcliente = document.getElementById("idcliente").value;
  const url = idcliente ? "clientes/updateCliente" : "clientes/createCliente";
  const method = idcliente ? "PUT" : "POST";

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
          text: result.message || "Cliente registrado correctamente.",
          icon: "success",
          confirmButtonText: "Aceptar",
        }).then(() => {
          $("#Tablaclientes").DataTable().ajax.reload();
          cerrarModal("clienteModal");
        });
      } else {
        Swal.fire({
          title: "¡Error!",
          text: result.message || "No se pudo registrar el cliente.",
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

// Función para confirmar la eliminación de un cliente
function confirmarEliminacion(idcliente) {
  Swal.fire({
    title: "¿Estás seguro?",
    text: "Esta acción desactivará al cliente.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      eliminarcliente(idcliente);
    }
  });
}

// Función para eliminar un cliente
function eliminarcliente(idcliente) {
  fetch(`clientes/deleteCliente`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ idcliente }),
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status) {
        Swal.fire({
          title: "¡Éxito!",
          text: result.message || "Cliente eliminado correctamente.",
          icon: "success",
          confirmButtonText: "Aceptar",
        }).then(() => {
          $("#Tablaclientes").DataTable().ajax.reload();
        });
      } else {
        Swal.fire({
          title: "¡Error!",
          text: result.message || "No se pudo eliminar el cliente.",
          icon: "error",
          confirmButtonText: "Aceptar",
        });
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire({
        title: "¡Error!",
        text: "Ocurrió un error al intentar eliminar el cliente.",
        icon: "error",
        confirmButtonText: "Aceptar",
      });
    });
}

// Función para abrir el modal de edición de cliente
function abrirModalclienteParaEdicion(idcliente) {
  fetch(`clientes/getclienteById/${idcliente}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      if (!data.status) {
        throw new Error(data.message || "Error al cargar los datos del cliente.");
      }

      const cliente = data.data;

      // Asignar los valores a los campos del formulario
      document.getElementById("idcliente").value = cliente.idcliente || "";
      document.getElementById("nombre").value = cliente.nombre || "";
      document.getElementById("apellido").value = cliente.apellido || "";
      document.getElementById("cedula").value = cliente.cedula || "";
      document.getElementById("telefono_principal").value = cliente.telefono_principal || "";
      document.getElementById("direccion").value = cliente.direccion || "";
      document.getElementById("observaciones").value = cliente.observaciones || "";
      document.getElementById("estatus").value = cliente.estatus || "";

      abrirModal("clienteModal");
    })
    .catch((error) => {
      console.error("Error capturado al cargar los datos:", error.message);
      Swal.fire({
        title: "¡Error!",
        text: "Ocurrió un error al cargar los datos del cliente. Por favor, intenta nuevamente.",
        icon: "error",
        confirmButtonText: "Aceptar",
      });
    });
}
