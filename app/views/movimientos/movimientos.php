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
        <h1 class="text-3xl font-bold text-gray-900">Gestión de Movimientos de existencias</h1>
        <p class="text-green-500 text-lg">Movimientos</p>

        <div class="bg-white p-6 mt-6 rounded-2xl shadow-md">
            <div class="flex justify-between items-center mb-4">
                <?php if ($permisos['puede_crear']): ?>
                    <a href="vista copy.html" class="bg-green-500 text-white px-6 py-2 rounded-lg font-semibold">Registrar</a>
                <?php else: ?>
                    <div class="text-gray-500 text-sm">
                        <i class="fas fa-lock mr-2"></i>No tiene permisos para registrar movimientos
                    </div>
                <?php endif; ?>
                <?php if (!$permisos['puede_ver']): ?>
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded">
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

            <table id="TablaMovimiento" class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-gray-500 text-sm border-b">
                        <th class="py-2">Nro. Movimiento</th>
                     
                        <th class="py-2">Producto</th>
                        <th class="py-2">Tipo de Movimiento</th>
                        <th class="py-2">Entrada</th>
                        <th class="py-2">Salida</th>
                        <th class="py-2">Stock Resultante</th>
                        <th class="py-2">Estatus</th>
                        <?php if ($permisos['puede_editar'] || $permisos['puede_eliminar']): ?>
                            <th class="py-2">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="text-gray-900">
                </tbody>
            </table>
        </div>
    </div>
</main>
</div>
<div id="permisosUsuario" data-permisos='<?= json_encode($permisos) ?>' style="display:none"></div>
<?php footerAdmin($data); ?>