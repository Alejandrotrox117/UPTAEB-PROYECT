<?php
// filepath: c:\xampp\htdocs\project\core\Auth.php
require_once "app/models/RolesModel.php";

class Auth {
    private $rolesModel;

    public function __construct() {
        $this->rolesModel = new RolesModel();
    }

    public function tienePermiso($usuarioId, $permisoNombre) {
        $permisos = $this->rolesModel->getPermisosPorUsuario($usuarioId);
        foreach ($permisos as $permiso) {
            if ($permiso['nombre'] === $permisoNombre) {
                return true;
            }
        }
        return false;
    }
}