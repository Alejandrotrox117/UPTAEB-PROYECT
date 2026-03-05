<?php headerAdmin($data); ?>

<!-- Main Content -->
<main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 bg-gray-100">
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Hola, Richard 👋</h2>
    </div>

    <div class="mt-0 sm:mt-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Historial de Tasas de Cambio</h1>
        <p class="text-green-600 text-base md:text-lg">Tasas Oficiales del Banco Central de Venezuela (BCV)</p>

        <div class="bg-white p-4 md:p-6 mt-6 rounded-2xl shadow-lg">

            <!-- Fila combinada para Botones de Actualización y Pestañas de Moneda -->
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-8 w-full">
                <form id="formActualizarUSD" class="flex-shrink-0 w-full sm:w-auto">
                    <input type="hidden" name="moneda" value="" />
                    <button id="moneda" type="submit"
                        class="w-full sm:w-auto bg-green-500 hover:bg-green-600 text-white px-6 py-2 md:py-3 rounded-lg font-semibold shadow transition-colors">
                        <i class="fas fa-sync-alt mr-2"></i>Actualizar Tasas
                    </button>
                </form>

                <nav id="tabsHistorial"
                    class="flex items-center p-1 space-x-1 rtl:space-x-reverse text-sm text-gray-600 bg-gray-200 dark:bg-gray-700 rounded-xl justify-center w-full sm:w-auto">
                    <button role="tab" type="button" data-moneda="USD"
                        class="tab-button w-1/2 sm:w-40 flex justify-center whitespace-nowrap items-center h-10 px-4 font-medium rounded-lg outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-inset text-yellow-600 shadow bg-white dark:text-white dark:bg-yellow-600 transition-all duration-200"
                        aria-selected="true">
                        Dólar ($)
                    </button>

                    <button role="tab" type="button" data-moneda="EUR"
                        class="tab-button w-1/2 sm:w-40 flex justify-center whitespace-nowrap items-center h-10 px-4 font-medium rounded-lg outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-inset hover:text-gray-800 focus:text-yellow-600 dark:text-gray-400 dark:hover:text-gray-300 dark:focus:text-gray-400 transition-all duration-200"
                        aria-selected="false">
                        Euro (€)
                    </button>
                </nav>
            </div>
            <!-- Contenido de cada pestaña -->
            <div id="historialUSD" class="overflow-x-auto w-full relative">
                <table id="tablaTasasUsd" class="display stripe hover responsive nowrap fuente-tabla-pequena"
                    style="width:100%; min-width: 800px;">
                    <thead>
                        <tr class="text-gray-600 text-xs uppercase tracking-wider bg-gray-50 border-b border-gray-200">
                            <th class="px-3 py-3 text-left">Código</th>
                            <th class="px-3 py-3 text-left">Tasa a VES</th>
                            <th class="px-3 py-3 text-left">Fecha Publicación BCV</th>
                            <th class="px-3 py-3 text-left">Fecha Captura</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyTasasUsd" class="text-gray-700 text-sm divide-y divide-gray-200"></tbody>
                </table>
                <div id="mensajeNoDatosUsd" class="hidden text-center py-4 text-gray-500">No hay datos USD</div>
            </div>

            <div id="historialEUR" class="hidden overflow-x-auto w-full relative">
                <table id="tablaTasasEur" class="display stripe hover responsive nowrap fuente-tabla-pequena"
                    style="width:100%; min-width: 800px;">
                    <thead>
                        <tr class="text-gray-600 text-xs uppercase tracking-wider bg-gray-50 border-b border-gray-200">
                            <th class="px-3 py-3 text-left">Código</th>
                            <th class="px-3 py-3 text-left">Tasa a VES</th>
                            <th class="px-3 py-3 text-left">Fecha Publicación BCV</th>
                            <th class="px-3 py-3 text-left">Fecha Captura</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyTasasEur" class="text-gray-700 text-sm divide-y divide-gray-200"></tbody>
                </table>
                <div id="mensajeNoDatosEur" class="hidden text-center py-4 text-gray-500">No hay datos EUR</div>
            </div>

            <p class="mt-8 text-sm text-gray-500">
                <strong>Nota Importante:</strong> La obtención de tasas depende de la estructura del sitio web del
                BCV...
            </p>
        </div>
    </div>
</main>
</div>

<script src="<?= base_url('app/assets/js/ayuda/tasas-tour.js'); ?>"></script>
<?php footerAdmin($data); ?>