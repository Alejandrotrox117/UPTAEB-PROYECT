<?php headerAdmin($data); ?>

<main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 bg-gray-100">
  <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6 print:hidden">
    <div>
      <h1 class="text-2xl md:text-3xl font-bold text-gray-900">
        <i class="fas fa-file-invoice text-blue-600 mr-2"></i>
        Factura de Compra
      </h1>
      <nav class="flex text-sm text-gray-600 mt-2">
        <a href="<?= base_url(); ?>" class="hover:text-blue-600">
          <i class="fas fa-home mr-1"></i>Inicio
        </a>
        <span class="mx-2">/</span>
        <a href="<?= base_url(); ?>/compras" class="hover:text-blue-600">Compras</a>
        <span class="mx-2">/</span>
        <span class="text-gray-500">Factura</span>
      </nav>
    </div>
  </div>

  <div class="bg-white rounded-2xl shadow-lg overflow-hidden print:shadow-none print:rounded-none">
    
    <?php if(empty($data['arrCompra']) || empty($data['arrCompra']['solicitud'])): ?>
      <div class="p-8 text-center">
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
          <i class="fas fa-exclamation-triangle text-yellow-500 text-4xl mb-4"></i>
          <h3 class="text-lg font-semibold text-yellow-800 mb-2">Datos no encontrados</h3>
          <p class="text-yellow-700">No se pudieron cargar los datos de la compra solicitada.</p>
          <a href="<?= base_url(); ?>/compras" class="mt-4 inline-block bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
            <i class="fas fa-arrow-left mr-2"></i>Volver a Compras
          </a>
        </div>
      </div>
    <?php else: 
      $compra = $data['arrCompra']['solicitud'];
      $proveedor = trim(($compra['nombrePersona'] ?? '') . ' ' . ($compra['apellidoPersona'] ?? ''));
    ?>
      
      <!-- Contenido de la Factura -->
      <div id="facturaContent" class="p-6 md:p-8 print:p-6">
        
        <!-- ENCABEZADO PRINCIPAL - 3 COLUMNAS -->
        <div class="grid grid-cols-3 gap-4 mb-6 print:mb-6 print:pb-4">
          
          <!-- LOGO (Columna 1) -->
          <div class="flex justify-start items-start print:border print:border-black print:p-3">
            <img src="/project/app/assets/img/LOGO.png" alt="Logo" class="h-16 md:h-20 object-contain print:h-16">
          </div>
          
          <!-- INFO EMPRESA (Columna 2) -->
          <div class="text-center print:border print:border-black print:p-3">
            <h2 class="text-lg font-bold text-gray-800 mb-2 print:text-base print:mb-2 print:text-black print:font-bold">RECUPERADORA LA PRADERA DE PAVIA</h2>
            <div class="text-xs text-gray-600 space-y-1 print:text-sm print:text-black print:space-y-1">
              <p><span class="font-semibold">RIF:</span> J-27.436.820-5</p>
              <p><span class="font-semibold">Dirección:</span></p>
              <p class="text-[10px] print:text-xs">Estado Lara, Municipio Iribarren, KM 12 Carretera Vieja Hacia Carora Pavía Barquisimeto.</p>
            </div>
          </div>
          
          <!-- INFO DE FACTURA (Columna 3) -->
          <div class="text-center print:border print:border-black print:p-3">
            <h3 class="text-lg font-bold text-blue-800 mb-2 print:text-base print:text-black print:mb-2 print:font-bold">FACTURA DE COMPRA</h3>
            <div class="text-sm text-gray-600 space-y-1 print:text-sm print:text-black print:space-y-1">
              <p><span class="font-semibold">Fecha:</span> <?= date('d/m/Y', strtotime($compra['fecha'])) ?></p>
              <p><span class="font-semibold">Hora:</span> <?= date('H:i:s') ?></p>
            </div>
          </div>
        </div>

        <!-- SECCIÓN PROVEEDOR DIVIDIDA EN DOS PARTES -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6 print:grid-cols-2 print:gap-6 print:mb-6">
          
          <!-- PARTE 1: DATOS DEL PROVEEDOR -->
          <div class="bg-gray-50 p-6 rounded-lg print:bg-gray-50 print:p-4 print:rounded-none">
            <h4 class="text-lg font-semibold text-gray-800 mb-4 border-b border-gray-300 pb-2 print:text-lg print:mb-3 print:text-black print:border-gray-400 print:pb-2 print:font-semibold">
              PROVEEDOR
            </h4>
            <div class="space-y-3 text-sm print:space-y-2 print:text-sm">
              <div class="print:block">
                <span class="font-semibold text-gray-700 print:text-black print:font-semibold">Nombre/Razón Social: </span>
                <span class="text-gray-900 print:text-black"><?= htmlspecialchars($proveedor ?: 'No especificado') ?></span>
              </div>
              <div class="print:block">
                <span class="font-semibold text-gray-700 print:text-black print:font-semibold">Identificación: </span>
                <span class="text-gray-900 print:text-black"><?= htmlspecialchars($compra['personaId'] ?? 'No especificado') ?></span>
              </div>
              <div class="print:block">
                <span class="font-semibold text-gray-700 print:text-black print:font-semibold">Teléfono: </span>
                <span class="text-gray-900 print:text-black"><?= htmlspecialchars($compra['telefono'] ?? 'No especificado') ?></span>
              </div>
              <?php if(!empty($compra['direccion'])): ?>
              <div class="print:block">
                <span class="font-semibold text-gray-700 print:text-black print:font-semibold">Dirección: </span>
                <span class="text-gray-900 print:text-black"><?= htmlspecialchars($compra['direccion']) ?></span>
              </div>
              <?php endif; ?>
              <?php if(!empty($compra['email'])): ?>
              <div class="print:block">
                <span class="font-semibold text-gray-700 print:text-black print:font-semibold">Email: </span>
                <span class="text-gray-900 print:text-black"><?= htmlspecialchars($compra['email']) ?></span>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- PARTE 2: INFORMACIÓN DE LA COMPRA -->
          <div class="bg-blue-50 p-6 rounded-lg print:bg-blue-50 print:p-4 print:rounded-none">
            <h4 class="text-lg font-semibold text-gray-800 mb-4 border-b border-gray-300 pb-2 print:text-lg print:mb-3 print:text-black print:border-gray-400 print:pb-2 print:font-semibold">
              INFORMACIÓN DE COMPRA
            </h4>
            <div class="space-y-3 text-sm print:space-y-2 print:text-sm">
              <div class="print:block">
                <span class="font-semibold text-gray-700 print:text-black print:font-semibold">Nro. Compra: </span>
                <span class="text-gray-900 print:text-black"><?= htmlspecialchars($compra['nro_compra']) ?></span>
              </div>
              <div class="print:block">
                <span class="font-semibold text-gray-700 print:text-black print:font-semibold">Estado: </span>
                <span class="text-gray-900 print:text-black"><?= htmlspecialchars($compra['estatus_compra']) ?></span>
              </div>
              <div class="print:block">
                <span class="font-semibold text-gray-700 print:text-black print:font-semibold">Fecha Compra: </span>
                <span class="text-gray-900 print:text-black"><?= date('d/m/Y', strtotime($compra['fecha'])) ?></span>
              </div>
              <?php if(!empty($compra['observaciones_compra'])): ?>
              <div class="mt-4 print:mt-3">
                <span class="font-semibold text-gray-700 print:text-black print:font-semibold">Observaciones:</span>
                <div class="text-gray-900 text-sm mt-2 bg-white p-3 rounded border print:text-black print:bg-white print:border print:border-gray-300 print:p-2 print:mt-1 print:rounded-none">
                  <?= htmlspecialchars($compra['observaciones_compra']) ?>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- SECCIÓN PRODUCTOS MEJORADA -->
        <div class="mb-6 print:mb-6">
          <div class="overflow-x-auto print:overflow-visible">
            <table class="w-full table-auto border-collapse print:text-sm">
              <thead>
                <tr class="bg-gray-200 text-gray-800 text-sm print:bg-gray-200 print:text-black print:text-sm">
                  <th class="px-6 py-4 text-center font-bold border border-gray-400 print:px-4 print:py-3 print:border print:border-gray-400 print:font-bold">#</th>
                  <th class="px-6 py-4 text-left font-bold border border-gray-400 print:px-4 print:py-3 print:border print:border-gray-400 print:font-bold">Descripción</th>
                  <th class="px-6 py-4 text-left font-bold border border-gray-400 print:px-4 print:py-3 print:border print:border-gray-400 print:font-bold">Detalles</th>
                  <th class="px-6 py-4 text-center font-bold border border-gray-400 print:px-4 print:py-3 print:border print:border-gray-400 print:font-bold">Cantidad</th>
                  <th class="px-6 py-4 text-center font-bold border border-gray-400 print:px-4 print:py-3 print:border print:border-gray-400 print:font-bold">Precio Unit.</th>
                  <th class="px-6 py-4 text-center font-bold border border-gray-400 print:px-4 print:py-3 print:border print:border-gray-400 print:font-bold">Total</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                  $contador = 1;
                  $subtotal = 0;
                  $productos_mostrados = array();

                  if(!empty($data['arrCompra']['detalles']) && count($data['arrCompra']['detalles']) > 0){
                      foreach ($data['arrCompra']['detalles'] as $producto) {
                          if(!in_array($producto['productoId'], $productos_mostrados)) {
                              $total_linea = floatval($producto['subtotal_linea']);
                              $subtotal += $total_linea;
                              $productos_mostrados[] = $producto['productoId'];
                ?>
                <tr class="hover:bg-gray-50 transition-colors print:hover:bg-transparent bg-white print:bg-white">
                  <td class="px-6 py-3 text-center text-sm font-medium text-gray-900 border border-gray-400 print:px-4 print:py-2 print:text-sm print:text-black print:border print:border-gray-400"><?= $contador ?></td>
                  <td class="px-6 py-3 border border-gray-400 print:px-4 print:py-2 print:border print:border-gray-400">
                    <div class="text-sm font-semibold text-gray-900 print:text-sm print:text-black print:font-semibold">
                      <?= htmlspecialchars($producto['nombreProducto']) ?>
                    </div>
                  </td>
                  <td class="px-6 py-3 text-sm text-gray-600 border border-gray-400 print:px-4 print:py-2 print:text-sm print:text-gray-600 print:border print:border-gray-400">
                    <?= htmlspecialchars($producto['modelo'] ?: 'Sin detalles adicionales') ?>
                  </td>
                  <td class="px-6 py-3 text-center text-sm font-medium text-gray-900 border border-gray-400 print:px-4 print:py-2 print:text-sm print:text-black print:border print:border-gray-400">
                    <?= number_format($producto['cantidad'], 2) ?>
                  </td>
                  <td class="px-6 py-3 text-center text-sm font-medium text-gray-900 border border-gray-400 print:px-4 print:py-2 print:text-sm print:text-black print:border print:border-gray-400">
                    $ <?= number_format($producto['precio'], 2) ?>
                  </td>
                  <td class="px-6 py-3 text-center text-sm font-bold text-gray-900 border border-gray-400 print:px-4 print:py-2 print:text-sm print:text-black print:border print:border-gray-400 print:font-bold">
                    $ <?= number_format($total_linea, 2) ?>
                  </td>
                </tr>
                <?php 
                              $contador++;
                          }
                      }
                  } else {
                ?>
                <tr>
                  <td colspan="6" class="px-6 py-8 text-center text-gray-500 border border-gray-400 print:px-4 print:py-6 print:text-black print:border print:border-gray-400">
                    <i class="fas fa-info-circle text-2xl mb-2 text-gray-400 print:hidden"></i>
                    <p>No hay productos registrados en esta compra</p>
                  </td>
                </tr>
                <?php } ?>
              </tbody>
              <tfoot>
                <tr class="bg-white print:bg-white">
                  <th colspan="5" class="px-6 py-4 text-right text-lg font-bold text-gray-800 border border-gray-400 print:px-4 print:py-3 print:text-base print:text-black print:border print:border-gray-400 print:font-bold">
                    TOTAL GENERAL:
                  </th>
                  <td class="px-6 py-4 text-center text-xl font-bold text-green-700 border border-gray-400 print:px-4 print:py-3 print:text-base print:text-green-700 print:border print:border-gray-400 print:font-bold">
                    Bs. <?= number_format(floatval($compra['total_general']), 2) ?>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- Información adicional -->
        <div class="bg-gray-50 p-4 mb-6 rounded-lg print:bg-gray-50 print:p-4 print:mb-6 print:rounded-none">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm print:grid-cols-3 print:gap-4 print:text-sm">
            <div class="flex items-center print:block">
              <i class="fas fa-cube text-blue-600 mr-2 print:hidden"></i>
              <span class="font-semibold text-gray-700 print:text-blue-600 print:font-semibold">📦 Total de Productos: </span>
              <span class="text-gray-900 ml-2 print:text-black print:ml-1"><?= ($contador - 1) ?> items</span>
            </div>
            <div class="flex items-center print:block">
              <i class="fas fa-calendar text-blue-600 mr-2 print:hidden"></i>
              <span class="font-semibold text-gray-700 print:text-blue-600 print:font-semibold">📅 Fecha de Emisión: </span>
              <span class="text-gray-900 ml-2 print:text-black print:ml-1"><?= date('d/m/Y H:i:s') ?></span>
            </div>
            <div class="flex items-center print:block">
              <i class="fas fa-user text-blue-600 mr-2 print:hidden"></i>
              <span class="font-semibold text-gray-700 print:text-blue-600 print:font-semibold">👤 Usuario: </span>
              <span class="text-gray-900 ml-2 print:text-black print:ml-1"><?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Sistema') ?></span>
            </div>
          </div>
        </div>

        <!-- Pie de página para impresión -->
        <div class="hidden print:block text-center text-sm text-black mt-8 pt-4 border-t border-gray-400">
          <p>Esta factura fue generada automáticamente por el sistema el <?= date('d/m/Y') ?> a las <?= date('H:i:s') ?></p>
          <p class="mt-2">Para consultas o reclamos, contacte a través de nuestros canales oficiales</p>
          <p class="mt-2 font-bold">RECUPERADORA LA PRADERA DE PAVIA - Todos los derechos reservados</p>
        </div>

      </div>

      <!-- Botones de Acción -->
      <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 print:hidden">
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
          <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center justify-center">
            <i class="fas fa-print mr-2"></i>
            Imprimir Factura
          </button>
          <a href="<?= base_url(); ?>/compras" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center justify-center">
            <i class="fas fa-arrow-left mr-2"></i>
            Volver a Compras
          </a>
        </div>
      </div>

    <?php endif; ?>
  </div>
</main>

<!-- Estilos de Impresión Optimizados -->
<style>
@media print {
  /* Forzar impresión de colores y fondos */
  * {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    color-adjust: exact !important;
  }

  /* Configuración de página */
  @page {
    margin: 1.5cm 1cm 1cm 1cm;
    size: A4;
  }

  /* Ocultar elementos no necesarios */
  body * {
    visibility: hidden;
  }
  
  #facturaContent, #facturaContent * {
    visibility: visible;
  }
  
  #facturaContent {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    background: white !important;
  }

  /* Estilos generales */
  body {
    font-family: Arial, sans-serif !important;
    font-size: 14px !important;
    line-height: 1.4 !important;
    color: #000 !important;
    background: white !important;
  }

  /* Encabezado */
  h1, h2, h3, h4, h5, h6 {
    color: #000 !important;
    page-break-after: avoid;
  }

  /* Grid layouts */
  .grid-cols-3 {
    display: grid !important;
    grid-template-columns: 1fr 2fr 1fr !important;
    gap: 16px !important;
  }

  .print\\:grid-cols-2 {
    display: grid !important;
    grid-template-columns: 1fr 1fr !important;
  }

  .print\\:grid-cols-3 {
    display: grid !important;
    grid-template-columns: 1fr 1fr 1fr !important;
  }

  /* Fondos de color */
  .print\\:bg-gray-50 {
    background-color: #f9f9f9 !important;
  }

  .print\\:bg-blue-50 {
    background-color: #eff6ff !important;
  }

  .print\\:bg-gray-200 {
    background-color: #e5e7eb !important;
  }

  .print\\:bg-white {
    background-color: white !important;
  }

  /* Bordes */
  .print\\:border {
    border: 1px solid #000 !important;
  }

  .print\\:border-gray-400 {
    border-color: #9ca3af !important;
  }

  .print\\:border-black {
    border-color: #000 !important;
  }

  /* Texto y colores */
  .print\\:text-black {
    color: #000 !important;
  }

  .print\\:text-gray-600 {
    color: #4b5563 !important;
  }

  .print\\:text-green-700 {
    color: #047857 !important;
  }

  .print\\:text-blue-600 {
    color: #2563eb !important;
  }

  .print\\:font-bold {
    font-weight: bold !important;
  }

  .print\\:font-semibold {
    font-weight: 600 !important;
  }

  /* Tamaños de texto */
  .print\\:text-sm {
    font-size: 14px !important;
  }

  .print\\:text-base {
    font-size: 16px !important;
  }

  .print\\:text-lg {
    font-size: 18px !important;
  }

  .print\\:text-xs {
    font-size: 12px !important;
  }

  /* Espaciado */
  .print\\:p-3 {
    padding: 12px !important;
  }

  .print\\:p-4 {
    padding: 16px !important;
  }

  .print\\:px-4 {
    padding-left: 16px !important;
    padding-right: 16px !important;
  }

  .print\\:py-2 {
    padding-top: 8px !important;
    padding-bottom: 8px !important;
  }

  .print\\:py-3 {
    padding-top: 12px !important;
    padding-bottom: 12px !important;
  }

  .print\\:pb-2 {
    padding-bottom: 8px !important;
  }

  .print\\:pb-4 {
    padding-bottom: 16px !important;
  }

  .print\\:mb-2 {
    margin-bottom: 8px !important;
  }

  .print\\:mb-3 {
    margin-bottom: 12px !important;
  }

  .print\\:mb-6 {
    margin-bottom: 24px !important;
  }

  .print\\:mt-1 {
    margin-top: 4px !important;
  }

  .print\\:mt-3 {
    margin-top: 12px !important;
  }

  .print\\:ml-1 {
    margin-left: 4px !important;
  }

  .print\\:gap-4 {
    gap: 16px !important;
  }

  .print\\:gap-6 {
    gap: 24px !important;
  }

  /* Espaciado entre elementos */
  .print\\:space-y-1 > * + * {
    margin-top: 4px !important;
  }

  .print\\:space-y-2 > * + * {
    margin-top: 8px !important;
  }

  /* Tabla */
  table {
    border-collapse: collapse !important;
    width: 100% !important;
    margin: 0 !important;
  }

  table th,
  table td {
    border: 1px solid #9ca3af !important;
    padding: 8px 12px !important;
    font-size: 14px !important;
    line-height: 1.4 !important;
  }

  table thead th {
    background-color: #e5e7eb !important;
    font-weight: bold !important;
  }

  table tbody td {
    background-color: white !important;
  }

  table tfoot th,
  table tfoot td {
    background-color: white !important;
    font-weight: bold !important;
    border-top: 2px solid #000 !important;
  }

  /* Display y alineación */
  .print\\:block {
    display: block !important;
  }

  .print\\:text-center {
    text-align: center !important;
  }

  .print\\:text-left {
    text-align: left !important;
  }

  .print\\:text-right {
    text-align: right !important;
  }

  /* Logo */
  img {
    max-height: 60px !important;
    width: auto !important;
  }

  /* Ocultar elementos específicos */
  .print\\:hidden {
    display: none !important;
  }

  .hidden.print\\:block {
    display: block !important;
  }

  /* Overflow */
  .print\\:overflow-visible {
    overflow: visible !important;
  }

  /* Hover effects - remove in print */
  .print\\:hover\\:bg-transparent:hover {
    background-color: transparent !important;
  }

  /* Iconos - ocultar en impresión */
  .fas, .fab, .far {
    display: none !important;
  }

  /* Rounded - quitar en impresión */
  .print\\:rounded-none {
    border-radius: 0 !important;
  }
}

/* Estilos para pantalla */
@media screen {
  .bg-gray-25 {
    background-color: #fafafa;
  }
}
</style>

<script>
// Función de impresión mejorada
function imprimirFactura() {
  const originalTitle = document.title;
  document.title = 'Factura_<?= $compra['nro_compra'] ?? 'Compra' ?>_<?= date('Y-m-d') ?>';
  
  window.print();
  
  setTimeout(() => {
    document.title = originalTitle;
  }, 1000);
}

// Event listeners para impresión
window.addEventListener('beforeprint', function() {
  document.title = 'Factura_<?= $compra['nro_compra'] ?? 'Compra' ?>_<?= date('Y-m-d') ?>';
  document.body.classList.add('printing');
});

window.addEventListener('afterprint', function() {
  document.body.classList.remove('printing');
});
</script>

<?php footerAdmin($data); ?>