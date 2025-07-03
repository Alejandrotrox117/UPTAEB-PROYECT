<?php headerAdmin($data); ?>

<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-red-500 to-red-700 p-2">
    <div class="w-full max-w-md bg-white rounded-lg shadow-2xl p-6">
        <div class="text-center mb-6">
            <div class="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Token Inválido</h2>
            <p class="text-gray-600 text-sm mt-2">El enlace de recuperación no es válido</p>
        </div>
        
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <div class="flex items-center mb-3">
                <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                <h4 class="text-red-700 font-semibold">Enlace no válido</h4>
            </div>
            <p class="text-red-700 text-sm">
                <?= $data['error'] ?? 'El enlace de recuperación es inválido o ha expirado.' ?>
            </p>
        </div>
        
        <div class="space-y-4">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h5 class="text-blue-700 font-semibold mb-2">💡 ¿Por qué puede pasar esto?</h5>
                <ul class="text-blue-700 text-sm space-y-1">
                    <li class="flex items-start">
                        <span class="text-blue-500 mr-2">•</span>
                        <span>El enlace ha expirado (más de 1 hora desde que lo solicitaste)</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-blue-500 mr-2">•</span>
                        <span>El enlace ya fue utilizado para cambiar la contraseña</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-blue-500 mr-2">•</span>
                        <span>El enlace está incompleto o dañado</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-blue-500 mr-2">•</span>
                        <span>Hay un problema temporal con el sistema</span>
                    </li>
                </ul>
            </div>
            
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <h5 class="text-green-700 font-semibold mb-2">✅ ¿Qué puedes hacer?</h5>
                <ul class="text-green-700 text-sm space-y-1">
                    <li class="flex items-start">
                        <span class="text-green-500 mr-2">1.</span>
                        <span>Solicita un nuevo enlace de recuperación</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-green-500 mr-2">2.</span>
                        <span>Verifica tu correo electrónico inmediatamente</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-green-500 mr-2">3.</span>
                        <span>Usa el enlace dentro de la primera hora</span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="text-center mt-6 space-y-3">
            <a 
                href="<?= base_url() ?>/login/resetPassword" 
                class="inline-block w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors text-center"
            >
                Solicitar Nuevo Enlace
            </a>
            
            <a 
                href="<?= base_url() ?>/login" 
                class="text-blue-600 hover:text-blue-800 text-sm hover:underline"
            >
                ← Volver al inicio de sesión
            </a>
        </div>
    </div>

    <?php footerAdmin($data); ?>
</body>
</html>
