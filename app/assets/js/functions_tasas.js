document.addEventListener('DOMContentLoaded', function () {
  // ... (selectores existentes) ...
  const tbodyTasasUsd = document.getElementById('tbodyTasasUsd');
  const tbodyTasasEur = document.getElementById('tbodyTasasEur');
  const mensajeNoDatosUsd = document.getElementById('mensajeNoDatosUsd');
  const mensajeNoDatosEur = document.getElementById('mensajeNoDatosEur');
  const contenedorMensajesFlash = document.getElementById(
    'contenedorMensajesFlash',
  );
  const formActualizarUSD = document.getElementById('formActualizarUSD');
  const formActualizarEUR = document.getElementById('formActualizarEUR');

  // Nuevos selectores para pestañas y contenidos
  const tabsContainer = document.getElementById('tabsHistorial');
  const tabButtons = document.querySelectorAll('.tab-button');
  const historialContenidos = document.querySelectorAll('.historial-contenido');
  const historialUSDDiv = document.getElementById('historialUSD');
  const historialEURDiv = document.getElementById('historialEUR');

  // URLs (asegúrate que BASE_URL esté definida)
  const BASE_URL = window.BASE_URL || ''; // Obtener BASE_URL global o usar vacío
  const URL_OBTENER_DATOS = `${BASE_URL}/tasas/obtenerDatosTasasJson`;
  const URL_ACTUALIZAR_TASA = `${BASE_URL}/tasas/actualizar`;

  // ... (funciones mostrarMensajeFlash, formatearFecha, formatearFechaHora, renderizarTabla existentes) ...
  function mostrarMensajeFlash(tipo, texto) {
    contenedorMensajesFlash.innerHTML = '';
    if (!texto) return;
    let clasesBg = '',
      clasesText = '',
      clasesBorder = '';
    switch (tipo) {
      case 'exito':
        (clasesBg = 'bg-green-100'),
          (clasesText = 'text-green-700'),
          (clasesBorder = 'border-green-400');
        break;
      case 'error':
        (clasesBg = 'bg-red-100'),
          (clasesText = 'text-red-700'),
          (clasesBorder = 'border-red-400');
        break;
      case 'advertencia':
        (clasesBg = 'bg-yellow-100'),
          (clasesText = 'text-yellow-700'),
          (clasesBorder = 'border-yellow-400');
        break;
      default:
        (clasesBg = 'bg-blue-100'),
          (clasesText = 'text-blue-700'),
          (clasesBorder = 'border-blue-400');
    }
    const divMensaje = document.createElement('div');
    (divMensaje.className = `mb-6 p-4 rounded-md text-sm ${clasesBg} border ${clasesBorder} ${clasesText}`),
      divMensaje.setAttribute('role', 'alert'),
      (divMensaje.innerHTML = `
            <p class="font-bold">${tipo.charAt(0).toUpperCase() + tipo.slice(1)}!</p>
            <p>${texto}</p>
        `),
      contenedorMensajesFlash.appendChild(divMensaje);
  }
  function formatearFecha(fechaISO) {
    if (!fechaISO) return '';
    const fecha = new Date(fechaISO),
      dia = String(fecha.getDate()).padStart(2, '0'),
      mes = String(fecha.getMonth() + 1).padStart(2, '0'),
      ano = fecha.getFullYear();
    return `${dia}/${mes}/${ano}`;
  }
  function formatearFechaHora(fechaISO) {
    if (!fechaISO) return '';
    const fecha = new Date(fechaISO),
      horas = String(fecha.getHours()).padStart(2, '0'),
      minutos = String(fecha.getMinutes()).padStart(2, '0'),
      segundos = String(fecha.getSeconds()).padStart(2, '0');
    return `${formatearFecha(fechaISO)} ${horas}:${minutos}:${segundos}`;
  }
  function renderizarTabla(tbodyElement, tasas, mensajeNoDatosElement) {
    tbodyElement.innerHTML = '';
    if (tasas && tasas.length > 0) {
      mensajeNoDatosElement.classList.add('hidden'),
        tasas.forEach((tasa) => {
          const tr = document.createElement('tr');
          (tr.className = 'border-b border-gray-200 hover:bg-gray-50'),
            (tr.innerHTML = `
                    <td class="py-3 px-4">${tasa.codigo_moneda}</td>
                    <td class="py-3 px-4">${parseFloat(tasa.tasa_a_ves).toLocaleString('es-VE', { minimumFractionDigits: 4, maximumFractionDigits: 4 })}</td>
                    <td class="py-3 px-4">${formatearFecha(tasa.fecha_publicacion_bcv)}</td>
                    <td class="py-3 px-4">${formatearFechaHora(tasa.fecha_captura)}</td>
                `),
            tbodyElement.appendChild(tr);
        });
    } else mensajeNoDatosElement.classList.remove('hidden');
  }

  async function cargarDatosTasas() {
    try {
      const response = await fetch(URL_OBTENER_DATOS);
      if (!response.ok) {
        throw new Error(
          `Error HTTP ${response.status}: ${response.statusText}`,
        );
      }
      const data = await response.json();

      if (data.mensajeFlash) {
        mostrarMensajeFlash(data.mensajeFlash.tipo, data.mensajeFlash.texto);
      }

      // Renderizar ambas tablas, la visibilidad se controla por las pestañas
      renderizarTabla(tbodyTasasUsd, data.tasasUsd, mensajeNoDatosUsd);
      renderizarTabla(tbodyTasasEur, data.tasasEur, mensajeNoDatosEur);

      // Asegurar que la pestaña activa inicial coincida con el contenido visible
      actualizarVistaPestana();
    } catch (error) {
      console.error('Error al cargar datos de tasas:', error);
      mostrarMensajeFlash(
        'error',
        'No se pudieron cargar los datos de las tasas. Intente más tarde.',
      );
    }
  }

  async function manejarActualizacion(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.textContent;
    (submitButton.disabled = !0),
      (submitButton.textContent = 'Actualizando...');
    try {
      const response = await fetch(URL_ACTUALIZAR_TASA, {
        method: 'POST',
        body: formData,
      });
      if (!response.ok) {
        let errorData = {
          tipo: 'error',
          texto: `Error del servidor: ${response.status}`,
        };
        try {
          errorData = await response.json();
        } catch (e) {}
        throw errorData;
      }
      const resultado = await response.json();
      if (
        (mostrarMensajeFlash(resultado.tipo, resultado.texto),
        'exito' === resultado.tipo || 'advertencia' === resultado.tipo)
      )
        cargarDatosTasas(); // Recargar ambas tablas
    } catch (error) {
      console.error('Error al actualizar tasa:', error),
        error && error.tipo && error.texto
          ? mostrarMensajeFlash(error.tipo, error.texto)
          : mostrarMensajeFlash(
              'error',
              'Ocurrió un error al intentar actualizar la tasa. Verifique la consola.',
            );
    } finally {
      (submitButton.disabled = !1),
        (submitButton.textContent = originalButtonText);
    }
  }

  // --- Lógica para Pestañas ---
  function actualizarEstilosPestana(pestanaActiva) {
    tabButtons.forEach((button) => {
      if (button === pestanaActiva) {
        button.classList.add(
          'text-yellow-600',
          'shadow',
          'bg-white',
          'dark:text-white',
          'dark:bg-yellow-600',
        );
        button.classList.remove(
          'hover:text-gray-800',
          'focus:text-yellow-600',
          'dark:text-gray-400',
          'dark:hover:text-gray-300',
          'dark:focus:text-gray-400',
        );
        button.setAttribute('aria-selected', 'true');
      } else {
        button.classList.remove(
          'text-yellow-600',
          'shadow',
          'bg-white',
          'dark:text-white',
          'dark:bg-yellow-600',
        );
        button.classList.add(
          'hover:text-gray-800',
          'focus:text-yellow-600',
          'dark:text-gray-400',
          'dark:hover:text-gray-300',
          'dark:focus:text-gray-400',
        );
        button.setAttribute('aria-selected', 'false');
      }
    });
  }

  function mostrarContenidoPestana(moneda) {
    historialContenidos.forEach((contenido) => {
      contenido.classList.add('hidden');
    });

    if (moneda === 'USD' && historialUSDDiv) {
      historialUSDDiv.classList.remove('hidden');
    } else if (moneda === 'EUR' && historialEURDiv) {
      historialEURDiv.classList.remove('hidden');
    }
  }

  function actualizarVistaPestana() {
    const pestanaActiva = tabsContainer.querySelector(
      '.tab-button[aria-selected="true"]',
    );
    if (pestanaActiva) {
      const monedaSeleccionada = pestanaActiva.dataset.moneda;
      actualizarEstilosPestana(pestanaActiva);
      mostrarContenidoPestana(monedaSeleccionada);
    }
  }

  if (tabsContainer) {
    tabsContainer.addEventListener('click', function (event) {
      const targetButton = event.target.closest('.tab-button');
      if (targetButton) {
        const monedaSeleccionada = targetButton.dataset.moneda;
        actualizarEstilosPestana(targetButton);
        mostrarContenidoPestana(monedaSeleccionada);
      }
    });
  }
  // --- Fin Lógica para Pestañas ---

  // Cargar datos iniciales
  cargarDatosTasas(); // Esto ya llama a actualizarVistaPestana al final

  // Event Listeners para los formularios de actualización
  if (formActualizarUSD) {
    formActualizarUSD.addEventListener('submit', manejarActualizacion);
  }
  if (formActualizarEUR) {
    formActualizarEUR.addEventListener('submit', manejarActualizacion);
  }
});
