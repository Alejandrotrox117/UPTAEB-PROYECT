<?php
use App\Helpers\PermisosModuloVerificar;

headerAdmin($data);
$permisos = PermisosModuloVerificar::getPermisosUsuarioModulo('clientes');
?>

<?= renderJavaScriptData('permisosClientes', $permisos); ?>

<main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 bg-gray-100">
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Hola, <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?> 👋</h2>
    </div>

    <div class="mt-0 sm:mt-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900"><?php echo $data['page_title']; ?></h1>
        <p class="text-green-600 text-base md:text-lg">Gestión integral de clientes del sistema</p>
    </div>

    <?php if (!$permisos['ver']): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 mt-6 rounded-r-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700 font-medium">
                        <strong>Acceso Restringido:</strong> No tienes permisos para ver la lista de clientes.
                    </p>
                </div>
            </div>
        </div>
    <?php else: ?>

        <div class="bg-white p-4 md:p-6 mt-6 rounded-2xl shadow-lg">
            <div class="flex justify-between items-center mb-6">
                <?php if ($permisos['crear']): ?>
                    <button id="abrirModalBtn"
                        class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 md:px-6 rounded-lg font-semibold shadow text-sm md:text-base">
                        <i class="fas fa-user-plus mr-1 md:mr-2"></i> Registrar Cliente
                    </button>
                <?php else: ?>
                    <div class="bg-gray-100 px-4 py-2 md:px-6 rounded-lg text-gray-500 text-sm md:text-base">
                        <i class="fas fa-lock mr-1 md:mr-2"></i> Sin permisos para crear clientes
                    </div>
                <?php endif; ?>

                <?php if ($permisos['exportar']): ?>
                    <button id="btnExportarClientes"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 md:px-6 rounded-lg font-semibold shadow text-sm md:text-base ml-2">
                        <i class="fas fa-download mr-1 md:mr-2"></i> Exportar
                    </button>
                <?php endif; ?>
            </div>


            <div class="overflow-x-auto w-full relative">
                <table id="Tablaclientes" class="display stripe hover responsive nowrap fuente-tabla-pequena" style="width:100%; min-width: 700px;">
                    <thead>
                        <tr class="text-gray-600 text-xs uppercase tracking-wider bg-gray-50 border-b border-gray-200">
                       
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 text-sm divide-y divide-gray-200">
                     
                    </tbody>
                </table>
                <div id="loaderTableClientes" class="flex justify-center items-center my-4" style="display: none;">
                    <div class="dot-flashing"></div>
                </div>
            </div>
        </div>

    <?php endif; ?>
</main>

<!-- RESTO DE MODALES IGUAL QUE EN LA RESPUESTA ANTERIOR -->
<?php if ($permisos['crear']): ?>
    <!-- Modal Registrar Cliente -->
    <div id="clienteModal" class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] transition-opacity duration-300 z-50 p-4">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-md">
            <!-- Encabezado -->
            <div class="px-4 py-4 border-b flex justify-between items-center">
               <h3 class="text-xl font-bold text-gray-800">
        <i class="mr-2 text-green-600 fas fa-user-plus"></i>
        Registrar Nuevo Cliente
      </h3>
                <button type="button" id="cerrarModalBtnX" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Formulario -->
            <form id="clienteForm" class="px-4 py-4">
                <div class="grid grid-cols-2 md:grid-cols-2 gap-4">
                    <div class="">
                        <input type="hidden" id="idcliente" name="idcliente" value="">
                        <label for="cedula" class="block font-medium mb-2">Cédula <span class="text-red-500" >*</span> </label>
                        <input type="text" id="cedula" name="cedula"
                            class="w-full border rounded-lg px-4 py-2 text-lg focus:ring-2 focus:ring-green-400"
                            placeholder="V-12345678" required>
                        <div id="error-cedula-vacio" class="text-red-500 text-sm mt-1 hidden"></div>
                        <div id="error-cedula-formato" class="text-red-500 text-sm mt-1 hidden"></div>
                    </div>
                    <div class="">
                        <label for="nombre" class="block text-gray-700 font-medium mb-2">Nombre<span class="text-red-500">*</span></label>
                        <input type="text" id="nombre" name="nombre"
                            class="w-full border rounded-lg px-4 py-2 text-lg focus:outline-none focus:ring-2 focus:ring-green-400"
                            placeholder="Ingrese el nombre" required>
                        <div id="error-nombre-vacio" class="text-red-500 text-sm mt-1 hidden"></div>
                        <div id="error-nombre-formato" class="text-red-500 text-sm mt-1 hidden"></div>
                    </div>
                    <div class="">
                        <label for="apellido" class="block text-gray-700 font-medium mb-2">Apellido<span class="text-red-500">*</span></label>
                        <input type="text" id="apellido" name="apellido"
                            class="w-full border rounded-lg px-4 py-2 text-lg focus:outline-none focus:ring-2 focus:ring-green-400"
                            placeholder="Ingrese el apellido" required>
                        <div id="error-apellido-vacio" class="text-red-500 text-sm mt-1 hidden"></div>
                        <div id="error-apellido-formato" class="text-red-500 text-sm mt-1 hidden"></div>
                    </div>
                    <div class="">
                        <label for="telefono_principal" class="block text-gray-700 font-medium mb-2">Teléfono Principal<span class="text-red-500">*</span></label>
                        <input type="text" id="telefono_principal" name="telefono_principal"
                            class="w-full border rounded-lg px-4 py-2 text-lg focus:outline-none focus:ring-2 focus:ring-green-400"
                            placeholder="Ingrese el teléfono" required>
                        <div id="error-telefono_principal-vacio" class="text-red-500 text-sm mt-1 hidden"></div>
                        <div id="error-telefono_principal-formato" class="text-red-500 text-sm mt-1 hidden"></div>
                    </div>
                   
                    <div class="">
                        <label for="direccion" class="block text-gray-700 font-medium mb-2">Dirección</span></label>
                        <input type="text" id="direccion" name="direccion"
                            class="w-full border rounded-lg px-4 py-2 text-lg focus:outline-none focus:ring-2 focus:ring-green-400"
                            placeholder="Ingrese la dirección" >
                        <div id="error-direccion-vacio" class="text-red-500 text-sm mt-1 hidden"></div>
                        <div id="error-direccion-formato" class="text-red-500 text-sm mt-1 hidden"></div>
                    </div>
                </div>
                <div class="grid-flow-row mt-4">
                    <label for="observaciones" class="block text-gray-700 font-medium mb-2">Observaciones</label>
                    <input type="text" id="observaciones" name="observaciones"
                        class="w-full border rounded-lg px-4 py-2 text-lg focus:outline-none focus:ring-2 focus:ring-green-400"
                        placeholder="Ingrese las observaciones">
                    <div id="error-observaciones-formato" class="text-red-500 text-sm mt-1 hidden"></div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-2 gap-4 mt-4">
                    <div class="">
                        <button type="button" id="cerrarModalBtn"
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition text-lg">
                            Cancelar
                        </button>
                    </div>
                    <div class="flex justify-end">

                        <button type="submit" id="registrarClienteBtn"
                            class="px-4 ml-10 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition text-lg">
                            Registrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($permisos['editar']): ?>
    <!-- Modal Actualizar Cliente -->
    <div id="modalActualizarCliente" class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center bg-transparent bg-opacity-30 backdrop-blur-[2px] transition-opacity duration-300 z-50 p-4">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-md">
            <!-- Encabezado -->
            <div class="px-4 py-4 border-b flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-800">Actualizar Cliente</h3>
                <button type="button" id="btnCerrarModalActualizarX" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Formulario -->
            <form id="formActualizarCliente" class="px-4 py-4">
                <div class="grid grid-cols-2 md:grid-cols-2 gap-4">
                    <!--  CAMPO OCULTO PARA ID -->
                    <input type="hidden" id="idclienteActualizar" name="idcliente" value="">

                    <div class="">
                        <label for="cedulaActualizar" class="block font-medium mb-2">Cédula<span class="text-red-500">*</span></label>
                        <input type="text" id="cedulaActualizar" name="cedula"
                            class="w-full border rounded-lg px-4 py-2 text-lg focus:ring-2 focus:ring-green-400"
                            placeholder="V-12345678" required>
                        <div id="error-cedulaActualizar-vacio" class="text-red-500 text-sm mt-1 hidden"></div>
                        <div id="error-cedulaActualizar-formato" class="text-red-500 text-sm mt-1 hidden"></div>
                    </div>

                    <div class="">
                        <label for="nombreActualizar" class="block text-gray-700 font-medium mb-2">Nombre<span class="text-red-500">*</span></label>
                        <input type="text" id="nombreActualizar" name="nombre"
                            class="w-full border rounded-lg px-4 py-2 text-lg focus:outline-none focus:ring-2 focus:ring-green-400"
                            placeholder="Ingrese el nombre" required>
                        <div id="error-nombreActualizar-vacio" class="text-red-500 text-sm mt-1 hidden"></div>
                        <div id="error-nombreActualizar-formato" class="text-red-500 text-sm mt-1 hidden"></div>
                    </div>

                    <div class="">
                        <label for="apellidoActualizar" class="block text-gray-700 font-medium mb-2">Apellido <span class="text-red-500">*</span></label>
                        <input type="text" id="apellidoActualizar" name="apellido"
                            class="w-full border rounded-lg px-4 py-2 text-lg focus:outline-none focus:ring-2 focus:ring-green-400"
                            placeholder="Ingrese el apellido" required>
                        <div id="error-apellidoActualizar-vacio" class="text-red-500 text-sm mt-1 hidden"></div>
                        <div id="error-apellidoActualizar-formato" class="text-red-500 text-sm mt-1 hidden"></div>
                    </div>

                    <div class="">
                        <label for="telefono_principalActualizar" class="block text-gray-700 font-medium mb-2">Teléfono Principal<span class="text-red-500">*</span></label>
                        <input type="text" id="telefono_principalActualizar" name="telefono_principal"
                            class="w-full border rounded-lg px-4 py-2 text-lg focus:outline-none focus:ring-2 focus:ring-green-400"
                            placeholder="Ingrese el teléfono principal" required>
                        <div id="error-telefono_principalActualizar-vacio" class="text-red-500 text-sm mt-1 hidden"></div>
                        <div id="error-telefono_principalActualizar-formato" class="text-red-500 text-sm mt-1 hidden"></div>
                    </div>

                    <div class="">
                        <label for="direccionActualizar" class="block text-gray-700 font-medium mb-2">Dirección <span class="text-red-500">*</span></label>
                        <input type="text" id="direccionActualizar" name="direccion"
                            class="w-full border rounded-lg px-4 py-2 text-lg focus:outline-none focus:ring-2 focus:ring-green-400"
                            placeholder="Ingrese la dirección" >
                        <div id="error-direccionActualizar-vacio" class="text-red-500 text-sm mt-1 hidden"></div>
                        <div id="error-direccionActualizar-formato" class="text-red-500 text-sm mt-1 hidden"></div>
                    </div>

               
                    <div class="">
                        <label for="estatusActualizar" class="block text-gray-700 font-medium mb-2">Estatus<span class="text-red-500">*</span></label>
                        <select id="estatusActualizar" name="estatus"
                            class="w-full border rounded-lg px-4 py-2 text-lg focus:outline-none focus:ring-2 focus:ring-green-400">
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                        <div id="error-estatusActualizar-vacio" class="text-red-500 text-sm mt-1 hidden"></div>
                    </div>
                </div>

                <div class="grid-flow-row mt-4">
                    <label for="observacionesActualizar" class="block text-gray-700 font-medium mb-2">Observaciones</label>
                    <input type="text" id="observacionesActualizar" name="observaciones"
                        class="w-full border rounded-lg px-4 py-2 text-lg focus:outline-none focus:ring-2 focus:ring-green-400"
                        placeholder="Ingrese las observaciones">
                    <div id="error-observacionesActualizar-formato" class="text-red-500 text-sm mt-1 hidden"></div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-2 gap-4 mt-4">
                    <div class="">
                        <button type="button" id="btnCancelarModalActualizar"
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition text-lg">
                            Cancelar
                        </button>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" id="btnActualizarCliente"
                            class="px-4 ml-10 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition text-lg">
                            <i class="fas fa-save mr-2"></i> Actualizar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Modal Ver Cliente -->
<div id="modalVerCliente" class="opacity-0 pointer-events-none fixed inset-0 flex items-center justify-center  bg-opacity-50 backdrop-blur-sm transition-opacity duration-300 z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden w-11/12 max-w-md">
        <!-- Encabezado -->
        <div class="px-6 py-4 border-b border-gray-200 bg-green-50">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-bold text-green-800">
                    <i class="fas fa-eye mr-2"></i>Detalles del Cliente
                </h3>
                <button type="button" id="btnCerrarModalVerCliente" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Contenido -->
        <div class="px-6 py-4">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Cédula:</label>
                        <p id="verClienteCedula" class="text-gray-900 font-medium">-</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Estatus:</label>
                        <p id="verClienteEstatus" class="text-gray-900 font-medium">-</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Nombre:</label>
                        <p id="verClienteNombre" class="text-gray-900 font-medium">-</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Apellido:</label>
                        <p id="verClienteApellido" class="text-gray-900 font-medium">-</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Teléfono Principal:</label>
                    <p id="verClienteTelefono" class="text-gray-900 font-medium">-</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Dirección:</label>
                    <p id="verClienteDireccion" class="text-gray-900 font-medium">-</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Observaciones:</label>
                    <p id="verClienteObservaciones" class="text-gray-900 font-medium">-</p>
                </div>
            </div>
        </div>

        <!-- Pie del modal -->
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end">
            <button type="button" id="btnCerrarModalVerCliente2"
                class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200 font-medium">
                <i class="fas fa-times mr-2"></i>Cerrar
            </button>
        </div>
    </div>
</div>



<?php footerAdmin($data); ?>