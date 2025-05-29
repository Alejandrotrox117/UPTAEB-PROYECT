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
 
  TablaBitacora = $("#TablaBitacora").DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "bitacora/getBitacoraData",
      type: "GET",
      dataSrc: "data",
    },
    columns: [
      { data: "idbitacora", title: "ID Bitacora" },
      { data: "tabla", title: "tabla" },
      { data: "accion", title: "Empleado" },
      { data: "nombre_usuario", title: "Usuario" },
      { data: "fecha", title: "fecha" },
      
      {
        data: null,
        title: "Acciones",
        orderable: false,
        render: function (data, type, row) {
          return `
           <button class="ver-detalle-btn text-green-500 hover:text-green-700 p-1 rounded-full ml-2" data-idbitacora="${row.idbitacora}">
          <i class="fas fa-eye"></i>
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

  
});
