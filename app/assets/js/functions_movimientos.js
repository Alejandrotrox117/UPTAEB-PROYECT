import {
  abrirModal,
  cerrarModal,
  obtenerPermisosUsuario,
} from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  validarCamposVacios,
  validarDetalleVenta,
  validarSelect,
  validarFecha,
  limpiarValidaciones,
  cargarSelect,
  registrarEntidad,
  validarCampo,
} from "./validaciones.js";

document.addEventListener("DOMContentLoaded", function () {
   const PERMISOS_USUARIO = obtenerPermisosUsuario();
  window.PERMISOS_USUARIO = PERMISOS_USUARIO;
 
 inicializarDataTable();
 
  // Delegación para botón "Ver Detalle"
  document.body.addEventListener("click", function (e) {
    if (e.target.closest(".ver-detalle-btn")) {
      const id = e.target.closest(".ver-detalle-btn").dataset.idmovimiento;
      fetch(`movimientos/getDetalleMovimiento?idmovimiento=${id}`)
        .then(res => res.json())
        .then(res => {
          if (res.status && res.data) {
            const d = res.data;
            document.getElementById("detalleMovimientoContenido").innerHTML = `
              <div class="mb-2"><b>Nro. Movimiento:</b> ${d.idmovimiento}</div>
              <div class="mb-2"><b>Producto:</b> ${d.nombre_producto}</div>
              <div class="mb-2"><b>Tipo de Movimiento:</b> ${d.tipo_movimiento}</div>
              <div class="mb-2"><b>Entrada:</b> ${d.entrada}</div>
              <div class="mb-2"><b>Salida:</b> ${d.salida}</div>
              <div class="mb-2"><b>Stock Resultante:</b> ${d.total}</div>
              <div class="mb-2"><b>Estatus:</b> ${d.estatus}</div>
             
            `;
            abrirModal("modalDetalleMovimiento");
          } else {
            document.getElementById("detalleMovimientoContenido").innerHTML = `<div class="text-red-500">No se encontró el movimiento.</div>`;
            abrirModal("modalDetalleMovimiento");
          }
        });
    }
  });

  // Cerrar modal con los botones del modal
  document.getElementById("cerrarDetalleMovimiento").onclick = function () {
    cerrarModal("modalDetalleMovimiento");
  };
  document.getElementById("cerrarDetalleMovimiento2").onclick = function () {
    cerrarModal("modalDetalleMovimiento");
  };


});












  function inicializarDataTable() {
  let columnsConfig = [
    { data: "idmovimiento", title: "Nro. Movimiento" },
  
    { data: "nombre_producto", title: "Producto" }, // Asegúrate de traer este campo desde el backend
    { data: "tipo_movimiento", title: "Tipo de Movimiento" }, // Traer desde join con tipo_movimiento
    { data: "entrada", title: "Entrada" },
    { data: "salida", title: "Salida" },
    { data: "total", title: "Stock Resultante" },
    { data: "estatus", title: "Estatus",
      render: function (data, type, row) {
        if (data === "activo") {
          return `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">${data}</span>`;
        } else if (data === "inactivo") {
          return `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">${data}</span>`;
        } else {
          return `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">${data}</span>`;
        }
      },
    },
  ];

  const tienePermisoEditar =
    typeof window.PERMISOS_USUARIO !== "undefined" &&
    window.PERMISOS_USUARIO.puede_editar;
  const tienePermisoEliminar =
    typeof window.PERMISOS_USUARIO !== "undefined" &&
    window.PERMISOS_USUARIO.puede_eliminar;

  if (tienePermisoEditar || tienePermisoEliminar) {
    columnsConfig.push({
      data: null,
      title: "Acciones",
      orderable: false,
      searchable: false,
      render: function (data, type, row) {
        let botonesHtml =
          '<div class="flex justify-center items-center gap-x-2">';
        if (tienePermisoEditar) {
          botonesHtml += `
            <button 
              type="button"
              class="ver-detalle-btn text-green-500 hover:text-indigo-700 p-1 rounded-full focus:outline-none" 
              data-idmovimiento="${row.idmovimiento}" 
              title="Ver Detalle">
              <i class="fas fa-eye"></i>
            </button>
            <button 
              type="button"
              class="editar-btn text-blue-500 hover:text-blue-700 p-1 rounded-full focus:outline-none" 
              data-idmovimiento="${row.idmovimiento}" 
              title="Editar Movimiento">
              <i class="fas fa-edit"></i>
            </button>
          `;
        }
        if (tienePermisoEliminar) {
          botonesHtml += `
            <button 
              type="button"
              class="eliminar-btn text-red-500 hover:text-red-700 p-1 rounded-full focus:outline-none" 
              data-idmovimiento="${row.idmovimiento}" 
              title="Eliminar Movimiento">
              <i class="fas fa-trash"></i>
            </button>
          `;
        }
        botonesHtml += "</div>";
        return botonesHtml;
      },
    });
  }

  $("#TablaMovimiento").DataTable({
    processing: true,
    serverSide: false,
    ajax: {
      url: "movimientos/getMovimientos",
      type: "GET",
      dataSrc: "data",
      error: function (xhr, status, error) {
        console.error(
          "Error al cargar datos para DataTable:",
          status,
          error,
          xhr.responseText
        );
        $("#TablaMovimiento_processing").hide();
        var table = $("#TablaMovimiento").DataTable();
        table.clear().draw();
      },
    },
    columns: columnsConfig,
    language: {
      decimal: "",
      emptyTable: "No hay información disponible en la tabla.",
      info: "Mostrando _START_ a _END_ de _TOTAL_ Entradas",
      infoEmpty: "Mostrando 0 a 0 de 0 Entradas",
      infoFiltered: "(Filtrado de _MAX_ total entradas)",
      lengthMenu: "Mostrar _MENU_ Entradas",
      loadingRecords: "Cargando...",
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
    order: [[0, "desc"]],
  });
}
