<?php
namespace App\Models;

use DOMDocument;
use DOMXPath;

class BcvScraperModel
{
    // URL oficial del BCV
    private $bcvUrl = 'https://www.bcv.org.ve/';

    // Funcion para obtener el HTML de la pagina del BCV
    private function fetchBcvHtml()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->bcvUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("Actualizacion (BCV): Error de cURL - " . $curlError . " al acceder a " . $this->bcvUrl);
            return false;
        }

        if ($httpCode == 200 && $html) {
            return $html;
        } else {
            error_log("Actualizacion (BCV): Error HTTP {$httpCode} al obtener HTML de: " . $this->bcvUrl);
            return false;
        }
    }



    public function obtenerDatosTasaBcv(string $codigoMoneda): ?array
    {
        $html = $this->fetchBcvHtml();
        if (!$html) {
            error_log("Actualizacion (BCV): No se pudo obtener el HTML para {$codigoMoneda}.");
            return null;
        }

        $doc = new DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new DOMXPath($doc);

        $monedaId = strtolower($codigoMoneda) === 'usd' ? 'dolar' : 'euro';

        // Extraer tasa
        $rateNode = $xpath->query("//div[@id='{$monedaId}']//strong")->item(0);
        $tasa = null;
        if ($rateNode) {
            $tasaStr = trim($rateNode->nodeValue);
            $tasaStr = str_replace('.', '', $tasaStr);
            $tasaStr = str_replace(',', '.', $tasaStr);
            $tasa = (float)$tasaStr;
        } else {
            error_log("Actualizacion (BCV): No se encontró el nodo de la tasa para {$monedaId}.");
            return null;
        }

        // Usar la fecha actual del sistema
        $fechaPublicacion = date('Y-m-d');

        if ($tasa !== null) {
            return ['tasa' => round($tasa, 4), 'fecha_bcv' => $fechaPublicacion];
        }

        error_log("Actualizacion (BCV): Falló la extracción de datos para {$codigoMoneda}. Tasa: " . ($tasa ?? 'NULA'));
        return null;
    }
}
?>
