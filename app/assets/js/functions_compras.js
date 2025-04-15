document.addEventListener("DOMContentLoaded", function () {
  $('#TablaCompras').DataTable({ // Asocia la configuración al ID "TablaCompras"
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
        last: "Último",
        next: "Siguiente",
        previous: "Anterior",
      },
    },
    ajax: {
      url: "Compras/getComprasData",
      dataSrc: "", // Configura el origen de datos
    },
    columns: [
      { "data": "nro_compra" },
      { "data": "fecha" },
      { "data": "idproveedor" },
      { "data": "idmaterial" },
      { "data": "peso_neto" },
      { "data": "%_descuento" },
      { "data": "total" }
    ],
    destroy: true,
    responsive: true,
    pageLength: 10,
    order: [[0, "asc"]],
  });



  /* Modal de Registro */
  const openRegistrationModalBtn = document.getElementById('openRegistrationModalBtn');
  const registrationModal = document.getElementById('registrationModal');
  const registrationCloseBtn = document.getElementById('registrationCloseBtn');
  const registrationCancelBtn = document.getElementById('registrationCancelBtn');

  const openRegistrationModal = () => {
    registrationModal.classList.remove('opacity-0', 'pointer-events-none');
    registrationModal.classList.add('opacity-100');
  };

  const closeRegistrationModal = () => {
    registrationModal.classList.remove('opacity-100');
    registrationModal.classList.add('opacity-0', 'pointer-events-none');
  };

  if(openRegistrationModalBtn) {
    openRegistrationModalBtn.addEventListener('click', openRegistrationModal);
  }
  if(registrationCloseBtn) {
    registrationCloseBtn.addEventListener('click', closeRegistrationModal);
  }
  if(registrationCancelBtn) {
    registrationCancelBtn.addEventListener('click', closeRegistrationModal);
  }

  registrationModal.addEventListener('click', (e) => {
    if (e.target === registrationModal) {
      closeRegistrationModal();
    }
  });

  /* Modal de Eliminación */
  const openDeletionModalBtn = document.getElementById('openDeletionModalBtn');
  const deletionModal = document.getElementById('deletionModal');
  const deletionCloseBtn = document.getElementById('deletionCloseBtn');
  const deletionCancelBtn = document.getElementById('deletionCancelBtn');

  const openDeletionModal = () => {
    deletionModal.classList.remove('opacity-0', 'pointer-events-none');
    deletionModal.classList.add('opacity-100');
  };

  const closeDeletionModal = () => {
    deletionModal.classList.remove('opacity-100');
    deletionModal.classList.add('opacity-0', 'pointer-events-none');
  };

  if(openDeletionModalBtn) {
    openDeletionModalBtn.addEventListener('click', openDeletionModal);
  }
  if(deletionCloseBtn) {
    deletionCloseBtn.addEventListener('click', closeDeletionModal);
  }
  if(deletionCancelBtn) {
    deletionCancelBtn.addEventListener('click', closeDeletionModal);
  }

  deletionModal.addEventListener('click', (e) => {
    if (e.target === deletionModal) {
      closeDeletionModal();
    }
  });
});

 
