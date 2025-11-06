<?php
namespace App\Helpers;

use App\Models\PermisosModel;

class PermisosHelper
{
    // Constantes de permisos según tu BD
    const SOLO_LECTURA = 1;
    const SOLO_REGISTRAR = 2;
    const SOLO_EDITAR = 3;
    const SOLO_ELIMINAR = 4;
    const REGISTRAR_Y_EDITAR = 5;
    const EDITAR_Y_ELIMINAR = 6;
    const REGISTRAR_Y_ELIMINAR = 7;
    const ACCESO_TOTAL = 8;

    private static function obtenerInstanciaModelo(): PermisosModel
    {
        return new PermisosModel();
    }

    public static function puedeVer(int $idUsuario, string $modulo): bool
    {
        $modelo = self::obtenerInstanciaModelo();
        $permisosPermitidos = [
            self::SOLO_LECTURA,
            self::REGISTRAR_Y_EDITAR,
            self::EDITAR_Y_ELIMINAR,
            self::REGISTRAR_Y_ELIMINAR,
            self::ACCESO_TOTAL
        ];
        
        error_log("PermisosHelper::puedeVer - Usuario: $idUsuario, Módulo: $modulo");
        $resultado = $modelo->verificarPermisoEspecifico($idUsuario, $modulo, $permisosPermitidos);
        error_log("PermisosHelper::puedeVer - Resultado: " . ($resultado ? 'SÍ' : 'NO'));
        
        return $resultado;
    }

    public static function puedeCrear(int $idUsuario, string $modulo): bool
    {
        $modelo = self::obtenerInstanciaModelo();
        $permisosPermitidos = [
            self::SOLO_REGISTRAR,
            self::REGISTRAR_Y_EDITAR,
            self::REGISTRAR_Y_ELIMINAR,
            self::ACCESO_TOTAL
        ];
        
        return $modelo->verificarPermisoEspecifico($idUsuario, $modulo, $permisosPermitidos);
    }

    public static function puedeEditar(int $idUsuario, string $modulo): bool
    {
        $modelo = self::obtenerInstanciaModelo();
        $permisosPermitidos = [
            self::SOLO_EDITAR,
            self::REGISTRAR_Y_EDITAR,
            self::EDITAR_Y_ELIMINAR,
            self::ACCESO_TOTAL
        ];
        
        return $modelo->verificarPermisoEspecifico($idUsuario, $modulo, $permisosPermitidos);
    }

    public static function puedeEliminar(int $idUsuario, string $modulo): bool
    {
        $modelo = self::obtenerInstanciaModelo();
        $permisosPermitidos = [
            self::SOLO_ELIMINAR,
            self::EDITAR_Y_ELIMINAR,
            self::REGISTRAR_Y_ELIMINAR,
            self::ACCESO_TOTAL
        ];
        
        return $modelo->verificarPermisoEspecifico($idUsuario, $modulo, $permisosPermitidos);
    }

    public static function tieneAccesoTotal(int $idUsuario, string $modulo): bool
    {
        $modelo = self::obtenerInstanciaModelo();
        return $modelo->verificarPermisoEspecifico($idUsuario, $modulo, [self::ACCESO_TOTAL]);
    }

    public static function puedeRegistrarYEditar(int $idUsuario, string $modulo): bool
    {
        $modelo = self::obtenerInstanciaModelo();
        $permisosPermitidos = [
            self::REGISTRAR_Y_EDITAR,
            self::ACCESO_TOTAL
        ];
        
        return $modelo->verificarPermisoEspecifico($idUsuario, $modulo, $permisosPermitidos);
    }

    public static function puedeEditarYEliminar(int $idUsuario, string $modulo): bool
    {
        $modelo = self::obtenerInstanciaModelo();
        $permisosPermitidos = [
            self::EDITAR_Y_ELIMINAR,
            self::ACCESO_TOTAL
        ];
        
        return $modelo->verificarPermisoEspecifico($idUsuario, $modulo, $permisosPermitidos);
    }

    public static function puedeRegistrarYEliminar(int $idUsuario, string $modulo): bool
    {
        $modelo = self::obtenerInstanciaModelo();
        $permisosPermitidos = [
            self::REGISTRAR_Y_ELIMINAR,
            self::ACCESO_TOTAL
        ];
        
        return $modelo->verificarPermisoEspecifico($idUsuario, $modulo, $permisosPermitidos);
    }

    /**
     * Obtiene todos los permisos de un usuario para un módulo
     */
    public static function obtenerPermisosUsuario(int $idUsuario, string $modulo): array
    {
        $modelo = self::obtenerInstanciaModelo();
        return $modelo->obtenerPermisosUsuarioModulo($idUsuario, $modulo);
    }

    /**
     * Verifica si el usuario tiene acceso al módulo (cualquier permiso)
     */
    public static function tieneAccesoModulo(int $idUsuario, string $modulo): bool
    {
        $modelo = self::obtenerInstanciaModelo();
        return $modelo->tieneAccesoModulo($idUsuario, $modulo);
    }

    /**
     * Obtiene todos los módulos a los que un usuario tiene acceso
     */
    public static function obtenerModulosUsuario(int $idUsuario): array
    {
        $modelo = self::obtenerInstanciaModelo();
        return $modelo->obtenerModulosUsuario($idUsuario);
    }
}
?>