<?php headerAdmin($data); ?>
<body class="bg-blue-50">
    <div class="flex h-screen">
   
        <!-- Sidebar -->
        <aside class="w-84 bg-white shadow-md p-5 flex flex-col">
            <h1 class="text-2xl font-bold text-green-600">Recuperadora</h1>
            <nav class="mt-5">
                <ul>
                    <li class="p-3 rounded-lg hover:bg-green-100  hover:p-3 hover:bg-green-600 hover:text-white hover:rounded-lg "><span><i class="fa-solid fa-weight-scale  icon "></i></span><span class="ml-5">Dashboard</span></li>
                    <li class="p-3 rounded-lg hover:bg-green-100  hover:p-3 hover:bg-green-600 hover:text-white hover:rounded-lg "><span><i class="fa-solid fa-weight-scale  icon "></i></span><span class="ml-5">Romana</span></li>
                    <li class="p-3 rounded-lg hover:bg-green-100  hover:p-3 hover:bg-green-600 hover:text-white hover:rounded-lg "><span><i class="fa-solid fa-weight-scale  icon "></i></span><span class="ml-5">Compra de Materiales</span></li>
                    <li class="p-3 rounded-lg hover:bg-green-100  hover:p-3 hover:bg-green-600 hover:text-white hover:rounded-lg "><span><i class="fa-solid fa-weight-scale  icon "></i></span><span class="ml-5">Movimiento de Inventarios</span></li>
                    <li class="p-3 rounded-lg hover:bg-green-100  hover:p-3 hover:bg-green-600 hover:text-white hover:rounded-lg "><span><i class="fa-solid fa-weight-scale  icon "></i></span><span class="ml-5">Clasidficacion de Materiales</span></li>
                    <li class="p-3 rounded-lg hover:bg-green-100  hover:p-3 hover:bg-green-600 hover:text-white hover:rounded-lg "><span><i class="fa-solid fa-weight-scale  icon "></i></span><span class="ml-5">Empleados temporales</span></li>
                    <li class="p-3 rounded-lg hover:bg-green-100  hover:p-3 hover:bg-green-600 hover:text-white hover:rounded-lg "><span><i class="fa-solid fa-weight-scale  icon "></i></span><span class="ml-5">Ventas de Materiales </span></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-6">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold">Hola, Richard 👋</h2>

                <input type="text" placeholder="Search" class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none">
            </div>
            <div class="flex justify-between bg-white p-6 rounded-2xl shadow-md space-x-8 mt-5">
                <!-- Tarjeta 1 -->
                <div class="flex items-center space-x-4">
                    <div class="flex items-center justify-center w-16 h-16 bg-green-100 rounded-full">
                        <i class="fa-solid fa-weight-scale text-green-500 text-3xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Pesado total</p>
                        <p class="text-gray-900 text-2xl font-bold">2,535Kg</p>
                        <div class="flex items-center text-green-500 text-sm font-semibold">
                            <i class="fa-solid fa-arrow-up text-xs"></i>
                            <span class="ml-1">16% </span>
                            <span class="text-gray-500 font-normal ml-1">día anterior</span>
                        </div>
                    </div>
                </div>

                <!-- Separador -->
                <div class="border-l border-gray-300 h-16"></div>

                <!-- Tarjeta 2 -->
                <div class="flex items-center space-x-4">
                    <div class="flex items-center justify-center w-16 h-16 bg-green-100 rounded-full">
                        <i class="fa-solid fa-box text-green-500 text-3xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Total cartón</p>
                        <p class="text-gray-900 text-2xl font-bold">2,085Kg</p>
                        <div class="flex items-center text-green-500 text-sm font-semibold">
                            <i class="fa-solid fa-arrow-up text-xs"></i>
                            <span class="ml-1">16% </span>
                            <span class="text-gray-500 font-normal ml-1">día anterior</span>
                        </div>
                    </div>
                </div>

                <!-- Separador -->
                <div class="border-l border-gray-300 h-16"></div>

                <!-- Tarjeta 3 -->
                <div class="flex items-center space-x-4">
                    <div class="flex items-center justify-center w-16 h-16 bg-green-100 rounded-full">
                        <i class="fa-solid fa-file text-green-500 text-3xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Total archivo</p>
                        <p class="text-gray-900 text-2xl font-bold">450Kg</p>
                        <div class="flex items-center text-red-500 text-sm font-semibold">
                            <i class="fa-solid fa-arrow-down text-xs"></i>
                            <span class="ml-1">60% </span>
                            <span class="text-gray-500 font-normal ml-1">día anterior</span>
                        </div>
                    </div>
                </div>
                <div class="border-l border-gray-300 h-16"></div>

                <!-- Tarjeta 3 -->
                <div class="flex items-center space-x-4">
                    <div class="flex items-center justify-center w-16 h-16 bg-green-100 rounded-full">
                        <i class="fa-solid fa-file text-green-500 text-3xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Total archivo</p>
                        <p class="text-gray-900 text-2xl font-bold">450Kg</p>
                        <div class="flex items-center text-red-500 text-sm font-semibold">
                            <i class="fa-solid fa-arrow-down text-xs"></i>
                            <span class="ml-1">60% </span>
                            <span class="text-gray-500 font-normal ml-1">día anterior</span>
                        </div>
                    </div>
                </div>
            </div>


            <div class=" min-h-screen mt-4">
                <h1 class="text-3xl font-bold text-gray-900">Romana</h1>
                <p class="text-green-500 text-lg">Pesado de mercancía</p>

                <div class="bg-white p-6 mt-6 rounded-2xl shadow-md">
                    <div class="flex justify-between items-center mb-4">
                        <a href="vista copy.html" class="bg-green-500 text-white px-6 py-2 rounded-lg font-semibold">Registrar</a>
                        <div class="flex space-x-4">
                            <div class="relative">
                                <i class="fa-solid fa-search absolute left-3 top-2 text-gray-400"></i>
                                <input type="text" placeholder="Search" class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none">
                            </div>
                            <div class="relative">
                                <span class="text-gray-600">Ver:</span>
                                <select class="border rounded-lg py-2 px-4 ml-2 focus:outline-none">
                                    <option>Más reciente</option>
                                    <option>Más antiguo</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-gray-500 text-sm border-b">
                                <th class="py-2">Nro</th>
                                <th class="py-2">Fecha</th>
                                <th class="py-2">Peso</th>
                                <th class="py-2">Unidad</th>
                                <th class="py-2">Material</th>
                                <th class="py-2">Proveedor</th>
                                <th class="py-2">Finalidad</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-900">
                            <tr class="border-b">
                                <td class="py-3">01</td>
                                <td>10-03-2025</td>
                                <td>1,735</td>
                                <td>Kg</td>
                                <td>Cartón</td>
                                <td>Fernando Gutierrez</td>
                                <td>Venta de materiales</td>
                            </tr>
                            <tr class="border-b">
                                <td class="py-3">02</td>
                                <td>10-03-2025</td>
                                <td>100</td>
                                <td>Kg</td>
                                <td>Cartón</td>
                                <td>Jhonkleider Rodriquez</td>
                                <td>Empacado por empleado</td>
                            </tr>
                            <tr>
                                <td class="py-3">03</td>
                                <td>10-03-2025</td>
                                <td>450</td>
                                <td>Kg</td>
                                <td>Archivo</td>
                                <td>Humberto Hernandez</td>
                                <td>Empacado por empleado</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="flex justify-between items-center mt-4 text-gray-500 text-sm">
                        <span>Mostrando del 1 al 8 de 700 registros</span>
                        <div class="flex items-center space-x-2">
                            <button class="px-3 py-1 border rounded-md text-gray-500">&lt;</button>
                            <button class="px-3 py-1 bg-green-500 text-white rounded-md">1</button>
                            <button class="px-3 py-1 border rounded-md text-gray-500">2</button>
                            <button class="px-3 py-1 border rounded-md text-gray-500">3</button>
                            <button class="px-3 py-1 border rounded-md text-gray-500">4</button>
                            <span class="px-3">...</span>
                            <button class="px-3 py-1 border rounded-md text-gray-500">40</button>
                            <button class="px-3 py-1 border rounded-md text-gray-500">&gt;</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <?php footerAdmin($data); ?>
