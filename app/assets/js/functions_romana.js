document.addEventListener("DOMContentLoaded", function () {
  $("#TablaRomana").DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "romana/getRomanaData",
      type: "GET",
      dataSrc: "data",
    },
    columns: [
      { data: "peso", title: "Peso", width: "10%" },
      { data: "fecha", title: "Fecha y Hora", width: "20%" },
      { data: "fecha_creacion", title: "Fecha y Hora de Consulta", width: "30%" },
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



});  
