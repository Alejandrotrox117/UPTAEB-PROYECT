import { abrirModal, cerrarModal } from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  validarCamposVacios,
  validarSelect,
  validarFecha,
  limpiarValidaciones,
  cargarSelect,
  registrarEntidad,
} from "./validaciones.js";
let tablaProduccion = "";
document.addEventListener("DOMContentLoaded", function () {
  cargarEstadisticas();
  tablaProduccion = $("#TablaProduccion").DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "produccion/getProduccionData",
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
           <button class="ver-detalle-btn text-green-500 hover:text-green-700 p-1 rounded-full ml-2" data-idproduccion="${row.idproduccion}">
          <i class="fas fa-eye"></i>
        </button>
            <button class="editar-btn text-blue-500 hover:text-blue-700 p-1 rounded-full" data-idproduccion="${row.idproduccion}">
              <i class="fas fa-edit"></i>
            </button>
            <button class="eliminar-btn text-red-500 hover:text-red-700 p-1 rounded-full ml-2" data-idproduccion="${row.idproduccion}">
              <i class="fas fa-trash"></i>
            </button>`;
        },
      },
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
        previous: "Anterior",
      },
      zeroRecords: "Sin resultados encontrados",
    },
    destroy: true,
    responsive: true,
    pageLength: 10,
    order: [[0, "asc"]],
  });
  // Campos a validar en el formulario de producción

  let detalleProduccionItems = []; // Lista global de ítems del detalle

  const camposProduccion = [
    {
      id: "fecha_inicio",
      tipo: "date",
      mensajes: {
        vacio: "La fecha de inicio es obligatoria.",
        fechaPosterior: "La fecha no puede ser posterior a hoy.",
      },
    },
    {
      id: "estado",
      tipo: "select",
      mensajes: {
        vacio: "Debe seleccionar un estado.",
      },
    },
    {
      id: "idempleado_seleccionado",
      tipo: "hidden",
      mensajes: {
        vacio: "Debe seleccionar un empleado.",
      },
    },
    {
      id: "idproducto",
      tipo: "hidden",
      mensajes: {
        vacio: "Debe seleccionar un producto terminado.",
      },
    },
  ];

  // BOTÓN PARA ABRIR EL MODAL DE PRODUCCIÓN
  document
    .getElementById("abrirModalProduccion")
    .addEventListener("click", function () {
      abrirModal("produccionModal");

      // Cargar select de PRODUCTO TERMINADO (el principal)
      cargarSelect({
        selectId: "select_producto_principal",
        endpoint: "productos/getListaProductosParaFormulario",
        optionTextFn: (p) => `${p.nombre_producto} (${p.nombre_categoria})`,
        optionValueFn: (p) => p.idproducto || "",
        placeholder: "Seleccione un producto...",
        onLoaded: (productos) => {
          listaProductos = productos;
        },
      });

      // Inicializar buscador de empleados
      inicializarBuscadorEmpleado();
      inicializarBuscadorEmpleadoTarea();

      // Inicializar validaciones
      inicializarValidaciones(camposProduccion, "formRegistrarProduccion");
    });

  document.addEventListener("click", function (e) {
    if (e.target.closest(".ver-detalle-btn")) {
      const idproduccion = e.target
        .closest(".ver-detalle-btn")
        .getAttribute("data-idproduccion");
      if (!idproduccion || isNaN(idproduccion)) {
        alert("ID inválido");
        return;
      }
      verDetalleProduccion(idproduccion);
    }
  });

  document
    .getElementById("btnCerrarModalProduccion")
    .addEventListener("click", function () {
      cerrarModal("produccionModal");
      limpiarValidaciones(camposProduccion, "formRegistrarProduccion");
      limpiarFormularioProduccion();
    });

  // BOTÓN CANCELAR DEL MODAL
  document
    .getElementById("btnCancelarProduccion")
    .addEventListener("click", function () {
      cerrarModal("produccionModal");
      limpiarValidaciones(camposProduccion, "formRegistrarProduccion");
      limpiarFormularioProduccion();
    });

  // ACTUALIZAR CAMPO OCULTO CUANDO SE SELECCIONA UN PRODUCTO PRINCIPAL
  const selectProductoPrincipal = document.getElementById(
    "select_producto_principal"
  );
  if (selectProductoPrincipal) {
    selectProductoPrincipal.addEventListener("change", function () {
      const selectedOption = this.options[this.selectedIndex];
      const hiddenInput = document.getElementById("idproducto");
      if (selectedOption.value) {
        hiddenInput.value = selectedOption.value;
      } else {
        hiddenInput.value = "";
      }
    });
  }
  document.addEventListener("click", function (e) {
    if (e.target.closest(".editar-btn")) {
      const idproduccion = e.target
        .closest(".editar-btn")
        .getAttribute("data-idproduccion");

      if (!idproduccion || isNaN(idproduccion)) {
        Swal.fire("Error", "ID inválido.", "error");
        return;
      }

      abrirModalProduccionParaEdicion(idproduccion);
      cargarTareas(idproduccion);
      actualizarProgresoProduccion(idproduccion);
      inicializarBuscadorEmpleado();
      inicializarBuscadorEmpleadoTarea();

      cargarEmpleado();
      cargarProducto();
    }
  });
  // BOTÓN AGREGAR INSUMOS AL DETALLE
  document
    .getElementById("btnAgregarProductoDetalleProduccion")
    .addEventListener("click", function () {
      const selectInsumo = document.getElementById(
        "select_producto_agregar_detalle"
      );
      const selectedOption = selectInsumo.options[selectInsumo.selectedIndex];

      if (!selectedOption.value) {
        Swal.fire("Atención", "Seleccione un insumo.", "warning");
        return;
      }

      const idproducto = selectedOption.value;
      const nombreProducto = selectedOption.textContent;

      // Verificar si ya existe
      if (
        detalleProduccionItems.some((item) => item.idproducto === idproducto)
      ) {
        Swal.fire("Atención", "Este insumo ya fue agregado.", "warning");
        return;
      }

      detalleProduccionItems.push({
        idproducto: idproducto,
        nombre: nombreProducto,
        cantidad: 1,
        cantidad_consumida: 0,
        observaciones: "",
      });

      renderizarTablaDetalleProduccion();
      selectInsumo.value = ""; // Limpiar select
    });

  document
    .getElementById("btnGuardarProduccion")
    .addEventListener("click", function () {
      // Validar campos vacíos
      if (!validarCamposVacios(camposProduccion, "formRegistrarProduccion"))
        return;

      // Validar selects
      camposProduccion.forEach((campo) => {
        if (campo.tipo === "select") {
          validarSelect(campo.id, campo.mensajes, "formRegistrarProduccion");
        }
      });

      // Validar que haya al menos un insumo
      if (detalleProduccionItems.length === 0) {
        Swal.fire(
          "Atención",
          "Debe agregar al menos un insumo al detalle.",
          "warning"
        );
        return;
      }

      // Obtener valores directamente del DOM
      const idproduccion = document.getElementById("idproduccion").value.trim();
      const idproducto = document
        .getElementById("select_producto")
        .value.trim();
      const idempleado_seleccionado = document
        .getElementById("idempleado_seleccionado")
        .value.trim();
      const cantidad_a_realizar = document
        .getElementById("cantidad_a_realizar")
        .value.trim();
      const fecha_inicio = document.getElementById("fecha_inicio").value.trim();
      const fecha_fin =
        document.getElementById("fecha_fin").value.trim() || null;
      const estado = document.getElementById("estado").value.trim();

      // Validación básica
      if (
        !idproducto ||
        !idempleado_seleccionado ||
        !cantidad_a_realizar ||
        !fecha_inicio ||
        !estado
      ) {
        Swal.fire("Error", "Faltan campos obligatorios.", "error");
        return;
      }

      if (parseFloat(cantidad_a_realizar) <= 0) {
        Swal.fire("Error", "La cantidad debe ser mayor a cero.", "error");
        return;
      }

      // Preparar objeto de datos
      const data = {
        idproduccion,
        idempleado: idempleado_seleccionado,
        idproducto,
        cantidad_a_realizar,
        fecha_inicio,
        fecha_fin,
        estado,
        insumos: detalleProduccionItems,
      };

      console.log("Datos enviados:", data);

      // Definir URL y método
      const url = idproduccion
        ? "produccion/updateProduccion"
        : "produccion/createProduccion";
      const method = idproduccion ? "PUT" : "POST";

      // Enviar datos al backend
      fetch(url, {
        method: method,
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(data),
      })
        .then((res) => res.json())
        .then((result) => {
          if (result.status) {
            Swal.fire("Éxito", result.message, "success");
            cerrarModal("produccionModal");
            limpiarFormularioProduccion();
            tablaProduccion.ajax.reload(); // Recargar DataTable
          } else {
            Swal.fire("Error", result.message, "error");
          }
        })
        .catch((err) => {
          console.error("Error al enviar:", err);
          Swal.fire(
            "Error",
            "Hubo un problema al procesar la solicitud.",
            "error"
          );
        });
    });
  document
    .getElementById("btnAgregarTarea")
    .addEventListener("click", function () {
      const idempleado = document.getElementById(
        "idempleado_seleccionado_tarea"
      ).value;
      const idproduccion = document.getElementById("idproduccion").value;

      if (!idempleado || !idproduccion) {
        alert("Debe seleccionar un empleado y haber una producción abierta.");
        return;
      }

      const cantidad_asignada = prompt("¿Cuánto se le asigna?", "10");

      if (
        !cantidad_asignada ||
        isNaN(cantidad_asignada) ||
        cantidad_asignada <= 0
      ) {
        alert("Ingrese una cantidad válida.");
        return;
      }

      fetch("produccion/asignarTarea", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          idproduccion,
          idempleado,
          cantidad_asignada,
          estado: "pendiente",
          fecha_inicio: document.getElementById("fecha_inicio").value,
          observaciones: "",
        }),
      })
        .then((res) => res.json())
        .then((result) => {
          if (result.status) {
            Swal.fire("Éxito", result.message, "success");
            cargarTareas(idproduccion);
            actualizarProgresoProduccion(idproduccion);
          } else {
            Swal.fire("Error", result.message, "error");
          }
        })
        .catch((err) => {
          console.error("Error al asignar tarea:", err);
          alert("Ocurrió un error al asignar la tarea.");
        });
    });
  // FUNCIÓN PARA INICIALIZAR BUSCADOR DE EMPLEADOS
  function inicializarBuscadorEmpleado() {
    const inputCriterioEmpleado = document.getElementById(
      "inputCriterioEmpleado"
    );
    const btnBuscarEmpleado = document.getElementById("btnBuscarEmpleado");
    const listaResultadosEmpleado = document.getElementById(
      "listaResultadosEmpleado"
    );
    const inputIdEmpleado = document.getElementById("idempleado_seleccionado");
    const divInfoEmpleado = document.getElementById(
      "empleado_seleccionado_info"
    );

    if (!btnBuscarEmpleado || !inputCriterioEmpleado) return;

    btnBuscarEmpleado.addEventListener("click", async function () {
      const criterio = inputCriterioEmpleado.value.trim();
      if (criterio.length < 2) {
        Swal.fire(
          "Atención",
          "Ingrese al menos 2 caracteres para buscar.",
          "warning"
        );
        return;
      }

      listaResultadosEmpleado.innerHTML =
        '<div class="p-2 text-xs text-gray-500">Buscando...</div>';
      listaResultadosEmpleado.classList.remove("hidden");

      try {
        const response = await fetch(`produccion/getEmpleado`);
        const data = await response.json(); // ✅ Aquí defines 'data'

        listaResultadosEmpleado.innerHTML = "";

        if (data.status && Array.isArray(data.data)) {
          // ✅ Ahora sí puedes usar data
          data.data.forEach((emp) => {
            const itemDiv = document.createElement("div");
            itemDiv.textContent = `${emp.nombre} ${emp.apellido} (${emp.identificacion})`;
            itemDiv.dataset.id = emp.idempleado;
            itemDiv.dataset.nombre = emp.nombre;
            itemDiv.dataset.apellido = emp.apellido;
            itemDiv.dataset.cedula = emp.identificacion;

            itemDiv.addEventListener("click", function () {
              inputIdEmpleado.value = this.dataset.id;
              divInfoEmpleado.innerHTML = `Sel: <strong>${this.dataset.nombre} ${this.dataset.apellido}</strong> (C.I.: ${this.dataset.cedula})`;
              divInfoEmpleado.classList.remove("hidden");
              inputCriterioEmpleado.value = this.textContent;
              listaResultadosEmpleado.classList.add("hidden");
              listaResultadosEmpleado.innerHTML = "";
            });

            listaResultadosEmpleado.appendChild(itemDiv);
          });
        } else {
          listaResultadosEmpleado.innerHTML =
            '<div class="p-2 text-xs text-gray-500">No se encontraron empleados.</div>';
        }
      } catch (error) {
        console.error("Error al buscar empleados:", error);
        listaResultadosEmpleado.innerHTML =
          '<div class="p-2 text-xs text-red-500">Error al buscar. Intente de nuevo.</div>';
      }
    });

    inputCriterioEmpleado.addEventListener("input", function () {
      inputIdEmpleado.value = "";
      divInfoEmpleado.classList.add("hidden");
      listaResultadosEmpleado.classList.add("hidden");
    });
  }

  // RENDERIZAR TABLA DE DETALLE
  function renderizarTablaDetalleProduccion() {
    const tbody = document.getElementById("cuerpoTablaDetalleProduccion");
    const noDetallesMensaje = document.getElementById(
      "noDetallesMensajeProduccion"
    );

    if (!tbody) return;

    tbody.innerHTML = "";

    if (detalleProduccionItems.length === 0) {
      noDetallesMensaje.classList.remove("hidden");
      return;
    }

    noDetallesMensaje.classList.add("hidden");

    detalleProduccionItems.forEach((item, index) => {
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td class="px-3 py-2">${item.nombre}</td>
        <td class="px-3 py-2"><input type="number" value="${item.cantidad}" min="1" step="1" class="w-24 border rounded-md px-2 py-1 text-sm cantidad-requerida-input" data-index="${index}"></td>
        <td class="px-3 py-2"><input type="number" value="${item.cantidad_consumida}" min="0" step="1" class="w-24 border rounded-md px-2 py-1 text-sm cantidad-usada-input" data-index="${index}"></td>
        <td class="px-3 py-2"><input type="text" value="${item.observaciones}" class="w-full border rounded-md px-2 py-1 text-sm observaciones-input" data-index="${index}"></td>
        <td class="px-3 py-2 text-center">
          <button class="eliminar-detalle-btn text-red-500 hover:text-red-700" data-index="${index}">
            <i class="fas fa-trash-alt"></i>
          </button>
        </td>
      `;
      tbody.appendChild(tr);
    });

    // Eventos de edición
    document.querySelectorAll(".cantidad-requerida-input").forEach((input) => {
      input.addEventListener("input", function () {
        const index = parseInt(this.getAttribute("data-index"));
        detalleProduccionItems[index].cantidad = parseFloat(this.value) || 1;
      });
    });

    document.querySelectorAll(".cantidad-usada-input").forEach((input) => {
      input.addEventListener("input", function () {
        const index = parseInt(this.getAttribute("data-index"));
        detalleProduccionItems[index].cantidad_consumida =
          parseFloat(this.value) || 0;
      });
    });

    document.querySelectorAll(".observaciones-input").forEach((input) => {
      input.addEventListener("input", function () {
        const index = parseInt(this.getAttribute("data-index"));
        detalleProduccionItems[index].observaciones = this.value;
      });
    });

    document.querySelectorAll(".eliminar-detalle-btn").forEach((btn) => {
      btn.addEventListener("click", function (e) {
        e.preventDefault();
        const index = parseInt(this.getAttribute("data-index"));
        detalleProduccionItems.splice(index, 1);
        renderizarTablaDetalleProduccion();
      });
    });
  }

  // CARGAR SELECT DE INSUMOS (para el detalle)
  const selectInsumos = document.getElementById(
    "select_producto_agregar_detalle"
  );
  if (selectInsumos) {
    cargarSelect({
      selectId: "select_producto_agregar_detalle",
      endpoint: "productos/getListaProductosParaFormulario",
      optionTextFn: (p) => `${p.nombre_producto} (${p.nombre_categoria})`,
      optionValueFn: (p) => p.idproducto || "",
      placeholder: "Seleccione un insumo...",
      onLoaded: (productos) => {
        // Puedes filtrar aquí si deseas solo insumos o categorías específicas
      },
    });
  }
  // CARGAR SELECT DE INSUMOS (para el detalle)
  const selectProductoterminado = document.getElementById("select_producto");
  if (selectInsumos) {
    cargarSelect({
      selectId: "select_producto",
      endpoint: "productos/getListaProductosParaFormulario",
      optionTextFn: (p) => `${p.nombre_producto} (${p.nombre_categoria})`,
      optionValueFn: (p) => p.idproducto || "",
      placeholder: "Seleccione un producto terminado...",
      onLoaded: (productos) => {
        // Puedes filtrar aquí si deseas solo insumos o categorías específicas
      },
    });
  }

  // LIMPIAR FORMULARIO
  function limpiarFormularioProduccion() {
    const form = document.getElementById("formRegistrarProduccion");
    if (form) form.reset();

    // Limpiar lista de insumos
    detalleProduccionItems = [];
    renderizarTablaDetalleProduccion(); // Si usas esta función
  }
  function verDetalleProduccion(idproduccion) {
    const modal = document.getElementById("detalleModal");
    const contenido = document.getElementById("contenidoDetalleProduccion");

    abrirModalDetalleProduccion();

    Promise.all([
      fetch(`produccion/getProduccionById/${idproduccion}`).then((res) =>
        res.json()
      ),
      fetch(`produccion/getDetalleProduccionData/${idproduccion}`).then((res) =>
        res.json()
      ),
    ])
      .then(([produccionRes, detalleRes]) => {
        if (!produccionRes.status || !detalleRes.status)
          throw new Error("Error al cargar datos");

        const prod = produccionRes.data;
        const detalle = detalleRes.data;

        let html = `
            <h4 class="font-semibold mb-2">Datos Generales</h4>
            <ul class="mb-4 space-y-1">
                <li><strong>ID:</strong> ${prod.idproduccion}</li>
                <li><strong>Producto:</strong> ${prod.nombre_producto}</li>
                <li><strong>Cantidad a realizar:</strong> ${prod.cantidad_a_realizar}</li>
                <li><strong>Empleado:</strong> ${prod.nombre_empleado}</li>
                <li><strong>Fecha inicio:</strong> ${prod.fecha_inicio}</li>
                <li><strong>Fecha fin:</strong> ${prod.fecha_fin}</li>
                <li><strong>Estado:</strong> ${prod.estado}</li>
            </ul>
            <h4 class="font-semibold mb-2">Insumos</h4>
            <table class="min-w-full table-auto border-collapse">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border px-4 py-2">Producto</th>
                        <th class="border px-4 py-2">Cantidad</th>
                        <th class="border px-4 py-2">Consumida</th>
                        <th class="border px-4 py-2">Observaciones</th>
                    </tr>
                </thead>
                <tbody>
        `;

        detalle.forEach((insumo) => {
          html += `
                <tr>
                    <td class="border px-4 py-2">${insumo.nombre_producto}</td>
                    <td class="border px-4 py-2">${insumo.cantidad}</td>
                    <td class="border px-4 py-2">${
                      insumo.cantidad_consumida
                    }</td>
                    <td class="border px-4 py-2">${
                      insumo.observaciones || "-"
                    }</td>
                </tr>
            `;
        });

        html += `
                </tbody>
            </table>
        `;

        contenido.innerHTML = html;
      })
      .catch((err) => {
        console.error("Error al cargar detalle:", err);
        contenido.innerHTML = "<p>Error al cargar los detalles.</p>";
      });
  }

  function abrirModalDetalleProduccion() {
    const modal = document.getElementById("detalleModal");
    modal.classList.remove("opacity-0", "pointer-events-none");
    document.body.classList.add("overflow-hidden");
  }
  function cerrarModalDetalleProduccion() {
    const modal = document.getElementById("detalleModal");
    modal.classList.add("opacity-0", "pointer-events-none");
    document.body.classList.remove("overflow-hidden");
  }
  document
    .getElementById("cerrarDetalleProduccion")
    .addEventListener("click", cerrarModalDetalleProduccion);

  function abrirModalProduccionParaEdicion(idproduccion) {
    const tbody = document.getElementById("cuerpoTablaDetalleProduccion");

    if (!tbody) {
      console.error(
        "No se encontró el tbody con ID 'cuerpoTablaDetalleProduccion'"
      );
      return;
    }

    tbody.innerHTML = ""; // Limpiar tabla antes de cargar nuevos datos

    // Cargar insumos desde el backend
    fetch(`produccion/getDetalleProduccionData/${idproduccion}`)
      .then((res) => {
        if (!res.ok) throw new Error(`HTTP error ${res.status}`);
        return res.json();
      })
      .then((detalle) => {
        if (detalle.status && detalle.data.length > 0) {
          detalle.data.forEach((insumo) => {
            const tr = document.createElement("tr");
            tr.innerHTML = `
                        <td class="px-3 py-2">${insumo.nombre_producto}</td>
                        <td><input type="number" name="cantidad_insumo[]" class="w-full border rounded p-2" value="${
                          insumo.cantidad
                        }" required></td>
                        <td><input type="number" name="cantidad_utilizada[]" class="w-full border rounded p-2" value="${
                          insumo.cantidad_consumida
                        }" required></td>
                        <td><input type="text" name="observaciones[]" class="w-full border rounded p-2" value="${
                          insumo.observaciones || ""
                        }"></td>
                        <td><button type="button" class="eliminarInsumoBtn text-red-500"><i class="fas fa-trash"></i></button></td>
                    `;
            tbody.appendChild(tr);
          });
        } else {
          const tr = document.createElement("tr");
          tr.innerHTML = `<td colspan="5" class="text-center p-2">No hay insumos registrados.</td>`;
          tbody.appendChild(tr);
        }
      })
      .catch((err) => {
        console.error("Error al obtener detalle:", err);
        alert("Error al cargar los insumos.");
      });

    // Cargar datos generales de producción
    fetch(`produccion/getProduccionById/${idproduccion}`)
      .then((res) => res.json())
      .then((data) => {
        if (data.status) {
          const p = data.data;

          document.getElementById("idproduccion").value = p.idproduccion;
          document.getElementById("EmpleadoCargo").value = p.nombre_empleado;
          document.getElementById("select_producto").value = p.idproducto;
          document.getElementById("cantidad_a_realizar").value =
            p.cantidad_a_realizar;
         const fecha_inicio = p.fecha_inicio ? p.fecha_inicio.split(" ")[0] : "";
            const fecha_fin = p.fecha_fin ? p.fecha_fin.split(" ")[0] : "";
            
            document.getElementById("fecha_inicio").value = fecha_inicio;
            document.getElementById("fecha_fin").value = fecha_fin;
          document.getElementById("estado").value = p.estado;

          abrirModal("produccionModal");
        } else {
          alert("Producción no encontrada.");
        }
      })
      .catch((err) => {
        console.error("Error al obtener producción:", err);
        alert("Error al cargar los datos.");
      });
      
  }
  let listaProductos = [];
  let listaEmpleados = [];

  // Cargar productos al inicio
  async function cargarProducto() {
    try {
      const res = await fetch("produccion/getProductos");
      const data = await res.json();

      if (data.status) {
        listaProductos = data.data;
      } else {
        console.error("No se pudieron cargar los productos.");
      }
    } catch (err) {
      console.error("Error al cargar productos:", err);
    }
  }
  async function cargarEstadisticas() {
    try {
      const res = await fetch("produccion/getEstadisticas");
      const data = await res.json();

      if (data.status) {
        document.querySelector("#total-producciones").textContent =
          data.data.total;
        document.querySelector("#en-clasificacion").textContent =
          data.data.clasificacion;
        document.querySelector("#producidas").textContent =
          data.data.finalizadas;
      } else {
        console.error("Error al cargar estadísticas:", data.message);
      }
    } catch (err) {
      console.error("Error de red:", err);
    }
  }
  // Cargar empleados al inicio
  async function cargarEmpleado() {
    try {
      const res = await fetch("produccion/getEmpleado");
      const data = await res.json();

      if (data.status) {
        listaEmpleados = data.data;
      } else {
        console.error("No se pudieron cargar los empleados.");
      }
    } catch (err) {
      console.error("Error al cargar empleados:", err);
    }
  }
  function cargarEmpleadosTarea() {
    fetch("produccion/getEmpleado")
      .then((res) => res.json())
      .then((data) => {
        const select = document.getElementById("idempleado_tarea");
        select.innerHTML = "<option value=''>Seleccione un empleado</option>";
        data.data.forEach((emp) => {
          const option = document.createElement("option");
          option.value = emp.idempleado;
          option.textContent = `${emp.nombre} ${emp.apellido}`;
          select.appendChild(option);
        });
      })
      .catch((err) => console.error("Error al cargar empleados:", err));
  }
  function inicializarBuscadorEmpleadoTarea() {
    const inputCriterio = document.getElementById("inputCriterioEmpleadoTarea");
    const listaResultados = document.getElementById(
      "listaResultadosEmpleadoTarea"
    );
    const inputIdEmpleado = document.getElementById(
      "idempleado_seleccionado_tarea"
    );
    const divInfo = document.getElementById("empleado_seleccionado_info_tarea");

    if (!inputCriterio || !listaResultados || !inputIdEmpleado || !divInfo) {
      console.warn("Campos necesarios no encontrados para buscar empleado");
      return;
    }

    inputCriterio.addEventListener("input", function () {
      const criterio = this.value.trim();

      if (criterio.length < 2) {
        listaResultados.classList.add("hidden");
        return;
      }

      fetch("produccion/getEmpleado")
        .then((res) => res.json())
        .then((data) => {
          listaResultados.innerHTML = "";
          if (data.status && Array.isArray(data.data)) {
            data.data.forEach((emp) => {
              const itemDiv = document.createElement("div");
              itemDiv.textContent = `${emp.nombre} ${emp.apellido} (${emp.identificacion})`;
              itemDiv.dataset.id = emp.idempleado;
              itemDiv.dataset.nombre = emp.nombre;
              itemDiv.dataset.apellido = emp.apellido;
              itemDiv.dataset.cedula = emp.identificacion;

              itemDiv.addEventListener("click", function () {
                inputIdEmpleado.value = this.dataset.id;
                divInfo.innerHTML = `Sel: <strong>${this.dataset.nombre} ${this.dataset.apellido}</strong> (C.I.: ${this.dataset.cedula})`;
                divInfo.classList.remove("hidden");
                inputCriterio.value = this.textContent;
                listaResultados.classList.add("hidden");
              });

              listaResultados.appendChild(itemDiv);
            });

            listaResultados.classList.remove("hidden");
          } else {
            listaResultados.innerHTML =
              '<div class="p-2 text-xs text-gray-500">No se encontraron empleados.</div>';
          }
        })
        .catch((err) => {
          console.error("Error al buscar empleados:", err);
          listaResultados.innerHTML =
            '<div class="p-2 text-xs text-red-500">Error al buscar empleados.</div>';
        });
    });
  }

  // Llama esto cuando abras el modal de edición

  async function actualizarProgresoProduccion(idproduccion) {
    try {
      const res = await fetch(`produccion/getProduccionById/${idproduccion}`);
      const prodRes = await res.json();

      const resTareas = await fetch(
        `produccion/getTareasByProduccion/${idproduccion}`
      );
      const dataTareas = await resTareas.json();

      let total_realizado = 0;
      if (dataTareas.status && dataTareas.data.length > 0) {
        total_realizado = dataTareas.data.reduce(
          (acc, curr) => acc + parseFloat(curr.cantidad_realizada),
          0
        );
      }

      const cantidad_total = parseFloat(prodRes.data.cantidad_a_realizar);
      const progreso = ((total_realizado / cantidad_total) * 100).toFixed(2);

      document.getElementById("producido").textContent =
        total_realizado.toFixed(2);
      document.getElementById("faltante").textContent = (
        cantidad_total - total_realizado
      ).toFixed(2);
      document.getElementById("porcentaje-progreso").style.width =
        Math.min(progreso, 100) + "%";

      document.getElementById("porcentaje-progreso-texto").textContent =
        "${progreso}%";
    } catch (err) {
      console.error("Error al actualizar progreso:", err);
    }
  }
  let detalleTareasItems = [];

  async function cargarTareas(idproduccion) {
    const tbody = document.getElementById("detalleTareasBody");
    if (!tbody) {
      console.error("No se encontró el tbody para tareas");
      return;
    }

    tbody.innerHTML = "";

    try {
      const res = await fetch(
        `produccion/getTareasByProduccion/${idproduccion}`
      );
      const data = await res.json();

      if (data.status && data.data.length > 0) {
        data.data.forEach((tarea) => {
          const tr = document.createElement("tr");

          tr.innerHTML = `
                    <td>${tarea.nombre_empleado}</td>
                    <td>${tarea.cantidad_asignada}</td>
                    <td><input type="number" class="w-full border rounded p-2 tarea-realizada" data-id="${tarea.idtarea}" value="${tarea.cantidad_realizada}"></td>
                    <td>${tarea.estado}</td>
                    <td><button type="button" class="guardar-tarea-btn bg-green-500 text-white px-2 py-1 rounded">Guardar</button></td>
                `;
          tbody.appendChild(tr);
        });

        // Evento guardar cantidad realizada
        document.querySelectorAll(".guardar-tarea-btn").forEach((btn) => {
          btn.addEventListener("click", async function () {
            const fila = this.closest("tr");
            const idtarea = fila
              .querySelector(".tarea-realizada")
              .getAttribute("data-id");
            const cantidad_realizada =
              fila.querySelector(".tarea-realizada").value;

            const response = await fetch("produccion/updateTarea", {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({
                idtarea,
                cantidad_realizada,
              }),
            });

            const result = await response.json();
            if (result.status) {
              Swal.fire("Éxito", result.message, "success");
              cargarTareas(idproduccion);
              actualizarProgresoProduccion(idproduccion);
            } else {
              Swal.fire("Error", result.message, "error");
            }
          });
        });
      } else {
        const tr = document.createElement("tr");
        tr.innerHTML = `<td colspan="5" class="text-center p-2">No hay tareas asignadas.</td>`;
        tbody.appendChild(tr);
      }
    } catch (err) {
      console.error("Error al cargar tareas:", err);
      alert("Hubo un problema al cargar las tareas.");
    }
  }
   document.querySelectorAll('.tab-button').forEach(button => {
    button.addEventListener('click', () => {
      const tab = button.getAttribute('data-tab');

      
      document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active', 'border-green-500', 'text-green-600');
        btn.classList.add('border-transparent');
      });
      button.classList.add('active', 'border-green-500', 'text-green-600');
      button.classList.remove('border-transparent');


      document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
      });
      document.getElementById(`tab-${tab}`).classList.remove('hidden');
    });
  });
});
