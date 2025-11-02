<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="<?= base_url('app/assets/img/favicon.svg'); ?>" type="image/x-icon">
    <title>Recuperadora</title>
    <meta name="description" content="Recuperadora de materiales reciclables">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= base_url('app/assets/styles/styles.css'); ?>">

    <link href="<?= base_url('app/assets/fontawesome/css/all.min.css'); ?>" rel="stylesheet">
    <link href="<?= base_url('app/assets/fontawesome/css/solid.css'); ?>" rel="stylesheet">
    <link href="<?= base_url('app/assets/fontawesome/css/fontawesome.css'); ?>" rel="stylesheet">
    <link href="<?= base_url('app/assets/fontawesome/css/brands.css'); ?>" rel="stylesheet">
    <link href="<?= base_url('app/assets/DataTables/datatables.css'); ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('app/assets/sweetAlert/sweetalert2.min.css'); ?>">

</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-600 to-purple-700 p-2">
    <div class="w-full max-w-md bg-white rounded-lg shadow-2xl p-6">
        <div class="text-center mb-6">
            <img src="<?= base_url() ?>/app/assets/img/favicon.svg" class="mx-auto w-12 h-12 mb-4" alt="Logo">
            <h2 class="text-2xl font-bold text-gray-800">Nueva Contraseña</h2>
            <p class="text-gray-600 text-sm mt-2">Ingresa tu nueva contraseña</p>
        </div>
        
        <form id="formNuevaPassword" action="<?= base_url() ?>/login/actualizarPassword" method="POST" class="space-y-4">
            <!-- Token CSRF -->
            <input type="hidden" name="csrf_token" value="<?= $data['csrf_token'] ?? '' ?>">
            <input type="hidden" name="token" value="<?= $data['token'] ?>">>
            
            <div>
                <label for="txtPassword" class="block text-sm font-medium text-gray-700 mb-2">
                    Nueva Contraseña:
                </label>
                <input 
                    type="password" 
                    id="txtPassword" 
                    name="txtPassword"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    required
                    minlength="6"
                    placeholder="Mínimo 6 caracteres"
                >
                <div id="error-password" class="text-red-500 text-xs mt-1 hidden"></div>
            </div>
            
            <div>
                <label for="txtConfirmPassword" class="block text-sm font-medium text-gray-700 mb-2">
                    Confirmar Contraseña:
                </label>
                <input 
                    type="password" 
                    id="txtConfirmPassword" 
                    name="txtConfirmPassword"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    required
                    minlength="6"
                    placeholder="Repite la contraseña"
                >
                <div id="error-confirm" class="text-red-500 text-xs mt-1 hidden"></div>
            </div>
            
            <button 
                type="submit" 
                id="btnActualizar"
                class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors"
            >
                Actualizar Contraseña
            </button>
        </form>
        
        <div class="text-center mt-6">
            <a 
                href="<?= base_url() ?>/login" 
                class="text-blue-600 hover:text-blue-800 text-sm hover:underline"
            >
                ← Volver al inicio de sesión
            </a>
        </div>
    </div>

    <!-- Definir base_url para JavaScript ANTES de cargar los scripts que lo usan -->
    <script>
        const base_url = "<?= rtrim(base_url(), '/'); ?>";
        // También definir como propiedad global
        window.base_url = base_url;
    </script>

    <script src="<?= base_url() ?>/app/assets/js/functions_resetpass.js"></script>
    <script type="text/javascript" src="<?= base_url('app/assets/sweetAlert/sweetalert2.all.min.js'); ?>"></script>
   
</body>
</html>
