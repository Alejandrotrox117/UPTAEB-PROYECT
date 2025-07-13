<?php headerAdmin($data);?>

<input type="hidden" id="usuarioAuthRolNombre" value="<?php echo htmlspecialchars(strtolower($rolUsuarioAutenticado)); ?>">
<input type="hidden" id="usuarioAuthRolId" value="<?php echo htmlspecialchars($idRolUsuarioAutenticado); ?>">

<main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 bg-gray-100">
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Hola, <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?> 👋</h2>
    </div>

    <div class="mt-0 sm:mt-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900"><?php echo $data['page_title']; ?></h1>
        <p class="text-green-600 text-base md:text-lg">Listado de sueldos registrados en el sistema</p>
    </div>

    <div class="bg-white p-4 md:p-6 mt-6 rounded-2xl shadow-lg">
        <div class="flex justify-between items-center mb-6">
            <button id="btnAbrirModalRegistrarSueldo"
                class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 md:px-6 rounded-lg font-semibold shadow text-sm md:text-base">
                <i class="mr-1 md:mr-2"></i> Registrar Sueldo
            </button>
        </div>

        <div class="overflow-x-auto w-full relative">
            <table id="TablaSueldos" class="display stripe hover responsive nowrap fuente-tabla-pequena" style="width:100%; min-width: 900px;">
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

<!-- Modal Registrar Sueldo -->
<div id="modalRegistrarSueldo"
    class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-10/11 max-w-4xl max-h-[95vh]">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 id="tituloModalRegistrar" class="text-xl md:text-2xl font-bold text-gray-800">Registrar Sueldo</h3>
            <button id="btnCerrarModalRegistrar" type="button" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 md:h-8 md:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form id="formRegistrarSueldo" class="px-4 md:px-8 py-6 max-h-[calc(70vh-120px)] sm:max-h-[60vh] overflow-y-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <label for="tipoPersona" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Persona *</label>
                    <select id="tipoPersona" name="tipo_persona" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm">
                        <option value="">Seleccionar...</option>
                        <option value="persona">Persona</option>
                        <option value="empleado">Empleado</option>
                    </select>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="sueldoPersonaEmpleado" class="block text-sm font-medium text-gray-700 mb-1">Persona/Empleado *</label>
                    <select id="sueldoPersonaEmpleado" name="persona_empleado" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm" disabled>
                        <option value="">Seleccionar primero el tipo...</option>
                    </select>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="sueldoMonto" class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
                    <input type="number" id="sueldoMonto" name="monto" step="0.01" min="0" 
                           class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm"
                           placeholder="Ingrese el monto del sueldo">
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="sueldoMoneda" class="block text-sm font-medium text-gray-700 mb-1">Moneda *</label>
                    <select id="sueldoMoneda" name="idmoneda" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm">
                        <option value="">Seleccionar moneda...</option>
                    </select>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div class="md:col-span-2">
                    <label for="sueldoObservacion" class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea id="sueldoObservacion" name="observacion" rows="3" 
                              class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm resize-none"
                              placeholder="Observaciones adicionales..."></textarea>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
                <button id="btnCancelarRegistroSueldo" type="button" 
                        class="w-full sm:w-auto px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium text-sm transition-colors">
                    Cancelar
                </button>
                <button id="btnGuardarSueldo" type="submit" 
                        class="w-full sm:w-auto px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 font-medium text-sm transition-colors">
                    Registrar Sueldo
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Actualizar Sueldo -->
<div id="modalActualizarSueldo"
    class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-10/11 max-w-4xl max-h-[95vh]">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 id="tituloModalActualizar" class="text-xl md:text-2xl font-bold text-gray-800">Actualizar Sueldo</h3>
            <button id="btnCerrarModalActualizar" type="button" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 md:h-8 md:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form id="formActualizarSueldo" class="px-4 md:px-8 py-6 max-h-[calc(70vh-120px)] sm:max-h-[60vh] overflow-y-auto">
            <input type="hidden" id="idSueldoActualizar" name="idsueldo">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <label for="tipoPersonaActualizar" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Persona *</label>
                    <select id="tipoPersonaActualizar" name="tipo_persona" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm">
                        <option value="">Seleccionar...</option>
                        <option value="persona">Persona</option>
                        <option value="empleado">Empleado</option>
                    </select>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="sueldoPersonaEmpleadoActualizar" class="block text-sm font-medium text-gray-700 mb-1">Persona/Empleado *</label>
                    <select id="sueldoPersonaEmpleadoActualizar" name="persona_empleado" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm" disabled>
                        <option value="">Seleccionar primero el tipo...</option>
                    </select>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="sueldoMontoActualizar" class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
                    <input type="number" id="sueldoMontoActualizar" name="monto" step="0.01" min="0" 
                           class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm"
                           placeholder="Ingrese el monto del sueldo">
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div>
                    <label for="sueldoMonedaActualizar" class="block text-sm font-medium text-gray-700 mb-1">Moneda *</label>
                    <select id="sueldoMonedaActualizar" name="idmoneda" class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm">
                        <option value="">Seleccionar moneda...</option>
                    </select>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
                <div class="md:col-span-2">
                    <label for="sueldoObservacionActualizar" class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                    <textarea id="sueldoObservacionActualizar" name="observacion" rows="3" 
                              class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-400 text-sm resize-none"
                              placeholder="Observaciones adicionales..."></textarea>
                    <div class="text-red-500 text-xs mt-1 error-message"></div>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
                <button id="btnCancelarActualizacionSueldo" type="button" 
                        class="w-full sm:w-auto px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium text-sm transition-colors">
                    Cancelar
                </button>
                <button id="btnActualizarSueldo" type="submit" 
                        class="w-full sm:w-auto px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 font-medium text-sm transition-colors">
                    Actualizar Sueldo
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ver Sueldo -->
<div id="modalVerSueldo"
    class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-10/11 max-w-3xl max-h-[95vh]">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-xl md:text-2xl font-bold text-gray-800">Detalles del Sueldo</h3>
            <button id="btnCerrarModalVer" type="button" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 md:h-8 md:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div id="contenidoModalVer" class="px-4 md:px-8 py-6 max-h-[calc(70vh-120px)] sm:max-h-[60vh] overflow-y-auto">
            <!-- El contenido se llenará dinámicamente -->
        </div>
        <div class="px-4 md:px-6 py-4 border-t border-gray-200 flex justify-end">
            <button id="btnCerrarVer" type="button" 
                    class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 font-medium text-sm transition-colors">
                Cerrar
            </button>
        </div>
    </div>
</div>

<?php footerAdmin($data); ?>
