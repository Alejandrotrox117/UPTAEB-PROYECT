<?php headerAdmin($data); 
$permisos = $data['permisos'] ?? []; ?>

<!-- Main Content -->
<main class="flex-1 p-6">
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Hola, <?= $_SESSION['usuario_nombre'] ?? 'Usuario' ?> 👋</h2>
    <input type="text" placeholder="Search" class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none">
  </div>

  <div class="min-h-screen mt-4">
    <h1 class="text-3xl font-bold text-gray-900">Gestión de Asignaciones de Roles</h1>
    <p class="text-blue-500 text-lg">Asignaciones de Módulos y Permisos</p>
    <!-- Resumen de Asignaciones -->
    <div id="resumenContainer" class="bg-white p-6 mt-6 rounded-2xl shadow-md hidden">
      <div class="flex items-center mb-4">
        <i class="fas fa-chart-bar mr-2 text-purple-600 text-xl"></i>
        <h3 class="text-lg font-semibold text-gray-900">Informacion de los Roles</h3>
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
              <p class="text-sm font-medium text-green-800">Módulos Asignados</p>
              <p id="contadorModulos" class="text-lg font-semibold text-green-900">0</p>
            </div>
          </div>
        </div>
        <div class="bg-yellow-50 rounded-lg p-4">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <i class="fas fa-key text-yellow-600 text-xl"></i>
            </div>
            <div class="ml-3">
              <p class="text-sm font-medium text-yellow-800">Permiso Asignado</p>
              <p id="contadorPermisos" class="text-lg font-semibold text-yellow-900">0</p>
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
            <i class="fas fa-save mr-2"></i>Guardar Asignaciones
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
          Selecciona el rol al que deseas asignar módulos y permisos
        </p>
      </div>
    </div>

    <!-- Loading Spinner
    <div id="loadingSpinner" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-opacity-50 bg-gray-600 backdrop-blur-[2px]">
      <div class="bg-white rounded-xl shadow-lg p-6 max-w-sm mx-auto">
        <div class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <h3 class="text-lg font-medium text-gray-900 mt-4">Cargando...</h3>
          <p class="text-sm text-gray-500 mt-2">Por favor espere mientras procesamos la información</p>
        </div>
      </div>
    </div> -->

    <!-- Contenedor de Asignaciones -->
    <div id="asignacionesContainer" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
      <!-- Módulos -->
      <div class="bg-white p-6 rounded-2xl shadow-md max-h-[90vh]  ">
        <div class="flex items-center mb-4">
          <i class="fas fa-cube mr-2 text-green-600 text-xl"></i>
          <h3 class="text-lg font-semibold text-gray-900">Módulos del Sistema</h3>
        </div>
        <p class="text-sm text-gray-600 mb-4">
          Selecciona los módulos a los que tendrá acceso el rol
        </p>
        <div id="modulosContainer" class="space-y-3 max-h-[70vh] overflow-y-auto">
          <!-- Los módulos se cargarán dinámicamente aquí -->
          <div class="text-center py-8 text-gray-500">
            <i class="fas fa-cube text-4xl mb-4"></i>
            <p>Cargando módulos disponibles...</p>
          </div>
        </div>
      </div>

      <!-- Permisos -->
      <div class="bg-white p-6 rounded-2xl shadow-md">
        <div class="flex items-center mb-4">
          <i class="fas fa-key mr-2 text-yellow-600 text-xl"></i>
          <h3 class="text-lg font-semibold text-gray-900">Permisos del Sistema</h3>
        </div>
        <p class="text-sm text-gray-600 mb-4">
          Selecciona los permisos específicos para el rol
        </p>
        <div id="permisosContainer" class="space-y-3 max-h-96 overflow-y-auto">
          <!-- Los permisos se cargarán dinámicamente aquí -->
          <div class="text-center py-8 text-gray-500">
            <i class="fas fa-key text-4xl mb-4"></i>
            <p>Cargando permisos disponibles...</p>
          </div>
        </div>
      </div>
    </div>

    
  </div>
</main>
</div>

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

<?php footerAdmin($data); ?>