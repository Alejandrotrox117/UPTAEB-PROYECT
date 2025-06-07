<?php


class ExpresionesRegulares 
{
    // Expresiones regulares para validación
    private static $expresiones = [
        'nombre' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/',
        'apellido' => '/^[a-zA-Z\s]{3,12}$/',
        'telefono_principal' => '/^\d{11}$/',
        'telefono' => '/^\d{11}$/', // Alias para telefono_principal
        'direccion' => '/^.{5,100}$/',
        'estatus' => '/^(Activo|Inactivo)$/',
        'observaciones' => '/^.{0,50}$/',
        'email' => '/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/',
        'fecha' => '/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/',
        'fechaNacimiento' => '/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/',
        'cedula' => '/^(V|E|J)?-?\d{8}$/i',
        'password' => '/^.{6,16}$/',
        'textoGeneral' => '/^.{2,100}$/',
        'genero' => '/^(MASCULINO|FEMENINO|OTRO)$/i',
        // Expresiones adicionales
        'entero' => '/^\d+$/',
        'decimal' => '/^\d+(\.\d{1,2})?$/',
        
    ];

    /**
     * Obtiene una expresión regular específica
     * @param string $nombre Nombre de la expresión regular
     * @return string|null La expresión regular o null si no existe
     */
    public static function obtener(string $nombre): ?string 
    {
        return self::$expresiones[$nombre] ?? null;
    }

    /**
     * Obtiene todas las expresiones regulares
     * @return array Array con todas las expresiones regulares
     */
    public static function obtenerTodas(): array 
    {
        return self::$expresiones;
    }

    /**
     * Valida un valor contra una expresión regular específica
     * @param string $valor Valor a validar
     * @param string $expresion Nombre de la expresión regular
     * @return bool True si es válido, false si no
     */
    public static function validar(string $valor, string $expresion): bool 
    {
        $regex = self::obtener($expresion);
        if ($regex === null) {
            return false;
        }
        return preg_match($regex, $valor) === 1;
    }

    /**
     * Valida múltiples campos usando sus expresiones correspondientes
     * @param array $campos Array asociativo [campo => valor]
     * @param array $reglas Array asociativo [campo => expresion_regex]
     * @return array Array con los resultados de validación
     */
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

    /**
     * Verifica si existe una expresión regular
     * @param string $nombre Nombre de la expresión
     * @return bool True si existe, false si no
     */
    public static function existe(string $nombre): bool 
    {
        return array_key_exists($nombre, self::$expresiones);
    }

    /**
     * Limpia un string para que coincida con una expresión específica
     * @param string $valor Valor a limpiar
     * @param string $tipo Tipo de limpieza (nombre, email, telefono, etc.)
     * @return string Valor limpio
     */
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
            
            default:
                return trim($valor);
        }
    }

    /**
     * Obtiene el mensaje de error para una validación específica
     * @param string $campo Nombre del campo
     * @param string $expresion Nombre de la expresión
     * @return string Mensaje de error
     */
    public static function obtenerMensajeError(string $campo, string $expresion): string 
    {
        $mensajes = [
            'nombre' => "El {$campo} solo puede contener letras y espacios (2-50 caracteres).",
            'apellido' => "El {$campo} solo puede contener letras y espacios (3-12 caracteres).",
            'telefono' => "El {$campo} debe contener exactamente 11 dígitos.",
            'telefono_principal' => "El teléfono debe contener exactamente 11 dígitos.",
            'email' => "El formato del {$campo} no es válido.",
            'cedula' => "El formato de la {$campo} no es válido (Ej: V-12345678).",
            'fecha' => "El formato de fecha debe ser DD/MM/AAAA.",
            'fechaNacimiento' => "El formato de fecha de nacimiento debe ser DD/MM/AAAA.",
            'genero' => "El {$campo} debe ser MASCULINO, FEMENINO u OTRO.",
            'direccion' => "La {$campo} debe tener entre 5 y 100 caracteres.",
            'password' => "La contraseña debe tener entre 6 y 16 caracteres.",
            'textoGeneral' => "El {$campo} debe tener entre 2 y 100 caracteres.",
        ];

        return $mensajes[$expresion] ?? "El formato del {$campo} no es válido.";
    }
}
?>