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
        <header class="bg-green-600 text-white p-4 flex justify-between items-center">
            <h1 class="text-lg font-bold">Recuperadora</h1>
            <nav>
                <ul class="flex space-x-4">
                    <?php if (isset($_SESSION['usuario_id'])): ?>
                        <!-- Mostrar si el usuario está logueado -->
                        <li>
                            <a href="/project/home" class="hover:underline">Inicio</a>
                        </li>
                        <li>
                            <a href="/project/login/logout" class="hover:underline">Cerrar Sesión</a>
                        </li>
                    <?php else: ?>
                        <!-- Mostrar si el usuario no está logueado -->
                        <li>
                            <a href="/project/login" class="hover:underline">Iniciar Sesión</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </header>

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