document.addEventListener("DOMContentLoaded", function () {
    let tablaProveedores; 
    tablaProveedores = $("#TablaProveedores").DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "proveedores/getProveedoresData",
            type: "GET", 
            dataSrc: "data", 
        },
        columns: [
            { data: "idproveedor", title: "ID" },
            { data: "nombre", title: "Nombre/Razón Social" },
            { data: "apellido", title: "Apellido (Contacto)" },
            { data: "identificacion", title: "Identificación" },
            { data: "telefono_principal", title: "Teléfono" },
            { data: "correo_electronico", title: "Correo" },
            { data: "estatus", title: "Estatus" },
            {
                data: null,
                title: "Acciones",
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    return `
                        <button class="editar-proveedor-btn text-blue-500 hover:text-blue-700 p-1" data-idproveedor="${row.idproveedor}" title="Editar">
                            <i class="fas fa-edit fa-lg"></i>
                        </button>
                        <button class="eliminar-proveedor-btn text-red-500 hover:text-red-700 p-1 ml-2" data-idproveedor="${row.idproveedor}" title="Eliminar">
                            <i class="fas fa-trash fa-lg"></i>
                        </button>
                    `;
                },
            },
        ],
        language: {  },
        destroy: true,
        responsive: true,
        pageLength: 10,
        order: [[0, "asc"]],
    });

    const modalProveedor = document.getElementById("proveedorModal");
    const formProveedor = document.getElementById("proveedorForm");
    const modalTitulo = document.getElementById("modalProveedorTitulo");
    const btnSubmitProveedor = document.getElementById("btnSubmitProveedor");
    const inputIdPersona = document.getElementById("idproveedor");

       window.abrirModalProveedor = function (titulo = "Registrar Proveedor", formAction = "proveedores/createProveedor") {
        formProveedor.reset(); // 
        inputIdPersona.value = ""; 
        modalTitulo.textContent = titulo;
        formProveedor.setAttribute("data-action", formAction); 
        btnSubmitProveedor.textContent = "Registrar";
        modalProveedor.classList.remove("opacity-0", "pointer-events-none");
    };

    // Cerrar modal de proveedor
    window.cerrarModalProveedor = function () {
        modalProveedor.classList.add("opacity-0", "pointer-events-none");
        formProveedor.reset();
        inputIdPersona.value = "";
    };

    // Enviar formulario (Crear o Actualizar)
    formProveedor.addEventListener("submit", function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        const actionUrl = "proveedores/createProveedor";
        const method = "POST"; 

        if (!actionUrl) {
            Swal.fire("Error Interno", "URL de acción no definida para el formulario.", "error");
            console.error("El atributo data-action del formulario está vacío o no existe.");
            return;
        }

        // Validaciones básicas
        const nombre = formData.get('nombre');
        const identificacion = formData.get('identificacion');
        const telefono_principal = formData.get('telefono_principal');

        if (!nombre || !identificacion || !telefono_principal) {
            Swal.fire("Atención", "Nombre, Identificación y Teléfono son obligatorios.", "warning");
            return;
        }
        

        fetch(actionUrl, {
            method: method,
            body: formData // Enviar FormData directamente
        })
        .then((response) => {
            if (!response.ok) {
                
                return response.json().then(errData => {
                    
                    const error = new Error(errData.message || `Error HTTP: ${response.status}`);
                    error.data = errData; 
                    error.status = response.status;
                    throw error; 
                }).catch(() => {
                    
                    throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`);
                });
            }
            return response.json(); 
        })
        .then((result) => {
            
            if (result.status) {
                Swal.fire("¡Éxito!", result.message, "success");
                cerrarModalProveedor();
                if (tablaProveedores) {
                    tablaProveedores.ajax.reload();
                }
            } else {
                
                Swal.fire("Error", result.message || "Respuesta no exitosa del servidor.", "error");
            }
        })
        .catch((error) => {
            console.error("Error en fetch:", error);
            let errorMessage = "Ocurrió un error al procesar la solicitud.";
            
            if (error.data && error.data.message) {
                errorMessage = error.data.message;
            } else if (error.message) { 
                errorMessage = error.message;
            }
            Swal.fire("Error", errorMessage, "error");
        });
    });

    // Abrir modal para EDICIÓN
    function abrirModalProveedorParaEdicion(idProveedor) {
        fetch(`proveedores/getProveedorById/${idProveedor}`) 
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status} al obtener proveedor.`);
                }
                return response.json();
            })
            .then((res) => {
                if (res.status && res.data) {
                    const proveedor = res.data;
                    modalTitulo.textContent = "Actualizar Proveedor";
                    btnSubmitProveedor.textContent = "Actualizar";
                    formProveedor.setAttribute("data-action", "proveedores/updateProveedor");

                    inputIdPersona.value = proveedor.idproveedor || "";
                    document.getElementById("nombre").value = proveedor.nombre || "";
                    document.getElementById("apellido").value = proveedor.apellido || "";
                    document.getElementById("identificacion").value = proveedor.identificacion || "";
                    document.getElementById("telefono_principal").value = proveedor.telefono_principal || "";
                    document.getElementById("correo_electronico").value = proveedor.correo_electronico || "";
                    document.getElementById("direccion").value = proveedor.direccion || "";
                    document.getElementById("fecha_nacimiento").value = proveedor.fecha_nacimiento || "";
                    document.getElementById("genero").value = proveedor.genero || "";
                    document.getElementById("estatus").value = proveedor.estatus || "ACTIVO";
                    document.getElementById("observaciones").value = proveedor.observaciones || "";
                    
                    modalProveedor.classList.remove("opacity-0", "pointer-events-none");
                } else {
                    Swal.fire("Error", res.message || "No se pudieron cargar los datos del proveedor.", "error");
                }
            })
            .catch((error) => {
                console.error("Error al cargar proveedor para editar:", error);
                Swal.fire("Error", "Ocurrió un error al cargar los datos del proveedor.", "error");
            });
    }

    // Event listener para botones de Editar
    document.getElementById("TablaProveedores").addEventListener("click", function (e) {
        const target = e.target;
        if (target.closest(".editar-proveedor-btn")) {
            const idProveedor = target.closest(".editar-proveedor-btn").getAttribute("data-idproveedor");
            if (idProveedor) {
                abrirModalProveedorParaEdicion(idProveedor);
            }
        } else if (target.closest(".eliminar-proveedor-btn")) {
            const idProveedor = target.closest(".eliminar-proveedor-btn").getAttribute("data-idproveedor");
            if (idProveedor) {
                confirmarEliminacionProveedor(idProveedor);
            }
        }
    });

    // --- Lógica para Modal de Eliminación ---
    const modalConfirmar = document.getElementById("modalConfirmarEliminar");
    const btnConfirmarEliminar = document.getElementById("btnConfirmarEliminacion");
    let idProveedorAEliminar = null;

    window.cerrarModalConfirmarEliminar = function() {
        modalConfirmar.classList.add("opacity-0", "pointer-events-none");
        idProveedorAEliminar = null;
    }

    function confirmarEliminacionProveedor(id) {
        idProveedorAEliminar = id;
        modalConfirmar.classList.remove("opacity-0", "pointer-events-none");
    }

    btnConfirmarEliminar.addEventListener("click", function() {
        if (idProveedorAEliminar) {
            fetch(`proveedores/deleteProveedor`, { 
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ idpersona: idProveedorAEliminar }), 
            })
            .then((response) => response.json())
            .then((result) => {
                cerrarModalConfirmarEliminar();
                if (result.status) {
                    Swal.fire("Eliminado!", result.message, "success");
                    tablaProveedores.ajax.reload();
                } else {
                    Swal.fire("Error", result.message || "No se pudo eliminar el proveedor.", "error");
                }
            })
            .catch((error) => {
                cerrarModalConfirmarEliminar();
                console.error("Error al eliminar:", error);
                Swal.fire("Error", "Ocurrió un error al eliminar el proveedor.", "error");
            });
        }
    });

     if (document.getElementById("deletionCloseBtn")) { 
        document.getElementById("deletionCloseBtn").addEventListener('click', cerrarModalConfirmarEliminar);
    }
    modalConfirmar.addEventListener('click', function(event) {
        if (event.target === modalConfirmar) { 
            cerrarModalConfirmarEliminar();
        }
    });


});
