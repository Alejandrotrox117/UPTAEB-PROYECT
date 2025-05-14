document.addEventListener("DOMContentLoaded", function () {
  // Inicialización de DataTables para la tabla de empleados
  $("#TablaEmpleado").DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "empleados/getEmpleadoData", // URL del controlador para obtener datos
      type: "GET",
      dataSrc: "data",
    },
    columns: [
      { data: "idempleado", title: "Nro" },
      { data: "nombre", title: "Nombre" },
      { data: "apellido", title: "Apellido" },
      { data: "identificacion", title: "Identificación" },
      { data: "telefono_principal", title: "Teléfono" },
      { data: "correo_electronico", title: "Correo Electrónico" },
      { data: "direccion", title: "Dirección" },
      { data: "fecha_nacimiento", title: "Fecha de Nacimiento" },
      { data: "genero", title: "Género" },
      { data: "puesto", title: "Puesto" },
      { data: "salario", title: "Salario" },
      { data: "estatus", title: "Estatus" },
      {
        data: null,
        title: "Acciones",
        orderable: false,
        render: function (data, type, row) {
          // Botones para editar y eliminar empleados
          return `
                <button class="editar-btn text-blue-500 hover:text-blue-700 p-1 rounded-full" data-idempleado="${row.idempleado}">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="eliminar-btn text-red-500 hover:text-red-700 p-1 rounded-full ml-2" data-idempleado="${row.idempleado}">
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
      infoEmpty: "Mostrando 0 to 0 of 0 Entradas",
      infoFiltered: "(Filtrado de _MAX_ total entradas)",
      infoPostFix: "",
      thousands: ",",
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

  // Manejador de envío del formulario de empleado
  document.getElementById("empleadoForm").addEventListener("submit", function (e) {
    e.preventDefault(); // Evita que el formulario se envíe de forma tradicional

    // Convertir los datos del formulario en un objeto
    const formData = new FormData(this);
    const data = {};
    formData.forEach((value, key) => {
      data[key] = value;
    });

    console.log("Datos a enviar:", data); // Depuración

    // Validar campos obligatorios
    if (!data.nombre || !data.apellido || !data.identificacion) {
      alert("Por favor, completa todos los campos obligatorios.");
      return;
    }

    // Determinar si es una edición o una creación
    const idempleado = document.getElementById("idempleado").value;
    const url = idempleado ? "empleados/updateEmpleado" : "empleados/createEmpleado";
    const method = idempleado ? "PUT" : "POST";

    fetch(url, {
      method: method,
      headers: { "Content-Type": "application/json" }, // Asegura que los datos sean JSON
      body: JSON.stringify(data), // Convierte el objeto en una cadena JSON
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`Error HTTP: ${response.status}`);
        }
        return response.json();
      })
      .then((result) => {
        if (result.status) {
          alert(result.message);
          cerrarModalEmpleado();
          $('#TablaEmpleado').DataTable().ajax.reload(); // Recarga la tabla
        } else {
          alert(result.message);
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("Ocurrió un error al procesar la solicitud.");
      });
  });

  // Manejador de clic para botones de edición
  document.addEventListener("click", function (e) {
    if (e.target.closest(".editar-btn")) {
      const idempleado = e.target.closest(".editar-btn").getAttribute("data-idempleado");
      console.log("Botón de edición clicado. ID de empleado:", idempleado); // Depuración

      if (!idempleado || isNaN(idempleado)) {
        alert("ID de empleado no válido.");
        return;
      }

      abrirModalEmpleadoParaEdicion(idempleado);
    }
  });

  // Manejador de clic para botones de eliminación
  document.addEventListener("click", function (e) {
    if (e.target.closest(".eliminar-btn")) {
      const idempleado = e.target.closest(".eliminar-btn").getAttribute("data-idempleado");
      if (confirm("¿Estás seguro de desactivar este empleado?")) {
        eliminarEmpleado(idempleado);
      }
    }
  });
});

// Función para eliminar un empleado
function eliminarEmpleado(idempleado) {
  fetch(`empleados/deleteEmpleado`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ idempleado }),
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status) {
        alert(result.message); // Muestra mensaje de éxito
        $("#TablaEmpleado").DataTable().ajax.reload(); // Recarga la tabla
      } else {
        alert(result.message); // Muestra mensaje de error
      }
    })
    .catch((error) => console.error("Error:", error));
}

// Función para abrir el modal de edición
function abrirModalEmpleadoParaEdicion(idempleado) {
  console.log("ID de empleado recibido:", idempleado); // Depuración

  fetch(`empleados/getEmpleadoById/${idempleado}`)
    .then((response) => {
      console.log("Respuesta HTTP:", response); // Depuración
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      console.log("Datos recibidos del backend:", data); // Depuración

      if (!data.status) {
        throw new Error(data.message || "Error al cargar los datos.");
      }

      const empleado = data.data;

      // Asigna los valores a los campos del modal formulario
      document.getElementById("idempleado").value = empleado.idempleado || "";
      document.getElementById("nombre").value = empleado.nombre || "";
      document.getElementById("apellido").value = empleado.apellido || "";
      document.getElementById("identificacion").value = empleado.identificacion || "";
      document.getElementById("telefono_principal").value = empleado.telefono_principal || "";
      document.getElementById("correo_electronico").value = empleado.correo_electronico || "";
      document.getElementById("direccion").value = empleado.direccion || "";
      document.getElementById("fecha_nacimiento").value = empleado.fecha_nacimiento || "";
      document.getElementById("genero").value = empleado.genero || "";
      document.getElementById("puesto").value = empleado.puesto || "";
      document.getElementById("salario").value = empleado.salario || "";
      document.getElementById("estatus").value = empleado.estatus || "";
      document.getElementById("fecha_inicio").value = empleado.fecha_inicio || "";
      document.getElementById("fecha_fin").value = empleado.fecha_fin || "";

      // Abre el modal
      abrirModalEmpleado();
    })
    .catch((error) => {
      console.error("Error capturado:", error.message); // Depuración
      alert("Ocurrió un error al cargar los datos. Por favor, intenta nuevamente.");
    });
}

// Función para abrir el modal de empleado
function abrirModalEmpleado() {
  const modal = document.getElementById("empleadoModal");
  modal.classList.remove("opacity-0", "pointer-events-none");
}

// Función para cerrar el modal de empleado
function cerrarModalEmpleado() {
  const modal = document.getElementById("empleadoModal");
  modal.classList.add("opacity-0", "pointer-events-none");
  document.getElementById("empleadoForm").reset();
}