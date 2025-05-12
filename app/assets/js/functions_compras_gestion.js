document.addEventListener("DOMContentLoaded", function () {
  $("#TablaCompras").DataTable({
    processing: true,
    serverSide: true, // Cambiado a true si vas a implementar paginación y búsqueda del lado del servidor con el nuevo endpoint
    // Si serverSide es false, ajax.dataSrc debe ser "" y el backend debe devolver todo el array.
    // Si serverSide es true, el backend debe manejar los parámetros de DataTable (draw, start, length, search, order)
    // Por simplicidad, lo dejaré como estaba en tu código original (client-side processing después de cargar todo)
    // Para un verdadero server-side, el backend `getComprasDataTable` necesitaría más lógica.
    // Para este ejemplo, asumimos que `getComprasDataTable` devuelve todos los datos y DataTable los procesa.
    // Si son MUCHOS datos, serverSide: true es mejor.
    aProcessing: true, // Mantenido de tu código
    aServerSide: true, // Mantenido de tu código, implica que el backend maneja paginación/filtros
    language: {
      decimal: "",
      emptyTable: "No hay información de compras",
      info: "Mostrando _START_ a _END_ de _TOTAL_ Compras",
      infoEmpty: "Mostrando 0 a 0 de 0 Compras",
      infoFiltered: "(Filtrado de _MAX_ total compras)",
      lengthMenu: "Mostrar _MENU_ Compras",
      loadingRecords: "Cargando...",
      processing: "Procesando...",
      search: "Buscar:",
      zeroRecords: "No se encontraron compras",
      paginate: {
        first: "Primero",
        last: "Último",
        next: "Siguiente",
        previous: "Anterior",
      },
    },
    ajax: {
      url: "/Compras/getComprasDataTable", // Ajusta la URL base si es necesario
      dataSrc: "", // Porque el JSON devuelto es directamente el array de datos
    },
    columns: [
      { data: "nro_compra" },
      { data: "fecha" },
      { data: "proveedor_nombre" },
      { data: "materiales" }, // Este es el GROUP_CONCAT
      { data: "total_general" }, // Ya viene formateado con moneda desde el backend
      { data: "acciones", orderable: false, searchable: false },
    ],
    destroy: true,
    responsive: true,
    pageLength: 10,
    order: [[0, "desc"]], // Ordenar por Nro. Compra descendente (más nuevas primero)
  });
});

// Función de ejemplo para el botón "Ver" (necesitarías implementarla)
function verCompra(idcompra) {
  alert("Ver detalle de la compra ID: " + idcompra);
  // Aquí podrías abrir un modal con los detalles completos de la compra
  // haciendo otra petición AJAX para obtener los datos de esa compra específica.
}
