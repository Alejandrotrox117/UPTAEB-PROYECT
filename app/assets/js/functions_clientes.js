document.addEventListener("DOMContentLoaded", function () {
  // Inicializar DataTable
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

  // Expresiones regulares para validación
  const expresiones = {
    cedula: /^(V|E|J)-\d{8,10}$/, // Formato de cédula
    nombre: /^[a-zA-Z\s]{2,50}$/, // Nombre
    apellido: /^[a-zA-Z\s]{2,50}$/, // Apellido
    telefono_principal: /^\d{10}$/, // Teléfono
    direccion: /^.{5,100}$/, // Dirección
    estatus: /^(Activo|Inactivo)$/, // Estatus
    observaciones: /^.{0,200}$/, // Observaciones
  };

  // Validar formulario en tiempo real
  const validarCampo = (input, regex, mensaje) => {
    const errorDiv = input.nextElementSibling;

    if (!regex.test(input.value.trim())) {
      // Agregar clases de error
      input.classList.add("border-red-500", "focus:ring-red-500");
      input.classList.remove("border-gray-300", "focus:ring-green-400");

      // Mostrar mensaje de error
      if (errorDiv) {
        errorDiv.textContent = mensaje;
        errorDiv.classList.remove("hidden");
      }
      return false;
    } else {
      // Quitar clases de error y restaurar las clases predeterminadas
      input.classList.remove("border-red-500", "focus:ring-red-500");
      input.classList.add("border-gray-300", "focus:ring-green-400");

      // Ocultar mensaje de error
      if (errorDiv) {
        errorDiv.textContent = "";
        errorDiv.classList.add("hidden");
      }
      return true;
    }
  };

  const campos = [
    { id: "cedula", regex: expresiones.cedula, mensaje: "Formato de cédula inválido." },
    { id: "nombre", regex: expresiones.nombre, mensaje: "El nombre debe tener entre 2 y 50 caracteres alfabéticos." },
    { id: "apellido", regex: expresiones.apellido, mensaje: "El apellido debe tener entre 2 y 50 caracteres alfabéticos." },
    { id: "telefono_principal", regex: expresiones.telefono_principal, mensaje: "El teléfono debe tener exactamente 10 dígitos." },
    { id: "direccion", regex: expresiones.direccion, mensaje: "La dirección debe tener entre 5 y 100 caracteres." },
    { id: "estatus", regex: expresiones.estatus, mensaje: "El estatus debe ser 'Activo' o 'Inactivo'." },
    { id: "observaciones", regex: expresiones.observaciones, mensaje: "Las observaciones no deben exceder los 200 caracteres." },
  ];

  campos.forEach((campo) => {
    const input = document.getElementById(campo.id);
    if (input) {
      input.addEventListener("input", () => {
        validarCampo(input, campo.regex, campo.mensaje);
      });
    }
  });

  // Validar formulario al enviar
  document.getElementById("clienteForm").addEventListener("submit", function (e) {
    e.preventDefault();
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
    const formData = new FormData(this);
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
            cerrarModalCliente();
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
  });

  // Funciones para abrir y cerrar el modal
  window.abrirModalCliente = function () {
    const modal = document.getElementById("clienteModal");
    modal.classList.remove("opacity-0", "pointer-events-none");
  };

  window.cerrarModalCliente = function () {
    const modal = document.getElementById("clienteModal");
    modal.classList.add("opacity-0", "pointer-events-none");
    document.getElementById("clienteForm").reset();
  };
});

function eliminarcliente(idcliente) {
  fetch(`clientes/deleteCliente`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ idcliente }),
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status) {
        // Notificación de éxito con SweetAlert
        Swal.fire({
          title: "¡Éxito!",
          text: result.message || "Cliente eliminado correctamente.",
          icon: "success",
          confirmButtonText: "Aceptar",
        }).then(() => {
          // Recargar la tabla después de cerrar la alerta
          $("#Tablaclientes").DataTable().ajax.reload();
        });
      } else {
        // Notificación de error con SweetAlert
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
      // Notificación de error con SweetAlert
      Swal.fire({
        title: "¡Error!",
        text: "Ocurrió un error al intentar eliminar el cliente.",
        icon: "error",
        confirmButtonText: "Aceptar",
      });
    });
}

// Evento para manejar el clic en el botón de eliminar
document.addEventListener("click", function (e) {
  if (e.target.closest(".eliminar-btn")) {
    const idcliente = e.target
      .closest(".eliminar-btn")
      .getAttribute("data-idcliente");

    // Confirmación antes de eliminar
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
});

function abrirModalclienteParaEdicion(idcliente) {
  console.log("ID de cliente recibido para edición:", idcliente); // Depuración

  // Realizar la solicitud al backend para obtener los datos del cliente
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

      // Guardar los valores originales en atributos data-*
      document.getElementById("nombre").dataset.originalValue = cliente.nombre || "";
      document.getElementById("apellido").dataset.originalValue = cliente.apellido || "";
      document.getElementById("cedula").dataset.originalValue = cliente.cedula || "";
      document.getElementById("telefono_principal").dataset.originalValue = cliente.telefono_principal || "";
      document.getElementById("direccion").dataset.originalValue = cliente.direccion || "";
      document.getElementById("observaciones").dataset.originalValue = cliente.observaciones || "";
      document.getElementById("estatus").dataset.originalValue = cliente.estatus || "";

      // Abrir el modal para edición
      abrirModalcliente();
    })
    .catch((error) => {
      console.error("Error capturado al cargar los datos:", error.message); // Depuración
      Swal.fire({
        title: "¡Error!",
        text: "Ocurrió un error al cargar los datos del cliente. Por favor, intenta nuevamente.",
        icon: "error",
        confirmButtonText: "Aceptar",
      });
    });
}

// Evento para manejar el clic en el botón de editar
document.addEventListener("click", function (e) {
  if (e.target.closest(".editar-btn")) {
    const idcliente = e.target
      .closest(".editar-btn")
      .getAttribute("data-idcliente");

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
