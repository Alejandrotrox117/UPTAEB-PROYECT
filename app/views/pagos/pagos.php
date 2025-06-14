<?php headerAdmin($data);?>

<input type="hidden" id="usuarioAuthRolNombre" value="<?php echo htmlspecialchars(strtolower($rolUsuarioAutenticado)); ?>">
<input type="hidden" id="usuarioAuthRolId" value="<?php echo htmlspecialchars($idRolUsuarioAutenticado); ?>">

<main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 bg-gray-100">
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Hola, <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?> 👋</h2>
    </div>

    <div class="mt-0 sm:mt-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900"><?php echo $data['page_title']; ?></h1>
        <p class="text-green-600 text-base md:text-lg">Gestión de pagos del sistema</p>
    </div>

    <div class="bg-white p-4 md:p-6 mt-6 rounded-2xl shadow-lg">
        <div class="flex justify-between items-center mb-6">
            <button id="btnAbrirModalRegistrarPago"
                class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 md:px-6 rounded-lg font-semibold shadow text-sm md:text-base">
                <i class="fas fa-plus mr-1 md:mr-2"></i> Registrar Pago
            </button>
        </div>

        <div class="overflow-x-auto w-full relative">
            <table id="TablaPagos" class="display stripe hover responsive nowrap fuente-tabla-pequena" style="width:100%; min-width: 900px;">
                <thead>
                    <tr class="text-gray-600 text-xs uppercase tracking-wider bg-gray-50 border-b border-gray-200">
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm divide-y divide-gray-200">
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Modal Registrar Pago -->
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
                <label class="block text-sm font-medium text-gray-700 mb-3">Tipo de Pago <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <label class="flex items-center">
                        <input type="radio" name="tipoPago" value="compra" class="mr-2 text-green-600 focus:ring-green-500">
                        <span class="text-sm">Compra</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="tipoPago" value="venta" class="mr-2 text-green-600 focus:ring-green-500">
                        <span class="text-sm">Venta</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="tipoPago" value="sueldo" class="mr-2 text-green-600 focus:ring-green-500">
                        <span class="text-sm">Sueldo</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="tipoPago" value="otro" class="mr-2 text-green-600 focus:ring-green-500">
                        <span class="text-sm">Otro</span>
                    </label>
                </div>
                <div class="text-red-500 text-xs mt-1 error-message"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                
                <!-- Select para Compras (oculto inicialmente) -->
                <div id="containerCompras" class="md:col-span-2 hidden">
                    <label for="pagoCompra" class="block text-sm font-medium text-gray-700 mb-1">Seleccionar Compra <span class="text-red-500">*</span></label>
                    <select id="pagoCompra" name="idcompra" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm">
                        <option value="">Seleccionar compra...</option>
                    </select>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>

                <!-- Select para Ventas (oculto inicialmente) -->
                <div id="containerVentas" class="md:col-span-2 hidden">
                    <label for="pagoVenta" class="block text-sm font-medium text-gray-700 mb-1">Seleccionar Venta <span class="text-red-500">*</span></label>
                    <select id="pagoVenta" name="idventa" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm">
                        <option value="">Seleccionar venta...</option>
                    </select>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>

                <!-- Select para Sueldos (oculto inicialmente) -->
                <div id="containerSueldos" class="md:col-span-2 hidden">
                    <label for="pagoSueldo" class="block text-sm font-medium text-gray-700 mb-1">Seleccionar Sueldo Temporal <span class="text-red-500">*</span></label>
                    <select id="pagoSueldo" name="idsueldotemp" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm">
                        <option value="">Seleccionar sueldo temporal...</option>
                    </select>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>

                <!-- Información del destinatario (se llena automáticamente) -->
                <div id="containerDestinatario" class="md:col-span-2 hidden">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h4 class="text-sm font-medium text-blue-800 mb-2">Información del Destinatario</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-blue-600 font-medium">Nombre:</span>
                                <span id="destinatarioNombre" class="text-blue-800">-</span>
                            </div>
                            <div>
                                <span class="text-blue-600 font-medium">Identificación:</span>
                                <span id="destinatarioIdentificacion" class="text-blue-800">-</span>
                            </div>
                            <div>
                                <span class="text-blue-600 font-medium">Total:</span>
                                <span id="destinatarioTotal" class="text-blue-800 font-bold">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Descripción para "Otro" tipo de pago -->
                <div id="containerDescripcion" class="md:col-span-2 hidden">
                    <label for="pagoDescripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción <span class="text-red-500">*</span></label>
                    <textarea id="pagoDescripcion" name="descripcion" rows="3" placeholder="Descripción del pago..." class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm"></textarea>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>

                <!-- Campos comunes -->
                <div>
                    <label for="pagoMonto" class="block text-sm font-medium text-gray-700 mb-1">Monto <span class="text-red-500">*</span></label>
                    <input type="number" id="pagoMonto" name="monto" step="0.01" min="0" placeholder="0.00" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm">
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>

                <div>
                    <label for="pagoMetodoPago" class="block text-sm font-medium text-gray-700 mb-1">Método de Pago <span class="text-red-500">*</span></label>
                    <select id="pagoMetodoPago" name="idtipo_pago" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm">
                        <option value="">Seleccionar método...</option>
                        <option value="1">Efectivo</option>
                        <option value="2">Transferencia</option>
                        <option value="3">Tarjeta de Débito</option>
                        <option value="4">Tarjeta de Crédito</option>
                        <option value="5">Cheque</option>
                    </select>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>

                <div>
                    <label for="pagoReferencia" class="block text-sm font-medium text-gray-700 mb-1">Referencia</label>
                    <input type="text" id="pagoReferencia" name="referencia" placeholder="Número de referencia..." class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm">
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>

                <div>
                    <label for="pagoFecha" class="block text-sm font-medium text-gray-700 mb-1">Fecha de Pago <span class="text-red-500">*</span></label>
                    <input type="date" id="pagoFecha" name="fecha_pago" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm">
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>

                <div class="md:col-span-2">
                    <label for="pagoObservaciones" class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea id="pagoObservaciones" name="observaciones" rows="3" placeholder="Observaciones adicionales..." class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm"></textarea>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
            </div>
        </form>
        <div class="bg-gray-50 px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
            <button type="button" id="btnCancelarModalRegistrar" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm md:text-base font-medium">
                Cancelar
            </button>
            <button type="submit" id="btnGuardarPago" form="formRegistrarPago" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm md:text-base font-medium">
                <i class="fas fa-save mr-1 md:mr-2"></i> Guardar Pago
            </button>
        </div>
    </div>
</div>

<!-- Modal Ver Pago -->
<div id="modalVerPago" class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-11/12 max-w-4xl max-h-[95vh]">
        <div class="bg-white rounded-lg shadow-xl w-full max-h-[calc(95vh-80px)] sm:max-h-[90vh]">
            <div class="flex items-center justify-between p-4 md:p-6 border-b border-gray-200">
                <h3 class="text-lg md:text-xl font-semibold text-gray-900">
                    <i class="fas fa-eye mr-2 text-green-600"></i>
                    Detalles del Pago
                </h3>
                <button id="btnCerrarModalVer" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-4 md:p-6 overflow-y-auto max-h-[calc(95vh-180px)] sm:max-h-[70vh]">
                <div class="mb-6">
                    <h4 class="text-base md:text-lg font-medium text-gray-900 mb-4">
                        <i class="fas fa-credit-card mr-2 text-green-600"></i>
                        Información del Pago
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Tipo de Pago</label>
                            <p id="verPagoTipo" class="text-gray-900 font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Destinatario</label>
                            <p id="verPagoDestinatario" class="text-gray-900 font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Monto</label>
                            <p id="verPagoMonto" class="text-gray-900 font-medium text-lg text-green-600">-</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Método de Pago</label>
                            <p id="verPagoMetodo" class="text-gray-900 font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Referencia</label>
                            <p id="verPagoReferencia" class="text-gray-900 font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Fecha de Pago</label>
                            <p id="verPagoFecha" class="text-gray-900 font-medium">-</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Estatus</label>
                            <p id="verPagoEstatus" class="text-gray-900 font-medium">-</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-500">Observaciones</label>
                            <p id="verPagoObservaciones" class="text-gray-900 font-medium">-</p>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end pt-4 md:pt-6 border-t border-gray-200">
                    <button type="button" id="btnCerrarModalVer2"
                            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors duration-200 text-sm md:text-base">
                        <i class="fas fa-times mr-1 md:mr-2"></i>
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php footerAdmin($data); ?>