document.addEventListener('DOMContentLoaded', function () {
    // Selectores existentes
    const tbodyTasasUsd = document.getElementById('tbodyTasasUsd');
    const tbodyTasasEur = document.getElementById('tbodyTasasEur');
    const mensajeNoDatosUsd = document.getElementById('mensajeNoDatosUsd');
    const mensajeNoDatosEur = document.getElementById('mensajeNoDatosEur');
    const contenedorMensajesFlash = document.getElementById('contenedorMensajesFlash');
    const formActualizarUSD = document.getElementById('formActualizarUSD');
    const formActualizarEUR = document.getElementById('formActualizarEUR');

    // Selectores para las pestañas y sus contenidos
    const tabsContainer = document.getElementById('tabsHistorial'); // El <nav> que contiene los botones de pestañas
    const tabButtons = document.querySelectorAll('.tab-button');    // Todos los botones con la clase 'tab-button'
    const historialUSDDiv = document.getElementById('historialUSD'); // El div que contiene la tabla del dólar
    const historialEURDiv = document.getElementById('historialEUR'); // El div que contiene la tabla del euro

    const URL_OBTENER_DATOS = "tasas/getTasas";
    const URL_ACTUALIZAR_TASAS_BCV = "tasas/actualizarTasasBCV";
    console.log('URL_ACTUALIZAR_TASAS_BCV:', URL_ACTUALIZAR_TASAS_BCV);

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
            html: textoHtml, // Usamos html para permitir <br> u otro formato
            confirmButtonText: 'Entendido',
            // timer: tipo === 'exito' ? 2500 : undefined, // Opcional: cerrar automáticamente en éxito
            // timerProgressBar: tipo === 'exito',
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
                tr.className = 'border-b border-gray-200 hover:bg-gray-50';
                tr.innerHTML = `
                    <td class="py-3 px-4">${tasa.codigo_moneda}</td>
                    <td class="py-3 px-4">${parseFloat(tasa.tasa_a_ves).toLocaleString('es-VE', { minimumFractionDigits: 4, maximumFractionDigits: 4 })}</td>
                    <td class="py-3 px-4">${formatearFecha(tasa.fecha_publicacion_bcv)}</td>
                    <td class="py-3 px-4">${formatearFechaHora(tasa.fecha_captura)}</td>
                `;
                tbodyElement.appendChild(tr);
            });
        } else {
            mensajeNoDatosElement.classList.remove('hidden');
        }
    }

    async function cargarDatosTasas() {
        try {
            const response = await fetch(URL_OBTENER_DATOS);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const data = await response.json();

            if (data.mensajeFlash && data.mensajeFlash.texto) {
                 mostrarAlertaSweet(data.mensajeFlash.tipo, 
                                   data.mensajeFlash.tipo.charAt(0).toUpperCase() + data.mensajeFlash.tipo.slice(1) + "!", 
                                   data.mensajeFlash.texto);
            }
            renderizarTabla(tbodyTasasUsd, data.tasasUsd, mensajeNoDatosUsd);
            renderizarTabla(tbodyTasasEur, data.tasasEur, mensajeNoDatosEur);
            
            // Después de cargar los datos, asegurar que la pestaña correcta esté activa y visible
            actualizarVistaPestana(); 
        } catch (error) {
            console.error('Error al cargar datos de tasas:', error);
            mostrarAlertaSweet('error', 'Error de Carga', 'No se pudieron cargar los datos de las tasas. Intente más tarde o revise la consola.');
        }
    }

    async function manejarActualizacionGeneralBCV(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const submitButton = form.querySelector('#moneda');
        const originalButtonText = submitButton.textContent;

        submitButton.disabled = true;
        submitButton.textContent = 'Actualizando...';

        if (!submitButton) {
            console.error("No se encontró el botón de submit en el formulario:", form);
            mostrarAlertaSweet('error', 'Error Interno', 'No se pudo encontrar el botón de acción.');
            return;
        }

        try {
            const response = await fetch(URL_ACTUALIZAR_TASAS_BCV, { method: 'POST' });
            if (!response.ok) {
                let errorData = { tipo: 'error', texto: `Error del servidor: ${response.status}` };
                try { errorData = await response.json(); } catch (e) { /* no es json */ }
                throw errorData;
            }
            const resultado = await response.json();
            mostrarAlertaSweet(resultado.tipo, 
                               resultado.tipo.charAt(0).toUpperCase() + resultado.tipo.slice(1) + "!", 
                               resultado.texto);
            if (resultado.tipo === 'exito' || resultado.tipo === 'advertencia') {
                cargarDatosTasas();
            }
        } catch (error) {
            console.error('Error al actualizar tasas BCV:', error);
            if (error && error.tipo && error.texto) {
                mostrarAlertaSweet(error.tipo, 
                                   error.tipo.charAt(0).toUpperCase() + error.tipo.slice(1) + "!", 
                                   error.texto);
            } else {
                mostrarAlertaSweet('error', 'Error de Actualización', 'Ocurrió un error inesperado al intentar actualizar las tasas.');
            }
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = originalButtonText;
        }
    }

    // --- LÓGICA PARA LAS PESTAÑAS (SWITCH) ---
    function actualizarEstilosPestana(pestanaActiva) {
        tabButtons.forEach(button => {
            const esActiva = button === pestanaActiva;
            button.setAttribute('aria-selected', esActiva.toString());

            // Clases para estado activo (basadas en tu HTML para el botón Dólar por defecto)
            const clasesActivo = ['text-yellow-600', 'shadow', 'bg-white', 'dark:text-white', 'dark:bg-yellow-600'];
            // Clases para estado inactivo (basadas en tu HTML para el botón Euro por defecto)
            const clasesInactivoHoverFocus = [
                'hover:text-gray-800', 
                // 'focus:text-yellow-600', // El focus:ring-yellow-500 ya maneja el foco visual
                'dark:text-gray-400', 
                'dark:hover:text-gray-300', 
                // 'dark:focus:text-gray-400'
            ];
             // Quitar todas las clases de estilo relevantes primero
            button.classList.remove(...clasesActivo, ...clasesInactivoHoverFocus, 'text-gray-600');


            if (esActiva) {
                button.classList.add(...clasesActivo);
            } else {
                button.classList.add('text-gray-600', ...clasesInactivoHoverFocus); // Color base para inactivo
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

    // Función para establecer el estado inicial de las pestañas o actualizarlo
    function actualizarVistaPestana() {
        let pestanaActiva = tabsContainer ? tabsContainer.querySelector('.tab-button[aria-selected="true"]') : null;
        
        // Si no hay ninguna pestaña marcada como activa, seleccionar la primera por defecto
        if (!pestanaActiva && tabButtons.length > 0) {
            pestanaActiva = tabButtons[0]; // Tomar el primer botón de pestaña
            pestanaActiva.setAttribute('aria-selected', 'true'); // Marcarlo como seleccionado
        }

        if (pestanaActiva) {
            const monedaSeleccionada = pestanaActiva.dataset.moneda;
            actualizarEstilosPestana(pestanaActiva);
            mostrarContenidoPestana(monedaSeleccionada);
        }
    }

    // Event listener para el contenedor de las pestañas (delegación de eventos)
    if (tabsContainer) {
        tabsContainer.addEventListener('click', function(event) {
            const botonPestanaClickeado = event.target.closest('.tab-button');
            if (!botonPestanaClickeado) return; // Si el clic no fue en un botón de pestaña

            const moneda = botonPestanaClickeado.dataset.moneda;
            actualizarEstilosPestana(botonPestanaClickeado);
            mostrarContenidoPestana(moneda);
        });
    }
    // --- FIN LÓGICA PARA LAS PESTAÑAS ---

    // Cargar datos iniciales (esto ya llama a actualizarVistaPestana)
    cargarDatosTasas();

    // Event Listeners para los formularios de actualización
    if (formActualizarUSD) {
        formActualizarUSD.addEventListener('submit', manejarActualizacionGeneralBCV);
    }
    if (formActualizarEUR) {
        formActualizarEUR.addEventListener('submit', manejarActualizacionGeneralBCV);
    }
});
