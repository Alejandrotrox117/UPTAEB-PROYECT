<?php
require_once "app/models/RolesModel.php";

class Auth {
    private $rolesModel;

    public function __construct() {
        $this->rolesModel = new RolesModel();
    }

    public function tienePermiso($usuarioId, $permisoNombre) {
        $permisos = $this->rolesModel->getPermisosPorUsuario($usuarioId);

        foreach ($permisos as $permiso) {
            if ($permiso['titulo'] === $permisoNombre && $permiso['lectura'] == 1) {
                return true;
            }
        }

        return false;
    }
}