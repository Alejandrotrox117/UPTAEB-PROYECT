<?php 
headerAdmin($data); 
$permisos = $data['permisos'] ?? []; 
?>
<!-- Main Content -->
<main class="flex-1 p-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold">Hola, <?= $_SESSION['usuario_nombre'] ?? 'Usuario' ?> 👋</h2>
        <input type="text" placeholder="Search" class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none">
    </div>

    <div class="min-h-screen mt-4">
        <h1 class="text-3xl font-bold text-gray-900">Historial de Romana</h1>
        <p class="text-green-500 text-lg">Romana</p>

        <div class="bg-white p-6 mt-6 rounded-2xl shadow-md">
            <div class="flex justify-between items-center mb-4">
                <?php if (!$permisos['puede_ver']): ?>
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded w-full">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm">Acceso limitado: Solo puede ver la información básica.</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div style="overflow-x: auto;">
                <table id="TablaRomana" class="w-full text-left border-collapse mt-6">
                    <thead>
                        <tr class="text-gray-500 text-sm border-b">
                            <th class="py-2">Peso</th>
                            <th class="py-2">Fecha y Hora</th>
                            <th class="py-2">Fecha y Hora de Consulta</th>
                            <?php if ($permisos['puede_editar'] || $permisos['puede_eliminar']): ?>
                            <th class="py-2">Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="text-gray-900">
                        <!-- Las filas se cargarán por JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
</div>
<div id="permisosUsuario" data-permisos='<?= json_encode($permisos) ?>' style="display:none"></div>
<?php footerAdmin($data); ?>
