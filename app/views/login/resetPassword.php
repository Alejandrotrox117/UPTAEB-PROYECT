<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="/project/app/assets/img/favicon.svg" type="image/x-icon">
    <title>Recuperadora</title>
    <meta name="description" content="Recuperadora de materiales reciclables">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/project/app/assets/styles/styles.css">

    <link href="/project/app/assets/fontawesome/css/all.min.css" rel="stylesheet">
    <link href="/project/app/assets/fontawesome/css/solid.css" rel="stylesheet">
    <link href="/project/app/assets/fontawesome/css/fontawesome.css" rel="stylesheet">
    <link href="/project/app/assets/fontawesome/css/brands.css" rel="stylesheet">
    <link href="/project/app/assets/DataTables/datatables.css" rel="stylesheet">
    <link rel="stylesheet" href="/project/app/assets/sweetAlert/sweetalert2.min.css">

</head>

<body class="min-h-screen flex items-center justify-center bg-gradient-to-br ">
    <div class="w-full max-w-md bg-white rounded-lg shadow-2xl p-6">
        <div class="text-center mb-6">
            <img src="<?= base_url() ?>/app/assets/img/favicon.svg" class="mx-auto w-12 h-12 mb-4" alt="Logo">
            <h2 class="text-2xl font-bold text-gray-800">Recuperar Contraseña</h2>
            <p class="text-gray-600 text-sm mt-2">Ingresa tu correo electrónico para recibir instrucciones</p>
        </div>

        <form id="formResetPass" action="<?= base_url() ?>/login/enviarResetPassword" method="POST" class="space-y-4">
            <div>
                <label for="txtEmailReset" class="block text-sm font-medium text-gray-700 mb-2">
                    Correo Electrónico:
                </label>
                <input
                    type="email"
                    id="txtEmailReset"
                    name="txtEmailReset"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    required
                    placeholder="tu@correo.com">
                <div id="error-email" class="text-red-500 text-xs mt-1 hidden"></div>
            </div>

            <button
                type="submit"
                id="btnResetPass"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors">
                Enviar Enlace de Recuperación
            </button>
        </form>

        <div class="text-center mt-6">
            <a
                href="<?= base_url() ?>/login"
                class="text-blue-600 hover:text-blue-800 text-sm hover:underline">
                ← Volver al inicio de sesión
            </a>
        </div>
    </div>


    <script src="<?= base_url() ?>/app/assets/js/functions_resetpassword.js"></script>
    <script type="text/javascript" src="/project/app/assets/sweetAlert/sweetalert2.all.min.js"></script>
</body>

</html>