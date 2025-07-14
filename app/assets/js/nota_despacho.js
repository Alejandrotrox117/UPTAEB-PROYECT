document.addEventListener('DOMContentLoaded', function() {
  // Función mejorada para imprimir
  const printBtn = document.getElementById('printBtn');
  if (printBtn) {
    printBtn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      console.log('Botón de imprimir clickeado');
      
      // Preparar para impresión
      document.body.classList.add('printing');
      
      // Asegurar que los estilos se apliquen
      setTimeout(function() {
        try {
          window.print();
        } catch (error) {
          console.error('Error al imprimir:', error);
          alert('Error al intentar imprimir. Intente usar Ctrl+P manualmente.');
        }
        
        // Remover la clase después de imprimir
        setTimeout(function() {
          document.body.classList.remove('printing');
        }, 1000);
      }, 200);
    });
  }
  
  // Detectar eventos de impresión
  window.addEventListener('beforeprint', function() {
    console.log('Preparando impresión...');
    document.body.classList.add('printing');
  });
  
  window.addEventListener('afterprint', function() {
    console.log('Impresión completada o cancelada');
    document.body.classList.remove('printing');
  });
  
  // Agregar atajo de teclado para imprimir
  document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'p') {
      e.preventDefault();
      const printBtn = document.getElementById('printBtn');
      if (printBtn) {
        printBtn.click();
      }
    }
  });
  
  // Evitar errores de consola
  window.addEventListener('error', function(e) {
    console.log('Error capturado:', e.error);
  });
});
