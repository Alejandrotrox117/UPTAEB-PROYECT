import { abrirModal, cerrarModal } from "./exporthelpers.js";
import { expresiones, inicializarValidaciones } from "./validaciones.js";
import { validarCampo } from "./validaciones.js";
document.addEventListener("DOMContentLoaded", function () {
  // Inicialización de DataTables para la tabla de producción
  const campos = [
    { id: "nombre", regex: expresiones.nombre, mensaje: "El nombre debe tener entre 10 y 20 caracteres alfabéticos." },
    { id: "apellido", regex: expresiones.apellido, mensaje: "El apellido debe tener entre 10 y 20 caracteres alfabéticos." },
    { id: "telefono_principal", regex: expresiones.telefono_principal, mensaje: "El teléfono debe tener exactamente 11 dígitos. No debe contener letras." },
    { id: "direccion", regex: expresiones.direccion, mensaje: "La dirección debe tener entre 20 y 50 caracteres." },
   ];

  inicializarValidaciones(campos);
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
            <button class="editar-btn text-blue-500 hover:text-blue-700 p-1 rounded-full" data-idproduccion="${row.idproduccion}">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="eliminar-btn text-red-500 hover:text-red-700 p-1 rounded-full ml-2" data-idproduccion="${row.idproduccion}">
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
document
    .getElementById("registrarProduccionBtn")
    .addEventListener("click", function () {
      manejarRegistro(campos);
    });
  // Botón para abrir el modal de registro
  document
    .getElementById("abrirModalBtn")
    .addEventListener("click", function () {
      abrirModal("produccionModal");
      cargarEmpleado();
  cargarProducto();
    });

  // Botón para cerrar el modal
  document
    .getElementById("cerrarModalBtn")
    .addEventListener("click", function () {
      cerrarModal("produccionModal");
    });
  // Manejador de envío del formulario de producción
  document
    .getElementById("produccionForm")
    .addEventListener("submit", function (e) {
      e.preventDefault(); // Evita que el formulario se envíe de forma tradicional

      const formData = new FormData(this);
      const data = {};
      formData.forEach((value, key) => {
        data[key] = value;
      });

      console.log("Datos a enviar:", data); // Depuración

      // Validar campos obligatorios
      if (!data.cantidad_a_realizar || !data.fecha_inicio) {
        alert("Por favor, completa todos los campos obligatorios.");
        return;
      }

      const idproduccion = document.getElementById("idproduccion").value;
      const url = idproduccion
        ? "produccion/updateProduccion"
        : "produccion/createProduccion";
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
      const idproduccion = e.target
        .closest(".editar-btn")
        .getAttribute("data-idproduccion");

      if (!idproduccion || isNaN(idproduccion)) {
        alert("ID de producción no válido.");
        return;
      }

      abrirModalProduccionParaEdicion(idproduccion);
      cargarDetalle(idproduccion);
    }
  });

  // Función para abrir el modal de edición
  function abrirModalProduccionParaEdicion(idproduccion) {
    cargarEmpleado();
    cargarProducto();
    console.log("ID de producción recibido:", idproduccion); // Depuración

    fetch(`produccion/getProduccionById/${idproduccion}`)
      .then((response) => {
        console.log("Respuesta HTTP:", response); // Depuración
        if (!response.ok) {
          throw new Error(`Error HTTP: ${response.status}`);
        }
        return response.text(); // Obtiene la respuesta como texto
      })
      .then((text) => {
        console.log("Contenido de la respuesta:", text); // Depuración
        try {
          const data = JSON.parse(text); // Intenta parsear como JSON
          console.log("Datos recibidos del backend:", data);

          if (!data.status) {
            throw new Error(data.message || "Error al cargar los datos.");
          }

          const produccion = data.data;

          // Asigna los valores a los campos del formulario
          document.getElementById("idproduccion").value =
            produccion.idproduccion || "";
          setTimeout(() => {
            // Esperar a que se carguen las opciones
            document.getElementById("idempleado").value =
              produccion.idempleado || "";
            document.getElementById("idproducto").value =
              produccion.idproducto || "";
          }, 500);
          document.getElementById("cantidad_a_realizar").value =
            produccion.cantidad_a_realizar || "";
          document.getElementById("fecha_inicio").value =
            produccion.fecha_inicio || "";
          document.getElementById("fecha_fin").value =
            produccion.fecha_fin || "";
          document.getElementById("estado").value = produccion.estado || "";

          // Abre el modal
          abrirModalProduccion();
        } catch (error) {
          console.error("La respuesta no es un JSON válido:", text);
          throw new Error("La respuesta del servidor no es un JSON válido.");
        }
      })
      .catch((error) => {
        console.error("Error capturado:", error.message);
        alert(
          "Ocurrió un error al cargar los datos. Por favor, intenta nuevamente."
        );
      });
  }

  // Manejador de clic para botones de eliminación
  document.addEventListener("click", function (e) {
    if (e.target.closest(".eliminar-btn")) {
      const idproduccion = e.target
        .closest(".eliminar-btn")
        .getAttribute("data-idproduccion");
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

function cargarEmpleado() {
  return new Promise((resolve, reject) => {
    fetch("produccion/getEmpleado")
      .then((response) => {
        if (!response.ok) {
          throw new Error(`Error HTTP: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        if (data.status) {
          const selectEmpleado = document.getElementById("idempleado");
          if (!selectEmpleado) {
            throw new Error(
              "El elemento 'idempleado' no fue encontrado en el DOM."
            );
          }

          selectEmpleado.innerHTML = ""; // Limpiar opciones existentes

          const optionDefault = document.createElement("option");
          optionDefault.value = "";
          optionDefault.textContent = "Seleccione un Empleado a cargo";
          selectEmpleado.appendChild(optionDefault);

          data.data.forEach((empleado) => {
            const option = document.createElement("option");
            option.value = empleado.idempleado;
            option.textContent = empleado.nombre;
            selectEmpleado.appendChild(option);
          });

          resolve(); // Resuelve la promesa cuando se completa
        } else {
          reject(data.message || "Error al cargar los empleados.");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        reject("Ocurrió un error al cargar los empleados.");
      });
  });
}
function cargarProducto() {
  return new Promise((resolve, reject) => {
    fetch("produccion/getProductos") // Endpoint del backend
      .then((response) => {
        if (!response.ok) {
          throw new Error(`Error HTTP: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        if (data.status) {
          const selectProducto = document.getElementById("idproducto");
          if (!selectProducto) {
            throw new Error(
              "El elemento 'idproducto' no fue encontrado en el DOM."
            );
          }

          selectProducto.innerHTML = "";

          const optionDefault = document.createElement("option");
          optionDefault.value = "";
          optionDefault.textContent = "Seleccione un Producto";
          selectProducto.appendChild(optionDefault);

          data.data.forEach((producto) => {
            const option = document.createElement("option");
            option.value = producto.idproducto; // Valor del producto (ID)
            option.textContent = producto.nombre; // Nombre visible del producto
            selectProducto.appendChild(option);
          });

          resolve();
        } else {
          reject(data.message || "Error al cargar los productos.");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        reject("Ocurrió un error al cargar los productos.");
      });
  });
}
function abrirModalProduccion() {
  const modal = document.getElementById("produccionModal");
  cargarEmpleado();
  cargarProducto();
  modal.classList.remove("opacity-0", "pointer-events-none");
  // Inicialización de DataTables para la tabla de producción
}
function cargarDetalle(idproduccion) {
  $(document).ready(function () {
    const tablaProduccion = $("#TablaDetalleProduccion").DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: `produccion/getDetalleProduccionData/${idproduccion}`,
        type: "GET",
        dataSrc: "data", // Clave donde están los datos en la respuesta JSON
      },
      columns: [
        { data: "nombre_producto", title: "Producto" },
        { data: "unidad_medida", title: "Unidad de medida" },
        { data: "cantidad", title: "Cantidad" },
        { data: "cantidad_consumida", title: "Cantidad Consumida" },
        { data: "observaciones", title: "Observaciones" },
      ],
      language: {
        decimal: "",
        emptyTable: "No hay datos disponibles para mostrar.",
        info: "Mostrando _START_ a _END_ de _TOTAL_ entradas",
        infoEmpty: "Mostrando 0 a 0 de 0 entradas",
        infoFiltered: "(filtrado de _MAX_ entradas totales)",
        infoPostFix: "",
        thousands: ",",
        lengthMenu: "Mostrar _MENU_ entradas",
        loadingRecords: "Cargando datos, por favor espere...",
        processing: "Procesando...",
        search: "Buscar:",
        zeroRecords: "No se encontraron registros coincidentes.",
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

    // Evento para verificar cuando los datos se cargan
    $("#TablaDetalleProduccion").on("init.dt", function () {
      console.log("Datos cargados correctamente");
    });
  });
}

// Función para cerrar el modal de empleado
function cerrarModalProduccion() {
  const modal = document.getElementById("produccionModal");
  modal.classList.add("opacity-0", "pointer-events-none");
  document.getElementById("produccionForm").reset();
}
function validarCamposVacios(campos) {
  let formularioValido = true; // Variable para rastrear si el formulario es válido

  // Validar campos vacíos
  for (let campo of campos) {
    // Omitir la validación del campo idempleado
    if (campo.id === "idempleado") {
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