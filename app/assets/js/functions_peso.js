document.addEventListener("DOMContentLoaded", () => {
  const pesoValorDisplay = document.getElementById("pesoValorDisplay");
  const pesoFechaDisplay = document.getElementById("pesoFechaDisplay");
  const pesoFechaCreacionDisplay = document.getElementById(
    "pesoFechaCreacionDisplay"
  );
  const pesoUltimaActualizacion = document.getElementById(
    "pesoUltimaActualizacion"
  );
  const pesoUltimaSincronizacion = document.getElementById(
    "pesoUltimaSincronizacion"
  );
  const pesoDetalleValor = document.getElementById("peso-detalle-valor");
  const estadoBadge = document.getElementById("estado-badge");

  function actualizarUltimaActualizacion() {
    if (!pesoUltimaActualizacion) return;
    const ahora = new Date();
    pesoUltimaActualizacion.textContent = ahora.toLocaleTimeString("es-VE", {
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit",
    });
  }

  function obtenerMensajeGauge(progreso) {
    if (!Number.isFinite(progreso) || progreso <= 0) {
      return "Aún no hay lecturas activas.";
    }

    if (progreso >= 70) {
      return "Carga elevada. Verificar estado de la romana.";
    }

    if (progreso >= 35) {
      return "Carga en rango operativo normal.";
    }

    return "Carga ligera registrada. Listo para siguiente lectura.";
  }

  function aplicarDatosPeso(data) {
    if (!data) {
      return;
    }

    const fechaPrincipal = data.fecha || data.fecha_hora || null;
    const fechaSistema = data.fecha_creacion
      ? data.fecha_creacion
      : fechaPrincipal;
    const pesoNumero = data.peso !== undefined ? parseFloat(data.peso) : NaN;
    const hayPeso = Number.isFinite(pesoNumero);

    if (pesoDataset) {
      pesoDataset.dataset.status = hayPeso ? "1" : "0";
      pesoDataset.dataset.peso = data.peso ?? "";
      pesoDataset.dataset.fecha = fechaPrincipal ?? "";
      pesoDataset.dataset.fechaCreacion = fechaSistema ?? "";
      pesoDataset.dataset.estatus = data.estatus ?? "";
      pesoDataset.dataset.id = data.idromana ?? "";
    }

    if (pesoValorDisplay) {
        const partes = formatearPeso(data.peso).split(',');
        pesoValorDisplay.textContent = hayPeso ? partes[0] : '0';
        const kgSpan = pesoValorDisplay.nextElementSibling;
        if (kgSpan && kgSpan.tagName === 'SPAN') {
            kgSpan.textContent = hayPeso ? `,${partes[1] || '0'}kg` : ',0kg';
        }
    }
    
    if(pesoDetalleValor) {
        pesoDetalleValor.textContent = hayPeso ? formatearPeso(data.peso) : '--.--';
    }

    if (pesoGaugeMensaje) {
        const gaugeMax = gaugeMaxConfigurado > 0 ? gaugeMaxConfigurado : 1000;
        const progreso = hayPeso ? Math.max(0, Math.min(100, (pesoNumero / gaugeMax) * 100)) : 0;
        pesoGaugeMensaje.textContent = obtenerMensajeGauge(progreso);
    }

    if (pesoFechaDisplay) {
      const fechaFormateadaPrincipal =
        data.fecha_formateada || formatearFecha(fechaPrincipal);
      pesoFechaDisplay.textContent = fechaFormateadaPrincipal;
    }

    if (pesoFechaCreacionDisplay) {
      const fechaFormateadaSistema =
        data.fecha_creacion_formateada || formatearFecha(fechaSistema);
      pesoFechaCreacionDisplay.textContent = fechaFormateadaSistema;
    }

    if (pesoUltimaSincronizacion) {
      const fechaSincronizacion =
        data.fecha_formateada || formatearFecha(fechaPrincipal);
      pesoUltimaSincronizacion.textContent = fechaSincronizacion;
    }

    if (hayPeso) {
      if (pesoLecturaActual !== null && !Number.isNaN(pesoLecturaActual)) {
        pesoLecturaAnterior = pesoLecturaActual;
      }
      pesoLecturaActual = pesoNumero;
      actualizarVariacion(pesoLecturaAnterior, pesoLecturaActual);
    } else {
      pesoLecturaAnterior = null;
      pesoLecturaActual = null;
      actualizarVariacion(null, null);
    }

    const estadoNormalizado = (data.estatus ?? "desconocido").toLowerCase();
    if (estadoTexto) {
      estadoTexto.textContent = (data.estatus ?? "desconocido");
    }

    if (estadoIndicador) {
      estadoIndicador.className = `h-2.5 w-2.5 rounded-full ${
        estadoNormalizado === "activo"
          ? "bg-green-500"
          : estadoNormalizado === "inactivo"
          ? "bg-amber-500"
          : "bg-slate-400"
      }`;
    }
    
    if (estadoBadge) {
        const badgeClasses = {
            activo: 'bg-green-100 text-green-700',
            inactivo: 'bg-amber-100 text-amber-700',
            desconocido: 'bg-slate-100 text-slate-700'
        };
        const classes = badgeClasses[estadoNormalizado] || badgeClasses['desconocido'];
        estadoBadge.className = `px-2 py-0.5 text-xs font-medium rounded-full ${classes}`;
        estadoBadge.textContent = data.estatus ? data.estatus.charAt(0).toUpperCase() + data.estatus.slice(1) : "Desconocido";
    }


    if (registroIdTexto) {
      registroIdTexto.textContent = `${data.idromana ?? "—"}`;
    }
  }

  function setTrendIcon(diferencia) {
    const trendIcon = document.getElementById("trendIcon");
    const esNulo = diferencia === null;
    const esPositivo = diferencia > 0;
    const esNegativo = diferencia < 0;
    const esEstable = diferencia === 0;

    if (trendIcon) {
      let iconClass = "";
      let colorClass = "";
      let transform = "";

      if (esNulo) {
        iconClass = "fa-minus";
        colorClass = "text-slate-500";
      } else if (esEstable) {
        iconClass = "fa-minus";
        colorClass = "text-blue-500";
      } else if (esPositivo) {
        iconClass = "fa-arrow-up";
        colorClass = "text-green-500";
      } else {
        iconClass = "fa-arrow-down";
        colorClass = "text-red-500";
      }

      trendIcon.className = `fas ${iconClass} ${colorClass} transition-transform duration-300`;
      trendIcon.style.transform = transform;
    }
  }

  function actualizarVariacion(anterior, actual) {
    const variacionTexto = document.getElementById("pesoVariacionTexto");
    const esValido = anterior !== null && actual !== null;
    const diferencia = esValido ? actual - anterior : null;

    if (variacionTexto) {
      if (diferencia !== null) {
        const signo = diferencia > 0 ? "+" : "";
        variacionTexto.textContent = `${signo}${formatearPeso(diferencia)}`;
      } else {
        variacionTexto.textContent = "---";
      }
    }
    setTrendIcon(diferencia);
  }

  async function fetchUltimoPeso() {
    try {
      const response = await fetch("Peso/getUltimoPeso", {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const resultado = await response.json();
      if (!resultado.status || !resultado.data) {
        return;
      }

      const datosRespuesta = resultado.data;
      const datos = {
        peso: datosRespuesta.peso ?? null,
        fecha: datosRespuesta.fecha ?? null,
        fecha_creacion: datosRespuesta.fecha_creacion ?? null,
        fecha_formateada:
          datosRespuesta.fecha_formateada ||
          formatearFecha(datosRespuesta.fecha ?? null),
        fecha_creacion_formateada:
          datosRespuesta.fecha_creacion_formateada ||
          formatearFecha(datosRespuesta.fecha_creacion ?? null),
        estatus: datosRespuesta.estatus ?? "desconocido",
        idromana: datosRespuesta.idromana ?? null,
      };

      aplicarDatosPeso(datos);
    } catch (error) {
      console.error("Error al obtener el peso:", error);
      if (pesoDataset) {
        pesoDataset.dataset.status = "0";
      }
    } finally {
      actualizarUltimaActualizacion();
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    fetchUltimoPeso();
    setInterval(fetchUltimoPeso, 10000);
    setInterval(actualizarUltimaActualizacion, 1000);
  });
})();
