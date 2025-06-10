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
    
    <!-- reCAPTCHA script -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>

<body id="bodyLogin" class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-600 to-purple-700 p-2">
    <div class="login-container w-full max-w-xs bg-white rounded-lg shadow-2xl p-3 space-y-2">
        <div class="text-center">
            <img src="/project/app/assets/img/favicon.svg" class="mx-auto w-8 h-8 mb-1" alt="Logo Empresa">
            <h2 class="text-base sm:text-lg font-bold text-gray-800">Bienvenido</h2>
        </div>
        
        <form action="<?= base_url() ?>/login/loginUser" method="POST" id="formLogin" class="space-y-2">
            <div>
                <label for="txtEmail" class="block text-xs font-medium text-gray-700 mb-0.5">
                    Correo Electrónico:
                </label>
                <input 
                    type="email" 
                    id="txtEmail" 
                    name="txtEmail"
                    class="w-full px-2 py-1.5 border border-gray-300 rounded-md focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors text-sm"
                    required
                >
                <div id="error-email-vacio" class="text-red-500 text-xs mt-0.5 hidden"></div>
                <div id="error-email-formato" class="text-red-500 text-xs mt-0.5 hidden"></div>
            </div>
            
            <div>
                <label for="txtPass" class="block text-xs font-medium text-gray-700 mb-0.5">
                    Contraseña:
                </label>
                <div class="password-container">
                    <input 
                        type="password" 
                        id="txtPass" 
                        name="txtPass"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded-md focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors text-sm"
                        required
                    >
                    <div id="error-txtPass-vacio" class="text-red-500 text-xs mt-0.5 hidden"></div>
                    <div id="error-txtPass-formato" class="text-red-500 text-xs mt-0.5 hidden"></div>
                </div>
            </div>
            
            <!-- reCAPTCHA widget -->
            <div class="flex justify-center">
                <div 
                    class="g-recaptcha transform scale-75 origin-center" 
                    data-sitekey="<?= $data['recaptcha_site_key'] ?>"
                ></div>
            </div>
            <div id="error-recaptcha" class="text-red-500 text-xs text-center hidden">
                Por favor, verifica que no eres un robot
            </div>
            
            <button 
                type="submit" 
                class="w-full bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold py-1.5 px-3 rounded-md transition-all duration-200 hover:shadow-md text-sm"
            >
                Iniciar Sesión
            </button>
        </form>
        
        <div class="text-center">
            <a 
                href="resetpass.php" 
                class="text-blue-600 hover:text-purple-600 text-xs hover:underline transition-colors"
            >
                ¿Olvidaste tu contraseña?
            </a>
        </div>
    </div>

    <?php footerAdmin($data); ?>
</body>
</html>