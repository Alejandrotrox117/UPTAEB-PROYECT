<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="/project/app/assets/img/favicon.svg" type="image/x-icon">
    <title>Recuperadora</title>
    <meta name="description" content="Recuperadora de materiales reciclables">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--<link href="/project/app/assets/styles/styles.css" rel="stylesheet"> Tailwind -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="/project/app/assets/fontawesome/css/all.min.css" rel="stylesheet">
    <link href="/project/app/assets/fontawesome/css/solid.css" rel="stylesheet">
    <link href="/project/app/assets/fontawesome/css/fontawesome.css" rel="stylesheet">
    <link href="/project/app/assets/fontawesome/css/brands.css" rel="stylesheet">
    <link href="/project/app/assets/DataTables/datatables.css" rel="stylesheet"> <!-- DataTables -->
    <link href="/project/app/assets/DataTables/responsive.dataTables.css" rel="stylesheet"> <!-- DataTables -->
</head>

<body class="bg-blue-50">
    <div class="flex h-screen">

        <!-- Sidebar -->
        <aside class=" w-84 bg-white shadow-md p-5 flex flex-col">
            <img src="/project/app/assets/img/LOGO.png" alt="Recuperadora" class="text-2xl font-bold text-green-600">
            <nav class="mt-5">
                <ul>
                    <!-- Dashboard -->
                    <li
                        class="p-3 rounded-lg hover:bg-green-100  hover:p-3 hover:bg-green-600 hover:text-white hover:rounded-lg ">
                        <a href="/project/dashboard" class="flex items-center">
                            <span><i class="fa-solid fa-tachometer-alt icon"></i></span>
                            <span class="ml-5">Dashboard</span>
                        </a>
                    </li>
                    <!-- Romana -->
                    <li
                        class="p-3 rounded-lg hover:bg-green-100  hover:p-3 hover:bg-green-600 hover:text-white hover:rounded-lg ">
                        <a href="/project/romana" class="flex items-center">
                            <span><i class="fa-solid fa-weight-scale  icon "></i></span>
                            <span class="ml-5">Romana</span>
                        </a>
                    </li>
                    <!-- Compras -->
                    <li
                        class="p-3 rounded-lg hover:bg-green-100  hover:p-3 hover:bg-green-600 hover:text-white hover:rounded-lg ">
                        <a href="/project/compras" class="flex items-center">
                            <span><i class="fa-solid fa-cart-shopping icon"></i></span>
                            <span class="ml-5">Compra de Materiales</span>
                        </a>
                    </li>
                    <li
                        class="p-3 rounded-lg hover:bg-green-100  hover:p-3 hover:bg-green-600 hover:text-white hover:rounded-lg ">
                        <a href="/project/clasificacion" class="flex items-center">
                            <span><i class="fa-solid fa-layer-group icon"></i></span>
                            <span class="ml-5">Clasificacion de Materiales</span>
                        </a>
                    </li>
                    <li
                        class="p-3 rounded-lg hover:bg-green-100  hover:p-3 hover:bg-green-600 hover:text-white hover:rounded-lg ">
                        <a href="/project/personas" class="flex items-center">
                            <span><i class="fa-solid fa-user-clock icon"></i></span>
                            <span class="ml-5">Personas</span>
                        </a>
                    </li>
                    <li
                        class="p-3 rounded-lg hover:bg-green-100  hover:p-3 hover:bg-green-600 hover:text-white hover:rounded-lg ">
                        <a href="/project/ventas" class="flex items-center">
                            <span><i class="fa-solid fa-file-invoice-dollar icon"></i></span>
                            <span class="ml-5">Ventas de Materiales </span>
                        </a>
                    </li>
                    <li
                        class="p-3 rounded-lg hover:bg-green-100 hover:p-3 hover:bg-green-600 hover:text-white hover:rounded-lg">
                        <a href="/project/inventario" class="flex items-center">
                            <span><i class="fa-solid fa-boxes-stacked"></i></span>
                            <span class="ml-5">Inventario</span>
                        </a>
                    </li>
                    <li
                        class="p-3 rounded-lg hover:bg-green-100 hover:p-3 hover:bg-green-600 hover:text-white hover:rounded-lg">
                        <a href="/project/productos" class="flex items-center">
                            <span><i class="fa-solid fa-boxes-stacked"></i></span>
                            <span class="ml-5">Productos</span>
                        </a>
                    </li>
                    <li
                        class="p-3 rounded-lg hover:bg-green-100 hover:p-3 hover:bg-green-600 hover:text-white hover:rounded-lg">
                        <a href="/project/categorias" class="flex items-center">
                            <span><i class="fa-solid fa-boxes-stacked"></i></span>
                            <span class="ml-5">Categorias</span>
                        </a>
                    </li>

                    <li
                        class="p-3 rounded-lg hover:bg-green-100 hover:p-3 hover:bg-green-600 hover:text-white hover:rounded-lg">
                        <a href="/project/roles" class="flex items-center">
                            <span><i class="fa-solid fa-user-shield icon"></i></span>
                            <span class="ml-5">Roles</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>


</body>

</html>