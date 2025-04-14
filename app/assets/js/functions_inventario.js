var dtInventario;

document.addEventListener(
  "DOMContentLoaded",
  function () {
    dtInventario = $("#dtInventario").DataTable({
      aProcessing: true,
      aServerSide: true,
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
          last: "Ultimo",
          next: "Siguiente",
          previous: "Anterior",
        },
      },
      ajax: {
       url: base_url + "getInventario",
        dataSrc: "",
      },
      columns: [
        
        { data: "id_movimiento" },
        { data: "inicial" },

        { data: "ajuste" },
        { data: "" },
        { data: "material_compra" },
        { data: "despacho" },
        { data: "descuento" },
        {data: "final"},
        { data: "fecha" }


      ],
      destroy: true,
      responsive: true,
      pageLength: 10,
      order: [[0, "asc"]],
    });
    
  },
  false
 
);
