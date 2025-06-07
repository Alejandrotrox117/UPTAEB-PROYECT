import { abrirModal, cerrarModal, obtenerPermisosUsuario, } from "./exporthelpers.js";
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
let TablaBitacora = "";
document.addEventListener("DOMContentLoaded", function () {
  // const PERMISOS_USUARIO = obtenerPermisosUsuario();
  // window.PERMISOS_USUARIO = PERMISOS_USUARIO;

  if ($.fn.DataTable.isDataTable("#TablaBitacora")) {
    $("#TablaBitacora").DataTable().destroy();
  }

  const TablaBitacora = $("#TablaBitacora").DataTable({
    processing: true,
    ajax: {
      url: "bitacora/getBitacoraData",
      type: "POST",
      dataSrc: "data",
      data: function (d) {
        return {
          ...d,
          draw: d.draw || 1,
        };
      },
      error: function (xhr, error, thrown) {
        alert("Error al cargar los datos de la bitácora.");
        console.error("Error en la solicitud AJAX:", error, thrown);
      },
    },
    columns: [
      {
        data: "idbitacora",
        title: "ID",
        className:
          "all whitespace-nowrap py-2 px-3 text-gray-700 dt-fixed-col-background text-center",
      },
      {
        data: "tabla",
        title: "Módulo/Tabla",
        className: "desktop whitespace-nowrap py-2 px-3 text-gray-700",
      },
      {
        data: "accion",
        title: "Acción",
        className: "tablet-l whitespace-nowrap py-2 px-3 text-gray-700",
      },
      {
        data: "usuario",
        title: "Usuario",
        className: "desktop whitespace-nowrap py-2 px-3 text-gray-700",
      },
      {
        data: "fecha",
        title: "Fecha y Hora",
        className: "all whitespace-nowrap py-2 px-3 text-gray-700 text-center",
      },
      {
        data: "acciones",
        title: "Acciones",
        orderable: false,
        searchable: false,
        className: "all text-center actions-column py-1 px-2",
        render: function (data, type, row) {
          return data || '<i class="fas fa-eye text-gray-500"></i>';
        },
      },
    ],

    dom:
      "<'flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center mb-4'" +
      "l" + 
      "<'flex items-center'Bf>" + 
      ">" +
      "<'overflow-x-auto't>" +
      "<'flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center mt-4'i p>",
    buttons: [
      {
        extend: "pdfHtml5",
        text: '<i class="fas fa-file-pdf mr-2"></i>Exportar a PDF',
        titleAttr: "Exportar a PDF",
        className:
          "bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-md text-sm inline-flex items-center mr-2",
        exportOptions: {
          columns: [0, 1, 2, 3, 4],
        },
      },
    ],
    language: {
      processing: `
        <div class="fixed inset-0 bg-transparent backdrop-blur-[2px] bg-opacity-40 flex items-center justify-center z-[9999]" style="margin-left:0;">
            <div class="bg-white p-6 rounded-lg shadow-xl flex items-center space-x-3">
                <i class="fas fa-spinner fa-spin fa-2x text-green-500"></i>
                <span class="text-lg font-medium text-gray-700">Procesando...</span>
            </div>
        </div>`,
      emptyTable:
        '<div class="text-center py-4"><i class="fas fa-history fa-2x text-gray-400 mb-2"></i><p class="text-gray-600">No hay registros en la bitácora.</p></div>',
      info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
      infoEmpty: "Mostrando 0 registros",
      infoFiltered: "(filtrado de _MAX_ registros totales)",
      lengthMenu: "Mostrar _MENU_ registros",
      search: "_INPUT_",
      searchPlaceholder: "Buscar en bitácora...",
      zeroRecords:
        '<div class="text-center py-4"><i class="fas fa-search fa-2x text-gray-400 mb-2"></i><p class="text-gray-600">No se encontraron coincidencias.</p></div>',
      paginate: {
        first: '<i class="fas fa-angle-double-left"></i>',
        last: '<i class="fas fa-angle-double-right"></i>',
        next: '<i class="fas fa-angle-right"></i>',
        previous: '<i class="fas fa-angle-left"></i>',
      },
    },
    responsive: {
      details: {
        type: "column",
        target: -1,
        renderer: function (api, rowIdx, columns) {
          var data = $.map(columns, function (col, i) {
            return col.hidden && col.title
              ? `<tr data-dt-row="${col.rowIndex}" data-dt-column="${col.columnIndex}" class="bg-gray-50 hover:bg-gray-100">
                                   <td class="font-semibold pr-2 py-1.5 text-sm text-gray-700 w-1/3">${col.title}:</td>
                                   <td class="py-1.5 text-sm text-gray-900">${col.data}</td>
                               </tr>`
              : "";
          }).join("");
          return data
            ? $(
                '<table class="w-full table-fixed details-table border-t border-gray-200"/>'
              ).append(data)
            : false;
        },
      },
    },
    autoWidth: false,
    pageLength: 10,
    order: [[0, "desc"]], 
    scrollX: true,
    fixedColumns: {
      left: 1,
    },
    className: "compact",
    initComplete: function (settings, json) {
      console.log("DataTable Bitacora inicializado correctamente");
      window.TablaBitacora = this.api();
    },
    drawCallback: function (settings) {
      $(settings.nTableWrapper)
        .find('.dataTables_filter input[type="search"]')
        .addClass(
          "py-2 px-3 text-sm border-gray-300 rounded-md focus:ring-green-400 focus:border-green-400 text-gray-700 bg-white"
        )
        .removeClass("form-control-sm");

      var api = new $.fn.dataTable.Api(settings);
      if (
        api.fixedColumns &&
        typeof api.fixedColumns === "function" &&
        api.fixedColumns().relayout
      ) {
        api.fixedColumns().relayout();
      }
    },
  });
});
