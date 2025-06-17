<?php headerAdmin($data); ?>

<!-- Main Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-credit-card mr-3 text-green-600"></i>
                            <?= $data['page_title'] ?>
                        </h1>
                        <p class="mt-1 text-sm text-gray-600"><?= $data['page_content'] ?></p>
                    </div>
                    <div class="mt-4 sm:mt-0">
                        <button id="btnAbrirModalRegistrarTipoPago" 
                                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:outline-none focus:border-green-700 focus:ring focus:ring-green-200 active:bg-green-600 disabled:opacity-25 transition">
                            <i class="fas fa-plus mr-2"></i>
                            Nuevo Tipo de Pago
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Lista de Tipos de Pagos</h3>
                </div>
                <div class="overflow-x-auto">
                    <table id="TablaTiposPagos" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estatus</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Creación</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <!-- Los datos se cargan dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Registrar Tipo de Pago -->
    <div id="modalRegistrarTipoPago" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 lg:w-1/3 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 flex items-center">
                        <i class="fas fa-plus-circle mr-2 text-green-600"></i>
                        Registrar Tipo de Pago
                    </h3>
                    <button id="btnCerrarModalRegistrar" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form id="formRegistrarTipoPago" class="space-y-4">
                    <div>
                        <label for="tipoPagoNombre" class="block text-sm font-medium text-gray-700 mb-1">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="tipoPagoNombre" 
                               name="tipoPagoNombre"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                               placeholder="Ej: Efectivo, Transferencia, etc.">
                        <div id="error-tipoPagoNombre" class="text-red-500 text-xs mt-1 hidden"></div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" 
                                id="btnCancelarModalRegistrar"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            <i class="fas fa-times mr-2"></i>Cancelar
                        </button>
                        <button type="submit" 
                                id="btnGuardarTipoPago"
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                            <i class="fas fa-save mr-2"></i>Guardar Tipo de Pago
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Actualizar Tipo de Pago -->
    <div id="modalActualizarTipoPago" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 lg:w-1/3 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 flex items-center">
                        <i class="fas fa-edit mr-2 text-blue-600"></i>
                        Actualizar Tipo de Pago
                    </h3>
                    <button id="btnCerrarModalActualizar" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form id="formActualizarTipoPago" class="space-y-4">
                    <input type="hidden" id="idTipoPagoActualizar" name="idTipoPagoActualizar">
                    
                    <div>
                        <label for="tipoPagoNombreActualizar" class="block text-sm font-medium text-gray-700 mb-1">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="tipoPagoNombreActualizar" 
                               name="tipoPagoNombreActualizar"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Ej: Efectivo, Transferencia, etc.">
                        <div id="error-tipoPagoNombreActualizar" class="text-red-500 text-xs mt-1 hidden"></div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" 
                                id="btnCancelarModalActualizar"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            <i class="fas fa-times mr-2"></i>Cancelar
                        </button>
                        <button type="submit" 
                                id="btnActualizarTipoPago"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <i class="fas fa-save mr-2"></i>Actualizar Tipo de Pago
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Ver Tipo de Pago -->
    <div id="modalVerTipoPago" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 lg:w-1/3 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 flex items-center">
                        <i class="fas fa-eye mr-2 text-green-600"></i>
                        Información del Tipo de Pago
                    </h3>
                    <button id="btnCerrarModalVer" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="space-y-4">
                    <div class="grid grid-cols-1 gap-4">
                        <div class="bg-gray-50 p-3 rounded-md">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre:</label>
                            <p id="verTipoPagoNombre" class="text-sm text-gray-900 font-semibold"></p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-md">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estatus:</label>
                            <p id="verTipoPagoEstatus" class="text-sm text-gray-900"></p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-md">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Creación:</label>
                            <p id="verTipoPagoFechaCreacion" class="text-sm text-gray-900"></p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-md">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Última Modificación:</label>
                            <p id="verTipoPagoFechaModificacion" class="text-sm text-gray-900"></p>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button id="btnCerrarModalVer2" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            <i class="fas fa-times mr-2"></i>Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
</div>

<?php footerAdmin($data); ?>