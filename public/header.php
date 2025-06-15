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

// DEBUG COMPLETO - TEMPORAL
if (isset($_GET['debug']) && $_GET['debug'] == 'session') {
    echo "<div style='background: #f0f0f0; padding: 20px; margin: 10px; border: 2px solid #000;'>";
    echo "<h2>DEBUG COMPLETO DE SESIÓN:</h2>";
    echo "<h3>Estado de la sesión:</h3>";
    echo "Session Status: " . session_status() . " (1=disabled, 2=none, 3=active)<br>";
    echo "Session ID: " . session_id() . "<br>";
    echo "<h3>Variables de sesión:</h3>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    echo "<h3>Cookies:</h3>";
    echo "<pre>";
    print_r($_COOKIE);
    echo "</pre>";
    echo "<h3>Información adicional:</h3>";
    echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "<br>";
    echo "Está logueado: " . (isset($_SESSION['usuario_id']) ? 'SÍ' : 'NO') . "<br>";
    echo "</div>";
}

require_once __DIR__ . '/../helpers/permisosVerificar.php'; 

// DEBUG TEMPORAL - Eliminar después
if (isset($_GET['debug']) && $_GET['debug'] == 'permisos') {
    echo "<pre>";
    echo "Usuario ID: " . ($_SESSION['usuario_id'] ?? 'No definido') . "\n";
    echo "Rol ID: " . ($_SESSION['rol_id'] ?? 'No definido') . "\n";
    echo "Nombre Usuario: " . ($_SESSION['usuario_nombre'] ?? 'No definido') . "\n";
    
    // Verificar permisos específicos
    $permisoRolesIntegrado = PermisosVerificar::verificarPermisoAccion('RolesIntegrado', 'ver');
    echo "Permiso RolesIntegrado: " . ($permisoRolesIntegrado ? 'SÍ' : 'NO') . "\n";
    echo "</pre>";
}
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
  <link href="/project/app/assets/DataTables/responsive.dataTables.css" rel="stylesheet">
  <link rel="stylesheet" href="/project/app/assets/sweetAlert/sweetalert2.min.css">
  <style>
    body {
      overflow-x: hidden;
    }
    #sidebar nav::-webkit-scrollbar { width: 6px; }
    #sidebar nav::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 3px; }
    #sidebar nav::-webkit-scrollbar-track { background-color: #f7fafc; }
  </style>
</head>

<body class="bg-gray-100 min-h-screen">
  <header 
    class="lg:hidden fixed top-0 left-0 right-0 bg-white shadow-md p-4 flex items-center justify-between z-50 h-16">
    
    <img src="/project/app/assets/img/LOGO.png" alt="Recuperadora" class="h-16 w-auto">
    <button id="mobile-menu-toggle" class="text-gray-700 hover:text-green-600 focus:outline-none p-2">
      <i class="fa-solid fa-bars text-2xl"></i>
    </button>
  </header>

  <div id="sidebar-overlay" class="fixed inset-0 bg-transparent bg-opacity-50 z-30 lg:hidden hidden"></div>

  <div class="flex min-h-screen pt-16 lg:pt-0">
    
    <aside id="sidebar" 
           class="fixed inset-y-0 left-0 z-40 w-64 bg-white shadow-xl 
                  transform -translate-x-full transition-transform duration-300 ease-in-out 
                  lg:relative lg:translate-x-0 lg:shadow-md lg:flex lg:flex-col">
      
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

          <!-- Acceso directo a Romana -->
          <?php if (PermisosVerificar::verificarPermisoAccion('romana', 'ver')): ?>
          <!-- <li class="menu-item">
            <a href="/project/romana" class="nav-link flex items-center p-3 rounded-md text-gray-700 hover:bg-green-100 hover:text-green-700 group">
              <i class="nav-icon fa-solid fa-weight-scale w-5 text-center text-gray-500 group-hover:text-green-600"></i>
              <span class="nav-text ml-3 font-medium">Romana</span>
            </a>
          </li> -->
          <?php endif; ?>

          <!-- Gestionar Compras -->
          <?php if (PermisosVerificar::verificarPermisoAccion('compras', 'ver') || PermisosVerificar::verificarPermisoAccion('proveedores', 'ver')): ?>
          <li class="menu-item-group">
            <details class="group">
              <summary class="nav-link-summary flex cursor-pointer list-none items-center justify-between p-3 rounded-md text-gray-700 hover:bg-green-100 hover:text-green-700 group">
                <div class="flex items-center">
                  <i class="nav-icon fa-solid fa-cart-shopping w-5 text-center text-gray-500 group-hover:text-green-600"></i>
                  <span class="nav-text ml-3 font-medium">Gestionar Compras</span>
                </div>
                <i class="fa-solid fa-chevron-down text-xs transition-transform duration-300 group-open:rotate-180 text-gray-400 group-hover:text-green-500"></i>
              </summary>
              <ul class="ml-4 mt-1 space-y-1 pl-3 border-l border-gray-200">
                <?php if (PermisosVerificar::verificarPermisoAccion('compras', 'ver')): ?>
                <li class="menu-item">
                  <a href="/project/compras" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group">
                    <i class="nav-icon fa-solid fa-cart-shopping w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i>
                    <span class="nav-text ml-3">Compras</span>
                  </a>
                </li>
                <?php endif; ?>
                <?php if (PermisosVerificar::verificarPermisoAccion('proveedores', 'ver')): ?>
                <li class="menu-item">
                  <a href="/project/proveedores" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group">
                    <i class="nav-icon fa-solid fa-truck w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i>
                    <span class="nav-text ml-3">Proveedores</span>
                  </a>
                </li>
                <?php endif; ?>
              </ul>
            </details>
          </li>
          <?php endif; ?>

          <!-- Gestionar Produccion -->
          <?php if (
            PermisosVerificar::verificarPermisoAccion('produccion', 'ver') ||
            PermisosVerificar::verificarPermisoAccion('empleados', 'ver') ||
            PermisosVerificar::verificarPermisoAccion('productos', 'ver') ||
            PermisosVerificar::verificarPermisoAccion('categorias', 'ver')
          ): ?>
          <li class="menu-item-group">
            <details class="group">
              <summary class="nav-link-summary flex cursor-pointer list-none items-center justify-between p-3 rounded-md text-gray-700 hover:bg-green-100 hover:text-green-700 group">
                <div class="flex items-center">
                  <i class="nav-icon fa-solid fa-cogs w-5 text-center text-gray-500 group-hover:text-green-600"></i>
                  <span class="nav-text ml-3 font-medium">Gestionar Produccion</span>
                </div>
                <i class="fa-solid fa-chevron-down text-xs transition-transform duration-300 group-open:rotate-180 text-gray-400 group-hover:text-green-500"></i>
              </summary>
              <ul class="ml-4 mt-1 space-y-1 pl-3 border-l border-gray-200">
                <?php if (PermisosVerificar::verificarPermisoAccion('produccion', 'ver')): ?>
                <li class="menu-item"><a href="/project/produccion" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group"><i class="nav-icon fa-solid fa-industry w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i><span class="nav-text ml-3">Produccion</span></a></li>
                <?php endif; ?>
                <?php if (PermisosVerificar::verificarPermisoAccion('empleados', 'ver')): ?>
                <li class="menu-item"><a href="/project/empleados" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group"><i class="nav-icon fa-solid fa-user-group w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i><span class="nav-text ml-3">Empleados</span></a></li>
                <?php endif; ?>
                <?php if (PermisosVerificar::verificarPermisoAccion('productos', 'ver')): ?>
                <li class="menu-item"><a href="/project/productos" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group"><i class="nav-icon fa-solid fa-box w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i><span class="nav-text ml-3">Productos</span></a></li>
                <?php endif; ?>
                <?php if (PermisosVerificar::verificarPermisoAccion('categorias', 'ver')): ?>
                <li class="menu-item"><a href="/project/categorias" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group"><i class="nav-icon fa-solid fa-tags w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i><span class="nav-text ml-3">Categorias</span></a></li>
                <?php endif; ?>
              </ul>
            </details>
          </li>
          <?php endif; ?>

          <!-- Gestionar Pagos -->
           <?php if (
            PermisosVerificar::verificarPermisoAccion('romana', 'ver') ||
            PermisosVerificar::verificarPermisoAccion('personas', 'ver') ||
            PermisosVerificar::verificarPermisoAccion('tasas', 'ver')
          ): ?>
          <li class="menu-item-group">
            <details class="group">
              <summary class="nav-link-summary flex cursor-pointer list-none items-center justify-between p-3 rounded-md text-gray-700 hover:bg-green-100 hover:text-green-700 group">
                <div class="flex items-center">
                  <i class="nav-icon fa-solid fa-credit-card w-5 text-center text-gray-500 group-hover:text-green-600"></i>
                  <span class="nav-text ml-3 font-medium">Gestionar Pagos</span>
                </div>
                <i class="fa-solid fa-chevron-down text-xs transition-transform duration-300 group-open:rotate-180 text-gray-400 group-hover:text-green-500"></i>
              </summary>
              <ul class="ml-4 mt-1 space-y-1 pl-3 border-l border-gray-200">
              
                <?php if (PermisosVerificar::verificarPermisoAccion('personas', 'ver')): ?>
                <li class="menu-item"><a href="/project/personas" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group"><i class="nav-icon fa-solid fa-user-shield w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i><span class="nav-text ml-3">Personas</span></a></li>
                <?php endif; ?>
                <?php if (PermisosVerificar::verificarPermisoAccion('tasas', 'ver')): ?>
                <li class="menu-item"><a href="/project/tasas" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group"><i class="nav-icon fas fa-coins w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i><span class="nav-text ml-3">Historico de Tasas BCV</span></a></li>
                <?php endif; ?>
              </ul>
            </details>
          </li>
          <?php endif; ?>

          <!-- Movimientos de existencias -->
          <?php if (PermisosVerificar::verificarPermisoAccion('movimientos', 'ver')): ?>
          <li class="menu-item">
            <a href="/project/movimientos" class="nav-link flex items-center p-3 rounded-md text-gray-700 hover:bg-green-100 hover:text-green-700 group">
              <i class="nav-icon fa-solid fa-boxes-stacked w-5 text-center text-gray-500 group-hover:text-green-600"></i>
              <span class="nav-text ml-3 font-medium">Gestionar Movimientos</span>
            </a>
          </li>
          <?php endif; ?>

          <!-- Sueldos Temporales -->
          <?php if (PermisosVerificar::verificarPermisoAccion('sueldos_temporales', 'ver')): ?>
          <li class="menu-item">
            <a href="/project/sueldos_temporales" class="nav-link flex items-center p-3 rounded-md text-gray-700 hover:bg-green-100 hover:text-green-700 group">
              <i class="nav-icon fa-solid fa-money-bill-wave w-5 text-center text-gray-500 group-hover:text-green-600"></i>
              <span class="nav-text ml-3 font-medium">Gestionar Sueldos</span>
            </a>
          </li>
          <?php endif; ?>

          <!-- Gestionar Ventas -->
          <?php if (PermisosVerificar::verificarPermisoAccion('ventas', 'ver') || PermisosVerificar::verificarPermisoAccion('clientes', 'ver')): ?>
          <li class="menu-item-group">
            <details class="group">
              <summary class="nav-link-summary flex cursor-pointer list-none items-center justify-between p-3 rounded-md text-gray-700 hover:bg-green-100 hover:text-green-700 group">
                <div class="flex items-center">
                  <i class="nav-icon fa-solid fa-cash-register w-5 text-center text-gray-500 group-hover:text-green-600"></i>
                  <span class="nav-text ml-3 font-medium">Gestionar Ventas</span>
                </div>
                <i class="fa-solid fa-chevron-down text-xs transition-transform duration-300 group-open:rotate-180 text-gray-400 group-hover:text-green-500"></i>
              </summary>
              <ul class="ml-4 mt-1 space-y-1 pl-3 border-l border-gray-200">
                <?php if (PermisosVerificar::verificarPermisoAccion('ventas', 'ver')): ?>
                <li class="menu-item"><a href="/project/ventas" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group"><i class="nav-icon fa-solid fa-file-invoice-dollar w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i><span class="nav-text ml-3">Ventas</span></a></li>
                <?php endif; ?>
                <?php if (PermisosVerificar::verificarPermisoAccion('clientes', 'ver')): ?>
                <li class="menu-item"><a href="/project/clientes" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group"><i class="nav-icon fa-solid fa-users w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i><span class="nav-text ml-3">Clientes</span></a></li>
                <?php endif; ?>
              </ul>
            </details>
          </li>
          <?php endif; ?>

          <!-- Generar Reportes -->
          <?php if (PermisosVerificar::verificarPermisoAccion('reportes', 'ver')): ?>
          <li class="menu-item">
            <a href="/project/reportes" class="nav-link flex items-center p-3 rounded-md text-gray-700 hover:bg-green-100 hover:text-green-700 group">
              <i class="nav-icon fa-solid fa-file-lines w-5 text-center text-gray-500 group-hover:text-green-600"></i>
              <span class="nav-text ml-3 font-medium">Generar Reportes</span>
            </a>
          </li>
          <?php endif; ?>

          <!-- Seguridad -->
          <?php if (
            PermisosVerificar::verificarPermisoAccion('usuarios', 'ver') ||
            PermisosVerificar::verificarPermisoAccion('roles', 'ver') ||
            PermisosVerificar::verificarPermisoAccion('RolesPermisos', 'ver') ||
            PermisosVerificar::verificarPermisoAccion('RolesAsignaciones', 'ver') ||
            PermisosVerificar::verificarPermisoAccion('RolesIntegrado', 'ver') ||
            PermisosVerificar::verificarPermisoAccion('modulos', 'ver') ||
            PermisosVerificar::verificarPermisoAccion('rolesmodulos', 'ver')||
            PermisosVerificar::verificarPermisoAccion('bitacora', 'ver')
          ): ?>
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
                 <?php if (PermisosVerificar::verificarPermisoAccion('usuarios', 'ver')): ?>
                <li class="menu-item"><a href="/project/usuarios" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group"><i class="nav-icon fa-solid fa-user w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i><span class="nav-text ml-3">Gestionar Usuarios</span></a></li>
                <?php endif; ?>
                <?php if (PermisosVerificar::verificarPermisoAccion('roles', 'ver')): ?>
                <li class="menu-item"><a href="/project/roles" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group"><i class="nav-icon fa-solid fa-user-tag w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i><span class="nav-text ml-3">Gestionar Rol</span></a></li>
                <?php endif; ?>
               
                <?php if (PermisosVerificar::verificarPermisoAccion('RolesIntegrado', 'ver')): ?>
                <li class="menu-item"><a href="/project/RolesIntegrado" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group"><i class="nav-icon fa-solid fa-cogs w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i><span class="nav-text ml-3">Gestión Integral</span></a></li>
                <?php endif; ?>
             
                <?php if (PermisosVerificar::verificarPermisoAccion('modulos', 'ver')): ?>
                <li class="menu-item"><a href="/project/modulos" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group"><i class="nav-icon fa-solid fa-puzzle-piece w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i><span class="nav-text ml-3">Modulos</span></a></li>
                <?php endif; ?>
               
                 <?php if (PermisosVerificar::verificarPermisoAccion('bitacora', 'ver')): ?>
                <li class="menu-item"><a href="/project/bitacora" class="nav-link flex items-center p-2 rounded-md text-sm text-gray-600 hover:bg-green-100 hover:text-green-600 group"><i class="nav-icon fa-solid fa-clipboard-list w-4 text-center text-xs text-gray-400 group-hover:text-green-500"></i><span class="nav-text ml-3">Bitácora</span></a></li>
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

<script>
document.addEventListener("DOMContentLoaded", () => {
  const mobileMenuToggle = document.getElementById("mobile-menu-toggle");
  const sidebar = document.getElementById("sidebar");
  const sidebarOverlay = document.getElementById("sidebar-overlay");
  const sidebarClose = document.getElementById("sidebar-close");

  function openSidebar() {
    if (sidebar && sidebarOverlay) {
      sidebar.classList.remove("-translate-x-full");
      sidebarOverlay.classList.remove("hidden");
      document.body.classList.add("overflow-hidden");
    }
  }

  function closeSidebar() {
    if (sidebar && sidebarOverlay) {
      sidebar.classList.add("-translate-x-full");
      sidebarOverlay.classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    }
  }

  if (mobileMenuToggle) {
    mobileMenuToggle.addEventListener("click", openSidebar);
  } else {
    console.error("Botón de menú móvil (mobile-menu-toggle) no encontrado.");
  }

  if (sidebarClose) {
    sidebarClose.addEventListener("click", closeSidebar);
  }

  if (sidebarOverlay) {
    sidebarOverlay.addEventListener("click", closeSidebar);
  }

  const sidebarLinks = sidebar ? sidebar.querySelectorAll("a.nav-link") : [];
  sidebarLinks.forEach((link) => {
    link.addEventListener("click", () => {
      if (window.innerWidth < 1024) { // Tailwind's lg breakpoint
        closeSidebar();
      }
    });
  });

  window.addEventListener("resize", () => {
    if (window.innerWidth >= 1024) {
      // En desktop, el sidebar es visible por layout (lg:relative) y el overlay no es necesario.
      closeSidebar(); // Esto asegura que si se redimensiona de móvil a desktop con el menú abierto, se oculte el overlay y se quite el overflow-hidden del body.
      document.body.classList.remove("overflow-hidden"); // Asegurar que el body pueda hacer scroll
      if(sidebarOverlay) sidebarOverlay.classList.add("hidden"); // Asegurar que el overlay esté oculto
    }
  });

  // Active link functionality
  const currentPath = window.location.pathname;
  const navLinksQuery = "#sidebar nav a.nav-link"; // Usar la clase común
  const allNavLinks = document.querySelectorAll(navLinksQuery);

  allNavLinks.forEach((link) => {
    const linkElement = link;
    const listItem = linkElement.closest("li.menu-item, li.menu-item-group"); 
    const icon = linkElement.querySelector("i.nav-icon");
    const textSpan = linkElement.querySelector("span.nav-text");

    if (listItem) listItem.classList.remove("bg-green-600");
    linkElement.classList.remove("text-white");
    if (icon) icon.classList.remove("text-white");
    if (textSpan) textSpan.classList.remove("text-white");


    if (linkElement.getAttribute("href") === currentPath) {
      if (listItem) {
        // Aplicar estilos activos al LI y al A
        listItem.classList.add("bg-green-600"); // Fondo verde al LI
        listItem.classList.add("rounded-md"); // Fondo verde al LI
        linkElement.classList.add("text-white"); // Texto blanco al A
        if (icon) icon.classList.add("text-white"); // Icono blanco
        if (textSpan) textSpan.classList.add("text-white"); // Texto del span blanco
        
        // Quitar clases de hover/group-hover para el estado activo
        linkElement.classList.remove("hover:bg-green-100", "hover:text-green-700");
        if(icon) icon.classList.remove("text-gray-500", "group-hover:text-green-600");
        if(textSpan) textSpan.classList.remove("text-gray-700");


        // Si el enlace activo está dentro de un <details>, abrirlo
        let parentDetails = linkElement.closest("details");
        if (parentDetails) {
          parentDetails.setAttribute("open", "");
          // También aplicar estilo activo al summary del details
          const summary = parentDetails.querySelector("summary.nav-link-summary");
          if (summary) {
            summary.classList.add("bg-green-600", "text-white"); // Fondo y texto al summary
            const summaryIcon = summary.querySelector("i.nav-icon");
            const summaryText = summary.querySelector("span.nav-text");
            if(summaryIcon) summaryIcon.classList.add("text-white");
            if(summaryText) summaryText.classList.add("text-white");
            summary.classList.remove("hover:bg-green-100", "hover:text-green-700");
          }
        }
      }
    }
  });
});
</script>