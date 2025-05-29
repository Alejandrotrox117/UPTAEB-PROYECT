
<?php require_once __DIR__ . '/../helpers/permisosVerificar.php'; ?>
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
  <link href="/project/app/assets/fontawesome/css/solid.css" rel="stylesheet">
  <link href="/project/app/assets/fontawesome/css/fontawesome.css" rel="stylesheet">
  <link href="/project/app/assets/fontawesome/css/brands.css" rel="stylesheet">
  <link href="/project/app/assets/DataTables/datatables.css" rel="stylesheet">
  <link href="/project/app/assets/DataTables/responsive.dataTables.css" rel="stylesheet">
  <link rel="stylesheet" href="/project/app/assets/sweetAlert/sweetalert2.min.css">
</head>

<body class="bg-blue-50 min-h-screen">
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="flex flex-col bg-white p-2 shadow-md">
      <img src="/project/app/assets/img/LOGO.png" alt="Recuperadora" class="logo-sidebar">
      <nav class="text-sm">
        <ul class="space-y-1">
          <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
            <a href="/project/dashboard" class="flex items-center">
              <span><i class="fa-solid fa-tachometer-alt icon"></i></span>
              <span class="ml-5">Dashboard</span>
            </a>
          </li>

          <!-- Gestionar Compras -->
          <?php if (PermisosVerificar::verificarPermisoAccion('compras', 'ver') || PermisosVerificar::verificarPermisoAccion('proveedores', 'ver')): ?>
          <li class="menu-item-group">
            <details class="group">
              <summary class="flex cursor-pointer list-none items-center justify-between rounded-lg p-3 hover:bg-green-100 hover:bg-green-600 hover:text-white">
                <div class="flex items-center">
                  <span><i class="fa-solid fa-cart-shopping icon"></i></span>
                  <span class="ml-5">Gestionar Compras</span>
                </div>
                <span class="shrink-0 transition duration-300 group-open:rotate-180">
                  <i class="fa-solid fa-chevron-down text-xs"></i>
                </span>
              </summary>
              <ul class="ml-5 mt-1 space-y-1">
                <?php if (PermisosVerificar::verificarPermisoAccion('compras', 'ver')): ?>
                <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/compras" class="flex items-center text-sm">
                    <span><i class="fa-solid fa-cart-shopping icon"></i></span>
                    <span class="ml-5">Compras</span>
                  </a>
                </li>
                <?php endif; ?>
                <?php if (PermisosVerificar::verificarPermisoAccion('proveedores', 'ver')): ?>
                <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/proveedores" class="flex items-center text-sm">
                    <span><i class="fa-solid fa-truck icon"></i></span>
                    <span class="ml-5">Proveedores</span>
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
              <summary class="flex cursor-pointer list-none items-center justify-between rounded-lg p-3 hover:bg-green-100 hover:bg-green-600 hover:text-white">
                <div class="flex items-center">
                  <span><i class="fa-solid fa-cogs icon"></i></span>
                  <span class="ml-5">Gestionar Produccion</span>
                </div>
                <span class="shrink-0 transition duration-300 group-open:rotate-180">
                  <i class="fa-solid fa-chevron-down text-xs"></i>
                </span>
              </summary>
              <ul class="ml-5 mt-1 space-y-1">
                <?php if (PermisosVerificar::verificarPermisoAccion('produccion', 'ver')): ?>
                <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/produccion" class="flex items-center text-sm">
                    <span><i class="fa-solid fa-industry icon"></i></span>
                    <span class="ml-5">Produccion</span>
                  </a>
                </li>
                <?php endif; ?>
                <?php if (PermisosVerificar::verificarPermisoAccion('empleados', 'ver')): ?>
                <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/empleados" class="flex items-center text-sm">
                    <span><i class="fa-solid fa-user-group icon"></i></span>
                    <span class="ml-5">Empleados</span>
                  </a>
                </li>
                <?php endif; ?>
                <?php if (PermisosVerificar::verificarPermisoAccion('productos', 'ver')): ?>
                <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/productos" class="flex items-center text-sm">
                    <span><i class="fa-solid fa-box icon"></i></span>
                    <span class="ml-5">Productos</span>
                  </a>
                </li>
                <?php endif; ?>
                <?php if (PermisosVerificar::verificarPermisoAccion('categorias', 'ver')): ?>
                <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/categorias" class="flex items-center text-sm">
                    <span><i class="fa-solid fa-tags icon"></i></span>
                    <span class="ml-5">Categorias</span>
                  </a>
                </li>
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
              <summary class="flex cursor-pointer list-none items-center justify-between rounded-lg p-3 hover:bg-green-100 hover:bg-green-600 hover:text-white">
                <div class="flex items-center">
                  <span><i class="fa-solid fa-cogs icon"></i></span>
                  <span class="ml-5">Gestionar Pagos</span>
                </div>
                <span class="shrink-0 transition duration-300 group-open:rotate-180">
                  <i class="fa-solid fa-chevron-down text-xs"></i>
                </span>
              </summary>
              <ul class="ml-5 mt-1 space-y-1">
                <?php if (PermisosVerificar::verificarPermisoAccion('romana', 'ver')): ?>
                <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/romana" class="flex items-center text-sm">
                    <span><i class="fa-solid fa-weight-scale icon"></i></span>
                    <span class="ml-5">Pagos</span>
                  </a>
                </li>
                <?php endif; ?>
                <?php if (PermisosVerificar::verificarPermisoAccion('personas', 'ver')): ?>
                <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/personas" class="flex items-center text-sm">
                    <span><i class="fa-solid fa-user-shield icon"></i></span>
                    <span class="ml-5">Personas</span>
                  </a>
                </li>
                <?php endif; ?>
                <?php if (PermisosVerificar::verificarPermisoAccion('tasas', 'ver')): ?>
                <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/tasas" class="flex items-center text-sm">
                    <span><i class="fas fa-coins icon"></i></span>
                    <span class="ml-5">Historico de Tasas BCV</span>
                  </a>
                </li>
                <?php endif; ?>
              </ul>
            </details>
          </li>
          <?php endif; ?>

          <!-- Movimientos de existencias -->
          <?php if (PermisosVerificar::verificarPermisoAccion('movimientos', 'ver')): ?>
          <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
            <a href="/project/movimientos" class="flex items-center">
              <span><i class="fa-solid fa-boxes-stacked icon"></i></span>
              <span class="ml-5">Gestionar Movimientos de existencias</span>
            </a>
          </li>
          <?php endif; ?>

          <!-- Sueldos Temporales -->
          <?php if (PermisosVerificar::verificarPermisoAccion('sueldos_temporales', 'ver')): ?>
          <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
            <a href="/project/movimientos" class="flex items-center">
              <span><i class="fa-solid fa-money-bill-wave icon"></i></span>
              <span class="ml-5">Gestionar Sueldos Temporales</span>
            </a>
          </li>
          <?php endif; ?>

          <!-- Gestionar Ventas -->
          <?php if (PermisosVerificar::verificarPermisoAccion('ventas', 'ver') || PermisosVerificar::verificarPermisoAccion('clientes', 'ver')): ?>
          <li class="menu-item-group">
            <details class="group">
              <summary class="flex cursor-pointer list-none items-center justify-between rounded-lg p-3 hover:bg-green-100 hover:bg-green-600 hover:text-white">
                <div class="flex items-center">
                  <span><i class="fa-solid fa-cash-register icon"></i></span>
                  <span class="ml-5">Gestionar Ventas</span>
                </div>
                <span class="shrink-0 transition duration-300 group-open:rotate-180">
                  <i class="fa-solid fa-chevron-down text-xs"></i>
                </span>
              </summary>
              <ul class="ml-5 mt-1 space-y-1">
                <?php if (PermisosVerificar::verificarPermisoAccion('ventas', 'ver')): ?>
                <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/ventas" class="flex items-center">
                    <span><i class="fa-solid fa-file-invoice-dollar icon"></i></span>
                    <span class="ml-5">Ventas</span>
                  </a>
                </li>
                <?php endif; ?>
                <?php if (PermisosVerificar::verificarPermisoAccion('clientes', 'ver')): ?>
                <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/clientes" class="flex items-center text-sm">
                    <span><i class="fa-solid fa-users icon"></i></span>
                    <span class="ml-5">Clientes</span>
                  </a>
                </li>
                <?php endif; ?>
              </ul>
            </details>
          </li>
          <?php endif; ?>

          <!-- Generar Reportes -->
          <?php if (PermisosVerificar::verificarPermisoAccion('reportes', 'ver')): ?>
          <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
            <a href="/project/movimientos" class="flex items-center">
              <span><i class="fa-solid fa-file-lines icon"></i></span>
              <span class="ml-5">Generar Reportes</span>
            </a>
          </li>
          <?php endif; ?>

          <!-- Seguridad -->
          <?php if (
            PermisosVerificar::verificarPermisoAccion('usuarios', 'ver') ||
            PermisosVerificar::verificarPermisoAccion('roles', 'ver') ||
            PermisosVerificar::verificarPermisoAccion('RolesPermisos', 'ver') ||
            PermisosVerificar::verificarPermisoAccion('modulos', 'ver') ||
            PermisosVerificar::verificarPermisoAccion('rolesmodulos', 'ver')
          ): ?>
          <li class="menu-item-group">
            <details class="group">
              <summary class="flex cursor-pointer list-none items-center justify-between rounded-lg p-3 hover:bg-green-100 hover:bg-green-600 hover:text-white">
                <div class="flex items-center">
                  <span><i class="fa-solid fa-user-lock icon"></i></span>
                  <span class="ml-5">Seguridad</span>
                </div>
                <span class="shrink-0 transition duration-300 group-open:rotate-180">
                  <i class="fa-solid fa-chevron-down text-xs"></i>
                </span>
              </summary>
              <?php if (PermisosVerificar::verificarPermisoAccion('usuarios', 'ver')): ?>
              <ul class="ml-5 mt-1 space-y-1">
                <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/usuarios" class="flex items-center text-sm">
                    <span><i class="fa-solid fa-user icon"></i></span>
                    <span class="ml-5">Gestionar Usuarios</span>
                  </a>
                </li>
              </ul>
              <?php endif; ?>
              <?php if (PermisosVerificar::verificarPermisoAccion('roles', 'ver')): ?>
              <ul class="ml-5 mt-1 space-y-1">
                <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/roles" class="flex items-center text-sm">
                    <span><i class="fa-solid fa-user-tag icon"></i></span>
                    <span class="ml-5">Gestionar Rol</span>
                  </a>
                </li>
              </ul>
              <?php endif; ?>
              <?php if (PermisosVerificar::verificarPermisoAccion('RolesPermisos', 'ver')): ?>
              <ul class="ml-5 mt-1 space-y-1">
                <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/RolesPermisos" class="flex items-center text-sm">
                    <span><i class="fa-solid fa-key icon"></i></span>
                    <span class="ml-5">Asignar Permisos</span>
                  </a>
                </li>
              </ul>
              <?php endif; ?>
              <?php if (PermisosVerificar::verificarPermisoAccion('modulos', 'ver')): ?>
              <ul class="ml-5 mt-1 space-y-1">
                <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/modulos" class="flex items-center text-sm">
                    <span><i class="fa-solid fa-puzzle-piece icon"></i></span>
                    <span class="ml-5">Modulos</span>
                  </a>
                </li>
              </ul>
              <?php endif; ?>
              <?php if (PermisosVerificar::verificarPermisoAccion('rolesmodulos', 'ver')): ?>
              <ul class="ml-5 mt-1 space-y-1">
                <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/rolesmodulos" class="flex items-center text-sm">
                    <span><i class="fa-solid fa-link icon"></i></span>
                    <span class="ml-5">Asignar Modulos</span>
                  </a>
                </li>
              </ul>
               <ul class="ml-5 mt-1 space-y-1">
                <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/bitacora" class="flex items-center text-sm">
                    <span><i class="fa-solid fa-user-shield icon"></i></span>
                    <span class="ml-5">Bitacora</span>
                  </a>
                </li>
              </ul>
              <?php endif; ?>
            </details>
          </li>
          <?php endif; ?>
        </ul>
        <ul>
          <li class="menu-item rounded-lg p-3  hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
            <a href="<?= base_url(); ?>/logout" class="flex items-center">
              <span><i class="fa-solid fa-right-to-bracket"></i></span>
              <span class="ml-5">Cerrar Sesión</span>
            </a>
          </li>
        </ul>
      </nav>
    </aside>
</body>

<script>
  document.addEventListener("DOMContentLoaded", () => {
    const currentPath = window.location.pathname;
    const pathDisplay = document.getElementById("current-path-display");
    if (pathDisplay) {
      pathDisplay.textContent = currentPath;
    }

    const navLinks = document.querySelectorAll("aside nav a");

    navLinks.forEach((link) => {
      const linkPath = link.getAttribute("href");
      const listItem = link.closest("li");

      if (listItem) {
        listItem.classList.remove("bg-green-600", "text-white");

        if (linkPath === currentPath) {
          listItem.classList.add("bg-green-600", "text-white");
          listItem.classList.remove("hover:bg-green-100", "hover:text-black");
          const detailsParent = link.closest("details");
          if (detailsParent) {
            detailsParent.setAttribute("open", "");
          }
        }
      }
    });
    const groupItems = document.querySelectorAll("li.menu-item-group");
    groupItems.forEach(groupLi => {
      const hasActiveChild = groupLi.querySelector('details li.bg-green-600');
      if (!hasActiveChild) {
        groupLi.classList.remove("bg-green-600", "text-white");
        const summary = groupLi.querySelector('summary');
        if (summary) {
          summary.classList.remove("text-white");
        }
      } else {
        const summary = groupLi.querySelector('summary');
        if (summary) {
          summary.classList.remove("bg-green-600");
        }
      }
    });
  });
</script>