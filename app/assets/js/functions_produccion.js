import { abrirModal, cerrarModal } from "./exporthelpers.js";
import { expresiones, inicializarValidaciones } from "./validaciones.js";
import { validarCampo } from "./validaciones.js";

let tablaProduccion;

document.addEventListener("DOMContentLoaded", function () {
  // Campos a validar en tiempo real
  const campos = [
    { id: "idproducto", regex: null, mensaje: "Debe seleccionar un producto." },
    { id: "cantidad_a_realizar", regex: /^[0-9]+(\.[0-9]{1,2})?$/, mensaje: "La cantidad debe ser un número válido." },
    { id: "fecha_inicio", regex: /^\d{4}-\d{2}-\d{2}$/, mensaje: "Fecha de inicio inválida. Use el formato YYYY-MM-DD." },
    { id: "fecha_fin", regex: /^(\d{4}-\d{2}-\d{2})?$|null/, mensaje: "Fecha de fin inválida. Use el formato YYYY-MM-DD." }
  ];

  inicializarValidaciones(campos);

  // Inicialización de DataTables
  tablaProduccion = $("#TablaProduccion").DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "produccion/getProduccionData",
      type: "GET",
      dataSrc: "data"
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
            </button>`;
        }
      }
    ],
    language: {
      decimal: "",
      emptyTable: "No hay información",
      info: "Mostrando _START_ a _END_ de _TOTAL_ Entradas",
      infoEmpty: "Mostrando 0 to 0 of 0 Entradas",
      infoFiltered: "(Filtrado de _MAX_ total entradas)",
      paginate: {
        first: "Primero",
        last: "Último",
        next: "Siguiente",
        previous: "Anterior"
      },
      zeroRecords: "Sin resultados encontrados"
    },
    destroy: true,
    responsive: true,
    pageLength: 10,
    order: [[0, "asc"]]
  });
document.addEventListener("click", function (e) {
  if (e.target.closest(".eliminarInsumoBtn")) {
    e.target.closest("tr").remove();
  }
});

document.getElementById("agregarInsumoBtn").addEventListener("click", () => {
  if (!window.productos) {
    alert("Espere a que se carguen los productos...");
    return;
  }

  const tbody = document.getElementById("detalleProduccionBody");

  const tr = document.createElement("tr");
  tr.innerHTML = `
    <td>
      <select name="idproducto_insumo[]" class="w-full border rounded p-2" required>
        <option value="">Seleccione un producto</option>
        ${window.productos.map(p => `<option value="${p.idproducto}">${p.nombre}</option>`).join('')}
      </select>
    </td>
    <td>
      <input type="number" name="cantidad_insumo[]" class="w-full border rounded p-2" min="0" step="0.01" required />
    </td>
    <td>
      <input type="number" name="cantidad_utilizada[]" class="w-full border rounded p-2" min="0" step="0.01" required />
    </td>
    <td>
      <input type="text" name="observaciones[]" class="w-full border rounded p-2" />
    </td>
    <td>
      <button type="button" class="eliminarInsumoBtn text-red-500"><i class="fas fa-trash"></i></button>
    </td>
  `;
  tbody.appendChild(tr);
});
  // Evento del botón Registrar
  document.getElementById("registrarProduccionBtn").addEventListener("click", function () {
    manejarRegistro(campos);
  });

  // Abrir modal
  document.getElementById("abrirModalBtn").addEventListener("click", function () {
    abrirModal("produccionModal");
    cargarEmpleado();
    cargarProducto();
  });

  // Cerrar modal
  document.getElementById("cerrarModalBtn").addEventListener("click", function () {
    cerrarModal("produccionModal");
  });

  // Manejador de envío del formulario
  document.getElementById("produccionForm").addEventListener("submit", function (e) {
  e.preventDefault(); // Evita el envío tradicional

  const formData = new FormData(document.getElementById("produccionForm"));
  const data = {};
  formData.forEach((value, key) => {
    if (!key.includes("[]")) {
      data[key] = value;
    }
  });

  // Agregar insumos manualmente como array de objetos
  data.insumos = [];
  document.querySelectorAll("#detalleProduccionBody tr").forEach(fila => {
    const idproductoInsumo = fila.querySelector("select[name='idproducto_insumo[]']");
    const cantidadInsumo = fila.querySelector("input[name='cantidad_insumo[]']");
    const cantidadUtilizada = fila.querySelector("input[name='cantidad_utilizada[]']");

    if (idproductoInsumo && cantidadInsumo && cantidadUtilizada) {
      data.insumos.push({
        idproducto: idproductoInsumo.value,
        cantidad: cantidadInsumo.value,
        cantidad_utilizada: cantidadUtilizada.value
      });
    }
  });

  console.log("Datos a enviar:", data); // Depuración

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
  .then(response => {
    if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
    return response.json();
  })
  .then(result => {
    if (result.status) {
      alert(result.message);
      cerrarModalProduccion();
      tablaProduccion.ajax.reload();
    } else {
      alert(result.message || "Error al procesar la solicitud.");
    }
  })
  .catch(error => {
    console.error("Error:", error);
    alert("Ocurrió un error al procesar la solicitud.");
  });
});

  // Manejador de edición
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

  // Manejador de eliminación
  document.addEventListener("click", function (e) {
    if (e.target.closest(".eliminar-btn")) {
      const idproduccion = e.target.closest(".eliminar-btn").getAttribute("data-idproduccion");
      if (confirm("¿Estás seguro de eliminar esta producción?")) {
        eliminarProduccion(idproduccion);
      }
    }
  });
});

// Función para validar campos vacíos
function validarCamposVacios(campos) {
  let formularioValido = true;

  for (let campo of campos) {
    const input = document.getElementById(campo.id);
    if (!input) {
      console.warn(`El campo con ID "${campo.id}" no existe en el DOM.`);
      continue;
    }

    const valor = input.value.trim();
    if (valor === "") {
      Swal.fire({
        title: "¡Error!",
        text: `El campo "${campo.id}" no puede estar vacío.`,
        icon: "error",
        confirmButtonText: "Aceptar"
      });
      formularioValido = false;
    }
  }

  return formularioValido;
}

// Función para manejar registro/edición
function manejarRegistro(campos) {
  const esValido = validarCamposVacios(campos);
  if (!esValido) return;

  const form = document.getElementById("produccionForm");
  const formData = new FormData(form);
  const data = {};

  formData.forEach((value, key) => {
    data[key] = value;
  });

  const idproduccion = document.getElementById("idproduccion").value;
  const url = idproduccion ? "produccion/updateProduccion" : "produccion/createProduccion";
  const method = idproduccion ? "PUT" : "POST";

  fetch(url, {
    method: method,
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data),
  })
  .then(response => {
    if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
    return response.json();
  })
  .then(result => {
    if (result.status) {
      alert(result.message);
      cerrarModalProduccion();
      tablaProduccion.ajax.reload();
    } else {
      alert(result.message);
    }
  })
  .catch(error => {
    console.error("Error:", error);
    alert("Ocurrió un error al procesar la solicitud.");
  });
}

// Cargar empleado
function cargarEmpleado() {
  return new Promise((resolve, reject) => {
    fetch("produccion/getEmpleado")
      .then(res => res.json())
      .then(data => {
        if (data.status) {
          const selectEmpleado = document.getElementById("idempleado");
          selectEmpleado.innerHTML = "<option value=''>Seleccione Empleado</option>";
          data.data.forEach(emp => {
            const option = document.createElement("option");
            option.value = emp.idempleado;
            option.textContent = emp.nombre;
            selectEmpleado.appendChild(option);
          });
          resolve();
        } else {
          reject("Error al cargar empleados.");
        }
      })
      .catch(err => {
        console.error("Error al cargar empleados:", err);
        reject("Error al cargar empleados.");
      });
  });
}

// Cargar producto
function cargarProducto() {
  return new Promise((resolve, reject) => {
    fetch("produccion/getProductos")
      .then(res => res.json())
      .then(data => {
        if (data.status) {
          const selectProducto = document.getElementById("idproducto");
          selectProducto.innerHTML = "<option value=''>Seleccione Producto</option>";
          data.data.forEach(prod => {
            const option = document.createElement("option");
            option.value = prod.idproducto;
            option.textContent = prod.nombre;
            selectProducto.appendChild(option);
          });
          resolve();
        } else {
          reject("Error al cargar productos.");
        }
      })
      .catch(err => {
        console.error("Error al cargar productos:", err);
        reject("Error al cargar productos.");
      });
  });
}

// Cargar datos de producción al editar
function abrirModalProduccionParaEdicion(idproduccion) {
  // Después de cargar los datos principales
fetch(`produccion/getDetalleProduccionData?idproduccion=${idproduccion}`)
  .then(res => res.json())
  .then(detalle => {
    const tbody = document.getElementById("detalleProduccionBody");
    tbody.innerHTML = ""; // Limpiar antes de cargar nuevos

    if (detalle.data && detalle.data.length > 0) {
      detalle.data.forEach(insumo => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>
            <select name="idproducto_insumo[]" class="w-full border rounded p-2" required>
              ${window.productos.map(p => `<option value="${p.idproducto}">${p.nombre}</option>`).join('')}
            </select>
          </td>
          <td>
            <input type="number" name="cantidad_insumo[]" class="w-full border rounded p-2" min="0" step="0.01" value="${insumo.cantidad}" required />
          </td>
          <td>
            <input type="number" name="cantidad_utilizada[]" class="w-full border rounded p-2" min="0" step="0.01" value="${insumo.cantidad_consumida}" required />
          </td>
          <td>
            <input type="text" name="observaciones[]" class="w-full border rounded p-2" value="${insumo.observaciones || ''}" />
          </td>
          <td>
            <button type="button" class="eliminarInsumoBtn text-red-500"><i class="fas fa-trash"></i></button>
          </td>
        `;
        tbody.appendChild(tr);
      });
    } else {
      // Si no hay insumos, puedes dejar vacío o mostrar un mensaje
      const tr = document.createElement("tr");
      tr.innerHTML = `<td colspan="5" class="text-center p-2">No hay insumos registrados.</td>`;
      tbody.appendChild(tr);
    }
  });
  fetch(`produccion/getProduccionById/${idproduccion}`)
    .then(res => res.json())
    .then(data => {
      if (data.status) {
        const produccion = data.data;
        document.getElementById("idproduccion").value = produccion.idproduccion || "";
        document.getElementById("idempleado").value = produccion.idempleado || "";
        document.getElementById("idproducto").value = produccion.idproducto || "";
        document.getElementById("cantidad_a_realizar").value = produccion.cantidad_a_realizar || "";
        document.getElementById("fecha_inicio").value = produccion.fecha_inicio || "";
        document.getElementById("fecha_fin").value = produccion.fecha_fin || "";
        document.getElementById("estado").value = produccion.estado || "";
        abrirModal("produccionModal");
      } else {
        alert("Error al cargar los datos.");
      }
    })
    .catch(err => {
      console.error("Error al cargar producción:", err);
      alert("Ocurrió un error al cargar los datos.");
    });
}

// Eliminar producción
function eliminarProduccion(idproduccion) {
  fetch("produccion/deleteProduccion", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ idproduccion })
  })
  .then(res => res.json())
  .then(result => {
    if (result.status) {
      alert(result.message);
      tablaProduccion.ajax.reload();
    } else {
      alert(result.message);
    }
  })
  .catch(err => console.error("Error al eliminar:", err));
}

// Cerrar modal
function cerrarModalProduccion() {
  const modal = document.getElementById("produccionModal");
  if (modal) {
    modal.classList.add("opacity-0", "pointer-events-none");
    document.getElementById("produccionForm").reset();
  }
}
