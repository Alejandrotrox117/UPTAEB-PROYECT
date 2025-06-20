document.addEventListener('DOMContentLoaded', function () {
    
    const tbodyTasasUsd = document.getElementById('tbodyTasasUsd');
    const tbodyTasasEur = document.getElementById('tbodyTasasEur');
    const mensajeNoDatosUsd = document.getElementById('mensajeNoDatosUsd');
    const mensajeNoDatosEur = document.getElementById('mensajeNoDatosEur');
    const contenedorMensajesFlash = document.getElementById('contenedorMensajesFlash');
    const formActualizarUSD = document.getElementById('formActualizarUSD');
    const formActualizarEUR = document.getElementById('formActualizarEUR');
    const tabsContainer = document.getElementById('tabsHistorial');
    const tabButtons = document.querySelectorAll('.tab-button');
    const historialUSDDiv = document.getElementById('historialUSD');
    const historialEURDiv = document.getElementById('historialEUR');

    const URL_OBTENER_DATOS = "tasas/getTasas";
    const URL_ACTUALIZAR_TASAS_BCV = "tasas/actualizarTasasBCV";

    let dataTasasUsd = [];
    let dataTasasEur = [];
    let dataTableUsd = null;
    let dataTableEur = null;
    let monedaActiva = 'USD'; 

    
    function mostrarAlertaSweet(tipo, titulo, textoHtml = '') {
        let icono;
        switch (tipo) {
            case 'exito':
                icono = 'success';
                break;
            case 'error':
                icono = 'error';
                break;
            case 'advertencia':
                icono = 'warning';
                break;
            case 'info':
                icono = 'info';
                break;
            default:
                icono = 'question';
        }
        Swal.fire({
            icon: icono,
            title: titulo,
            html: textoHtml,
            confirmButtonText: 'Entendido',
        });
    }

    function formatearFecha(fechaISO) {
        if (!fechaISO) return '';
        const fecha = new Date(fechaISO);
        const dia = String(fecha.getDate()).padStart(2, '0');
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');
        const ano = fecha.getFullYear();
        return `${dia}/${mes}/${ano}`;
    }

    function formatearFechaHora(fechaISO) {
        if (!fechaISO) return '';
        const fecha = new Date(fechaISO);
        const horas = String(fecha.getHours()).padStart(2, '0');
        const minutos = String(fecha.getMinutes()).padStart(2, '0');
        const segundos = String(fecha.getSeconds()).padStart(2, '0');
        return `${formatearFecha(fechaISO)} ${horas}:${minutos}:${segundos}`;
    }

    function renderizarTabla(tbodyElement, tasas, mensajeNoDatosElement) {
        tbodyElement.innerHTML = '';
        if (tasas && tasas.length > 0) {
            mensajeNoDatosElement.classList.add('hidden');
            tasas.forEach((tasa) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${tasa.codigo_moneda}</td>
                    <td>${parseFloat(tasa.tasa_a_ves).toLocaleString('es-VE', { minimumFractionDigits: 4, maximumFractionDigits: 4 })}</td>
                    <td>${formatearFecha(tasa.fecha_publicacion_bcv)}</td>
                    <td>${formatearFechaHora(tasa.fecha_captura)}</td>
                `;
                tbodyElement.appendChild(tr);
            });
        } else {
            mensajeNoDatosElement.classList.remove('hidden');
        }
    }

    
    function limpiarDataTable(tablaId) {
        
        try {
            const tabla = $('#' + tablaId);
            if ($.fn.DataTable.isDataTable(tabla)) {
                tabla.DataTable().destroy();
                console.log(`DataTable ${tablaId} destruido correctamente`);
            }
        } catch (error) {
            console.error(`Error al destruir DataTable ${tablaId}:`, error);
        }
    }

    function inicializarDataTable(tablaId, instancia) {
        
        limpiarDataTable(tablaId);

        console.log(`Inicializando DataTable para ${tablaId}`);
        return $('#' + tablaId).DataTable({
            destroy: true,
            pageLength: 10,
            language: {
                decimal: "",
                emptyTable: "No hay información",
                info: "Mostrando _START_ a _END_ de _TOTAL_ Entradas",
                infoEmpty: "Mostrando 0 a 0 de 0 Entradas",
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
                aria: {
                    sortAscending: ": activar para ordenar la columna ascendente",
                    sortDescending: ": activar para ordenar la columna descendente"
                }
            },
            order: [[0, "asc"]],
            columns: [
                { title: "Código" },
                { title: "Tasa a VES" },
                { title: "Fecha Publicación BCV" },
                { title: "Fecha Captura" }
            ]
        });
    }

    
    async function cargarDatosTasas() {
        try {
            
            limpiarDataTable('tablaTasasUsd');
            limpiarDataTable('tablaTasasEur');

            const response = await fetch(URL_OBTENER_DATOS);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const data = await response.json();

            if (data.mensajeFlash && data.mensajeFlash.texto) {
                mostrarAlertaSweet(
                    data.mensajeFlash.tipo,
                    data.mensajeFlash.tipo.charAt(0).toUpperCase() + data.mensajeFlash.tipo.slice(1) + "!",
                    data.mensajeFlash.texto
                );
            }

            dataTasasUsd = data.tasasUsd || [];
            dataTasasEur = data.tasasEur || [];

            
            renderizarTabla(tbodyTasasUsd, dataTasasUsd, mensajeNoDatosUsd);
            renderizarTabla(tbodyTasasEur, dataTasasEur, mensajeNoDatosEur);

            
            await new Promise(resolve => setTimeout(resolve, 100));

            
            dataTableUsd = inicializarDataTable('tablaTasasUsd', dataTableUsd);
            dataTableEur = inicializarDataTable('tablaTasasEur', dataTableEur);

            console.log('DataTables reinicializados con éxito');
        } catch (error) {
            console.error('Error al cargar datos de tasas:', error);
            mostrarAlertaSweet('error', 'Error de Carga', 'No se pudieron cargar los datos de las tasas. Intente más tarde o revise la consola.');
        }
    }

    
    async function manejarActualizacionGeneralBCV(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const submitButton = form.querySelector('button[type="submit"], #moneda');
        if (!submitButton) {
            console.error("No se encontró el botón de submit en el formulario:", form);
            mostrarAlertaSweet('error', 'Error Interno', 'No se pudo encontrar el botón de acción.');
            return;
        }

        const originalButtonText = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.textContent = 'Actualizando...';

        try {
            const response = await fetch(URL_ACTUALIZAR_TASAS_BCV, { method: 'POST' });
            if (!response.ok) {
                let errorData = { tipo: 'error', texto: `Error del servidor: ${response.status}` };
                try { errorData = await response.json(); } catch (e) { }
                throw errorData;
            }
            
            const resultado = await response.json();
            mostrarAlertaSweet(
                resultado.tipo,
                resultado.tipo.charAt(0).toUpperCase() + resultado.tipo.slice(1) + "!",
                resultado.texto
            );
            
            if (resultado.tipo === 'exito' || resultado.tipo === 'advertencia') {
                
                console.log("Actualizando datos después de operación exitosa...");
                await cargarDatosTasas();
                console.log("Datos actualizados con éxito.");
            }
        } catch (error) {
            console.error('Error al actualizar tasas BCV:', error);
            if (error && error.tipo && error.texto) {
                mostrarAlertaSweet(
                    error.tipo,
                    error.tipo.charAt(0).toUpperCase() + error.tipo.slice(1) + "!",
                    error.texto
                );
            } else {
                mostrarAlertaSweet('error', 'Error de Actualización', 'Ocurrió un error inesperado al intentar actualizar las tasas.');
            }
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = originalButtonText;
        }
    }

    function actualizarEstilosPestana(pestanaActiva) {
        tabButtons.forEach(button => {
            const esActiva = button === pestanaActiva;
            button.setAttribute('aria-selected', esActiva.toString());

            
            const clasesActivo = ['text-yellow-600', 'shadow', 'bg-white', 'dark:text-white', 'dark:bg-yellow-600'];
            
            const clasesInactivoHoverFocus = [
                'hover:text-gray-800',
                'dark:text-gray-400',
                'dark:hover:text-gray-300',
            ];
            button.classList.remove(...clasesActivo, ...clasesInactivoHoverFocus, 'text-gray-600');

            if (esActiva) {
                button.classList.add(...clasesActivo);
            } else {
                button.classList.add('text-gray-600', ...clasesInactivoHoverFocus);
            }
        });
    }

    function mostrarContenidoPestana(monedaSeleccionada) {
        if (historialUSDDiv) historialUSDDiv.classList.add('hidden');
        if (historialEURDiv) historialEURDiv.classList.add('hidden');

        if (monedaSeleccionada === 'USD' && historialUSDDiv) {
            historialUSDDiv.classList.remove('hidden');
        } else if (monedaSeleccionada === 'EUR' && historialEURDiv) {
            historialEURDiv.classList.remove('hidden');
        }
    }

    function actualizarVistaPestana(moneda) {
        monedaActiva = moneda;
        mostrarContenidoPestana(moneda);
        const pestanaActiva = Array.from(tabButtons).find(btn => btn.dataset.moneda === moneda);
        if (pestanaActiva) actualizarEstilosPestana(pestanaActiva);
    }

    
    if (tabsContainer) {
        tabsContainer.addEventListener('click', function (event) {
            const botonPestanaClickeado = event.target.closest('.tab-button');
            if (!botonPestanaClickeado) return;
            const moneda = botonPestanaClickeado.dataset.moneda;
            actualizarVistaPestana(moneda);
        });
    }

    
    if (formActualizarUSD) {
        formActualizarUSD.addEventListener('submit', manejarActualizacionGeneralBCV);
        
    }
    if (formActualizarEUR) {
        formActualizarEUR.addEventListener('submit', manejarActualizacionGeneralBCV);
        
    }

    
    cargarDatosTasas();
});
