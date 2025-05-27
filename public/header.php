<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <link rel="shortcut icon" href="/project/app/assets/img/favicon.svg" type="image/x-icon">
  <title>Recuperadora</title>
  <meta name="description" content="Recuperadora de materiales reciclables">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script> -->
  <link rel="stylesheet" href="/project/app/assets/styles/styles.css">
  <link href="/project/app/assets/fontawesome/css/all.min.css" rel="stylesheet">
  <link href="/project/app/assets/fontawesome/css/solid.css" rel="stylesheet">
  <link href="/project/app/assets/fontawesome/css/fontawesome.css" rel="stylesheet">
  <link href="/project/app/assets/fontawesome/css/brands.css" rel="stylesheet">
  <link href="/project/app/assets/DataTables/datatables.css" rel="stylesheet"> <!-- DataTables -->
  <link href="/project/app/assets/DataTables/responsive.dataTables.css" rel="stylesheet">
  <link rel="stylesheet" href="/project/app/assets/sweetAlert/sweetalert2.min.css">
  <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" /> DataTables -->
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

          <li class="menu-item-group">
            <details class="group">
              <summary class="flex cursor-pointer list-none items-center justify-between rounded-lg p-3 hover:bg-green-100 hover:bg-green-600 hover:text-white">
                <div class="flex items-center">
                  <span><i class="fa-solid fa-cogs icon"></i></span>
                  <span class="ml-5">Procesos</span>
                </div>
                <span class="shrink-0 transition duration-300 group-open:rotate-180">
                  <i class="fa-solid fa-chevron-down text-xs"></i>
                </span>
              </summary>
              <ul class="ml-5 mt-1 space-y-1">
                <li
                  class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/romana" class="flex items-center text-sm">
                    <span><i class="fa-solid fa-weight-scale icon"></i></span>
                    <span class="ml-5">Romana</span>
                  </a>
                </li>
                <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/clasificacion" class="flex items-center text-sm">
                    <span><i class="fa-solid fa-layer-group icon"></i></span>
                    <span class="ml-4">Clasificacion de Materiales</span>
                  </a>
                </li>
                <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/empleados" class="flex items-center text-sm">
                    <span><i class="fa-solid fa-shield-halved icon"></i></span>
                    <span class="ml-5">Empleados</span>
                  </a>
                </li>
                <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/produccion" class="flex items-center text-sm">
                    <span><i class="fa-solid fa-shield-halved icon"></i></span>
                    <span class="ml-5">Produccion</span>
                  </a>
                </li>
              </ul>
            </details>
          </li>
          <li class="menu-item-group">
            <details class="group">
              <summary class="flex cursor-pointer list-none items-center justify-between rounded-lg p-3 hover:bg-green-100 hover:bg-green-600 hover:text-white">
                <div class="flex items-center">
                  <span><i class="fa-solid fa-cart-shopping icon"></i></span>
                  <span class="ml-5">Gestionar Compras de Materiales</span>
                </div>
                <span class="shrink-0 transition duration-300 group-open:rotate-180">
                  <i class="fa-solid fa-chevron-down text-xs"></i>
                </span>
              </summary>
              <ul class="ml-5 mt-1 space-y-1">
                <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/compras" class="flex items-center text-sm">
                    <span><i class="fa-solid fa-cart-shopping icon"></i></span>
                    <span class="ml-5">Compras</span>
                  </a>
                </li>
                <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/proveedores" class="flex items-center text-sm">
                    <span><i class="fa-solid fa-truck icon"></i></span>
                    <span class="ml-5">Proveedores</span>
                  </a>
                </li>
              </ul>
            </details>
          </li>

          <li class="menu-item-group">
            <details class="group">
              <summary class="flex cursor-pointer list-none items-center justify-between rounded-lg p-3 hover:bg-green-100 hover:bg-green-600 hover:text-white">
                <div class="flex items-center">
                  <span><i class="fa-solid fa-boxes-stacked icon"></i></span>
                  <span class="ml-5">Gestión de Productos</span>
                </div>
                <span class="shrink-0 transition duration-300 group-open:rotate-180">
                  <i class="fa-solid fa-chevron-down text-xs"></i>
                </span>
              </summary>
              <ul class="ml-5 mt-1 space-y-1">
                <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/productos" class="flex items-center text-sm">
                    <span><i class="fa-solid fa-box icon"></i></span>
                    <span class="ml-5">Productos</span>
                  </a>
                </li>
                <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/categorias" class="flex items-center text-sm">
                    <span><i class="fa-solid fa-tags icon"></i></span>
                    <span class="ml-5">Categorias</span>
                  </a>
                </li>
                <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/tasas" class="flex items-center text-sm">
                    <span><i class="fas fa-coins icon"></i></span>
                    <span class="ml-5">Historico de Tasas BCV</span>
                  </a>
                </li>
              </ul>
            </details>
          </li>

          <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
            <a href="/project/inventario" class="flex items-center">
              <span><i class="fa-solid fa-warehouse icon"></i></span>
              <span class="ml-5">Inventario</span>
            </a>
          </li>
          <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
            <a href="/project/ventas" class="flex items-center">
              <span><i class="fa-solid fa-file-invoice-dollar icon"></i></span>
              <span class="ml-5">Ventas de Materiales</span>
            </a>
          </li>
          <li class="menu-item-group">
            <details class="group">
              <summary class="flex cursor-pointer list-none items-center justify-between rounded-lg p-3 hover:bg-green-100 hover:bg-green-600 hover:text-white">
                <div class="flex items-center">
                  <span><i class="fa-solid fa-shield-halved icon"></i></span>
                  <span class="ml-5">Personas</span>
                </div>
                <span class="shrink-0 transition duration-300 group-open:rotate-180">
                  <i class="fa-solid fa-chevron-down text-xs"></i>
                </span>
              </summary>
              <ul class="ml-5 mt-1 space-y-1">
                <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/clientes" class="flex items-center text-sm">
                    <span><i class="fa-solid fa-user-shield icon"></i></span>
                    <span class="ml-5">Clientes</span>
                  </a>
                </li>
              </ul>
              <ul class="ml-5 mt-1 space-y-1">
                <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/personas" class="flex items-center text-sm">
                    <span><i class="fa-solid fa-user-shield icon"></i></span>
                    <span class="ml-5">Personas</span>
                  </a>
                </li>
              </ul>
            </details>
          </li>

          <li class="menu-item-group">
            <details class="group">
              <summary class="flex cursor-pointer list-none items-center justify-between rounded-lg p-3 hover:bg-green-100 hover:bg-green-600 hover:text-white">
                <div class="flex items-center">
                  <span><i class="fa-solid fa-shield-halved icon"></i></span>
                  <span class="ml-5">Seguridad</span>
                </div>
                <span class="shrink-0 transition duration-300 group-open:rotate-180">
                  <i class="fa-solid fa-chevron-down text-xs"></i>
                </span>
              </summary>
              <ul class="ml-5 mt-1 space-y-1">
                <li class="menu-item rounded-lg p-3 hover:bg-green-100 hover:rounded-lg hover:bg-green-600 hover:p-3 hover:text-white">
                  <a href="/project/roles" class="flex items-center text-sm">
                    <span><i class="fa-solid fa-user-shield icon"></i></span>
                    <span class="ml-5">Roles</span>
                  </a>
                </li>
              </ul>
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