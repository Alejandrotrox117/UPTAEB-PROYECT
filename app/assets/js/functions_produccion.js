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

document.addEventListener("DOMContentLoaded", function () {
  // Inicialización de DataTables
  $("#TablaProduccion").DataTable({
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

      // Inicializar validaciones
      inicializarValidaciones(camposProduccion, "formRegistrarProduccion");
    });

  // BOTÓN PARA CERRAR EL MODAL
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

  // BOTÓN GUARDAR PRODUCCIÓN
 document.getElementById("btnGuardarProduccion").addEventListener("click", function () {
    if (!validarCamposVacios(camposProduccion, "formRegistrarProduccion")) return;

    // Validar selects
    camposProduccion.forEach((campo) => {
        if (campo.tipo === "select") {
            validarSelect(campo.id, campo.mensajes, "formRegistrarProduccion");
        }
    });

    // Validar insumos
    if (detalleProduccionItems.length === 0) {
        Swal.fire("Atención", "Debe agregar al menos un insumo al detalle.", "warning");
        return;
    }

    const form = document.getElementById("formRegistrarProduccion");
    const formData = new FormData(form);

    // Obtener valores directamente del DOM para asegurarlos
    const idempleado_seleccionado = document.getElementById("idempleado_seleccionado")?.value.trim();
    const idproducto = document.getElementById("select_producto")?.value.trim();
    const cantidad_a_realizar = document.getElementById("cantidad_a_realizar")?.value.trim();
    const fecha_inicio = document.getElementById("fecha_inicio")?.value;
    const fecha_fin = document.getElementById("fecha_fin")?.value || null;
    const estado = document.getElementById("estado")?.value || "borrador";
const data = {
        idempleado: idempleado_seleccionado,
        idproducto,
        cantidad_a_realizar,
        fecha_inicio,
        fecha_fin,
        estado,
        insumos: detalleProduccionItems
    };

    console.log("Datos completos a enviar:", data);
    // Validación manual de campos obligatorios
    if (!idproducto || !cantidad_a_realizar || !fecha_inicio) {
        Swal.fire("Error", "Faltan campos obligatorios.", "error");
        console.warn("Campos faltantes:", { idproducto, cantidad_a_realizar, fecha_inicio });
        return;
    }

    // Enviar también los insumos como array, no como string JSON
    

    // Registrar producción
   // Enviar datos al servidor con fetch()
fetch("produccion/createProduccion", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify(data)
})
.then(res => res.json())
.then(result => {
  if (result.status) {
    Swal.fire("Éxito", result.message, "success");
    $("#TablaProducciones").DataTable().ajax.reload();
    cerrarModal("produccionModal");
    limpiarFormularioProduccion();
  } else {
    Swal.fire("Error", result.message, "error");
  }
})
.catch(err => {
  console.error("Error al enviar:", err);
  Swal.fire("Error", "Hubo un problema al procesar la solicitud.", "error");
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
});
