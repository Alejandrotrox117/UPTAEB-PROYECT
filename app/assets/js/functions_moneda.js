document.addEventListener("DOMContentLoaded", function () {
  $("#TablaMoneda").DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "moneda/getMonedaData",
      type: "GET",
      dataSrc: "data",
    },
    columns: [
      { data: "idmoneda", title: "Nro" },
      { data: "nombre_moneda", title: "Nombre" },
      { data: "valor", title: "valor" },
      { data: "estatus", title: "Estado" },

      {
        data: null,
        title: "Acciones",
        orderable: false,
        render: function (data, type, row) {
          
          return `
                <button class="editar-btn text-blue-500 hover:text-blue-700 p-1 rounded-full" data-idmoneda="${row.idmoneda}">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="eliminar-btn text-red-500 hover:text-red-700 p-1 rounded-full ml-2" data-idmoneda="${row.idmoneda}">
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
  document.getElementById("monedaForm").addEventListener("submit", function (e) {
    e.preventDefault(); 

    
    const formData = new FormData(this);
    const data = {};
    formData.forEach((value, key) => {
        data[key] = value;
    });

    console.log("Datos a enviar:", data); 

    
    const idmoneda = document.getElementById("idmoneda").value;
    const url = idmoneda ? "moneda/actualizarMoneda" : "moneda/crearMoneda";
    const method = idmoneda ? "PUT" : "POST";

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
                cerrarModalMoneda();
                $('#TablaMoneda').DataTable().ajax.reload();
            } else {
                alert(result.message);
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            alert("Ocurrió un error al procesar la solicitud.");
        });
});

  document.addEventListener("click", function (e) {
    if (e.target.closest(".editar-btn")) {
      const idmoneda = e.target
        .closest(".editar-btn")
        .getAttribute("data-idmoneda");
      console.log("Botón de edición clicado. ID de moneda:", idmoneda); 

      if (!idmoneda || isNaN(idmoneda)) {
        alert("ID de persona no válido.");
        return;
      }

      abrirModalMonedaParaEdicion(idmoneda);
    }
  });
  document.addEventListener("click", function (e) {
    if (e.target.closest(".eliminar-btn")) {
      const idmoneda = e.target
        .closest(".eliminar-btn")
        .getAttribute("data-idmoneda");
      if (confirm("¿Estás seguro de desactivar esta persona?")) {
        eliminarMoneda(idmoneda);
      }
    }
  });
});

function abrirModalMoneda() {
  const modal = document.getElementById("monedaModal");
  modal.classList.remove("opacity-0", "pointer-events-none");
}

function cerrarModalMoneda() {
  const modal = document.getElementById("monedaModal");
  modal.classList.add("opacity-0", "pointer-events-none");
  document.getElementById("monedaForm").reset();
}

function abrirModalMonedaParaEdicion(idmoneda) {
  console.log("ID de moneda recibido:", idmoneda); 
  
  fetch(`moneda/getMonedaById/${idmoneda}`)
    .then((response) => {
      console.log("Respuesta HTTP:", response); 
      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      console.log("Datos recibidos del backend:", data); 

      if (!data.status) {
        throw new Error(data.message || "Error al cargar los datos.");
      }

      const moneda = data.data;

      
      document.getElementById("idmoneda").value = moneda.idmoneda || "";
      document.getElementById("nombre").value = moneda.nombre_moneda || "";
      document.getElementById("valor").value = moneda.valor || "";
      
      document.getElementById("estatus").value = moneda.estatus || "";
     
      
      abrirModalMoneda();
    })
    .catch((error) => {
      console.error("Error capturado:", error.message); 
      alert(
        "Ocurrió un error al cargar los datos. Por favor, intenta nuevamente."
      );
    });
}


function eliminarMoneda(idmoneda) {
  if (!confirm("¿Estás seguro de que deseas eliminar esta moneda?")) {
    return;
  }

  fetch(`moneda/deleteMoneda/${idmoneda}`, {
    method: "DELETE",
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status) {
        alert(result.message); 
        $("#TablaMoneda").DataTable().ajax.reload(); 
      } else {
        alert(result.message); 
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("Ocurrió un error al eliminar el moneda.");
    });
}
