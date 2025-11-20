// Función para actualizar resumen de nómina
function actualizarResumenNomina() {
  if (!tablaNomina) {
    console.warn('⚠️ Tabla de nómina no inicializada');
    return;
  }

  // Obtener todos los datos de la tabla
  const datos = tablaNomina.rows().data().toArray();
  
  const resumen = {
    total: datos.length,
    borrador: 0,
    enviado: 0,
    pagado: 0,
    cancelado: 0,
    totalSalarios: 0,
    salariosBorrador: 0,
    salariosEnviados: 0,
    salariosPagados: 0
  };

  datos.forEach(registro => {
    const estatus = registro.estatus || 'BORRADOR';
    const salario = parseFloat(registro.salario_total || 0);
    
    resumen.totalSalarios += salario;
    
    switch(estatus) {
      case 'BORRADOR':
        resumen.borrador++;
        resumen.salariosBorrador += salario;
        break;
      case 'ENVIADO':
        resumen.enviado++;
        resumen.salariosEnviados += salario;
        break;
      case 'PAGADO':
        resumen.pagado++;
        resumen.salariosPagados += salario;
        break;
      case 'CANCELADO':
        resumen.cancelado++;
        break;
    }
  });

  // Actualizar elementos del DOM
  const resumenHTML = `
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
      <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs text-gray-500 uppercase font-semibold">Borradores</p>
            <p class="text-2xl font-bold text-gray-700">${resumen.borrador}</p>
            <p class="text-xs text-gray-600 mt-1">$${resumen.salariosBorrador.toFixed(2)}</p>
          </div>
          <i class="fas fa-edit text-3xl text-gray-400"></i>
        </div>
      </div>
      
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs text-blue-600 uppercase font-semibold">Enviados</p>
            <p class="text-2xl font-bold text-blue-700">${resumen.enviado}</p>
            <p class="text-xs text-blue-600 mt-1">$${resumen.salariosEnviados.toFixed(2)}</p>
          </div>
          <i class="fas fa-paper-plane text-3xl text-blue-400"></i>
        </div>
      </div>
      
      <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs text-green-600 uppercase font-semibold">Pagados</p>
            <p class="text-2xl font-bold text-green-700">${resumen.pagado}</p>
            <p class="text-xs text-green-600 mt-1">$${resumen.salariosPagados.toFixed(2)}</p>
          </div>
          <i class="fas fa-check-circle text-3xl text-green-400"></i>
        </div>
      </div>
      
      <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-lg p-4 shadow-lg">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs uppercase font-semibold opacity-90">Total General</p>
            <p class="text-2xl font-bold">${resumen.total}</p>
            <p class="text-sm font-semibold mt-1">$${resumen.totalSalarios.toFixed(2)}</p>
          </div>
          <i class="fas fa-dollar-sign text-3xl opacity-75"></i>
        </div>
      </div>
    </div>
  `;

  // Insertar o actualizar el resumen
  let resumenContainer = document.getElementById('resumenNomina');
  if (!resumenContainer) {
    resumenContainer = document.createElement('div');
    resumenContainer.id = 'resumenNomina';
    const tabla = document.getElementById('TablaNomina');
    if (tabla && tabla.parentNode) {
      tabla.parentNode.insertBefore(resumenContainer, tabla);
    }
  }
  
  resumenContainer.innerHTML = resumenHTML;
  console.log(' Resumen de nómina actualizado:', resumen);
}
