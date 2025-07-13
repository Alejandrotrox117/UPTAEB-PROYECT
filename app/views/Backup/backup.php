<?php headerAdmin($data); ?>

<!-- Campos ocultos para información del usuario -->
<input type="hidden" id="usuarioAuthRolNombre" value="<?php echo htmlspecialchars($_SESSION['user']['rol_nombre'] ?? 'usuario'); ?>">
<input type="hidden" id="usuarioAuthRolId" value="<?php echo htmlspecialchars($_SESSION['user']['idrol'] ?? '0'); ?>">

<!-- Main Content -->
<main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 bg-gray-100">
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
        <h2 class="text-xl font-semibold text-gray-800">
            Hola, <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?> 👋
        </h2>
    </div>

    <!-- Header -->
    <div class="mt-0 sm:mt-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Gestión de Backups</h1>
        <p class="text-green-600 text-base md:text-lg">Administración de copias de seguridad del sistema</p>
    </div>

    <!-- Controles Principales -->
    <div class="bg-white p-4 rounded-lg shadow-md mb-6 mt-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">
                    <i class="fas fa-shield-alt mr-2 text-green-600"></i>
                    Opciones de Respaldo
                </h3>
                <p class="text-sm text-gray-600">Seleccione el tipo de backup que desea realizar</p>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-3">
                <button id="btnBackupCompleto" 
                        class="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors duration-200 flex items-center text-sm font-medium shadow-md">
                    <i class="fas fa-database mr-2"></i>
                    Backup Completo
                </button>
                
                <button id="btnBackupTabla" 
                        class="px-6 py-3 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 transition-colors duration-200 flex items-center text-sm font-medium shadow-md">
                    <i class="fas fa-table mr-2"></i>
                    Backup por Tabla
                </button>
                
                <button id="btnImportarDB" 
                        class="px-6 py-3 bg-orange-500 text-black rounded-lg hover:bg-orange-600 transition-colors duration-200 flex items-center text-sm font-medium shadow-md">
                    <i class="fas fa-upload mr-2"></i>
                    Importar DB
                </button>
                
                <button id="btnActualizarLista" 
                        class="px-4 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200 flex items-center text-sm" 
                        title="Actualizar lista">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Información del Sistema -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow-md">
            <div class="flex items-center">
                <div class="bg-blue-100 p-3 rounded-full mr-4">
                    <i class="fas fa-database text-blue-600"></i>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-500">Total de Backups</h4>
                    <p id="totalBackups" class="text-2xl font-bold text-gray-900">-</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-4 rounded-lg shadow-md">
            <div class="flex items-center">
                <div class="bg-green-100 p-3 rounded-full mr-4">
                    <i class="fas fa-clock text-green-600"></i>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-500">Último Backup</h4>
                    <p id="ultimoBackup" class="text-lg font-semibold text-gray-900">-</p>
                </div>
            </div>
        </div>
        
    </div>

    <!-- Tabla de Backups -->
    <div class="bg-white p-4 md:p-6 mt-6 rounded-2xl shadow-lg">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-archive mr-2 text-green-600"></i>
                    Historial de Backups
                </h3>
                <p class="text-sm text-gray-600 mt-1">Lista de todas las copias de seguridad disponibles</p>
            </div>
        </div>
        
        <div class="overflow-x-auto w-full relative" style="position: relative; min-height: 300px;">
            <table id="tablaBackups" class="display stripe hover responsive nowrap w-full min-w-full">
                <thead>
                    <tr class="text-gray-600 text-xs uppercase tracking-wider bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-3 text-left">Archivo</th>
                        <th class="px-4 py-3 text-left">Tipo</th>
                        <th class="px-4 py-3 text-left">Tamaño</th>
                        <th class="px-4 py-3 text-left">Fecha</th>
                        <th class="px-4 py-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm divide-y divide-gray-200">
                    <!-- Datos se cargan dinámicamente -->
                </tbody>
            </table>
            
            <!-- Loader -->
            <div id="loaderTableBackups" class="hidden absolute inset-0 bg-white bg-opacity-80 z-10">
                <div class="flex justify-center items-center h-full">
                    <div class="bg-white p-5 rounded-lg shadow-md flex items-center">
                        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-green-500 mr-3"></div>
                        <span class="text-lg font-medium text-gray-700">Cargando backups...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- MODAL BACKUP POR TABLA -->
<div id="modalBackupTabla" 
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 transform scale-95 transition-transform duration-300">
        
        <!-- Header del Modal -->
        <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-purple-50">
            <div class="flex items-center">
                <div class="bg-indigo-100 p-3 rounded-full mr-4">
                    <i class="fas fa-table text-indigo-600"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Backup por Tabla</h3>
                    <p class="text-sm text-gray-600">Seleccione la tabla a respaldar</p>
                </div>
            </div>
            <button id="btnCerrarModalTabla" 
                    class="text-gray-400 hover:text-gray-600 transition-colors duration-200 p-2 hover:bg-gray-100 rounded-full">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Contenido del Modal -->
        <div class="p-6">
            <form id="formBackupTabla">
                <div class="mb-4">
                    <label for="selectTabla" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-database mr-1"></i> Seleccionar Tabla
                    </label>
                    <select id="selectTabla" name="tabla" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Seleccione una tabla...</option>
                    </select>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-500 mt-0.5 mr-2"></i>
                        <div class="text-sm text-blue-700">
                            <p class="font-medium">Información importante:</p>
                            <p>El backup incluirá la estructura y datos de la tabla seleccionada.</p>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Footer del Modal -->
        <div class="flex justify-end gap-3 p-6 border-t border-gray-200 bg-gray-50">
            <button id="btnCancelarBackupTabla" 
                    class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200 flex items-center">
                <i class="fas fa-times mr-2"></i>
                Cancelar
            </button>
            <button id="btnConfirmarBackupTabla" 
                    class="px-4 py-2 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 transition-colors duration-200 flex items-center">
                <i class="fas fa-check mr-2"></i>
                Crear Backup
            </button>
        </div>
    </div>
</div>

<!-- MODAL IMPORTAR BASE DE DATOS -->
<div id="modalImportarDB" 
     class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 transform scale-95 transition-transform duration-300">
        
        <!-- Header del Modal -->
        <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-gradient-to-r from-orange-50 to-yellow-50">
            <div class="flex items-center">
                <div class="bg-orange-100 p-3 rounded-full mr-4">
                    <i class="fas fa-upload text-orange-600"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-black-100">Importar Base de Datos</h3>
                    <p class="text-sm text-gray-600">Selecciona un archivo SQL para importar</p>
                </div>
            </div>
            <button id="btnCerrarModalImportar" class="text-gray-400 hover:text-gray-600 text-xl p-1">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Contenido del Modal -->
        <form id="formImportarDB" enctype="multipart/form-data">
            <div class="p-6">
                <!-- Advertencia -->
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-4">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-orange-500 mt-0.5 mr-2"></i>
                        <div class="text-sm text-orange-700">
                            <p class="font-medium">¡Advertencia!</p>
                            <p>Esta acción sobrescribirá todos los datos actuales. Asegúrate de tener un backup antes de continuar.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Selección de archivo -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Archivo SQL (.sql)
                    </label>
                    <input type="file" 
                           id="archivoSQL" 
                           name="archivo" 
                           accept=".sql"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Solo archivos .sql permitidos</p>
                </div>
                
                <!-- Opciones de importación -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Base de datos de destino</label>
                    <select id="selectBaseDatos" name="base_datos" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="bd_pda">BD Principal (bd_pda)</option>
                        <option value="bd_pda_seguridad">BD Seguridad (bd_pda_seguridad)</option>
                        <option value="ambas">Ambas bases de datos</option>
                    </select>
                </div>
            </div>
            
            <!-- Footer del Modal -->
            <div class="flex justify-end gap-3 p-6 border-t border-gray-200 bg-gray-50">
                <button type="button" id="btnCancelarImportar" 
                        class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200 flex items-center">
                    <i class="fas fa-times mr-2"></i>
                    Cancelar
                </button>
                <button type="submit" id="btnConfirmarImportar" 
                        class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors duration-200 flex items-center">
                    <i class="fas fa-upload mr-2"></i>
                    Importar
                </button>
            </div>
        </form>
    </div>
</div>

<?php footerAdmin($data); ?>

<!-- Scripts específicos -->
<script type="module" src="<?= base_url(); ?>app/assets/js/functions_backup.js"></script>