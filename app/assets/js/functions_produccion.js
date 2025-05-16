document.addEventListener("DOMContentLoaded", function () {
  // Inicialización de DataTables para la tabla de producción
  const tablaProduccion = $("#TablaProduccion").DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "produccion/getProduccionData", // URL del controlador para obtener datos
      type: "GET",
      dataSrc: "data",
    },
    columns: [
      { data: "idproduccion", title: "ID Producción" },
      { data: "nombre_producto", title: "Producto" },
      { data: "nombre_empleado", title: "Empleado" },
      { data: "cantidad_a_realizar", title: "Cantidad" },
      { data: "fecha_inicio", title: "Fecha Inicio" },
      { data: "fecha_fin", title: "Fecha Fin" },
      { data: "estado", title: "Estado" },
      {
        data: null,
        title: "Acciones",
        orderable: false,
        render: function (data, type, row) {
          return `
            <button class="editar-btn bg-blue-500 text-white px-4 py-2 rounded" data-idproduccion="${row.idproduccion}">
              Editar
            </button>
            <button class="eliminar-btn bg-red-500 text-white px-4 py-2 rounded ml-2" data-idproduccion="${row.idproduccion}">
              Eliminar
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

  // Manejador de envío del formulario de producción
  document.getElementById("produccionForm").addEventListener("submit", function (e) {
    e.preventDefault(); // Evita que el formulario se envíe de forma tradicional

    const formData = new FormData(this);
    const data = {};
    formData.forEach((value, key) => {
      data[key] = value;
    });

    console.log("Datos a enviar:", data); // Depuración

    // Validar campos obligatorios
    if (!data.idproducto || !data.idempleado || !data.cantidad_a_realizar || !data.fecha_inicio) {
      alert("Por favor, completa todos los campos obligatorios.");
      return;
    }

    const idproduccion = document.getElementById("idproduccion").value;
    const url = idproduccion ? "produccion/updateProduccion" : "produccion/createProduccion";
    const method = idproduccion ? "PUT" : "POST";

    fetch(url, {
      method: method,
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
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
          cerrarModalProduccion();
          tablaProduccion.ajax.reload();
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
      const idproduccion = e.target.closest(".editar-btn").getAttribute("data-idproduccion");

      if (!idproduccion || isNaN(idproduccion)) {
        alert("ID de producción no válido.");
        return;
      }

      abrirModalProduccionParaEdicion(idproduccion);
    }
  });
function abrirModalProduccion() {
  const modal = document.getElementById("produccionModal");
  modal.classList.remove("opacity-0", "pointer-events-none");
}

// Función para cerrar el modal de empleado
function cerrarModalProduccion() {
  const modal = document.getElementById("produccionModal");
  modal.classList.add("opacity-0", "pointer-events-none");
  document.getElementById("produccionForm").reset();
}
  // Función para abrir el modal de edición
  function abrirModalProduccionParaEdicion(idproduccion) {
    fetch(`produccion/getProduccionById/${idproduccion}`)
      .then((response) => {
        if (!response.ok) {
          throw new Error(`Error HTTP: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        if (!data.status) {
          throw new Error(data.message || "Error al cargar los datos.");
        }

        const produccion = data.data;

        // Asigna los valores a los campos del formulario
        document.getElementById("idproduccion").value = produccion.idproduccion || "";
        document.getElementById("idproducto").value = produccion.idproducto || "";
        document.getElementById("idempleado").value = produccion.idempleado || "";
        document.getElementById("cantidad_a_realizar").value = produccion.cantidad_a_realizar || "";
        document.getElementById("fecha_inicio").value = produccion.fecha_inicio || "";
        document.getElementById("fecha_fin").value = produccion.fecha_fin || "";
        document.getElementById("estado").value = produccion.estado || "";

        // Abre el modal
        abrirModalProduccion();
      })
      .catch((error) => {
        console.error("Error capturado:", error.message);
        alert("Ocurrió un error al cargar los datos. Por favor, intenta nuevamente.");
      });
  }

  // Manejador de clic para botones de eliminación
  document.addEventListener("click", function (e) {
    if (e.target.closest(".eliminar-btn")) {
      const idproduccion = e.target.closest(".eliminar-btn").getAttribute("data-idproduccion");
      if (confirm("¿Estás seguro de eliminar esta producción?")) {
        eliminarProduccion(idproduccion);
      }
    }
  });

  // Función para eliminar una producción
  function eliminarProduccion(idproduccion) {
    fetch("produccion/deleteProduccion", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ idproduccion }),
    })
      .then((response) => response.json())
      .then((result) => {
        if (result.status) {
          alert(result.message);
          tablaProduccion.ajax.reload();
        } else {
          alert(result.message);
        }
      })
      .catch((error) => console.error("Error:", error));
  }
});