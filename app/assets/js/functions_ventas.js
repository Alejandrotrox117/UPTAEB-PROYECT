import { abrirModal, cerrarModal } from "./exporthelpers.js";
import {
  expresiones,
  inicializarValidaciones,
  validarCamposVacios,
  validarFecha,
  validarSelect,
  registrarEntidad,limpiarValidaciones
} from "./validaciones.js";


document.addEventListener("DOMContentLoaded", function () {
  // Inicializar DataTable
  inicializarDataTable();
  // campos y validaciones importados del archivo validaciones.js
  //
  const camposVentas = [
    {
      id: "fecha_venta_modal",
      tipo: "date",
      mensajes: {
        vacio: "La fecha es obligatoria.",
        fechaPosterior: "La fecha no puede ser posterior a hoy.",
      },
    },
    {
      id: "idmoneda_general",
      tipo: "select",
      mensajes: {
        vacio: "Debe seleccionar una moneda.",
      },
    },
    {
      id: "nombre_cliente",
      tipo: "input",
      regex: expresiones.nombre,
      mensajes: {
        vacio: "El nombre es obligatorio.",
        formato: "El nombre debe tener entre 2 y 20 caracteres.",
      },
    },
    
   
    {
      id: "observaciones",
      tipo: "textarea",
      regex: expresiones.observaciones,
      mensajes: {
        vacio: "Las observaciones son obligatorias.",
        formato: "Las observaciones no deben exceder los 50 caracteres.",
      },
    },
    {
      id: "estatus",
      tipo: "select",
      mensajes: {
        vacio: "Debe seleccionar un estatus.",
      },
    },
  ];
  
  const camposClientes = [
    
 {
      id: "cedula",
      tipo: "input",
      regex: expresiones.cedula,
      mensajes: {
        vacio: "La cédula es obligatoria.",
        formato:
          "La Cédula debe contener la estructura V-XXXXX No debe contener espacios y solo números.",
      },
    },
    {
      id: "nombre",
      tipo: "input",
      regex: expresiones.nombre,
      mensajes: {
        vacio: "El nombre es obligatorio.",
        formato:
          "El nombre debe tener entre 5 y 30 caracteres. No debe contener números.",
      },
    },
    {
      id: "apellido",
      tipo: "input",
      regex: expresiones.apellido,
      mensajes: {
        vacio: "El apellido es obligatorio.",
        formato:
          "El apellido debe tener entre 5 y 30 caracteres. No debe contener números.",
      },
    },
    
   
   
    {
      id: "telefono_principal",
      tipo: "input",
      regex: expresiones.telefono_principal,
      mensajes: {
        vacio: "El teléfono es obligatorio.",
        formato:
          "El teléfono debe tener exactamente 11 dígitos. No debe contener letras.",
      },
    },
    {
      id: "direccion",
      tipo: "input",
      regex: expresiones.direccion,
      mensajes: {
        vacio: "La dirección es obligatoria.",
        formato: "La dirección debe tener entre 20 y 50 caracteres.",
      },
    },
    {
      id: "observacionesCliente",
      tipo: "input",
      regex: expresiones.observaciones,
      mensajes: {
        vacio: "Las observaciones son obligatorias.",
        formato: "Las observaciones no deben exceder los 50 caracteres.",
      },
    },
    
    {
      id: "estatus",
      tipo: "select",
      mensajes: {
        vacio: "Debe seleccionar un estatus.",
      },
    },
  
     
  
    
    ];

  


  
  

  
  
//REGISTRAR VENTA
document.getElementById("registrarVentaBtn").addEventListener("click", function () {
  if(!validarCamposVacios(camposVentas,"ventaForm"))return;
  camposVentas.forEach((campo) => {
  if (campo.tipo === "select") {
    validarSelect(campo.id, campo.mensajes, "ventaForm");
  }
});
  registrarEntidad({
    formId: "ventaForm",
    endpoint: "ventas/createventa",
    campos: camposVentas,
    onSuccess: (result) => {
      Swal.fire({
        title: "¡Éxito!",
        text: result.message || "Venta registrada correctamente.",
        icon: "success",
        confirmButtonText: "Aceptar",
      }).then(() => {
        $("#Tablaventas").DataTable().ajax.reload();
        cerrarModal("ventaModal");
        limpiarFormulario();
      });
    }
  });
});

  
  //NOTIFICACION PARA CONFIRMACION DE ELIMINACION DE VENTA
  function confirmarEliminacion(idventa) {
    Swal.fire({
      title: "¿Estás seguro?",
      text: "Esta acción desactivará al venta.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Sí, eliminar",
      cancelButtonText: "Cancelar",
    }).then((result) => {
      if (result.isConfirmed) {
        eliminarventa(idventa);
      }
    });
  }

  // ELIMINAR VENTA
  function eliminarventa(idventa) {
    fetch(`ventas/deleteventa`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ idventa }),
    })
      .then((response) => response.json())
      .then((result) => {
        if (result.status) {
          Swal.fire({
            title: "¡Éxito!",
            text: result.message || "venta eliminado correctamente.",
            icon: "success",
            confirmButtonText: "Aceptar",
          }).then(() => {
            $("#Tablaventas").DataTable().ajax.reload();
          });
        } else {
          Swal.fire({
            title: "¡Error!",
            text: result.message || "No se pudo eliminar el venta.",
            icon: "error",
            confirmButtonText: "Aceptar",
          });
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        Swal.fire({
          title: "¡Error!",
          text: "Ocurrió un error al intentar eliminar el venta.",
          icon: "error",
          confirmButtonText: "Aceptar",
        });
      });
  }

  // MODIFICAR VENTA
  function abrirModalventaParaEdicion(idventa) {
    fetch(`ventas/getventaById/${idventa}`)
      .then((response) => {
        if (!response.ok) {
          throw new Error(`Error HTTP: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        if (!data.status) {
          throw new Error(
            data.message || "Error al cargar los datos del venta."
          );
        }

        const venta = data.data;

        // Asignar los valores a los campos del formulario
        document.getElementById("idventa").value = venta.idventa || "";
        document.getElementById("nombre").value = venta.nombre || "";
        document.getElementById("apellido").value = venta.apellido || "";
        document.getElementById("cedula").value = venta.cedula || "";
        document.getElementById("telefono_principal").value =
          venta.telefono_principal || "";
        document.getElementById("direccion").value = venta.direccion || "";
        document.getElementById("observaciones").value =
          venta.observaciones || "";
        document.getElementById("estatus").value = venta.estatus || "";

        abrirModal("ventaModal");
      })
      .catch((error) => {
        console.error("Error capturado al cargar los datos:", error.message);
        Swal.fire({
          title: "¡Error!",
          text: "Ocurrió un error al cargar los datos del venta. Por favor, intenta nuevamente.",
          icon: "error",
          confirmButtonText: "Aceptar",
        });
      });
  }




//AGREGAR CLIENTE
 //Boton registrar cliente
  document
    .getElementById("registrarClienteBtn")
    .addEventListener("click", function () {
      //VALIDACIONES
     if (!validarCamposVacios(camposClientes, "clienteForm")) return;
      // Validar selects SOLO del formulario de cliente
    camposClientes.forEach((campo) => {
      if (campo.tipo === "select") {
        const form = document.getElementById("clienteForm");
        const input = form ? form.querySelector(`#${campo.id}`) : null;
        if (input) validarSelect(input, campo.mensajes);
      }
    });
       
      registrarEntidad({
    formId: "clienteForm",
    endpoint: "clientes/createcliente",
    campos: camposClientes,
    onSuccess: (result) => {
      Swal.fire({
        title: "¡Éxito!",
        text: result.message || "Cliente registrada correctamente.",
        icon: "success",
        confirmButtonText: "Aceptar",
      }).then(() => {
        limpiarValidaciones(camposClientes,"clienteForm");
        cerrarModal("clienteModal");
        limpiarFormulario();
      });
    }
  });
  
      
      
    });









  //BUSCADOR DE CLIENTES 
function inicializarBuscadorCliente() {
    const inputCriterioClienteModal = document.getElementById(
      "inputCriterioClienteModal"
    );
    const btnBuscarClienteModal = document.getElementById(
      "btnBuscarClienteModal"
    );
    const listaResultadosClienteModal = document.getElementById(
      "listaResultadosClienteModal"
    );
    const inputIdCliente = document.getElementById("idcliente");
    const divInfoClienteModal = document.getElementById(
      "cliente_seleccionado_info_modal"
    );

    if (!btnBuscarClienteModal || !inputCriterioClienteModal) return;

    btnBuscarClienteModal.addEventListener("click", async function () {
      const criterio = inputCriterioClienteModal.value.trim();
      if (criterio.length < 2) {
        Swal.fire(
          "Atención",
          "Ingrese al menos 2 caracteres para buscar.",
          "warning"
        );
        return;
      }

      listaResultadosClienteModal.innerHTML =
        '<div class="p-2 text-xs text-gray-500">Buscando...</div>';
      listaResultadosClienteModal.classList.remove("hidden");

      try {
        const response = await fetch(
          `clientes/buscar?criterio=${encodeURIComponent(criterio)}`
        );
        if (!response.ok) {
          throw new Error("Error en la respuesta del servidor");
        }
        const clientes = await response.json();

        listaResultadosClienteModal.innerHTML = "";
        if (clientes && clientes.length > 0) {
          clientes.forEach((cli) => {
            const itemDiv = document.createElement("div");
            itemDiv.textContent = `${cli.nombre || ""} ${cli.apellido || ""} (${
              cli.cedula || ""
            })`.trim();
            itemDiv.dataset.idcliente = cli.id;
            itemDiv.dataset.nombre = cli.nombre || "";
            itemDiv.dataset.apellido = cli.apellido || "";
            itemDiv.dataset.cedula = cli.cedula || "";

            itemDiv.addEventListener("click", function () {
              inputIdCliente.value = this.dataset.idcliente;
              divInfoClienteModal.innerHTML = `Sel: <strong>${this.dataset.nombre} ${this.dataset.apellido}</strong> (C.I.: ${this.dataset.cedula})`;
              divInfoClienteModal.classList.remove("hidden");
              inputCriterioClienteModal.value = this.textContent;
              listaResultadosClienteModal.classList.add("hidden");
              listaResultadosClienteModal.innerHTML = "";
            });
            listaResultadosClienteModal.appendChild(itemDiv);
          });
        } else {
          listaResultadosClienteModal.innerHTML =
            '<div class="p-2 text-xs text-gray-500">No se encontraron clientes.</div>';
        }
      } catch (error) {
        console.error("Error al buscar clientes:", error);
        listaResultadosClienteModal.innerHTML =
          '<div class="p-2 text-xs text-red-500">Error al buscar. Intente de nuevo.</div>';
      }
    });

    // Limpiar selección si se escribe otro cliente en el input
    inputCriterioClienteModal.addEventListener("input", function () {
      inputIdCliente.value = "";
      divInfoClienteModal.classList.add("hidden");
      listaResultadosClienteModal.classList.add("hidden");
    });
  }




  

  // Función para calcular totales
  const descuentoInput = document.getElementById(
    "descuento_porcentaje_general"
  );
  if (descuentoInput) {
    descuentoInput.addEventListener("input", function () {
      calcularTotales();
    });
  }

  function calcularTotales() {
    const subtotalInput = document.getElementById("subtotal_general");
    const descuentoInput = document.getElementById(
      "descuento_porcentaje_general"
    );
    const montoDescuentoInput = document.getElementById(
      "monto_descuento_general"
    );
    const totalInput = document.getElementById("total_general");

    if (
      !subtotalInput ||
      !descuentoInput ||
      !montoDescuentoInput ||
      !totalInput
    )
      return;

    const subtotal = parseFloat(subtotalInput.value) || 0;
    const descuento = parseFloat(descuentoInput.value) || 0;

    const montoDescuento = (subtotal * descuento) / 100;
    const total = subtotal - montoDescuento;

    montoDescuentoInput.value = montoDescuento.toFixed(2);
    totalInput.value = total.toFixed(2);
  }

  

  // BOTONES DE MODAL DE VENTAS
  document
    .getElementById("abrirModalBtn")
    .addEventListener("click", function () {
      abrirModal("ventaModal");
      inicializarValidaciones(camposVentas,"ventaForm");
    });

  // Botón para cerrar el modal
  document
    .getElementById("cerrarModalBtn")
    .addEventListener("click", function () {
      cerrarModal("ventaModal");
      limpiarValidaciones(camposVentas,"ventaForm");
      limpiarFormulario();
    });

  // Botón para registrar la venta
  // document
  //   .getElementById("registrarVentaBtn")
  //   .addEventListener("click", function () {
  //     RegistrarNuevaVenta(campos);
  //   });

  // Botón para agregar un producto al detalle
  // document.getElementById("agregarDetalleBtn").addEventListener("click", function () {
  //   agregarProductoDetalle();
  // });

  //BOTONES DE FORMULARIO CLIENTE
  document
    .getElementById("abrirModalCliente")
    .addEventListener("click", function () {
      abrirModal("clienteModal");
      inicializarValidaciones(camposClientes,"clienteForm");
    });
  // Botón buscar clietnte
  document
    .getElementById("btnBuscarClienteModal")
    .addEventListener("click", function () {
      inicializarBuscadorCliente();
    });
  document
    .getElementById("btnCerrarModalCliente")
    .addEventListener("click", function (e) {
      cerrarModal("clienteModal");
      limpiarValidaciones(camposClientes,"clienteForm");
    });
    document
    .getElementById("cerrarModalClienteBtn")
    .addEventListener("click", function (e) {
      cerrarModal("clienteModal");
      limpiarValidaciones(camposClientes,"clienteForm");
    });
 
 
   
  
    
  //FIN DE BOTONES DE FORMULARIO CLIENTE







  // Evento para recalcular totales cuando cambie el descuento
  document
    .getElementById("descuento_porcentaje_general")
    .addEventListener("input", function () {
      calcularTotales();
    });

  // Evento para manejar el clic en el botón de eliminar
  document.addEventListener("click", function (e) {
    if (e.target.closest(".eliminar-btn")) {
      const idventa = e.target
        .closest(".eliminar-btn")
        .getAttribute("data-idventa");
      confirmarEliminacion(idventa);
    }
  });

  // Evento para manejar el clic en el botón de editar
  document.addEventListener("click", function (e) {
    if (e.target.closest(".editar-btn")) {
      const idventa = e.target
        .closest(".editar-btn")
        .getAttribute("data-idventa");
      if (!idventa || isNaN(idventa)) {
        Swal.fire({
          title: "¡Error!",
          text: "ID de venta no válido.",
          icon: "error",
          confirmButtonText: "Aceptar",
        });
        return;
      }
      abrirModalventaParaEdicion(idventa);
    }
  });

  // Función para agregar un producto al detalle
  function agregarProductoDetalle() {
    const detalleVentaBody = document.getElementById("detalleVentaBody");
    const nuevaFila = `
      <tr>
        <td><input type="text" name="producto[]" class="border rounded-lg px-2 py-1 w-full"></td>
        <td><input type="text" name="descripcion[]" class="border rounded-lg px-2 py-1 w-full"></td>
        <td><input type="number" name="cantidad[]" class="border rounded-lg px-2 py-1 w-full cantidad-input" value="1" min="1"></td>
        <td><input type="number" name="precio_unitario[]" class="border rounded-lg px-2 py-1 w-full precio-input" value="0" min="0"></td>
        <td><input type="number" name="subtotal[]" class="border rounded-lg px-2 py-1 w-full subtotal-input" value="0" readonly></td>
        <td>
          <button type="button" class="eliminar-detalle-btn text-red-500 hover:text-red-700">
            <i class="fas fa-trash"></i>
          </button>
        </td>
      </tr>
    `;
    detalleVentaBody.insertAdjacentHTML("beforeend", nuevaFila);
    actualizarEventosDetalle();
  }

  // Función para actualizar eventos en los inputs del detalle
  function actualizarEventosDetalle() {
    const detalleVentaBody = document.getElementById("detalleVentaBody");
    const cantidadInputs = detalleVentaBody.querySelectorAll(".cantidad-input");
    const precioInputs = detalleVentaBody.querySelectorAll(".precio-input");
    const eliminarBtns = detalleVentaBody.querySelectorAll(
      ".eliminar-detalle-btn"
    );

    cantidadInputs.forEach((input) => {
      input.addEventListener("input", calcularSubtotal);
    });

    precioInputs.forEach((input) => {
      input.addEventListener("input", calcularSubtotal);
    });

    eliminarBtns.forEach((btn) => {
      btn.addEventListener("click", function (e) {
        e.target.closest("tr").remove();
        calcularTotales();
      });
    });
  }

  // Función para calcular el subtotal de cada fila
  function calcularSubtotal() {
    const fila = this.closest("tr");
    const cantidad = fila.querySelector(".cantidad-input").value || 0;
    const precio = fila.querySelector(".precio-input").value || 0;
    const subtotal = fila.querySelector(".subtotal-input");

    subtotal.value = (cantidad * precio).toFixed(2);
    calcularTotales();
  }

  // Función para calcular los totales generales
  function calcularTotales() {
    const detalleVentaBody = document.getElementById("detalleVentaBody");
    let subtotalGeneral = 0;

    // Sumar todos los subtotales
    detalleVentaBody.querySelectorAll(".subtotal-input").forEach((input) => {
      subtotalGeneral += parseFloat(input.value) || 0;
    });

    // Calcular el descuento
    const descuentoPorcentaje =
      parseFloat(
        document.getElementById("descuento_porcentaje_general").value
      ) || 0;
    const montoDescuento = (subtotalGeneral * descuentoPorcentaje) / 100;

    // Calcular el total general
    const totalGeneral = subtotalGeneral - montoDescuento;

    // Actualizar los campos
    document.getElementById("subtotal_general").value =
      subtotalGeneral.toFixed(2);
    document.getElementById("monto_descuento_general").value =
      montoDescuento.toFixed(2);
    document.getElementById("total_general").value = totalGeneral.toFixed(2);
  }

  function limpiarFormulario() {
    // Reiniciar el formulario completo
    const formulario = document.getElementById("ventaForm");
    if (formulario) {
      formulario.reset();
    }

    // Reiniciar los selects
    const selects = formulario.querySelectorAll("select");
    selects.forEach((select) => {
      select.value = ""; // Reinicia el valor del select
      select.classList.remove("border-red-500", "focus:ring-red-500");
      select.classList.add("border-gray-300", "focus:ring-green-400");
    });

    // Reiniciar los inputs de tipo date
    const dateInputs = formulario.querySelectorAll('input[type="date"]');
    dateInputs.forEach((input) => {
      input.value = ""; // Reinicia el valor del input de tipo date
      input.classList.remove("border-red-500", "focus:ring-red-500");
      input.classList.add("border-gray-300", "focus:ring-green-400");
    });

    // Limpiar el cuerpo del detalle de la venta (si aplica)
    const detalleVentaBody = document.getElementById("detalleVentaBody");
    if (detalleVentaBody) {
      detalleVentaBody.innerHTML = ""; // Elimina todas las filas del detalle
    }

    // Reiniciar el buscador de clientes
    const inputCriterioClienteModal = document.getElementById(
      "inputCriterioClienteModal"
    );
    const listaResultadosClienteModal = document.getElementById(
      "listaResultadosClienteModal"
    );
    const inputIdCliente = document.getElementById("idcliente");
    const divInfoClienteModal = document.getElementById(
      "cliente_seleccionado_info_modal"
    );

    if (inputCriterioClienteModal) inputCriterioClienteModal.value = "";
    if (listaResultadosClienteModal) {
      listaResultadosClienteModal.innerHTML = "";
      listaResultadosClienteModal.classList.add("hidden");
    }
    if (inputIdCliente) inputIdCliente.value = "";
    if (divInfoClienteModal) {
      divInfoClienteModal.innerHTML = "";
      divInfoClienteModal.classList.add("hidden");
    }
  }
});

// Función para inicializar DataTable
function inicializarDataTable() {
  $("#Tablaventas").DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "ventas/getventasData",
      type: "GET",
      dataSrc: "data",
    },
    columns: [
      { data: "nro_venta", title: "Nro" },
      { data: "nombre_producto", title: "Producto" },
      { data: "fecha", title: "Fecha" },
      { data: "total_general", title: "Total" },
      { data: "observaciones", title: "Observaciones" },
      { data: "estatus", title: "Estatus" },
      {
        data: null,
        title: "Acciones",
        orderable: false,
        render: function (data, type, row) {
          return `
            <button class="editar-btn text-blue-500 hover:text-blue-700 p-1 rounded-full" data-idventa="${row.idventa}">
              <i class="fas fa-edit"></i>
            </button>
            <button class="eliminar-btn text-red-500 hover:text-red-700 p-1 rounded-full ml-2" data-idventa="${row.idventa}">
              <i class="fas fa-trash"></i>
            </button>
          `;
        },
      },
    ],
    language: {
      decimal: "",
      emptyTable: "No hay información",
      info: "Mostrando _START_ a _END_ de _TOTAL_ Entradas",
      infoEmpty: "Mostrando 0 a 0 de 0 Entradas",
      infoFiltered: "(Filtrado de _MAX_ total entradas)",
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
}


