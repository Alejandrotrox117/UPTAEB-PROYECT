document.addEventListener("DOMContentLoaded", function () {
  $("#TablaCategorias").DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "categorias/getCategoriasData",
      type: "GET",
      dataSrc: "data",
    },
    columns: [
      { data: "idcategoria", title: "Nro" },
      { data: "nombre", title: "Nombre" },
      { data: "descripcion", title: "descripcion" },
      { data: "estatus", title: "Estado" },

      {
        data: null,
        title: "Acciones",
        orderable: false,
        render: function (data, type, row) {
          
          return `
                <button class="editar-btn text-blue-500 hover:text-blue-700 p-1 rounded-full" data-idcategoria="${row.idcategoria}">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="eliminar-btn text-red-500 hover:text-red-700 p-1 rounded-full ml-2" data-idcategoria="${row.idcategoria}">
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
  document.getElementById("CategoriaForm").addEventListener("submit", function (e) {
    e.preventDefault(); 

    
    const formData = new FormData(this);
    const data = {};
    formData.forEach((value, key) => {
        data[key] = value;
    });

    console.log("Datos a enviar:", data); 

    
    const idcategoria = document.getElementById("idcategoria").value;
    const url = idcategoria ? "categorias/actualizarCategoria" : "categorias/crearCategoria";
    const method = idcategoria ? "PUT" : "POST";

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
                cerrarModalCategoria();
                $('#TablaCategorias').DataTable().ajax.reload();
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
      const idcategoria = e.target
        .closest(".editar-btn")
        .getAttribute("data-idcategoria");
      console.log("Botón de edición clicado. ID de categoria:", idcategoria); 

      if (!idcategoria || isNaN(idcategoria)) {
        alert("ID de persona no válido.");
        return;
      }

      abrirModalCategoriaParaEdicion(idcategoria);
    }
  });
});

function abrirModalCategoria() {
  const modal = document.getElementById("categoriaModal");
  modal.classList.remove("opacity-0", "pointer-events-none");
}

function cerrarModalCategoria() {
  const modal = document.getElementById("categoriaModal");
  modal.classList.add("opacity-0", "pointer-events-none");
  document.getElementById("CategoriaForm").reset();
}

function abrirModalCategoriaParaEdicion(idcategoria) {
  console.log("ID de categoria recibido:", idcategoria); 

  fetch(`categorias/getCategoriaById/${idcategoria}`)
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

      const categoria = data.data;

      
      document.getElementById("idcategoria").value = categoria.idcategoria || "";
      document.getElementById("nombre").value = categoria.nombre || "";
      document.getElementById("descripcion").value = categoria.descripcion || "";
      
      document.getElementById("estatus").value = categoria.estatus || "";
     
      
      abrirModalCategoria();
    })
    .catch((error) => {
      console.error("Error capturado:", error.message); 
      alert(
        "Ocurrió un error al cargar los datos. Por favor, intenta nuevamente."
      );
    });
}


function eliminarcategoria(idcategoria) {
  if (!confirm("¿Estás seguro de que deseas eliminar este categoria?")) {
    return;
  }

  fetch(`categorias/deleteCategoria/${idcategoria}`, {
    method: "DELETE",
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status) {
        alert(result.message); 
        $("#Tablacategorias").DataTable().ajax.reload(); 
      } else {
        alert(result.message); 
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("Ocurrió un error al eliminar el categoria.");
    });
}
