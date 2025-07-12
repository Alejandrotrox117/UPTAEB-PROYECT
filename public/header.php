<?php 
// INICIAR SESIÓN SIEMPRE
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// VERIFICAR SI EL USUARIO ESTÁ LOGUEADO
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol_id'])) {
    // Si no está logueado y no está en la página de login, redirigir
    $current_page = basename($_SERVER['REQUEST_URI']);
    if ($current_page !== 'login' && strpos($_SERVER['REQUEST_URI'], 'login') === false) {
        header('Location: /project/login');
        exit();
    }
}

require_once __DIR__ . '/../helpers/PermisosModuloVerificar.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <link rel="shortcut icon" href="/project/app/assets/img/favicon.svg" type="image/x-icon">
  <title>Recuperadora</title>
  <meta name="description" content="Recuperadora de materiales reciclables">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="/project/app/assets/styles/styles.css">
  <link href="/project/app/assets/fontawesome/css/all.min.css" rel="stylesheet">
  <link href="/project/app/assets/DataTables/datatables.css" rel="stylesheet">
  <link rel="stylesheet" href="/project/app/assets/sweetAlert/sweetalert2.min.css">
  <!-- Shepherd.js CSS Local -->
  <link rel="stylesheet" href="/project/app/assets/shepherd.js/shepherd-simple.css">
  <!-- Estilos del sistema -->
  <link rel="stylesheet" href="/project/app/assets/css/system-styles.css">
</head>

<body class="bg-gray-100 min-h-screen">
  <!-- Header para Móvil -->
  <header class="lg:hidden fixed top-0 left-0 right-0 bg-white shadow-md p-4 flex items-center justify-between z-50 h-16">
    <img src="/project/app/assets/img/LOGO.png" alt="Recuperadora" class="h-16 w-auto">
    <div class="flex items-center space-x-4">
      <?php 
      // Mostrar notificaciones si el usuario tiene acceso a cualquier módulo que puede generar notificaciones
      $puedeVerNotificaciones = PermisosModuloVerificar::verificarPermisoModuloAccion('productos', 'ver') ||
                                PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'ver') ||
                                PermisosModuloVerificar::verificarAccesoModulo('productos') ||
                                PermisosModuloVerificar::verificarAccesoModulo('compras');
      ?>
      <?php if ($puedeVerNotificaciones): ?>
      <div class="relative">
        <button id="mobile-notifications-toggle" class="text-gray-700 hover:text-green-600 focus:outline-none p-2 relative">
          <i class="fas fa-bell text-xl"></i>
          <span id="mobile-notification-badge" class="notification-badge hidden">0</span>
        </button>
      </div>
      <?php endif; ?>
      <button id="mobile-menu-toggle" class="text-gray-700 hover:text-green-600 focus:outline-none p-2">
        <i class="fa-solid fa-bars text-2xl"></i>
      </button>
    </div>
  </header>

  <div id="sidebar-overlay" class="fixed inset-0 bg-transparent bg-opacity-50 z-30 lg:hidden hidden"></div>

  <div class="flex min-h-screen">
    
    <!-- Menú Lateral (Sidebar) -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-white shadow-xl transform -translate-x-full transition-transform duration-300 ease-in-out lg:relative lg:translate-x-0 lg:shadow-md lg:flex lg:flex-col">
      <div class="flex items-center justify-between p-4 border-b border-gray-200 h-17">
        <img src="/project/app/assets/img/LOGO.png" alt="Recuperadora" class="h-16 w-auto">
        <button id="sidebar-close" class="text-gray-600 hover:text-green-600 lg:hidden p-2">
          <i class="fa-solid fa-times text-2xl"></i>
        </button>
      </div>

      <nav class="flex-1 flex flex-col overflow-y-auto p-3 text-sm">
        <ul class="space-y-1">
          <!-- Dashboard -->
          <li class="menu-item">
            <a href="/project/dashboard" class="nav-link flex items-center p-3 rounded-md text-gray-700 hover:bg-green-100 hover:text-green-700 group">
              <i class="nav-icon fa-solid fa-tachometer-alt w-5 text-center text-gray-500 group-hover:text-green-600"></i>
              <span class="nav-text ml-3 font-medium">Dashboard</span>
            </a>
          </li>

          <!-- Gestionar Compras -->
          <?php if (PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'ver') || PermisosModuloVerificar::verificarPermisoModuloAccion('proveedores', 'ver')): ?>
          <li class="menu-item-group"><details class="group"><summary class="nav-link-summary flex cursor-pointer list-none items-center justify-between p-3 rounded-md text-gray-700 hover:bg-green-100 hover:text-green-700 group"><div class="flex items-center"><i class="nav-icon fa-solid fa-cart-shopping w-5 text-center text-gray-500 group-hover:text-green-600"></i><span class="nav-text ml-3 font-medium">Gestionar Compras</span></div><i class="fa-solid fa-chevron-down text-xs transition-transform duration-300 group-open:rotate-180 text-gray-400 group-hover:text-green-500"></i></summary><ul class="ml-4 mt-1 space-y-1 pl-3 border-l border-gray-200"><?php if (PermisosModuloVerificar::verificarPermisoModuloAccion('compras', 'ver')): ?><li class="menu-item"><a href="/project/compras" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group"><i class="nav-icon fa-solid fa-cart-shopping w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i><span class="nav-text ml-3">Compras</span></a></li><?php endif; ?><?php if (PermisosModuloVerificar::verificarPermisoModuloAccion('proveedores', 'ver')): ?><li class="menu-item"><a href="/project/proveedores" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group"><i class="nav-icon fa-solid fa-truck w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i><span class="nav-text ml-3">Proveedores</span></a></li><?php endif; ?></ul></details></li>
          <?php endif; ?>

          <!-- Gestionar Produccion -->
          <?php if (PermisosModuloVerificar::verificarPermisoModuloAccion('produccion', 'ver') || PermisosModuloVerificar::verificarPermisoModuloAccion('empleados', 'ver') || PermisosModuloVerificar::verificarPermisoModuloAccion('productos', 'ver') || PermisosModuloVerificar::verificarPermisoModuloAccion('categorias', 'ver')): ?>
          <li class="menu-item-group"><details class="group"><summary class="nav-link-summary flex cursor-pointer list-none items-center justify-between p-3 rounded-md text-gray-700 hover:bg-green-100 hover:text-green-700 group"><div class="flex items-center"><i class="nav-icon fa-solid fa-cogs w-5 text-center text-gray-500 group-hover:text-green-600"></i><span class="nav-text ml-3 font-medium">Gestionar Produccion</span></div><i class="fa-solid fa-chevron-down text-xs transition-transform duration-300 group-open:rotate-180 text-gray-400 group-hover:text-green-500"></i></summary><ul class="ml-4 mt-1 space-y-1 pl-3 border-l border-gray-200"><?php if (PermisosModuloVerificar::verificarPermisoModuloAccion('produccion', 'ver')): ?><li class="menu-item"><a href="/project/produccion" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group"><i class="nav-icon fa-solid fa-industry w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i><span class="nav-text ml-3">Produccion</span></a></li><?php endif; ?><?php if (PermisosModuloVerificar::verificarPermisoModuloAccion('empleados', 'ver')): ?><li class="menu-item"><a href="/project/empleados" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group"><i class="nav-icon fa-solid fa-user-group w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i><span class="nav-text ml-3">Empleados</span></a></li><?php endif; ?><?php if (PermisosModuloVerificar::verificarPermisoModuloAccion('productos', 'ver')): ?><li class="menu-item"><a href="/project/productos" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group"><i class="nav-icon fa-solid fa-box w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i><span class="nav-text ml-3">Productos</span></a></li><?php endif; ?><?php if (PermisosModuloVerificar::verificarPermisoModuloAccion('categorias', 'ver')): ?><li class="menu-item"><a href="/project/categorias" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group"><i class="nav-icon fa-solid fa-tags w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i><span class="nav-text ml-3">Categorias</span></a></li><?php endif; ?></ul></details></li>
          <?php endif; ?>

          <!-- Gestionar Pagos -->
           <?php if (PermisosModuloVerificar::verificarPermisoModuloAccion('pagos', 'ver') || PermisosModuloVerificar::verificarPermisoModuloAccion('personas', 'ver') || PermisosModuloVerificar::verificarPermisoModuloAccion('tasas', 'ver')): ?>
          <li class="menu-item-group"><details class="group"><summary class="nav-link-summary flex cursor-pointer list-none items-center justify-between p-3 rounded-md text-gray-700 hover:bg-green-100 hover:text-green-700 group"><div class="flex items-center"><i class="nav-icon fa-solid fa-credit-card w-5 text-center text-gray-500 group-hover:text-green-600"></i><span class="nav-text ml-3 font-medium">Gestionar Pagos</span></div><i class="fa-solid fa-chevron-down text-xs transition-transform duration-300 group-open:rotate-180 text-gray-400 group-hover:text-green-500"></i></summary><ul class="ml-4 mt-1 space-y-1 pl-3 border-l border-gray-200"><?php if (PermisosModuloVerificar::verificarPermisoModuloAccion('pagos', 'ver')): ?><li class="menu-item"><a href="/project/pagos" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group"><i class="nav-icon fa-solid fa-credit-card w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i><span class="nav-text ml-3">Pagos</span></a></li><?php endif; ?><?php if (PermisosModuloVerificar::verificarPermisoModuloAccion('personas', 'ver')): ?><li class="menu-item"><a href="/project/personas" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group"><i class="nav-icon fa-solid fa-user-shield w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i><span class="nav-text ml-3">Personas</span></a></li><?php endif; ?><?php if (PermisosModuloVerificar::verificarPermisoModuloAccion('tasas', 'ver')): ?><li class="menu-item"><a href="/project/tasas" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group"><i class="nav-icon fas fa-coins w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i><span class="nav-text ml-3">Historico de Tasas BCV</span></a></li><?php endif; ?></ul></details></li>
          <?php endif; ?>

          <!-- Movimientos de existencias -->
          <?php if (PermisosModuloVerificar::verificarPermisoModuloAccion('movimientos', 'ver')): ?>
          <li class="menu-item"><a href="/project/movimientos" class="nav-link flex items-center p-3 rounded-md text-gray-700 hover:bg-green-100 hover:text-green-700 group"><i class="nav-icon fa-solid fa-boxes-stacked w-5 text-center text-gray-500 group-hover:text-green-600"></i><span class="nav-text ml-3 font-medium">Gestionar Movimientos</span></a></li>
          <?php endif; ?>

          <!-- Gestionar Sueldos -->
          <?php if (PermisosModuloVerificar::verificarPermisoModuloAccion('sueldos', 'ver')): ?>
          <li class="menu-item"><a href="/project/sueldos" class="nav-link flex items-center p-3 rounded-md text-gray-700 hover:bg-green-100 hover:text-green-700 group"><i class="nav-icon fa-solid fa-money-bill-wave w-5 text-center text-gray-500 group-hover:text-green-600"></i><span class="nav-text ml-3 font-medium">Gestionar Sueldos</span></a></li>
          <?php endif; ?>

          <!-- Gestionar Ventas -->
          <?php if (PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'ver') || PermisosModuloVerificar::verificarPermisoModuloAccion('clientes', 'ver')): ?>
          <li class="menu-item-group"><details class="group"><summary class="nav-link-summary flex cursor-pointer list-none items-center justify-between p-3 rounded-md text-gray-700 hover:bg-green-100 hover:text-green-700 group"><div class="flex items-center"><i class="nav-icon fa-solid fa-cash-register w-5 text-center text-gray-500 group-hover:text-green-600"></i><span class="nav-text ml-3 font-medium">Gestionar Ventas</span></div><i class="fa-solid fa-chevron-down text-xs transition-transform duration-300 group-open:rotate-180 text-gray-400 group-hover:text-green-500"></i></summary><ul class="ml-4 mt-1 space-y-1 pl-3 border-l border-gray-200"><?php if (PermisosModuloVerificar::verificarPermisoModuloAccion('ventas', 'ver')): ?><li class="menu-item"><a href="/project/ventas" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group"><i class="nav-icon fa-solid fa-file-invoice-dollar w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i><span class="nav-text ml-3">Ventas</span></a></li><?php endif; ?><?php if (PermisosModuloVerificar::verificarPermisoModuloAccion('clientes', 'ver')): ?><li class="menu-item"><a href="/project/clientes" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group"><i class="nav-icon fa-solid fa-users w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i><span class="nav-text ml-3">Clientes</span></a></li><?php endif; ?></ul></details></li>
          <?php endif; ?>

          <!-- Generar Reportes -->
          <?php if (PermisosModuloVerificar::verificarPermisoModuloAccion('reportes', 'ver')): ?>
          <li class="menu-item"><a href="/project/reportes" class="nav-link flex items-center p-3 rounded-md text-gray-700 hover:bg-green-100 hover:text-green-700 group"><i class="nav-icon fa-solid fa-file-lines w-5 text-center text-gray-500 group-hover:text-green-600"></i><span class="nav-text ml-3 font-medium">Generar Reportes</span></a></li>
          <?php endif; ?>

          <!-- Seguridad -->
          <?php if (PermisosModuloVerificar::verificarPermisoModuloAccion('usuarios', 'ver') || PermisosModuloVerificar::verificarPermisoModuloAccion('roles', 'ver') || PermisosModuloVerificar::verificarPermisoModuloAccion('RolesIntegrado', 'ver') || PermisosModuloVerificar::verificarPermisoModuloAccion('modulos', 'ver') || PermisosModuloVerificar::verificarPermisoModuloAccion('bitacora', 'ver')): ?>
          <li class="menu-item-group">
            <details class="group">
              <summary class="nav-link-summary flex cursor-pointer list-none items-center justify-between p-3 rounded-md text-gray-700 hover:bg-green-100 hover:text-green-700 group">
                <div class="flex items-center">
                  <i class="nav-icon fa-solid fa-user-lock w-5 text-center text-gray-500 group-hover:text-green-600"></i>
                  <span class="nav-text ml-3 font-medium">Seguridad</span>
                </div>
                <i class="fa-solid fa-chevron-down text-xs transition-transform duration-300 group-open:rotate-180 text-gray-400 group-hover:text-green-500"></i>
              </summary>
              <ul class="ml-4 mt-1 space-y-1 pl-3 border-l border-gray-200">
                
                <!-- Gestionar Usuarios -->
                <?php if (PermisosModuloVerificar::verificarPermisoModuloAccion('usuarios', 'ver')): ?>
                <li class="menu-item">
                  <a href="/project/usuarios" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group">
                    <i class="nav-icon fa-solid fa-user w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i>
                    <span class="nav-text ml-3">Gestionar Usuarios</span>
                  </a>
                </li>
                <?php endif; ?>
                
                <!-- Gestionar Roles (Básico) -->
                <?php if (PermisosModuloVerificar::verificarPermisoModuloAccion('roles', 'ver')): ?>
                <li class="menu-item">
                  <a href="/project/roles" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group">
                    <i class="nav-icon fa-solid fa-user-tag w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i>
                    <span class="nav-text ml-3">Gestionar Roles</span>
                  </a>
                </li>
                <?php endif; ?>
                
                <!-- Gestión Integral de Permisos -->
                <?php if (PermisosModuloVerificar::verificarPermisoModuloAccion('RolesIntegrado', 'ver')): ?>
                <li class="menu-item">
                  <a href="/project/RolesIntegrado" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group">
                    <i class="nav-icon fa-solid fa-cogs w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i>
                    <span class="nav-text ml-3">Gestión Integral de Permisos</span>
                  </a>
                </li>
                <?php endif; ?>
                
                <!-- Gestionar Módulos -->
                <?php if (PermisosModuloVerificar::verificarPermisoModuloAccion('modulos', 'ver')): ?>
                <li class="menu-item">
                  <a href="/project/modulos" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group">
                    <i class="nav-icon fa-solid fa-th-large w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i>
                    <span class="nav-text ml-3">Gestionar Módulos</span>
                  </a>
                </li>
                <?php endif; ?>
                
                <!-- Bitácora -->
                <?php if (PermisosModuloVerificar::verificarPermisoModuloAccion('bitacora', 'ver')): ?>
                <li class="menu-item">
                  <a href="/project/bitacora" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group">
                    <i class="nav-icon fa-solid fa-history w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i>
                    <span class="nav-text ml-3">Bitácora</span>
                  </a>
                </li>
                <?php endif; ?>
                
              </ul>
            </details>
          </li>
          <?php endif; ?>
        </ul>

        <div class="mt-auto pt-4 border-t border-gray-200">
          <ul>
            <li class="menu-item">
              <a href="/project/logout" class="nav-link flex items-center p-3 rounded-md text-gray-700 hover:bg-red-100 hover:text-red-700 group">
                <i class="nav-icon fa-solid fa-right-to-bracket w-5 text-center text-gray-500 group-hover:text-red-600"></i>
                <span class="nav-text ml-3 font-medium">Cerrar Sesión</span>
              </a>
            </li>
          </ul>
        </div>
      </nav>
    </aside>

    <!-- Contenedor Principal -->
    <div class="flex-1 w-full relative">
        
        <!-- Icono de notificaciones posicionado absolutamente (solo para desktop) -->
        <?php if ($puedeVerNotificaciones): ?>
        <div class="hidden lg:block absolute top-4 right-6 z-20">
            <div class="relative">
                <button id="desktop-notifications-toggle" class="text-gray-600 hover:text-green-600 p-2 relative bg-white rounded-full shadow-md">
                    <i class="fas fa-bell text-xl"></i>
                    <span id="desktop-notification-badge" class="notification-badge hidden">0</span>
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- El tag <main> de tus vistas se renderizará aquí -->
        
        <!-- Dropdown de notificaciones -->
        <?php if ($puedeVerNotificaciones): ?>
        <div id="notifications-dropdown" class="fixed top-16 right-6 w-80 bg-white rounded-lg shadow-xl border border-gray-200 z-50 hidden">
          <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900"><i class="fas fa-bell mr-2 text-green-600"></i> Notificaciones</h3>
            <div class="flex space-x-2">
              <button id="mark-all-read-btn" class="text-xs text-blue-600 hover:text-blue-800 font-medium">Marcar todas leídas</button>
              <button id="close-notifications-btn" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times"></i></button>
            </div>
          </div>
          <div id="notifications-list" class="notification-dropdown p-2">
            <!-- Contenido se carga con JS -->
          </div>
          <div class="p-3 border-t border-gray-200 text-center">
            <button id="refresh-notifications-btn" class="text-sm text-green-600 hover:text-green-800 font-medium"><i class="fas fa-sync-alt mr-1"></i> Actualizar</button>
          </div>
        </div>
        <?php endif; ?>