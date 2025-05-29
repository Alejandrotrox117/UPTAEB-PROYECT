<?php


class PermisosHelper
{
   
    const SOLO_LECTURA = 1;
    const SOLO_EDITAR = 2;
    const SOLO_REGISTRAR = 3;
    const REGISTRAR_Y_EDITAR = 4;
    const SOLO_ELIMINAR = 5;
    const EDITAR_Y_ELIMINAR = 6;
    const REGISTRAR_Y_ELIMINAR = 7;
    const ACCESO_TOTAL = 8;

    private static $permisosModel;

    private static function getPermisosModel()
    {
        if (!self::$permisosModel) {
          
            require_once __DIR__ . '/../app/models/PermisosModel.php';
            self::$permisosModel = new PermisosModel();
        }
        return self::$permisosModel;
    }

  
    public static function puedeVer(int $idUsuario, string $modulo): bool
    {
        $permisosLectura = [
            self::SOLO_LECTURA,
            self::SOLO_ELIMINAR, 
            self::SOLO_EDITAR,
            self::SOLO_REGISTRAR,
            self::REGISTRAR_Y_EDITAR,
            self::EDITAR_Y_ELIMINAR,
            self::REGISTRAR_Y_ELIMINAR,
            self::ACCESO_TOTAL

        ];
        
        return self::getPermisosModel()->verificarPermisoEspecifico($idUsuario, $modulo, $permisosLectura);
    }

  
    public static function puedeCrear(int $idUsuario, string $modulo): bool
    {
        $permisosCrear = [
            self::SOLO_REGISTRAR,
            self::REGISTRAR_Y_EDITAR,
            self::REGISTRAR_Y_ELIMINAR,
            self::ACCESO_TOTAL
        ];
        
        return self::getPermisosModel()->verificarPermisoEspecifico($idUsuario, $modulo, $permisosCrear);
    }

    public static function puedeEditar(int $idUsuario, string $modulo): bool
    {
        $permisosEditar = [
            self::SOLO_EDITAR,
            self::REGISTRAR_Y_EDITAR,
            self::EDITAR_Y_ELIMINAR,
            self::ACCESO_TOTAL
        ];
        
        return self::getPermisosModel()->verificarPermisoEspecifico($idUsuario, $modulo, $permisosEditar);
    }

   
    public static function puedeEliminar(int $idUsuario, string $modulo): bool
    {
        $permisosEliminar = [
            self::SOLO_ELIMINAR,
            self::EDITAR_Y_ELIMINAR,
            self::REGISTRAR_Y_ELIMINAR,
            self::ACCESO_TOTAL
        ];
        
        return self::getPermisosModel()->verificarPermisoEspecifico($idUsuario, $modulo, $permisosEliminar);
    }

   
    public static function tieneAccesoTotal(int $idUsuario, string $modulo): bool
    {
        return self::getPermisosModel()->verificarPermisoEspecifico($idUsuario, $modulo, [self::ACCESO_TOTAL]);
    }

 
    public static function puedeEditarYEliminar(int $idUsuario, string $modulo): bool
    {
        $permisosRequeridos = [
            self::EDITAR_Y_ELIMINAR,
            self::ACCESO_TOTAL
        ];
        return self::getPermisosModel()->verificarPermisoEspecifico($idUsuario, $modulo, $permisosRequeridos);
    }

  
    public static function puedeRegistrarYEditar(int $idUsuario, string $modulo): bool
    {
        $permisosRequeridos = [
            self::REGISTRAR_Y_EDITAR,
            self::ACCESO_TOTAL
        ];
        return self::getPermisosModel()->verificarPermisoEspecifico($idUsuario, $modulo, $permisosRequeridos);
    }

   
    public static function puedeRegistrarYEliminar(int $idUsuario, string $modulo): bool
    {
        $permisosRequeridos = [
            self::REGISTRAR_Y_ELIMINAR,
            self::ACCESO_TOTAL
        ];
        return self::getPermisosModel()->verificarPermisoEspecifico($idUsuario, $modulo, $permisosRequeridos);
    }

    
    public static function getPermisosDetalle(int $idUsuario, string $modulo): array
    {
        return [
            'puede_ver' => self::puedeVer($idUsuario, $modulo),
            'puede_crear' => self::puedeCrear($idUsuario, $modulo),
            'puede_editar' => self::puedeEditar($idUsuario, $modulo),
            'puede_eliminar' => self::puedeEliminar($idUsuario, $modulo),
            'acceso_total_directo' => self::tieneAccesoTotal($idUsuario, $modulo) 
        ];
    }
}
?>