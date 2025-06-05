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
  const PERMISOS_USUARIO = obtenerPermisosUsuario();
  window.PERMISOS_USUARIO = PERMISOS_USUARIO;
 
  const TablaBitacora = $("#TablaBitacora").DataTable({
    ajax: {
      url: "bitacora/getBitacoraData",
      type: "POST",
      dataSrc: "data",
      data: function(d) {
        return {
          ...d,
          draw: d.draw || 1
        };
      },
      error: function(xhr, error, thrown) {
        mostrarAlerta('Error al cargar datos', 'error');
        console.error('Error en la solicitud AJAX:', error);
      }
    },
    columns: [
      { data: "idbitacora", title: "ID" },
      { data: "tabla", title: "Módulo/Tabla" },
      { data: "accion", title: "Acción", className: "text-center" },
      { data: "usuario", title: "Usuario" },
      { data: "fecha", title: "Fecha y Hora", className: "text-center" },
      {
        data: "acciones", 
        title: "Acciones",
        orderable: false,
        searchable: false,
        className: "text-center"
      }
    ],
    dom: 'Bfrtip',
    buttons: [
      // {
      //   extend: 'excelHtml5',
      //   text: '<div><i class="fas fa-file-excel m-auto"></i> Excel</div>',
      //   titleAttr: 'Exportar a Excel',
      //   className: 'btn btn-success btn-sm mr-1',
      //   exportOptions: {
      //     columns: [0, 1, 2, 3, 4]
      //   }
      // },
      {
        extend: 'pdfHtml5',
        text: '<div><i class="fas fa-file-pdf rows-col-"></i> PDF</div>',
        titleAttr: 'Exportar a PDF',
        className: 'btn btn-danger btn-sm',
        exportOptions: {
          columns: [0, 1, 2, 3, 4]
        }
      }
    ],
    language: {
      decimal: "",
      emptyTable: "No hay información",
      info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
      infoEmpty: "Mostrando 0 a 0 de 0 registros",
      infoFiltered: "(filtrado de _MAX_ registros totales)",
      lengthMenu: "Mostrar _MENU_ registros",
      loadingRecords: "Cargando...",
      processing: "Procesando...",
      search: "Buscar:",
      zeroRecords: "No se encontraron coincidencias",
      paginate: {
        first: "Primero",
        last: "Último",
        next: "Siguiente",
        previous: "Anterior"
      }
    },
    responsive: true,
    pageLength: 10,
    order: [[0, "desc"]],
    columnDefs: [
      { className: "text-center", targets: [0, 2, 4, 5] }
    ]
  });
  // Campos a validar en el formulario de producción

  
});
