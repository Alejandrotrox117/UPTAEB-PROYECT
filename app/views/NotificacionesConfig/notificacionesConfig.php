<?php headerAdmin($data); ?>

<main class="flex-1 p-6 mt-16 lg:mt-0">
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-bell mr-2 text-green-600"></i>
                Configuración de Notificaciones
            </h1>
            <p class="text-gray-600 mt-2">Gestiona qué notificaciones recibe cada rol en tiempo real</p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="mb-6">
                <label for="select-rol" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-user-tag mr-1"></i>
                    Seleccionar Rol
                </label>
                <select id="select-rol" class="w-full md:w-1/2 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    <option value="">Cargando roles...</option>
                </select>
                <p class="mt-1 text-sm text-gray-500">
                    Configura qué tipos de notificaciones recibirá este rol vía WebSocket
                </p>
            </div>

            <div id="mensaje-seleccionar" class="text-center py-12 text-gray-500">
                <i class="fas fa-bell text-6xl text-gray-300 mb-4"></i>
                <p class="text-lg font-medium">Selecciona un rol para configurar sus notificaciones</p>
                <p class="text-sm mt-2">Las notificaciones se envían en tiempo real vía WebSocket</p>
            </div>

            <div id="config-container" class="hidden">
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-500"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                Marca las notificaciones que <strong>SÍ</strong> quieres que reciba este rol. 
                                Las notificaciones se envían instantáneamente vía WebSocket.
                            </p>
                        </div>
                    </div>
                </div>

                <div id="modulos-container" class="space-y-6"></div>

                <div class="mt-8 flex justify-end space-x-3">
                    <button id="btn-cancelar" class="px-6 py-2 bg-gray-500 text-white font-medium rounded-lg hover:bg-gray-600 transition">
                        <i class="fas fa-times mr-2"></i>
                        Cancelar
                    </button>
                    <button id="btn-guardar" class="px-6 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-save mr-2"></i>
                        Guardar Configuración
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-6 bg-green-50 border border-green-200 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-green-800 mb-2">
                <i class="fas fa-question-circle mr-1"></i>
                ¿Cómo funciona?
            </h3>
            <ul class="text-sm text-green-700 space-y-1">
                <li><i class="fas fa-check text-green-600 mr-2"></i>Selecciona un rol y marca las notificaciones que debe recibir</li>
                <li><i class="fas fa-check text-green-600 mr-2"></i>Los usuarios con ese rol recibirán notificaciones en tiempo real</li>
                <li><i class="fas fa-check text-green-600 mr-2"></i>Las notificaciones se filtran automáticamente por permisos del módulo</li>
            </ul>
        </div>
    </div>
</main>

<?php footerAdmin($data); ?>
