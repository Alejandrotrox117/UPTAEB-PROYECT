<?php
namespace App\Helpers\Validation;

class ExpresionesRegulares
{
    const NOMBRE = '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/';
    const APELLIDO = '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,100}$/';
    const CEDULA = '/^(V|E|J)?-?\d{8}$/i';
    const TELEFONO = '/^\d{11}$/';
    const EMAIL = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
    const DIRECCION = '/^.{5,100}$/';
    const TEXTO_GENERAL = '/^.{2,100}$/';
    const GENERO = '/^(MASCULINO|FEMENINO|OTRO)$/';
    const PRECIO = '/^[0-9]+(\.[0-9]{1,2})?$/';
    const CANTIDAD = '/^[0-9]+(\.[0-9]{1,3})?$/';
    const SUBTOTAL = '/^[0-9]+(\.[0-9]{1,2})?$/';
    const TOTAL = '/^[0-9]+(\.[0-9]{1,2})?$/';
    const DESCUENTO_PORCENTAJE = '/^([0-9]|[1-9][0-9]|100)(\.[0-9]{1,2})?$/';
    const MONTO_DESCUENTO = '/^[0-9]+(\.[0-9]{1,2})?$/';

    private static $expresiones = [
        'nombre' => self::NOMBRE,
        'apellido' => self::APELLIDO,
        'cedula' => self::CEDULA,
        'telefono' => self::TELEFONO,
        'email' => self::EMAIL,
        'direccion' => self::DIRECCION,
        'textoGeneral' => self::TEXTO_GENERAL,
        'genero' => self::GENERO,
        'precio' => self::PRECIO,
        'cantidad' => self::CANTIDAD,
        'subtotal' => self::SUBTOTAL,
        'total' => self::TOTAL,
        'descuentoPorcentaje' => self::DESCUENTO_PORCENTAJE,
        'montoDescuento' => self::MONTO_DESCUENTO
    ];

    private static $mensajes = [
        'nombre' => 'El nombre debe contener solo letras y espacios, entre 2 y 50 caracteres.',
        'apellido' => 'El apellido debe contener solo letras y espacios, entre 2 y 100 caracteres.',
        'cedula' => 'Formato de cédula inválido. Ejemplo: V-12345678',
        'telefono' => 'El teléfono debe tener exactamente 11 dígitos.',
        'email' => 'Formato de email inválido.',
        'direccion' => 'La dirección debe tener entre 5 y 100 caracteres.',
        'textoGeneral' => 'El texto debe tener entre 2 y 100 caracteres.',
        'genero' => 'El género debe ser MASCULINO, FEMENINO u OTRO.',
        'precio' => 'El precio debe ser un número positivo con hasta 2 decimales.',
        'cantidad' => 'La cantidad debe ser un número positivo con hasta 3 decimales.',
        'subtotal' => 'El subtotal debe ser un número positivo con hasta 2 decimales.',
        'total' => 'El total debe ser un número positivo con hasta 2 decimales.',
        'descuentoPorcentaje' => 'El descuento debe estar entre 0% y 100% con hasta 2 decimales.',
        'montoDescuento' => 'El monto de descuento debe ser un número positivo con hasta 2 decimales.'
    ];

    public static function obtener(string $nombre): ?string 
    {
        return self::$expresiones[$nombre] ?? null;
    }

    public static function obtenerTodas(): array 
    {
        return self::$expresiones;
    }

    public static function validar(string $valor, string $expresion): bool 
    {
        $regex = self::obtener($expresion);
        if ($regex === null) {
            return false;
        }
        return preg_match($regex, $valor) === 1;
    }

    public static function validarConDetalle(string $valor, string $expresion): array 
    {
        $regex = self::obtener($expresion);
        if ($regex === null) {
            return [
                'valido' => false,
                'mensaje' => "Tipo de validación '$expresion' no existe."
            ];
        }
        
        $esValido = preg_match($regex, $valor) === 1;
        $mensaje = $esValido ? '' : (self::$mensajes[$expresion] ?? "Formato inválido para '$expresion'.");
        
        return [
            'valido' => $esValido,
            'mensaje' => $mensaje
        ];
    }

    public static function validarCampos(array $campos, array $reglas): array 
    {
        $resultados = [];
        
        foreach ($reglas as $campo => $expresion) {
            $valor = $campos[$campo] ?? '';
            $resultados[$campo] = [
                'valido' => self::validar($valor, $expresion),
                'valor' => $valor,
                'expresion' => $expresion
            ];
        }
        
        return $resultados;
    }

    public static function existe(string $nombre): bool 
    {
        return array_key_exists($nombre, self::$expresiones);
    }

    public static function limpiar(string $valor, string $tipo = 'textoGeneral'): string 
    {
        switch ($tipo) {
            case 'nombre':
            case 'apellido':
                return preg_replace('/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/', '', trim($valor));
            
            case 'telefono':
            case 'telefono_principal':
                return preg_replace('/[^\d]/', '', trim($valor));
            
            case 'email':
                return filter_var(trim($valor), FILTER_SANITIZE_EMAIL);
            
            case 'cedula':
                return preg_replace('/[^VEJvej\d\-]/', '', strtoupper(trim($valor)));
            
            case 'alfanumerico':
                return preg_replace('/[^a-zA-Z0-9]/', '', trim($valor));
            
            case 'solo_numeros':
                return preg_replace('/[^\d]/', '', trim($valor));
            
            case 'codigo':
                return preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($valor)));
            
            case 'rol':
            case 'rol_nombre':
                // Solo permite letras, números, espacios y acentos comunes
                return preg_replace('/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-_]/', '', trim($valor));
            
            case 'descripcion':
                // Permite caracteres alfanuméricos, espacios, puntuación básica
                $limpio = trim($valor);
                $limpio = strip_tags($limpio);
                return htmlspecialchars($limpio, ENT_QUOTES, 'UTF-8');
            
            default:
                return trim($valor);
        }
    }

    public static function obtenerMensajeError(string $campo, string $expresion): string 
    {
        return self::$mensajes[$expresion] ?? "Error de validación en el campo '$campo'.";
    }
}
