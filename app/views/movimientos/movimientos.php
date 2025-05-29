<?php


headerAdmin($data); 
$permisos = $data['permisos'] ?? []; 
?>
<!-- Main Content -->
<main class="flex-1 p-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold">Hola, <?= $_SESSION['usuario_nombre'] ?? 'Usuario' ?> 👋</h2>
        <input type="text" placeholder="Search" class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none">
    </div>
    

    <div class="min-h-screen mt-4">
        <h1 class="text-3xl font-bold text-gray-900">Gestión de Movimientos de existencias</h1>
        <p class="text-green-500 text-lg">Movimientos</p>

        <div class="bg-white p-6 mt-6 rounded-2xl shadow-md">
            <div class="flex justify-between items-center mb-4">
                <?php if ($permisos['puede_crear']): ?>
                    <a href="vista copy.html" class="bg-green-500 text-white px-6 py-2 rounded-lg font-semibold">Registrar</a>
                <?php else: ?>
                    <div class="text-gray-500 text-sm">
                        <i class="fas fa-lock mr-2"></i>No tiene permisos para registrar movimientos
                    </div>
                <?php endif; ?>
                <?php if (!$permisos['puede_ver']): ?>
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm">Acceso limitado: Solo puede ver la información básica.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
               
            </div>

            <table id="TablaMovimiento" class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-gray-500 text-sm border-b">
                        <th class="py-2">Nro. Movimiento</th>
                     
                        <th class="py-2">Producto</th>
                        <th class="py-2">Tipo de Movimiento</th>
                        <th class="py-2">Entrada</th>
                        <th class="py-2">Salida</th>
                        <th class="py-2">Stock Resultante</th>
                        <th class="py-2">Estatus</th>
                        <?php if ($permisos['puede_editar'] || $permisos['puede_eliminar']): ?>
                            <th class="py-2">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="text-gray-900">
                </tbody>
            </table>
        </div>
    </div>
</main>

<form id="formMovimiento" class="space-y-4">
  <div>
    <label for="producto" class="block text-sm font-medium">Producto</label>
    <select id="producto" name="producto" class="border rounded w-full py-2 px-3" required>
      <option value="">Seleccione un producto</option>
      <!-- Opciones dinámicas -->
    </select>
  </div>
  <div>
    <label for="tipo_movimiento" class="block text-sm font-medium">Tipo de Movimiento</label>
    <select id="tipo_movimiento" name="tipo_movimiento" class="border rounded w-full py-2 px-3" required>
      <option value="">Seleccione tipo</option>
      <!-- Opciones dinámicas -->
    </select>
  </div>
  <div>
    <label for="cantidad" class="block text-sm font-medium">Cantidad</label>
    <input type="number" id="cantidad" name="cantidad" class="border rounded w-full py-2 px-3" min="0.01" step="0.01" required>
  </div>
  <div>
    <label for="fecha" class="block text-sm font-medium">Fecha</label>
    <input type="date" id="fecha" name="fecha" class="border rounded w-full py-2 px-3" value="<?= date('Y-m-d') ?>" required>
  </div>
  <div>
    <label for="observaciones" class="block text-sm font-medium">Observaciones</label>
    <textarea id="observaciones" name="observaciones" class="border rounded w-full py-2 px-3"></textarea>
  </div>
  <div class="flex justify-end">
    <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">Registrar Movimiento</button>
  </div>
</form>


<!-- Modal Detalle de Movimiento -->

<div id="modalDetalleMovimiento" class="fixed inset-0 z-50 flex items-center justify-center bg-opacity-50 opacity-0 pointer-events-none transparent backdrop-blur-[2px] transition-opacity duration-300">
  <div class="w-full max-w-2xl bg-white rounded-xl shadow-lg">
    <div class="flex items-center justify-between px-6 py-4 bg-gray-50 border-b border-gray-200">
      <h3 class="text-xl font-bold text-gray-800">
        <i class="mr-1 text-indigo-600 fas fa-eye"></i>Detalle de Movimiento
      </h3>
      <button id="cerrarDetalleMovimiento" class="p-1 text-gray-400 transition-colors rounded-full hover:text-gray-600 hover:bg-gray-200">
        <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
      </button>
    </div>
    <div class="px-8 py-6 max-h-[70vh] overflow-y-auto" id="detalleMovimientoContenido">
      <!-- Aquí se cargará el detalle por JS -->
    </div>
    <div class="flex justify-end px-6 py-3 bg-gray-50 border-t border-gray-200">
      <button type="button" id="cerrarDetalleMovimiento2" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition">Cerrar</button>
    </div>
  </div>
</div>
<div id="permisosUsuario" data-permisos='<?= json_encode($permisos) ?>' style="display:none"></div>
<?php footerAdmin($data); ?>