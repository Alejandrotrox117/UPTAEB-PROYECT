document.addEventListener("DOMContentLoaded", function () {
  const PERMISOS_USUARIO = obtenerPermisosUsuario();
  window.PERMISOS_USUARIO = PERMISOS_USUARIO;
  
  
  let columns = [
    { data: "peso", title: "Peso", width: "15%" },
    { data: "fecha", title: "Fecha y Hora", width: "25%" },
    { data: "fecha_creacion", title: "Fecha y Hora de Consulta", width: "25%" },
    { data: "estatus", title: "Estado", width: "15%" }
  ];

  
  if (PERMISOS_USUARIO.puede_editar || PERMISOS_USUARIO.puede_eliminar) {
    columns.push({
      data: null,
      title: "Acciones",
      width: "20%",
      orderable: false,
      render: function (data, type, row) {
        let botones = '';
        
        if (PERMISOS_USUARIO.puede_editar) {
          botones += `<button class="btn-editar bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs mr-1" data-id="${row.idromana}">
                        <i class="fas fa-edit"></i> Editar
                      </button>`;
        }
        
        if (PERMISOS_USUARIO.puede_eliminar) {
          botones += `<button class="btn-eliminar bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs" data-id="${row.idromana}">
                        <i class="fas fa-trash"></i> Eliminar
                      </button>`;
        }
        
        return botones;
      }
    });
  }

  $("#TablaRomana").DataTable({
    processing: true,
    serverSide: false, 
    ajax: {
      url: "romana/getRomanaData",
      type: "GET",
      dataSrc: "data", 
      error: function(xhr, error, thrown) {
        console.error('Error al cargar datos:', error);
        Swal.fire('Error', 'No se pudieron cargar los datos de la romana', 'error');
      }
    },
    columns: columns,
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
    order: [[2, "desc"]], 
  });

  
  $('#TablaRomana').on('click', '.btn-editar', function() {
    const idromana = $(this).data('id');
    editarRomana(idromana);
  });

  $('#TablaRomana').on('click', '.btn-eliminar', function() {
    const idromana = $(this).data('id');
    eliminarRomana(idromana);
  });
});


function obtenerPermisosUsuario() {
  const permisosElement = document.getElementById('permisosUsuario');
  if (permisosElement) {
    try {
      return JSON.parse(permisosElement.dataset.permisos);
    } catch (e) {
      console.error('Error al parsear permisos:', e);
      return {};
    }
  }
  return {};
}


function editarRomana(id) {
  console.log('Editar romana ID:', id);
  
}

function eliminarRomana(id) {
  Swal.fire({
    title: '¿Estás seguro?',
    text: "Esta acción no se puede deshacer",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#00c950',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      
      console.log('Eliminar romana ID:', id);
    }
  });
}
