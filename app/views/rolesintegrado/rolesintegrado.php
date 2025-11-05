<?php headerAdmin($data); ?>

<!-- Main Content -->
<main class="flex-1 p-6">
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Hola, <?= $_SESSION['usuario_nombre'] ?? 'Usuario' ?> 👋</h2>
  </div>

  <!-- Estilos para el indicador de arrastrar en el tour -->
  <style>
    .drag-indicator {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-right: 10px;
      color: #888;
      cursor: move;
    }
    .shepherd-draggable {
      cursor: grab;
    }
    .shepherd-draggable:active {
      cursor: grabbing;
    }
    .shepherd-has-title .shepherd-content .shepherd-header {
      display: flex;
      align-items: center;
      padding: 0.75em 0.75em 0;
    }
  </style>

  <div class="min-h-screen mt-4">
    <h1 class="text-3xl font-bold text-gray-900"><?php echo $data['page_title']; ?></h1>
    <p class="text-blue-500 text-lg">Gestión Integral de Módulos y Permisos Específicos</p>
    
    <!-- Resumen de Asignaciones -->
    <div id="resumenContainer" class="bg-white p-6 mt-6 rounded-2xl shadow-md hidden">
      <div class="flex items-center mb-4">
        <i class="fas fa-chart-bar mr-2 text-purple-600 text-xl"></i>
        <h3 class="text-lg font-semibold text-gray-900">Información del Rol</h3>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-blue-50 rounded-lg p-4">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <i class="fas fa-user-tag text-blue-600 text-xl"></i>
            </div>
            <div class="ml-3">
              <p class="text-sm font-medium text-blue-800">Rol Seleccionado</p>
              <p id="rolSeleccionado" class="text-lg font-semibold text-blue-900">-</p>
            </div>
          </div>
        </div>
        <div class="bg-green-50 rounded-lg p-4">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <i class="fas fa-cube text-green-600 text-xl"></i>
            </div>
            <div class="ml-3">
              <p class="text-sm font-medium text-green-800">Módulos con Acceso</p>
              <p id="contadorModulos" class="text-lg font-semibold text-green-900">0</p>
            </div>
          </div>
        </div>
        <div class="bg-yellow-50 rounded-lg p-4">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <i class="fas fa-cogs text-yellow-600 text-xl"></i>
            </div>
            <div class="ml-3">
              <p class="text-sm font-medium text-yellow-800">Permisos Específicos</p>
              <p id="contadorPermisosEspecificos" class="text-lg font-semibold text-yellow-900">0</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Selector de Rol -->
    <div class="bg-white p-6 mt-6 rounded-2xl shadow-md">
      <div class="flex justify-between items-center mb-4">
        <div class="flex items-center">
          <i class="fas fa-user-tag mr-2 text-blue-600 text-xl"></i>
          <h3 class="text-lg font-semibold text-gray-900">Selección de Rol</h3>
        </div>
        <div class="flex space-x-3">
          <button id="btnCancelar" class="bg-gray-500 text-white px-6 py-2 rounded-lg font-semibold hover:bg-gray-600 transition">
            <i class="fas fa-times mr-2"></i>Limpiar
          </button>
          <button id="btnGuardarAsignaciones" class="bg-green-500 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-600 transition">
            <i class="fas fa-save mr-2"></i>Guardar Configuración
          </button>
        </div>
      </div>
      
      <div class="max-w-md">
        <label for="selectRol" class="block text-sm font-medium text-gray-700 mb-2">
          Rol a configurar
        </label>
        <select id="selectRol" class="w-full px-4 py-2 border rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="">Seleccione un rol</option>
        </select>
        <p class="mt-1 text-sm text-gray-500">
          Selecciona el rol para configurar módulos y permisos específicos
        </p>
      </div>
    </div>

    <!-- Contenedor de Módulos con Permisos Específicos -->
    <div id="modulosPermisosContainer" class="bg-white p-6 mt-6 rounded-2xl shadow-md hidden">
      <div class="flex items-center mb-6">
        <i class="fas fa-cogs mr-2 text-purple-600 text-xl"></i>
        <h3 class="text-lg font-semibold text-gray-900">Módulos y Permisos Específicos</h3>
      </div>
      <p class="text-sm text-gray-600 mb-6">
        Para cada módulo, selecciona los permisos específicos que tendrá el rol
      </p>
      
      <div id="listaModulosPermisos" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Los módulos con sus permisos se cargarán aquí -->
        <div class="text-center py-8 text-gray-500 col-span-full">
          <i class="fas fa-cube text-4xl mb-4"></i>
          <p>Selecciona un rol para ver los módulos disponibles</p>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Notification Toast -->
<div id="notificationToast" class="hidden fixed top-4 right-4 z-50">
  <div class="bg-white rounded-lg shadow-lg border border-gray-200 p-4 max-w-sm">
    <div class="flex items-center">
      <div class="flex-shrink-0">
        <i id="notificationIcon" class="text-xl"></i>
      </div>
      <div class="ml-3">
        <p id="notificationMessage" class="text-sm font-medium text-gray-900"></p>
      </div>
      <div class="ml-4 flex-shrink-0">
        <button onclick="hideNotification()" class="text-gray-400 hover:text-gray-600">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Scripts específicos para el tour de ayuda -->
<script src="<?= base_url('app/assets/js/ayuda/rolesintegrado-tour.js'); ?>"></script>

<?php footerAdmin($data); ?>