var dtInventario;

// Mueve la función OpenModal fuera del bloque para que esté en el contexto global
function OpenModal() {
  // Usa jQuery para mostrar el modal
  $("#crud-modal").modal("show");
}
// Mostrar el modal
document.getElementById('show-modal').addEventListener('click', function () {
  document.getElementById('popup-modal').classList.remove('hidden');
});

// Ocultar el modal
document.getElementById('close-modal').addEventListener('click', function () {
  document.getElementById('popup-modal').classList.add('hidden');
});

document.getElementById('cancel-modal').addEventListener('click', function () {
  document.getElementById('popup-modal').classList.add('hidden');
});

// Acción de confirmación
document.getElementById('confirm-delete').addEventListener('click', function () {
  alert('Producto eliminado');
  document.getElementById('popup-modal').classList.add('hidden');
});
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
        url: "inventario/getInventario",
        dataSrc: "",
      },
      columns: [
        { data: "id_movimiento" },
        { data: "inicial" },
        { data: "ajuste" },
        { data: "material_compra" },
        { data: "despacho" },
        { data: "descuento" },
        { data: "final" },
        { data: "fecha" },
      ],
      destroy: true,
      responsive: true,
      pageLength: 10,
      order: [[0, "asc"]],
    });
  },
  false
);

