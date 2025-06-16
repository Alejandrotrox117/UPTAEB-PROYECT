<?php

class ExpresionesRegulares
{
    // Expresiones existentes
    const NOMBRE = '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/';
    const APELLIDO = '/^[a-zA-Z\s]{3,12}$/';
    const CEDULA = '/^(V|E|J)?-?\d{8}$/i';
    const TELEFONO = '/^\d{11}$/';
    const EMAIL = '/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/';
    const DIRECCION = '/^.{5,100}$/';
    const TEXTO_GENERAL = '/^.{2,100}$/';
    const GENERO = '/^(MASCULINO|FEMENINO|OTRO)$/';
    
    // Nuevas expresiones para campos numéricos de ventas
    const PRECIO = '/^[0-9]+(\.[0-9]{1,2})?$/';              // Precio positivo con hasta 2 decimales
    const CANTIDAD = '/^[0-9]+(\.[0-9]{1,3})?$/';            // Cantidad positiva con hasta 3 decimales
    const SUBTOTAL = '/^[0-9]+(\.[0-9]{1,2})?$/';            // Subtotal positivo con hasta 2 decimales
    const TOTAL = '/^[0-9]+(\.[0-9]{1,2})?$/';               // Total positivo con hasta 2 decimales
    const DESCUENTO_PORCENTAJE = '/^([0-9]|[1-9][0-9]|100)(\.[0-9]{1,2})?$/'; // 0-100 con hasta 2 decimales
    const MONTO_DESCUENTO = '/^[0-9]+(\.[0-9]{1,2})?$/';     // Monto descuento positivo con hasta 2 decimales

    private static $expresiones = [
        'nombre' => self::NOMBRE,
        'apellido' => self::APELLIDO,
        'cedula' => self::CEDULA,
        'telefono' => self::TELEFONO,
        'email' => self::EMAIL,
        'direccion' => self::DIRECCION,
        'textoGeneral' => self::TEXTO_GENERAL,
        'genero' => self::GENERO,
        // Campos numéricos para ventas
        'precio' => self::PRECIO,
        'cantidad' => self::CANTIDAD,
        'subtotal' => self::SUBTOTAL,
        'total' => self::TOTAL,
        'descuentoPorcentaje' => self::DESCUENTO_PORCENTAJE,
        'montoDescuento' => self::MONTO_DESCUENTO
    ];

    private static $mensajes = [
        'nombre' => 'El nombre debe contener solo letras y espacios, entre 2 y 50 caracteres.',
        'apellido' => 'El apellido debe contener solo letras y espacios, entre 3 y 12 caracteres.',
        'cedula' => 'Formato de cédula inválido. Ejemplo: V-12345678',
        'telefono' => 'El teléfono debe tener exactamente 11 dígitos.',
        'email' => 'Formato de email inválido.',
        'direccion' => 'La dirección debe tener entre 5 y 100 caracteres.',
        'textoGeneral' => 'El texto debe tener entre 2 y 100 caracteres.',
        'genero' => 'El género debe ser MASCULINO, FEMENINO u OTRO.',
        // Mensajes para campos numéricos de ventas
        'precio' => 'El precio debe ser un número positivo con hasta 2 decimales.',
        'cantidad' => 'La cantidad debe ser un número positivo con hasta 3 decimales.',
        'subtotal' => 'El subtotal debe ser un número positivo con hasta 2 decimales.',
        'total' => 'El total debe ser un número positivo con hasta 2 decimales.',
        'descuentoPorcentaje' => 'El descuento debe estar entre 0% y 100% con hasta 2 decimales.',
        'montoDescuento' => 'El monto de descuento debe ser un número positivo con hasta 2 decimales.'
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
     * Valida un valor contra una expresión regular específica y devuelve array con detalles
     * @param string $valor Valor a validar
     * @param string $expresion Nombre de la expresión regular
     * @return array Array con 'valido' y 'mensaje'
     */
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
        return self::$mensajes[$expresion] ?? "Error de validación en el campo '$campo'.";
    }

    /**
     * Obtiene una expresión regular por su tipo
     * @param string $tipo Tipo de expresión
     * @return string|null La expresión regular o null si no existe
     */
    public static function obtenerExpresion(string $tipo): ?string
    {
        return self::$expresiones[$tipo] ?? null;
    }
}
?>