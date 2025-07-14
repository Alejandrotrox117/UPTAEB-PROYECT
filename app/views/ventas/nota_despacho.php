<?php headerAdmin($data); ?>

<main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 bg-gray-100">
  <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6 print:hidden">
    <div>
      <h1 class="text-2xl md:text-3xl font-bold text-gray-900">
        <i class="fas fa-file-alt text-green-600 mr-2"></i>
        Nota de Despacho
      </h1>
      <nav class="flex text-sm text-gray-600 mt-2">
        <a href="<?= base_url(); ?>" class="hover:text-blue-600">
          <i class="fas fa-home mr-1"></i>Inicio
        </a>
        <span class="mx-2">/</span>
        <a href="<?= base_url(); ?>/ventas" class="hover:text-blue-600">Ventas</a>
        <span class="mx-2">/</span>
        <span class="text-gray-500">Nota de Despacho</span>
      </nav>
    </div>
  </div>

  <div class="bg-white rounded-2xl shadow-lg overflow-hidden print:shadow-none print:rounded-none">
    
    <?php if(empty($data['arrVenta']) || !$data['arrVenta']['status'] || empty($data['arrVenta']['venta'])): ?>
      <div class="p-8 text-center">
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
          <i class="fas fa-exclamation-triangle text-yellow-500 text-4xl mb-4"></i>
          <h3 class="text-lg font-semibold text-yellow-800 mb-2">Datos no encontrados</h3>
          <p class="text-yellow-700">No se pudieron cargar los datos de la venta solicitada.</p>
          <a href="<?= base_url(); ?>/ventas" class="mt-4 inline-block bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
            <i class="fas fa-arrow-left mr-2"></i>Volver a Ventas
          </a>
        </div>
      </div>
    <?php else: 
      $venta = $data['arrVenta']['venta'];
      $cliente = $venta['cliente_nombre'] ?? 'Cliente no especificado';
      
      // Debug temporal para ver qué datos tenemos - VISIBLE EN PANTALLA
      if (isset($_GET['debug'])) {
        echo "<div style='background: yellow; padding: 20px; margin: 20px; border: 2px solid red;'>";
        echo "<h3>DEBUG INFO:</h3>";
        echo "<h4>VENTA:</h4><pre>" . htmlspecialchars(print_r($venta, true)) . "</pre>";
        echo "<h4>DETALLES:</h4><pre>" . htmlspecialchars(print_r($data['arrVenta']['detalles'] ?? 'NO_DETALLES', true)) . "</pre>";
        echo "<h4>ESTRUCTURA COMPLETA:</h4><pre>" . htmlspecialchars(print_r($data['arrVenta'], true)) . "</pre>";
        echo "</div>";
      }
    ?>
      
      <!-- Contenido de la Nota de Despacho -->
      <div id="notaDespachoContent" class="p-6 md:p-8 print:p-6">
        
        <!-- ENCABEZADO PRINCIPAL - 3 COLUMNAS -->
        <div class="grid grid-cols-3 gap-4 mb-6 print:mb-6 print:pb-4">
          
          <!-- LOGO (Columna 1) -->
          <div class="flex justify-start items-start print:border print:border-black print:p-3">
            <img src="<?= base_url(); ?>/app/assets/img/LOGO.png" alt="Logo" class="h-16 md:h-20 object-contain print:h-16">
          </div>
          
          <!-- INFORMACIÓN DE LA EMPRESA (Columna 2) -->
          <div class="text-center print:border print:border-black print:p-3">
            <h2 class="text-lg md:text-xl font-bold text-gray-800 print:text-lg print:text-black print:font-bold">
              RECUPERADORA LA PRADERA DE PAVIA
            </h2>
            <div class="text-sm text-gray-600 mt-2 space-y-1 print:text-sm print:text-black print:mt-1">
              <p>RIF: J-27.436.820-5</p>
              <p>Estado Lara, Municipio Iribarren, KM 12 Carretera Vieja Hacia Carora Pavía Barquisimeto.</p>
  
            </div>
          </div>
          
          <!-- INFORMACIÓN DEL DOCUMENTO (Columna 3) -->
          <div class="text-right print:border print:border-black print:p-3">
            <h3 class="text-lg md:text-xl font-bold text-green-600 print:text-lg print:text-black print:font-bold">
              NOTA DE DESPACHO
            </h3>
            <div class="text-sm text-gray-700 mt-2 space-y-1 print:text-sm print:text-black print:mt-1">
              <p><strong>Nro:</strong> <?= htmlspecialchars($venta['nro_venta']) ?></p>
              <p><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($venta['fecha_venta'])) ?></p>
              <p><strong>Estado:</strong> <?= htmlspecialchars($venta['estatus']) ?></p>
            </div>
          </div>
        </div>

        <!-- SEPARADOR -->
        <hr class="border-gray-400 mb-6 print:border-gray-400 print:mb-4">

        <!-- INFORMACIÓN DEL CLIENTE Y VENTA EN DOS COLUMNAS -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 print:grid-cols-2 print:gap-4 print:mb-6">
          
          <!-- PARTE 1: INFORMACIÓN DEL CLIENTE -->
          <div class="bg-green-50 p-6 rounded-lg print:bg-green-50 print:p-4 print:rounded-none">
            <h4 class="text-lg font-semibold text-gray-800 mb-4 border-b border-gray-300 pb-2 print:text-lg print:mb-3 print:text-black print:border-gray-400 print:pb-2 print:font-semibold">
              INFORMACIÓN DEL CLIENTE
            </h4>
            <div class="space-y-3 text-sm print:space-y-2 print:text-sm">
              <div class="print:block">
                <span class="font-semibold text-gray-700 print:text-black print:font-semibold">Cliente: </span>
                <span class="text-gray-900 print:text-black"><?= htmlspecialchars($cliente ?: 'No especificado') ?></span>
              </div>
            </div>
          </div>

          <!-- PARTE 2: INFORMACIÓN DE LA VENTA -->
          <div class="bg-blue-50 p-6 rounded-lg print:bg-blue-50 print:p-4 print:rounded-none">
            <h4 class="text-lg font-semibold text-gray-800 mb-4 border-b border-gray-300 pb-2 print:text-lg print:mb-3 print:text-black print:border-gray-400 print:pb-2 print:font-semibold">
              INFORMACIÓN DE LA VENTA
            </h4>
            <div class="space-y-3 text-sm print:space-y-2 print:text-sm">
              <div class="print:block">
                <span class="font-semibold text-gray-700 print:text-black print:font-semibold">Nro. Venta: </span>
                <span class="text-gray-900 print:text-black"><?= htmlspecialchars($venta['nro_venta']) ?></span>
              </div>
              <div class="print:block">
                <span class="font-semibold text-gray-700 print:text-black print:font-semibold">Estado: </span>
                <span class="text-gray-900 print:text-black"><?= htmlspecialchars($venta['estatus']) ?></span>
              </div>
              <div class="print:block">
                <span class="font-semibold text-gray-700 print:text-black print:font-semibold">Fecha Venta: </span>
                <span class="text-gray-900 print:text-black"><?= date('d/m/Y', strtotime($venta['fecha_venta'])) ?></span>
              </div>
              <div class="print:block">
                <span class="font-semibold text-gray-700 print:text-black print:font-semibold">Moneda: </span>
                <span class="text-gray-900 print:text-black"><?= htmlspecialchars($venta['codigo_moneda'] ?? 'VES') ?></span>
              </div>
            </div>
          </div>
        </div>

        <!-- SECCIÓN PRODUCTOS -->
        <div class="mb-6 print:mb-6">
          <h4 class="text-lg font-semibold text-gray-800 mb-4 print:text-lg print:mb-3 print:text-black print:font-semibold">
            PRODUCTOS A DESPACHAR
          </h4>
          <div class="overflow-x-auto print:overflow-visible">
            <table class="w-full table-auto border-collapse print:text-sm">
              <thead>
                <tr class="bg-gray-200 text-gray-800 text-sm print:bg-gray-200 print:text-black print:text-sm">
                  <th class="px-6 py-4 text-center font-bold border border-gray-400 print:px-4 print:py-3 print:border print:border-gray-400 print:font-bold">#</th>
                  <th class="px-6 py-4 text-left font-bold border border-gray-400 print:px-4 print:py-3 print:border print:border-gray-400 print:font-bold">Producto</th>
                  <th class="px-6 py-4 text-left font-bold border border-gray-400 print:px-4 print:py-3 print:border print:border-gray-400 print:font-bold">Categoría</th>
                  <th class="px-6 py-4 text-center font-bold border border-gray-400 print:px-4 print:py-3 print:border print:border-gray-400 print:font-bold">Cantidad</th>
                  <th class="px-6 py-4 text-center font-bold border border-gray-400 print:px-4 print:py-3 print:border print:border-gray-400 print:font-bold">Precio Unit.</th>
                  <th class="px-6 py-4 text-center font-bold border border-gray-400 print:px-4 print:py-3 print:border print:border-gray-400 print:font-bold">Total</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                  $contador = 1;
                  $subtotal = 0;
                  
                  // DEBUG: Verificar qué datos tenemos
                  echo "<!-- DEBUG - Verificando detalles: -->";
                  echo "<!-- Existe detalles: " . (isset($data['arrVenta']['detalles']) ? 'SI' : 'NO') . " -->";
                  echo "<!-- Es array: " . (is_array($data['arrVenta']['detalles'] ?? null) ? 'SI' : 'NO') . " -->";
                  echo "<!-- Cantidad: " . count($data['arrVenta']['detalles'] ?? []) . " -->";
                  echo "<!-- Contenido detalles: " . print_r($data['arrVenta']['detalles'] ?? 'VACIO', true) . " -->";

                  if(!empty($data['arrVenta']['detalles']) && count($data['arrVenta']['detalles']) > 0){
                      foreach ($data['arrVenta']['detalles'] as $producto) {
                          $total_linea = floatval($producto['subtotal_general'] ?? 0);
                          $subtotal += $total_linea;
                ?>
                <tr class="hover:bg-gray-50 transition-colors print:hover:bg-transparent bg-white print:bg-white">
                  <td class="px-6 py-3 text-center text-sm font-medium text-gray-900 border border-gray-400 print:px-4 print:py-2 print:text-sm print:text-black print:border print:border-gray-400"><?= $contador ?></td>
                  <td class="px-6 py-3 border border-gray-400 print:px-4 print:py-2 print:border print:border-gray-400">
                    <div class="text-sm font-semibold text-gray-900 print:text-sm print:text-black print:font-semibold">
                      <?= htmlspecialchars($producto['nombre_producto'] ?? 'Producto sin nombre') ?>
                    </div>
                  </td>
                  <td class="px-6 py-3 text-sm text-gray-600 border border-gray-400 print:px-4 print:py-2 print:text-sm print:text-gray-600 print:border print:border-gray-400">
                    <?= htmlspecialchars($producto['nombre_categoria'] ?? 'Sin categoría') ?>
                  </td>
                  <td class="px-6 py-3 text-center text-sm font-medium text-gray-900 border border-gray-400 print:px-4 print:py-2 print:text-sm print:text-black print:border print:border-gray-400">
                    <?= number_format($producto['cantidad'], 2) ?>
                  </td>
                  <td class="px-6 py-3 text-center text-sm font-medium text-gray-900 border border-gray-400 print:px-4 print:py-2 print:text-sm print:text-black print:border print:border-gray-400">
                    <?= number_format($producto['precio_unitario_venta'], 2) ?>
                  </td>
                  <td class="px-6 py-3 text-center text-sm font-bold text-gray-900 border border-gray-400 print:px-4 print:py-2 print:text-sm print:text-black print:border print:border-gray-400 print:font-bold">
                    <?= number_format($total_linea, 2) ?>
                  </td>
                </tr>
                <?php 
                          $contador++;
                      }
                  } else {
                ?>
                <tr>
                  <td colspan="6" class="px-6 py-8 text-center text-gray-500 border border-gray-400 print:px-4 print:py-6 print:text-black print:border print:border-gray-400">
                    <i class="fas fa-info-circle text-2xl mb-2 text-gray-400 print:hidden"></i>
                    <p>No hay productos registrados en esta venta</p>
                  </td>
                </tr>
                <?php } ?>
              </tbody>
              <tfoot>
                <tr class="bg-white print:bg-white">
                  <th colspan="5" class="px-6 py-4 text-right text-lg font-bold text-gray-800 border border-gray-400 print:px-4 print:py-3 print:text-base print:text-black print:border print:border-gray-400 print:font-bold">
                    SUBTOTAL:
                  </th>
                  <td class="px-6 py-4 text-center text-lg font-bold text-gray-900 border border-gray-400 print:px-4 print:py-3 print:text-base print:text-black print:border print:border-gray-400 print:font-bold">
                    <?= number_format($venta['total_general'] ?? 0, 2) ?>
                  </td>
                </tr>
                <?php if(isset($venta['descuento_porcentaje_general']) && $venta['descuento_porcentaje_general'] > 0): ?>
                <tr class="bg-white print:bg-white">
                  <th colspan="5" class="px-6 py-4 text-right text-lg font-bold text-gray-800 border border-gray-400 print:px-4 print:py-3 print:text-base print:text-black print:border print:border-gray-400 print:font-bold">
                    DESCUENTO (<?= number_format($venta['descuento_porcentaje_general'] ?? 0, 2) ?>%):
                  </th>
                  <td class="px-6 py-4 text-center text-lg font-bold text-red-600 border border-gray-400 print:px-4 print:py-3 print:text-base print:text-black print:border print:border-gray-400 print:font-bold">
                    -<?= number_format($venta['monto_descuento_general'] ?? 0, 2) ?>
                  </td>
                </tr>
                <?php endif; ?>
                <tr class="bg-green-100 print:bg-green-100">
                  <th colspan="5" class="px-6 py-4 text-right text-xl font-bold text-gray-800 border border-gray-400 print:px-4 print:py-3 print:text-lg print:text-black print:border print:border-gray-400 print:font-bold">
                    TOTAL:
                  </th>
                  <td class="px-6 py-4 text-center text-xl font-bold text-green-700 border border-gray-400 print:px-4 print:py-3 print:text-lg print:text-black print:border print:border-gray-400 print:font-bold">
                    <?= number_format($venta['total_general'], 2) ?>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- FIRMAS Y SELLOS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-30 print:grid-cols-3 print:gap-6 print:mt-40">
          <div class="text-center print:text-center">
            <div class="border-t border-gray-400 pt-2 print:border-t print:border-gray-400 print:pt-2">
              <p class="text-sm font-semibold text-gray-700 print:text-sm print:text-black print:font-semibold">DESPACHADO POR</p>
              <p class="text-xs text-gray-500 mt-1 print:text-xs print:text-gray-600 print:mt-1">Nombre y Firma</p>
            </div>
          </div>
          <div class="text-center print:text-center">
            <div class="border-t border-gray-400 pt-2 print:border-t print:border-gray-400 print:pt-2">
              <p class="text-sm font-semibold text-gray-700 print:text-sm print:text-black print:font-semibold">RECIBIDO POR</p>
              <p class="text-xs text-gray-500 mt-1 print:text-xs print:text-gray-600 print:mt-1">Nombre y Firma del Cliente</p>
            </div>
          </div>
          <div class="text-center print:text-center">
            <div class="border-t border-gray-400 pt-2 print:border-t print:border-gray-400 print:pt-2">
              <p class="text-sm font-semibold text-gray-700 print:text-sm print:text-black print:font-semibold">FECHA DE ENTREGA</p>
              <p class="text-xs text-gray-500 mt-1 print:text-xs print:text-gray-600 print:mt-1">___/___/______</p>
            </div>
          </div>
        </div>

        <!-- PIE DE PÁGINA -->
        <div class="mt-8 pt-4 border-t border-gray-300 text-center text-xs text-gray-500 print:mt-6 print:pt-3 print:border-t print:border-gray-300 print:text-center print:text-xs print:text-gray-600">
          <p>Nota de Despacho generada el <?= date('d/m/Y H:i:s') ?></p>
          <p class="mt-1">Este documento NO tiene valor fiscal</p>
        </div>
      </div>
      
      <!-- Botones de Acción -->
      <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 print:hidden">
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
          <button id="printBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center justify-center">
            <i class="fas fa-print mr-2"></i>
            Imprimir Nota de Despacho
          </button>
          <a href="<?= base_url(); ?>/ventas" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center justify-center">
            <i class="fas fa-arrow-left mr-2"></i>
            Volver a Ventas
          </a>
        </div>
      </div>

    <?php endif; ?>
  </div>
</main>

<!-- CSS para impresión -->
<style>
@media print {
  /* Ocultar elementos que no deben aparecer en impresión */
  nav, .navbar, header, .header, .sidebar, .print\:hidden { display: none !important; }
  
  /* Configuración general para impresión */
  body { 
    font-size: 12pt !important;
    margin: 0 !important;
    padding: 0 !important;
    background: white !important;
    color: black !important;
  }
  
  /* Asegurar que solo se imprima el contenido principal */
  main { 
    margin: 0 !important;
    padding: 0 !important;
    background: white !important;
  }
  .print\:hidden { display: none !important; }
  .print\:block { display: block !important; }
  .print\:text-black { color: black !important; }
  .print\:border { border: 1px solid #000 !important; }
  .print\:border-gray-400 { border-color: #6b7280 !important; }
  .print\:bg-green-50 { background-color: #f0fdf4 !important; }
  .print\:bg-blue-50 { background-color: #eff6ff !important; }
  .print\:bg-green-100 { background-color: #dcfce7 !important; }
  .print\:bg-gray-200 { background-color: #e5e7eb !important; }
  .print\:bg-white { background-color: white !important; }
  .print\:font-bold { font-weight: bold !important; }
  .print\:font-semibold { font-weight: 600 !important; }
  .print\:text-sm { font-size: 11pt !important; }
  .print\:text-base { font-size: 12pt !important; }
  .print\:text-lg { font-size: 14pt !important; }
  .print\:shadow-none { box-shadow: none !important; }
  .print\:rounded-none { border-radius: 0 !important; }
  .print\:p-6 { padding: 1.5rem !important; }
  .print\:p-4 { padding: 1rem !important; }
  .print\:p-3 { padding: 0.75rem !important; }
  .print\:p-2 { padding: 0.5rem !important; }
  .print\:px-4 { padding-left: 1rem !important; padding-right: 1rem !important; }
  .print\:py-3 { padding-top: 0.75rem !important; padding-bottom: 0.75rem !important; }
  .print\:py-2 { padding-top: 0.5rem !important; padding-bottom: 0.5rem !important; }
  .print\:py-6 { padding-top: 1.5rem !important; padding-bottom: 1.5rem !important; }
  .print\:mb-6 { margin-bottom: 1.5rem !important; }
  .print\:mb-4 { margin-bottom: 1rem !important; }
  .print\:mb-3 { margin-bottom: 0.75rem !important; }
  .print\:mt-8 { margin-top: 2rem !important; }
  .print\:mt-40 { margin-top: 160px !important; }
  .print\:mt-6 { margin-top: 1.5rem !important; }
  .print\:mt-3 { margin-top: 0.75rem !important; }
  .print\:mt-1 { margin-top: 0.25rem !important; }
  .print\:pt-3 { padding-top: 0.75rem !important; }
  .print\:pt-2 { padding-top: 0.5rem !important; }
  .print\:pb-4 { padding-bottom: 1rem !important; }
  .print\:pb-2 { padding-bottom: 0.5rem !important; }
  .print\:gap-4 { gap: 1rem !important; }
  .print\:gap-6 { gap: 1.5rem !important; }
  .print\:space-y-2 > * + * { margin-top: 0.5rem !important; }
  .print\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
  .print\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)) !important; }
  .print\:text-center { text-align: center !important; }
  .print\:h-16 { height: 4rem !important; }
  .print\:overflow-visible { overflow: visible !important; }
  .print\:hover\:bg-transparent:hover { background-color: transparent !important; }
  .print\:text-gray-600 { color: #4b5563 !important; }
  .print\:text-xs { font-size: 10pt !important; }
}

@page {
  margin: 1cm;
  size: A4;
}
</style>

<script src="<?= base_url(); ?>/app/assets/js/nota_despacho.js"></script>

<?php footerAdmin($data); ?>
