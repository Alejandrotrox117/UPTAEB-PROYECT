<?php headerAdmin($data);?>

<input type="hidden" id="usuarioAuthRolNombre" value="<?php echo htmlspecialchars(strtolower($rolUsuarioAutenticado)); ?>">
<input type="hidden" id="usuarioAuthRolId" value="<?php echo htmlspecialchars($idRolUsuarioAutenticado); ?>">

<main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 bg-gray-100">
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Hola, <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?> 👋</h2>
    </div>

    <div class="mt-0 sm:mt-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900"><?php echo $data['page_title']; ?></h1>
        <p class="text-green-600 text-base md:text-lg">Listado de empleados registrados en el sistema</p>
    </div>

    <div class="bg-white p-4 md:p-6 mt-6 rounded-2xl shadow-lg">
        <div class="flex justify-between items-center mb-6">
            <button id="abrirModalBtn"
                class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 md:px-6 rounded-lg font-semibold shadow text-sm md:text-base">
                <i class="mr-1 md:mr-2"></i> Registrar Empleado
            </button>
        </div>

        <div class="overflow-x-auto w-full relative">
            <table id="TablaEmpleado" class="display stripe hover responsive nowrap fuente-tabla-pequena" style="width:100%; min-width: 900px;">
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



<!-- Modal -->
<div id="empleadoModal"
    class="fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-full sm:w-10/11 max-w-4xl max-h-[95vh]">
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 id="tituloModalRegistrar" class="text-xl md:text-2xl font-bold text-gray-800">Registrar Empleado</h3>
            <button id="btnCerrarModalRegistrar" type="button" onclick="cerrarModalEmpleado()" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 md:h-8 md:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form id="empleadoForm" class="px-4 md:px-8 py-6 max-h-[calc(70vh-120px)] sm:max-h-[60vh] overflow-y-auto">
      <!-- PASO 1: Selección de Tipo de Empleado -->
      <div class="mb-6 p-4 md:p-6 bg-gradient-to-r from-indigo-50 to-blue-50 rounded-xl border-l-4 border-indigo-500">
        <h4 class="text-base md:text-lg font-bold text-gray-800 mb-4">
          <i class="fas fa-users mr-2 text-indigo-600"></i>
          Tipo de Empleado
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <label class="cursor-pointer">
            <input type="radio" name="tipo_empleado" value="OPERARIO" id="tipo_operario" class="hidden peer" checked>
            <div class="p-4 md:p-6 border-2 rounded-xl transition-all peer-checked:border-green-500 peer-checked:bg-green-50 peer-checked:shadow-lg hover:shadow-md">
              <div class="flex items-center justify-between">
                <div>
                  <h5 class="font-bold text-gray-800 text-base md:text-lg">
                    <i class="fas fa-hard-hat mr-2 text-green-600"></i>
                    Operario
                  </h5>
                  <p class="text-xs md:text-sm text-gray-600 mt-1">Personal de producción, clasificación y empaque</p>
                </div>
                <i class="fas fa-check-circle text-2xl md:text-3xl text-green-500 hidden peer-checked:block"></i>
              </div>
            </div>
          </label>
          
          <label class="cursor-pointer">
            <input type="radio" name="tipo_empleado" value="ADMINISTRATIVO" id="tipo_administrativo" class="hidden peer">
            <div class="p-4 md:p-6 border-2 rounded-xl transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:shadow-lg hover:shadow-md">
              <div class="flex items-center justify-between">
                <div>
                  <h5 class="font-bold text-gray-800 text-base md:text-lg">
                    <i class="fas fa-briefcase mr-2 text-blue-600"></i>
                    Administrativo
                  </h5>
                  <p class="text-xs md:text-sm text-gray-600 mt-1">Gerentes, supervisores, contadores, etc.</p>
                </div>
                <i class="fas fa-check-circle text-2xl md:text-3xl text-blue-500 hidden peer-checked:block"></i>
              </div>
            </div>
          </label>
        </div>
      </div>

      <!-- PASO 2: Campos del Formulario -->
      <div id="campos_formulario">
        <!-- Campos SIEMPRE VISIBLES (para todos) -->
        <div class="mb-6 p-6 bg-gray-50 rounded-xl">
          <h4 class="text-md font-bold text-gray-800 mb-4 border-b pb-2">
            <i class="fas fa-user-circle mr-2 text-gray-600"></i>
            Información Básica
          </h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-gray-700 font-medium mb-2">
                <i class="fas fa-user text-indigo-500 mr-1"></i>
                Nombre <span class="text-red-500">*</span>
              </label>
              <input type="text" id="nombre" name="nombre" 
                class="w-full border-2 rounded-lg px-4 py-3 text-base focus:outline-none focus:border-indigo-500 transition" 
                required minlength="2" placeholder="Ej: Juan">
              <small class="text-red-500 hidden" id="error-nombre">Mínimo 2 caracteres</small>
            </div>
            
            <div>
              <label class="block text-gray-700 font-medium mb-2">
                <i class="fas fa-user text-indigo-500 mr-1"></i>
                Apellido <span class="text-red-500">*</span>
              </label>
              <input type="text" id="apellido" name="apellido" 
                class="w-full border-2 rounded-lg px-4 py-3 text-base focus:outline-none focus:border-indigo-500 transition" 
                required minlength="2" placeholder="Ej: Pérez">
              <small class="text-red-500 hidden" id="error-apellido">Mínimo 2 caracteres</small>
            </div>
            
            <div>
              <label class="block text-gray-700 font-medium mb-2">
                <i class="fas fa-id-card text-indigo-500 mr-1"></i>
                Cédula de Identidad <span class="text-red-500">*</span>
              </label>
              <input type="text" id="identificacion" name="identificacion" 
                class="w-full border-2 rounded-lg px-4 py-3 text-base focus:outline-none focus:border-indigo-500 transition" 
                required pattern="\d{7,10}" placeholder="Ej: 12345678">
              <small class="text-red-500 hidden" id="error-identificacion">Entre 7 y 10 dígitos</small>
            </div>
            
            <div>
              <label class="block text-gray-700 font-medium mb-2">
                <i class="fas fa-toggle-on text-indigo-500 mr-1"></i>
                Estatus
              </label>
              <select id="estatus" name="estatus" 
                class="w-full border-2 rounded-lg px-4 py-3 text-base focus:outline-none focus:border-indigo-500 transition">
                <option value="activo">Activo</option>
                <option value="inactivo">Inactivo</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Campos para OPERARIOS (simplificados) -->
        <div id="campos_operario" class="campos-dinamicos mb-6 p-6 bg-green-50 rounded-xl">
          <h4 class="text-md font-bold text-gray-800 mb-4 border-b pb-2">
            <i class="fas fa-hard-hat mr-2 text-green-600"></i>
            Información de Operario
          </h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-gray-700 font-medium mb-2">
                <i class="fas fa-tools text-green-500 mr-1"></i>
                Tipo de Operario
              </label>
              <select id="puesto_operario" name="puesto" 
                class="w-full border-2 rounded-lg px-4 py-3 text-base focus:outline-none focus:border-green-500 transition">
                <option value="">Seleccione...</option>
                <option value="Operario de Clasificación">Operario de Clasificación</option>
                <option value="Operario de Empaque">Operario de Empaque</option>
                <option value="Operario General">Operario General</option>
              </select>
            </div>
            
            <div>
              <label class="block text-gray-700 font-medium mb-2">
                <i class="fas fa-calendar-alt text-green-500 mr-1"></i>
                Fecha de Inicio
              </label>
              <input type="date" id="fecha_inicio_operario" name="fecha_inicio" 
                class="w-full border-2 rounded-lg px-4 py-3 text-base focus:outline-none focus:border-green-500 transition">
            </div>
          </div>
        </div>

        <!-- Campos para ADMINISTRATIVOS (completos) -->
        <div id="campos_administrativo" class="campos-dinamicos mb-6 p-6 bg-blue-50 rounded-xl hidden">
          <h4 class="text-md font-bold text-gray-800 mb-4 border-b pb-2">
            <i class="fas fa-briefcase mr-2 text-blue-600"></i>
            Información Administrativa Completa
          </h4>
          
          <!-- Fila 1: Información Personal -->
          <div class="mb-6">
            <h5 class="text-sm font-semibold text-gray-700 mb-3">Datos Personales</h5>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-gray-700 font-medium mb-2">
                  <i class="fas fa-venus-mars text-blue-500 mr-1"></i>
                  Género
                </label>
                <select id="genero" name="genero" 
                  class="w-full border-2 rounded-lg px-4 py-3 text-base focus:outline-none focus:border-blue-500 transition">
                  <option value="">Seleccione...</option>
                  <option value="masculino">Masculino</option>
                  <option value="femenino">Femenino</option>
                  <option value="otro">Otro</option>
                </select>
              </div>
              
              <div>
                <label class="block text-gray-700 font-medium mb-2">
                  <i class="fas fa-birthday-cake text-blue-500 mr-1"></i>
                  Fecha de Nacimiento
                </label>
                <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" 
                  class="w-full border-2 rounded-lg px-4 py-3 text-base focus:outline-none focus:border-blue-500 transition">
              </div>
              
              <div>
                <label class="block text-gray-700 font-medium mb-2">
                  <i class="fas fa-phone text-blue-500 mr-1"></i>
                  Teléfono
                </label>
                <input type="text" id="telefono_principal" name="telefono_principal" 
                  class="w-full border-2 rounded-lg px-4 py-3 text-base focus:outline-none focus:border-blue-500 transition"
                  placeholder="Ej: 04241234567">
              </div>
            </div>
          </div>
          
          <!-- Fila 2: Información de Contacto -->
          <div class="mb-6">
            <h5 class="text-sm font-semibold text-gray-700 mb-3">Contacto y Ubicación</h5>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-gray-700 font-medium mb-2">
                  <i class="fas fa-envelope text-blue-500 mr-1"></i>
                  Correo Electrónico
                </label>
                <input type="email" id="correo_electronico" name="correo_electronico" 
                  class="w-full border-2 rounded-lg px-4 py-3 text-base focus:outline-none focus:border-blue-500 transition"
                  placeholder="ejemplo@correo.com">
              </div>
              
              <div>
                <label class="block text-gray-700 font-medium mb-2">
                  <i class="fas fa-map-marker-alt text-blue-500 mr-1"></i>
                  Dirección
                </label>
                <input type="text" id="direccion" name="direccion" 
                  class="w-full border-2 rounded-lg px-4 py-3 text-base focus:outline-none focus:border-blue-500 transition"
                  placeholder="Calle, Urbanización, Ciudad">
              </div>
            </div>
          </div>
          
          <!-- Fila 3: Información Laboral -->
          <div>
            <h5 class="text-sm font-semibold text-gray-700 mb-3">Datos Laborales</h5>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-gray-700 font-medium mb-2">
                  <i class="fas fa-user-tie text-blue-500 mr-1"></i>
                  Puesto <span class="text-red-500">*</span>
                </label>
                <input type="text" id="puesto_administrativo" name="puesto" 
                  class="w-full border-2 rounded-lg px-4 py-3 text-base focus:outline-none focus:border-blue-500 transition"
                  placeholder="Ej: Gerente, Contador, Supervisor"
                  minlength="3">
              </div>
              
              <div>
                <label class="block text-gray-700 font-medium mb-2">
                  <i class="fas fa-dollar-sign text-blue-500 mr-1"></i>
                  Salario
                </label>
                <input type="number" id="salario" name="salario" 
                  class="w-full border-2 rounded-lg px-4 py-3 text-base focus:outline-none focus:border-blue-500 transition"
                  min="0" step="0.01" placeholder="0.00">
              </div>
              
              <div>
                <label class="block text-gray-700 font-medium mb-2">
                  <i class="fas fa-calendar-check text-blue-500 mr-1"></i>
                  Fecha de Inicio
                </label>
                <input type="date" id="fecha_inicio_admin" name="fecha_inicio" 
                  class="w-full border-2 rounded-lg px-4 py-3 text-base focus:outline-none focus:border-blue-500 transition">
              </div>
              
              <div>
                <label class="block text-gray-700 font-medium mb-2">
                  <i class="fas fa-calendar-times text-blue-500 mr-1"></i>
                  Fecha de Fin
                </label>
                <input type="date" id="fecha_fin" name="fecha_fin" 
                  class="w-full border-2 rounded-lg px-4 py-3 text-base focus:outline-none focus:border-blue-500 transition">
              </div>
            </div>
          </div>
        </div>

        <!-- Observaciones (para todos) -->
        <div class="mb-6">
          <label class="block text-gray-700 font-medium mb-2">
            <i class="fas fa-comment-alt text-gray-500 mr-1"></i>
            Observaciones
          </label>
          <textarea id="observaciones" name="observaciones" rows="3" 
            class="w-full border-2 rounded-lg px-4 py-3 text-base focus:outline-none focus:border-indigo-500 transition"
            placeholder="Notas adicionales (opcional)"></textarea>
        </div>

        <input type="hidden" id="idempleado" name="idempleado">
      </div>
    </form>
    <div class="bg-gray-50 px-4 md:px-6 py-3 md:py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3">
        <button type="button" id="cerrarModalBtn" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm md:text-base font-medium">
            Cancelar
        </button>
        <button type="submit" id="registrarEmpleadoBtn" form="empleadoForm" class="w-full sm:w-auto px-4 py-2 md:px-6 md:py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm md:text-base font-medium">
            <i class="fas fa-save mr-1 md:mr-2"></i> Guardar Empleado
        </button>
    </div>
  </div>
</div>

<?php footerAdmin($data); ?>