document.addEventListener("DOMContentLoaded", function () {
  $("#TablaProductos").DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "productos/getProductosData",
      type: "GET",
      dataSrc: "data",
    },
    columns: [
      { data: "idproducto", title: "Nro" },
      { data: "nombre", title: "Nombre" },
      { data: "descripcion", title: "descripcion" },
      { data: "unidad_medida", title: "Unidad de Medida" },
      { data: "precio", title: "Precio" },
      { data: "existencia", title: "Stock" },
      { data: "idcategoria", title: "Categoria" },
      { data: "estatus", title: "Estado" },
      { data: "fecha_creacion", title: "Creando en" },
      { data: "ultima_modificacion", title: "Ultima Modificacion" },

      {
        data: null,
        title: "Acciones",
        orderable: false,
        render: function (data, type, row) {
          // Generar botones con íconos de Font Awesome
          return `
                <button class="editar-btn text-blue-500 hover:text-blue-700 p-1 rounded-full" data-idproducto="${row.idproducto}">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="eliminar-btn text-red-500 hover:text-red-700 p-1 rounded-full ml-2" data-idproducto="${row.idproducto}">
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
  document.getElementById("productoForm").addEventListener("submit", function (e) {
    e.preventDefault(); // Evita que el formulario se envíe de forma tradicional

    // Convertir los datos del formulario en un objeto
    const formData = new FormData(this);
    const data = {};
    formData.forEach((value, key) => {
        data[key] = value;
    });

    console.log("Datos a enviar:", data); // Depuración

    // Determinar si es una edición o una creación
    const idproducto = document.getElementById("idproducto").value;
    const url = idproducto ? "productos/updateProducto" : "productos/createProducto";
    const method = idproducto ? "PUT" : "POST";

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
                cerrarModalProducto();
                $('#TablaProductos').DataTable().ajax.reload();
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
      const idproducto = e.target
        .closest(".editar-btn")
        .getAttribute("data-idproducto");
      console.log("Botón de edición clicado. ID de producto:", idproducto); // Depuración

      if (!idproducto || isNaN(idproducto)) {
        alert("ID de persona no válido.");
        return;
      }

      abrirModalProductoParaEdicion(idproducto);
    }
  });
});
function cargarCategorias() {
  return new Promise((resolve, reject) => {
    fetch("productos/getCategorias")
      .then((response) => {
        if (!response.ok) {
          throw new Error(`Error HTTP: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        if (data.status) {
          const selectCategoria = document.getElementById("idcategoria");
          selectCategoria.innerHTML = ""; // Limpia el campo <select>

          // Agrega una opción por defecto
          const optionDefault = document.createElement("option");
          optionDefault.value = "";
          optionDefault.textContent = "Seleccione una categoría";
          selectCategoria.appendChild(optionDefault);

          // Agrega las categorías obtenidas del backend
          data.data.forEach((categoria) => {
            const option = document.createElement("option");
            option.value = categoria.idcategoria;
            option.textContent = categoria.nombre;
            selectCategoria.appendChild(option);
          });

          resolve(); // Resuelve la promesa
        } else {
          reject(data.message || "Error al cargar las categorías.");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        reject("Ocurrió un error al cargar las categorías.");
      });
  });
}
function abrirModalProducto() {
  const modal = document.getElementById("productoModal");
  cargarCategorias(); // Carga las categorías
  modal.classList.remove("opacity-0", "pointer-events-none");
}

function cerrarModalProducto() {
  const modal = document.getElementById("productoModal");
  modal.classList.add("opacity-0", "pointer-events-none");
  document.getElementById("productoForm").reset();
}

function abrirModalProductoParaEdicion(idproducto) {
  console.log("ID de producto recibido:", idproducto); // Depuración

  fetch(`productos/getProductoById/${idproducto}`)
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

      const producto = data.data;

      // Asigna los valores a los campos del modal formulario
      document.getElementById("idproducto").value = producto.idproducto || "";
      document.getElementById("nombre").value = producto.nombre || "";
      document.getElementById("descripcion").value = producto.descripcion || "";
      document.getElementById("unidad_medida").value =
        producto.unidad_medida || "";
      document.getElementById("precio").value = producto.precio || "";
      document.getElementById("existencia").value = producto.existencia || "";
      document.getElementById("estatus").value = producto.estatus || "";

      // Selecciona la categoría correspondiente
      cargarCategorias()
        .then(() => {
          document.getElementById("idcategoria").value =
            producto.idcategoria || "";
        })
        .catch((error) => {
          console.error("Error al cargar categorías:", error);
          alert("Ocurrió un error al cargar las categorías.");
        });

      // Abre el modal
      abrirModalProducto();
    })
    .catch((error) => {
      console.error("Error capturado:", error.message); // Depuración
      alert(
        "Ocurrió un error al cargar los datos. Por favor, intenta nuevamente."
      );
    });
}
// Manejar el envío del formulario (crear o actualizar)

function eliminarProducto(idproducto) {
  if (!confirm("¿Estás seguro de que deseas eliminar este producto?")) {
    return;
  }

  fetch(`productos/deleteProducto/${idproducto}`, {
    method: "DELETE",
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.status) {
        alert(result.message); // Muestra mensaje de éxito
        $("#TablaProductos").DataTable().ajax.reload(); // Recarga la tabla
      } else {
        alert(result.message); // Muestra mensaje de error
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("Ocurrió un error al eliminar el producto.");
    });
}
