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