<?php headerAdmin($data); ?>

<!-- Main Content -->
<main class="flex-1 p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Historial de Tasas de Cambio</h1>
    </div>

    <div class="min-h-screen">
        <p class="text-green-600 text-lg mb-6">Tasas Oficiales del Banco Central de Venezuela (BCV)</p>

        <!-- Contenedor para Mensajes Flash (JS lo llenará, el error estático se quita de aquí) -->
        <div id="contenedorMensajesFlash" class="mb-6"></div>

        <div class="bg-white p-6 rounded-2xl shadow-md">
            <!-- Fila combinada para Botones de Actualización y Pestañas de Moneda -->
            <div class="flex flex-col lg:flex-row justify-between items-center gap-6 mb-8">

                <!-- Parte Izquierda: Sección de Actualizar Tasas -->
                <div class="flex flex-col lg:flex-row lg:justify-start lg:items-center gap-x-6 gap-y-4 mb-8 flex-wraps">
                    <div class="flex flex-wrap gap-3"> 
                        <form id="formActualizarUSD" class="inline-block">
                            <input type="hidden" name="moneda" value="USD">
                            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded-lg font-semibold text-sm transition duration-150 ease-in-out whitespace-nowrap">
                                Actualizar Tasas
                            </button>
                        </form>
                    </div>
                    <!-- Parte Derecha: Pestañas para filtrar historial -->
                <nav id="tabsHistorial" class="flex items-center W-20p-1 space-x-1 rtl:space-x-reverse text-sm text-gray-600 bg-gray-200 dark:bg-gray-700 rounded-xl w-full sm:w-auto justify-center">
                    <button role="tab" type="button"
                        data-moneda="USD"
                        class="tab-button flex whitespace-nowrap items-center h-8 w-36 sm:w-40 px-4 font-medium rounded-lg outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-inset text-yellow-600 shadow bg-white dark:text-white dark:bg-yellow-600"
                        aria-selected="true">
                        Dólar ($)
                    </button>

                    <button role="tab" type="button"
                        data-moneda="EUR"
                        class="tab-button flex whitespace-nowrap items-center h-8 w-36 sm:w-40 px-4 font-medium rounded-lg outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-inset hover:text-gray-800 focus:text-yellow-600 dark:text-gray-400 dark:hover:text-gray-300 dark:focus:text-gray-400">
                        Euro (€)
                    </button>
                </nav>
                </div>
            </div>

            <!-- Contenedores de Historial (se mostrará/ocultará con JS) -->
            <div id="historialUSD" class="historial-contenido mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Historial Reciente Dólar (USD)</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-gray-600 text-sm border-b border-gray-300">
                                <th class="py-3 px-4 font-semibold">Moneda</th>
                                <th class="py-3 px-4 font-semibold">Tasa a VES</th>
                                <th class="py-3 px-4 font-semibold">Fecha Publicación BCV</th>
                                <th class="py-3 px-4 font-semibold">Fecha de Captura</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyTasasUsd" class="text-gray-700"></tbody>
                    </table>
                </div>
                <p id="mensajeNoDatosUsd" class="text-gray-600 italic hidden">No hay datos históricos para USD.</p>
            </div>

            <div id="historialEUR" class="historial-contenido mb-8 hidden">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Historial Reciente Euro (EUR)</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-gray-600 text-sm border-b border-gray-300">
                                <th class="py-3 px-4 font-semibold">Moneda</th>
                                <th class="py-3 px-4 font-semibold">Tasa a VES</th>
                                <th class="py-3 px-4 font-semibold">Fecha Publicación BCV</th>
                                <th class="py-3 px-4 font-semibold">Fecha de Captura</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyTasasEur" class="text-gray-700"></tbody>
                    </table>
                </div>
                <p id="mensajeNoDatosEur" class="text-gray-600 italic hidden">No hay datos históricos para EUR.</p>
            </div>

            <p class="mt-8 text-sm text-gray-500">
                <strong>Nota Importante:</strong> La obtención de tasas depende de la estructura del sitio web del BCV...
            </p>
        </div>
    </div>
</main>
</div>

<?php footerAdmin($data); ?>
