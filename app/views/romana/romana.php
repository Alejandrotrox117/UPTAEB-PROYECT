<?php headerAdmin($data); ?>
<!-- Main Content -->
<main class="flex-1 p-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold">Hola, Richard 👋</h2>
        <input type="text" placeholder="Search" class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none">
    </div>

    <div class="min-h-screen mt-4">
        <h1 class="text-3xl font-bold text-gray-900">Historial de Romana</h1>
        <p class="text-green-500 text-lg">Romana</p>

        <div class="bg-white p-6 mt-6 rounded-2xl shadow-md">
            <div class="flex justify-between items-center mb-4">
            </div>

            <table id="TablaProductos" class="w-full text-left border-collapse mt-6">
                <thead>
                    <tr class="text-gray-500 text-sm border-b">
                        <th class="py-2">Peso</th>
                        <th class="py-2">Fecha y Hora</th>
                        <th class="py-2">Fecha y Hora de Consulta</th>
                    </tr>
                </thead>
                <tbody class="text-gray-900">
                    <td>

                    </td>
                </tbody>
            </table>
        </div>
    </div>
</main>
</div>
<?php footerAdmin($data); ?>
