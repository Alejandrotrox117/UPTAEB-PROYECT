<?php
// Verificar permisos de administrador
$permisos = PermisosModuloVerificar::getPermisosUsuarioModulo('usuarios');
if (!$permisos['total']) {
    header('Location: ' . base_url() . '/error');
    exit();
}
?>

<div class="container-fluid">
    <!-- Encabezado -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="bg-white rounded-lg shadow-sm border-0 p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-shield-alt me-2 text-primary"></i>
                            Panel de Administración
                        </h1>
                        <p class="text-muted mb-0">Gestión de seguridad y backups del sistema</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-info btn-sm" id="btnActualizarInfo">
                            <i class="fas fa-sync-alt me-1"></i>
                            Actualizar Info
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Información del Sistema -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Información del Sistema
                    </h5>
                </div>
                <div class="card-body">
                    <div id="infoSistema">
                        <div class="text-center py-3">
                            <i class="fas fa-spinner fa-spin me-2"></i>
                            Cargando información del sistema...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gestión de Backups -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-database me-2"></i>
                            Gestión de Backups
                        </h5>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-light btn-sm" id="btnActualizarBackups">
                                <i class="fas fa-sync-alt me-1"></i>
                                Actualizar Lista
                            </button>
                            <button type="button" class="btn btn-warning btn-sm" id="btnCrearBackup">
                                <i class="fas fa-plus me-1"></i>
                                Crear Backup
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Alertas -->
                    <div id="alertaBackup" class="alert alert-info d-none" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <span id="mensajeAlerta"></span>
                    </div>

                    <!-- Lista de Backups -->
                    <div id="listaBackups">
                        <div class="text-center py-3">
                            <i class="fas fa-spinner fa-spin me-2"></i>
                            Cargando lista de backups...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación -->
<div class="modal fade" id="modalConfirmacion" tabindex="-1" aria-labelledby="modalConfirmacionLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConfirmacionLabel">Confirmar Acción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="mensajeConfirmacion"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmarAccion">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- Estilos adicionales -->
<style>
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}

.info-item {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 1rem;
}

.info-item h6 {
    color: #495057;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.backup-item {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 0.75rem;
    transition: all 0.2s ease;
}

.backup-item:hover {
    background: #e9ecef;
    border-color: #adb5bd;
}

.backup-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.btn-xs {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    line-height: 1.2;
}

.text-size {
    font-size: 0.875rem;
    color: #6c757d;
}

.progress-crear {
    display: none;
    margin-top: 1rem;
}

@media (max-width: 768px) {
    .backup-actions {
        flex-direction: column;
    }
    
    .backup-actions .btn {
        width: 100%;
    }
}
</style>
