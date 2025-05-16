<?php
class BcvScraperModel 
{
    // API que proporciona las tasas del BCV
    private $apiUrl = 'https://pydolarve.org/api/v2/tipo-cambio';

    // Funcion para obtener los datos de la API
    private function fetchApiData()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15); 
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (PHP; TuAplicacion/1.0)'); 
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);   
        $jsonData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("Actualizacion (BCV): Error de cURL - " . $curlError . " al acceder a " . $this->apiUrl);
            return false;
        }

        if ($httpCode == 200 && $jsonData) {
            return $jsonData;
        } else {
            error_log("Actualizacion (BCV): Error HTTP {$httpCode} al obtener datos de la API: " . $this->apiUrl . " - Respuesta: " . $jsonData);
            return false;
        }
    }

    private function parseApiDate(string $apiDateString): ?string
    {
        $cadenaLimpia = trim(preg_replace('/^[a-záéíóúñ]+\,\s*/i', '', $apiDateString));

        $mesesEsp = [
            'enero' => '01', 'febrero' => '02', 'marzo' => '03', 'abril' => '04',
            'mayo' => '05', 'junio' => '06', 'julio' => '07', 'agosto' => '08',
            'septiembre' => '09', 'octubre' => '10', 'noviembre' => '11', 'diciembre' => '12'
        ];

        if (preg_match('/(\d{1,2})\s+de\s+([a-záéíóúñ]+)\s+de\s+(\d{4})/i', $cadenaLimpia, $matches)) {
            $dia = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $nombreMes = strtolower($matches[2]);
            $ano = $matches[3];

            if (isset($mesesEsp[$nombreMes])) {
                $mes = $mesesEsp[$nombreMes];
                $fechaFormateada = "$ano-$mes-$dia";

                $d = DateTime::createFromFormat('Y-m-d', $fechaFormateada);
                if ($d && $d->format('Y-m-d') === $fechaFormateada) {
                    return $fechaFormateada;
                }
            }
        }

        error_log("Actualizacion (BCV): No se pudo parsear la cadena de fecha de la API: '{$apiDateString}' (limpia: '{$cadenaLimpia}')");
        return null;
    }

    public function obtenerDatosTasaBcv(string $codigoMoneda): ?array
    {
        $jsonData = $this->fetchApiData();
        if (!$jsonData) {
            error_log("Actualizacion (BCV): No se pudo obtener el JSON de la API para {$codigoMoneda}.");
            return null;
        }
        $data = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE || !$data) {
            error_log("Actualizacion (BCV): Error al decodificar JSON para {$codigoMoneda}. Error: " . json_last_error_msg());
            return null;
        }

        $fechaPublicacion = null;
        if (isset($data['datetime']['date'])) {
            $fechaPublicacion = $this->parseApiDate($data['datetime']['date']);
        }

        if (!$fechaPublicacion) {
            error_log("Actualizacion (BCV): No se pudo obtener o parsear la fecha de publicación principal de la API.");
            return null;
        }

        // Paea extraer la tasa para la moneda solicitada
        $valorTasa = null;
        $monitorKey = strtolower($codigoMoneda); // La API usa 'usd', 'eur' como claves

        if (isset($data['monitors'][$monitorKey]['price'])) {
            $tasaApi = round($data['monitors'][$monitorKey]['price'], 4);
            if (is_numeric($tasaApi)) {
                $valorTasa = round($tasaApi, 4);
            } else {
                error_log("Actualizacion (BCV): El valor de la tasa para {$codigoMoneda} no es numérico: " . print_r($tasaApi, true));
            }
        } else {
            error_log("Actualizacion (BCV): No se encontró la tasa para la moneda '{$monitorKey}' en la respuesta de la API.");
        }

        if ($valorTasa !== null && $fechaPublicacion !== null) {   
            return ['tasa' => $valorTasa, 'fecha_bcv' => $fechaPublicacion];
        }
        
        error_log("Actualizacion (BCV): Falló la extracción completa de datos para {$codigoMoneda} desde la API. Tasa: " . ($valorTasa ?? 'NULA') . ", Fecha BCV: " . ($fechaPublicacion ?? 'NULA'));
        return null;
    }
}
?>
