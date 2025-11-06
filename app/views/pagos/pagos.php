<?php 
use App\Helpers\PermisosModuloVerificar;

headerAdmin($data);
//PERMISOS DEL USUARIO PARA EL MÓDULO 'PAGOS'
$permisos = PermisosModuloVerificar::getPermisosUsuarioModulo('pagos');
?>
<?= renderJavaScriptData('permisosPagos', $permisos); ?>

<main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 bg-gray-100">
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Hola, <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?> 👋</h2>
    </div>

    <div class="mt-0 sm:mt-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900"><?php echo $data['page_title']; ?></h1>
        <p class="text-green-600 text-base md:text-lg">Gestión de pagos del sistema</p>
    </div>

    <!-- CONTENIDO SOLO SI TIENE PERMISO PARA VER -->
    <?php if (!$permisos['ver']): ?>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 mt-6 rounded-r-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700 font-medium">
                    <strong>Acceso Restringido:</strong> No tienes permisos para ver la lista de pagos.
                </p>
                <p class="text-xs text-yellow-600 mt-1">
                    Contacta al administrador del sistema si necesitas acceso a este módulo.
                </p>
            </div>
        </div>
    </div>
    <?php else: ?>

    <div class="bg-white p-4 md:p-6 mt-6 rounded-2xl shadow-lg">
        <div class="flex justify-between items-center mb-6">
            <!-- CREAR SOLO SI TIENE PERMISOS -->
            <?php if ($permisos['crear']): ?>
            <button id="btnAbrirModalRegistrarPago"
                class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 md:px-6 rounded-lg font-semibold shadow text-sm md:text-base">
                <i class="mr-1 md:mr-2"></i> Registrar Pago
            </button>
            <?php else: ?>
            <div class="bg-gray-100 px-4 py-2 md:px-6 rounded-lg text-gray-500 text-sm md:text-base">
                <i class="fas fa-lock mr-1 md:mr-2"></i> Sin permisos para crear
            </div>
            <?php endif; ?>
        </div>

        <div class="overflow-x-auto w-full relative">
            <table id="TablaPagos" class="display stripe hover responsive nowrap fuente-tabla-pequena" style="width:100%; min-width: 900px;">
                <thead>
                    <tr>
                        <th>Destinatario</th>
                        <th>Tipo</th>
                        <th>Monto</th>
                        <th>Método</th>
                        <th>Fecha</th>
                        <th>Estatus</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm divide-y divide-gray-200">
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</main>

<!-- MODALES SOLO SI TIENE PERMISOS CORRESPONDIENTES -->

<?php if ($permisos['crear'] || $permisos['editar']): ?>
<!-- Modal Registrar/Editar Pago -->
<div id="modalRegistrarPago"
    class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-10/11 max-w-4xl max-h-[95vh]">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 id="tituloModalRegistrar" class="text-xl md:text-2xl font-bold text-gray-800">Registrar Pago</h3>
            <button id="btnCerrarModalRegistrar" type="button" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 md:h-8 md:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form id="formRegistrarPago" class="px-4 md:px-8 py-6 max-h-[calc(70vh-120px)] sm:max-h-[60vh] overflow-y-auto">
            
            <!-- Tipo de Pago -->
            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                <h4 class="text-lg font-semibold text-gray-800 mb-3">Tipo de Pago</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="tipoPago" value="compra" class="sr-only peer">
                        <div class="w-full p-3 border-2 border-gray-200 rounded-lg peer-checked:border-green-500 peer-checked:bg-green-50 transition-all">
                            <div class="flex items-center justify-center">
                                <i class="fas fa-shopping-cart text-blue-500 text-xl mb-1"></i>
                            </div>
                            <p class="text-center text-sm font-medium text-gray-700">Compra</p>
                        </div>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="tipoPago" value="venta" class="sr-only peer">
                        <div class="w-full p-3 border-2 border-gray-200 rounded-lg peer-checked:border-green-500 peer-checked:bg-green-50 transition-all">
                            <div class="flex items-center justify-center">
                                <i class="fas fa-cash-register text-green-500 text-xl mb-1"></i>
                            </div>
                            <p class="text-center text-sm font-medium text-gray-700">Venta</p>
                        </div>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="tipoPago" value="sueldo" class="sr-only peer">
                        <div class="w-full p-3 border-2 border-gray-200 rounded-lg peer-checked:border-green-500 peer-checked:bg-green-50 transition-all">
                            <div class="flex items-center justify-center">
                                <i class="fas fa-wallet text-purple-500 text-xl mb-1"></i>
                            </div>
                            <p class="text-center text-sm font-medium text-gray-700">Sueldo</p>
                        </div>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="tipoPago" value="otro" class="sr-only peer">
                        <div class="w-full p-3 border-2 border-gray-200 rounded-lg peer-checked:border-green-500 peer-checked:bg-green-50 transition-all">
                            <div class="flex items-center justify-center">
                                <i class="fas fa-ellipsis-h text-gray-500 text-xl mb-1"></i>
                            </div>
                            <p class="text-center text-sm font-medium text-gray-700">Otro</p>
                        </div>
                    </label>
                </div>
                <div id="error-tipoPago" class="text-red-500 text-sm mt-1 hidden"></div>
            </div>

            <!-- Campos dinámicos según tipo -->
            <div id="containerCompras" class="mb-4 hidden">
                <label for="pagoCompra" class="block text-sm font-medium text-gray-700 mb-1">Seleccionar Compra <span class="text-red-500">*</span></label>
                <select id="pagoCompra" name="pagoCompra" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"></select>
                <div id="error-pagoCompra" class="text-red-500 text-sm mt-1 hidden"></div>
            </div>
            <div id="containerVentas" class="mb-4 hidden">
                <label for="pagoVenta" class="block text-sm font-medium text-gray-700 mb-1">Seleccionar Venta <span class="text-red-500">*</span></label>
                <select id="pagoVenta" name="pagoVenta" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"></select>
                <div id="error-pagoVenta" class="text-red-500 text-sm mt-1 hidden"></div>
            </div>
            <div id="containerSueldos" class="mb-4 hidden">
                <label for="pagoSueldo" class="block text-sm font-medium text-gray-700 mb-1">Seleccionar Sueldo <span class="text-red-500">*</span></label>
                <select id="pagoSueldo" name="pagoSueldo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"></select>
                <div id="error-pagoSueldo" class="text-red-500 text-sm mt-1 hidden"></div>
            </div>
            <div id="containerDescripcion" class="mb-4 hidden">
                <label for="pagoDescripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción del Pago <span class="text-red-500">*</span></label>
                <textarea id="pagoDescripcion" name="pagoDescripcion" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" placeholder="Descripción detallada del pago..."></textarea>
                <div id="error-pagoDescripcion" class="text-red-500 text-sm mt-1 hidden"></div>
            </div>

            <!-- Información del destinatario -->
            <div id="containerDestinatario" class="mb-4 p-3 bg-blue-50 rounded-lg border border-blue-200 hidden">
                <h5 class="font-medium text-blue-800 mb-2"><i class="fas fa-info-circle mr-1"></i> Información del Destinatario</h5>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2 text-sm">
                    <div><span class="text-blue-600 font-medium">Nombre:</span> <span id="destinatarioNombre" class="text-gray-800 ml-1">-</span></div>
                    <div><span class="text-blue-600 font-medium">Identificación:</span> <span id="destinatarioIdentificacion" class="text-gray-800 ml-1">-</span></div>
                    <div><span class="text-blue-600 font-medium">Total:</span> <span id="destinatarioTotal" class="text-gray-800 ml-1 font-semibold">-</span></div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <label for="pagoMonto" class="block text-sm font-medium text-gray-700 mb-1">Monto <span class="text-red-500">*</span></label>
                    <div class="relative"><span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">Bs. </span><input type="text" id="pagoMonto" name="pagoMonto" class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" placeholder="0.0000"></div>
                    <div id="error-pagoMonto" class="text-red-500 text-sm mt-1 hidden"></div>
                </div>
                <div>
                    <label for="pagoMetodoPago" class="block text-sm font-medium text-gray-700 mb-1">Método de Pago <span class="text-red-500">*</span></label>
                    <select id="pagoMetodoPago" name="pagoMetodoPago" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"></select>
                    <div id="error-pagoMetodoPago" class="text-red-500 text-sm mt-1 hidden"></div>
                </div>
                <div>
                    <label for="pagoReferencia" class="block text-sm font-medium text-gray-700 mb-1">Referencia</label>
                    <input type="text" id="pagoReferencia" name="pagoReferencia" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" placeholder="Número de referencia o código">
                    <div id="error-pagoReferencia" class="text-red-500 text-sm mt-1 hidden"></div>
                </div>
                <div>
                    <label for="pagoFecha" class="block text-sm font-medium text-gray-700 mb-1">Fecha de Pago <span class="text-red-500">*</span></label>
                    <input type="date" id="pagoFecha" name="pagoFecha" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    <div id="error-pagoFecha" class="text-red-500 text-sm mt-1 hidden"></div>
                </div>
                <div class="md:col-span-2">
                    <label for="pagoObservaciones" class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea id="pagoObservaciones" name="pagoObservaciones" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" placeholder="Observaciones adicionales (opcional)"></textarea>
                    <div id="error-pagoObservaciones" class="text-red-500 text-sm mt-1 hidden"></div>
                </div>
            </div>
        </form>
        <div class="bg-gray-50 px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
            <button type="button" id="btnCancelarModalRegistrar" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm md:text-base font-medium"><i class="fas fa-times mr-1 md:mr-2"></i> Cancelar</button>
            <button type="submit" id="btnGuardarPago" form="formRegistrarPago" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm md:text-base font-medium"><i class="fas fa-save mr-1 md:mr-2"></i> Guardar Pago</button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($permisos['ver']): ?>
<!-- Modal Ver Pago -->
<div id="modalVerPago" class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-11/12 max-w-4xl max-h-[95vh]">
        <div class="bg-white rounded-lg shadow-xl w-full max-h-[calc(95vh-80px)] sm:max-h-[90vh]">
            <div class="flex items-center justify-between p-4 md:p-6 border-b border-gray-200">
                <h3 class="text-xl md:text-2xl font-bold text-gray-800"><i class="fas fa-eye text-green-600 mr-2"></i> Detalles del Pago</h3>
                <button id="btnCerrarModalVer" type="button" class="text-gray-500 hover:text-gray-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 md:h-8 md:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="p-4 md:p-6 overflow-y-auto max-h-[calc(95vh-180px)] sm:max-h-[70vh]">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div class="bg-gray-50 p-4 rounded-lg"><h4 class="text-lg font-semibold text-gray-800 mb-3"><i class="fas fa-info-circle text-blue-500 mr-2"></i> Información Principal</h4><div class="space-y-2"><div class="flex justify-between"><span class="text-gray-600">ID Pago:</span><span id="verPagoId" class="font-medium text-gray-800">-</span></div><div class="flex justify-between"><span class="text-gray-600">Tipo:</span><span id="verPagoTipo" class="font-medium text-gray-800">-</span></div><div class="flex justify-between"><span class="text-gray-600">Destinatario:</span><span id="verPagoDestinatario" class="font-medium text-gray-800">-</span></div><div class="flex justify-between"><span class="text-gray-600">Estatus:</span><span id="verPagoEstatus" class="font-medium">-</span></div></div></div>
                        <div class="bg-green-50 p-4 rounded-lg"><h4 class="text-lg font-semibold text-gray-800 mb-3"><i class="fas fa-dollar-sign text-green-500 mr-2"></i> Información Financiera</h4><div class="space-y-2"><div class="flex justify-between"><span class="text-gray-600">Monto:</span><span id="verPagoMonto" class="font-bold text-green-600 text-lg">-</span></div><div class="flex justify-between"><span class="text-gray-600">Método:</span><span id="verPagoMetodo" class="font-medium text-gray-800">-</span></div><div class="flex justify-between"><span class="text-gray-600">Referencia:</span><span id="verPagoReferencia" class="font-medium text-gray-800">-</span></div></div></div>
                    </div>
                    <div class="space-y-4">
                        <div class="bg-blue-50 p-4 rounded-lg"><h4 class="text-lg font-semibold text-gray-800 mb-3"><i class="fas fa-calendar-alt text-blue-500 mr-2"></i> Fechas</h4><div class="space-y-2"><div class="flex justify-between"><span class="text-gray-600">Fecha de Pago:</span><span id="verPagoFecha" class="font-medium text-gray-800">-</span></div><div class="flex justify-between"><span class="text-gray-600">Fecha de Creación:</span><span id="verPagoFechaCreacion" class="font-medium text-gray-800">-</span></div></div></div>
                        <div class="bg-purple-50 p-4 rounded-lg"><h4 class="text-lg font-semibold text-gray-800 mb-3"><i class="fas fa-user text-purple-500 mr-2"></i> Información de Persona</h4><div class="space-y-2"><div class="flex justify-between"><span class="text-gray-600">Persona Asignada:</span><span id="verPagoPersona" class="font-medium text-gray-800">-</span></div></div></div>
                        <div class="bg-yellow-50 p-4 rounded-lg"><h4 class="text-lg font-semibold text-gray-800 mb-3"><i class="fas fa-sticky-note text-yellow-500 mr-2"></i> Observaciones</h4><p id="verPagoObservaciones" class="text-gray-700 text-sm leading-relaxed">-</p></div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 flex justify-end">
                <button id="btnCerrarModalVerFooter" type="button" class="px-4 py-2 md:px-6 md:py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition text-sm md:text-base font-medium"><i class="fas fa-times mr-1 md:mr-2"></i> Cerrar</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php footerAdmin($data); ?>