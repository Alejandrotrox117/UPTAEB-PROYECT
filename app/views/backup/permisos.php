<?php 
require_once('helpers/helpers.php');
headerAdmin($data);
?>

<main class="flex-1 p-6">
    <div class="bg-red-50 border border-red-200 rounded-lg p-6 max-w-md mx-auto mt-20">
        <div class="flex items-center space-x-3">
            <div class="flex-shrink-0">
                <svg class="h-8 w-8 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-medium text-red-800">
                    Acceso Denegado
                </h3>
                <p class="mt-2 text-sm text-red-700">
                    No tienes permisos suficientes para acceder al módulo de gestión de backups.
                </p>
                <p class="mt-1 text-xs text-red-600">
                    Contacta con el administrador del sistema para solicitar los permisos necesarios.
                </p>
            </div>
        </div>
        
        <div class="mt-6 flex justify-center">
            <a href="<?= base_url('dashboard'); ?>" 
               class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                <i class="fas fa-arrow-left mr-2"></i>
                Volver al Dashboard
            </a>
        </div>
    </div>
</main>

<?php footerAdmin($data); ?>
