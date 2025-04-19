<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Redirigir al login si no hay sesión activa
if (!isset($_SESSION['usuario_id']) && $_SERVER['REQUEST_URI'] !== '/project/login') {
    header("Location: /project/login");
    exit;
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
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <link href="/project/app/assets/fontawesome/css/all.min.css" rel="stylesheet">
    <link href="/project/app/assets/fontawesome/css/solid.css" rel="stylesheet">
    <link href="/project/app/assets/fontawesome/css/fontawesome.css" rel="stylesheet">
    <link href="/project/app/assets/fontawesome/css/brands.css" rel="stylesheet">
    <link href="/project/app/assets/DataTables/datatables.css" rel="stylesheet"> <!-- DataTables -->
    <link href="/project/app/assets/DataTables/responsive.dataTables.css" rel="stylesheet"> <!-- DataTables -->
</head>

<body class="bg-blue-50">
    <div class="flex flex-col h-screen">
        <!-- Navbar -->


        <nav class="bg-white border-gray-200 ">
            <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
                <a href="https://flowbite.com/" class="flex items-center space-x-3 rtl:space-x-reverse">
                    <img src="/project/app/assets/img/LOGO.png" class="h-8" alt="Flowbite Logo" />
                    <span class="self-center text-2xl font-semibold whitespace-nowrap dark:text-black">Recuperadora la Pradera de Pavia</span>
                </a>
                <div class="flex items-center md:order-2 space-x-3 md:space-x-0 rtl:space-x-reverse">
                    <button type="button" class="flex text-sm bg-gray-800 rounded-full md:me-0 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600" id="user-menu-button" aria-expanded="false" data-dropdown-toggle="user-dropdown" data-dropdown-placement="bottom">
                        <span class="sr-only">Open user menu</span>
                        <img class="w-8 h-8 rounded-full" src="app\assets\img\cuenta.png" alt="user photo">
                    </button>
                    <!-- Dropdown menu -->
                    <div class="z-50 hidden my-4 text-base list-none bg-white divide-y divide-gray-100 rounded-lg shadow-sm dark:bg-gray-700 dark:divide-gray-600" id="user-dropdown">
                        <div class="px-4 py-3">
                            <span class="block text-sm text-gray-900 dark:text-white">
                                <?php  echo isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'Usuario'; ?>
                            </span>
                            <span class="block text-sm  text-gray-500 truncate dark:text-gray-400">name@flowbite.com</span>
                        </div>
                        <ul class="py-2" aria-labelledby="user-menu-button">

                            <?php if (isset($_SESSION['usuario_id'])): ?>
                                <!-- Mostrar si el usuario está logueado -->
                                <li>
                                    <a href="/project/home" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white">Inicio</a>
                                </li>
                                <li>
                                    <a href="/project/login/logout" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white">Cerrar Sesión</a>
                                </li>
                            <?php else: ?>
                                <!-- Mostrar si el usuario no está logueado -->
                                <li>
                                    <a href="/project/login" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white">Iniciar Sesión</a>
                                </li>
                            <?php endif; ?>

                        </ul>
                    </div>
                    <button data-collapse-toggle="navbar-user" type="button" class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600" aria-controls="navbar-user" aria-expanded="false">
                        <span class="sr-only">Open main menu</span>
                        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 17 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h15M1 7h15M1 13h15" />
                        </svg>
                    </button>
                </div>

            </div>
        </nav>


        <div class="flex flex-1">
            <!-- Sidebar -->
            <aside class="w-84 bg-white shadow-md p-5 flex flex-col">
                <img src="/project/app/assets/img/LOGO.png" alt="Recuperadora" class="text-2xl font-bold text-green-600">
                <nav class="mt-5">
                    <ul>
                        <!-- Dashboard -->
                        <li class="p-3 rounded-lg hover:bg-green-100 hover:bg-green-600 hover:text-white">
                            <a href="/project/dashboard" class="flex items-center">
                                <span><i class="fa-solid fa-tachometer-alt icon"></i></span>
                                <span class="ml-5">Dashboard</span>
                            </a>
                        </li>
                        <!-- Romana -->
                        <li class="p-3 rounded-lg hover:bg-green-100 hover:bg-green-600 hover:text-white">
                            <a href="/project/romana" class="flex items-center">
                                <span><i class="fa-solid fa-weight-scale icon"></i></span>
                                <span class="ml-5">Romana</span>
                            </a>
                        </li>
                        <!-- Compras -->
                        <li class="p-3 rounded-lg hover:bg-green-100 hover:bg-green-600 hover:text-white">
                            <a href="/project/compras" class="flex items-center">
                                <span><i class="fa-solid fa-cart-shopping icon"></i></span>
                                <span class="ml-5">Compra de Materiales</span>
                            </a>
                        </li>
                        <!-- Inventario -->
                        <li class="p-3 rounded-lg hover:bg-green-100 hover:bg-green-600 hover:text-white">
                            <a href="/project/inventario" class="flex items-center">
                                <span><i class="fa-solid fa-boxes-stacked"></i></span>
                                <span class="ml-5">Inventario</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </aside>
        </div>
    </div>
</body>

</html>