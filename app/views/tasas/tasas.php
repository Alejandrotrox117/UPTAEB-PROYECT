<?php headerAdmin($data); ?>

<!-- Main Content -->
<main class="flex-1 p-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold">Hola, Richard 👋</h2>
        <input type="text" placeholder="Search" class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none">
    </div>

    <div class="min-h-screen mt-4">
        <h1 class="text-3xl font-bold text-gray-900">Historial de Tasas de Cambio</h1>
        <p class="text-green-500 text-lg">Tasas Oficiales del Banco Central de Venezuela (BCV)</p>

        <div class="bg-white p-6 rounded-2xl shadow-md mt-6">

        <!-- Fila combinada para Botones de Actualización y Pestañas de Moneda -->
        <div class="flex flex-row justify-between items-center gap-4 mb-8">
            <form id="formActualizarUSD" class="flex-shrink-0">
                <input type="hidden" name="moneda" value="" />
                <button id="moneda" type="submit" class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg font-semibold shadow">
                    Actualizar Tasas
                </button>
            </form>

            <nav id="tabsHistorial" class="flex items-center p-1 space-x-1 rtl:space-x-reverse text-sm text-gray-600 bg-gray-200 dark:bg-gray-700 rounded-xl justify-center">
                <button
                    role="tab"
                    type="button"
                    data-moneda="USD"
                    class="tab-button flex whitespace-nowrap items-center h-8 w-36 sm:w-40 px-4 font-medium rounded-lg outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-inset text-yellow-600 shadow bg-white dark:text-white dark:bg-yellow-600"
                    aria-selected="true"
                >
                    Dólar ($)
                </button>

                <button
                    role="tab"
                    type="button"
                    data-moneda="EUR"
                    class="tab-button flex whitespace-nowrap items-center h-8 w-36 sm:w-40 px-4 font-medium rounded-lg outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-inset hover:text-gray-800 focus:text-yellow-600 dark:text-gray-400 dark:hover:text-gray-300 dark:focus:text-gray-400"
                    aria-selected="false"
                >
                    Euro (€)
                </button>
            </nav>
        </div>
        <!-- Contenido de cada pestaña -->
        <div id="historialUSD">
            <table id="tablaTasasUsd" class="w-full">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Tasa a VES</th>
                        <th>Fecha Publicación BCV</th>
                        <th>Fecha Captura</th>
                    </tr>
                </thead>
                <tbody id="tbodyTasasUsd"></tbody>
            </table>
            <div id="mensajeNoDatosUsd" class="hidden">No hay datos USD</div>
        </div>

        <div id="historialEUR" class="hidden">
            <table id="tablaTasasEur" class="w-full">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Tasa a VES</th>
                        <th>Fecha Publicación BCV</th>
                        <th>Fecha Captura</th>
                    </tr>
                </thead>
                <tbody id="tbodyTasasEur"></tbody>
            </table>
            <div id="mensajeNoDatosEur" class="hidden">No hay datos EUR</div>
        </div>

            <p class="mt-8 text-sm text-gray-500">
                <strong>Nota Importante:</strong> La obtención de tasas depende de la estructura del sitio web del BCV...
            </p>
        </div>
    </div>
</main>
</div>

<?php footerAdmin($data); ?>
