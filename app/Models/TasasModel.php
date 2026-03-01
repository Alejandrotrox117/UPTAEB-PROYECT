<?php
namespace App\Models;

use App\Core\Conexion;
use PDO;
use PDOException;

class TasasModel
{
    private $objModelTasasModel;

    private $id;
    private $codigoMoneda;
    private $fechaCaptura;
    private $tasa;
    private $fechaBcv;

    //SETTERS
    public function setId($id)
    {
        $this->id = $id;
    }
    public function setCodigoMoneda($codigoMoneda)
    {
        $this->codigoMoneda = $codigoMoneda;
    }
    public function setTasa($tasa)
    {
        $this->tasa = $tasa;
    }
    public function setFechaBcv($fechaBcv)
    {
        $this->fechaBcv = $fechaBcv;
    }
    public function setFechaCaptura($fechaCaptura)
    {
        $this->fechaCaptura = $fechaCaptura;
    }

    //GETTERS
    public function getId()
    {
        return $this->id;
    }
    public function getCodigoMoneda()
    {
        return $this->codigoMoneda;
    }
    public function getTasa()
    {
        return $this->tasa;
    }
    public function getFechaBcv()
    {
        return $this->fechaBcv;
    }
    public function getFechaCaptura()
    {
        return $this->fechaCaptura;
    }

    private function getInstanciaModel()
    {
        if ($this->objModelTasasModel == null) {
            $this->objModelTasasModel = new TasasModel();
        }
        return $this->objModelTasasModel;
    }

    public function guardarTasa(string $codigoMoneda, float $tasa, string $fechaBcv)
    {
        $objModelTasasModel = $this->getInstanciaModel();
        return $objModelTasasModel->ejecutarGuardarTasa($codigoMoneda, $tasa, $fechaBcv);
    }

    public function obtenerTasasPorMoneda(string $codigoMoneda, int $limite = 0)
    {
        $objModelTasasModel = $this->getInstanciaModel();
        return $objModelTasasModel->ejecutarObtenerTasasPorMoneda($codigoMoneda, $limite);
    }

    public function SelectAllTasas(): array
    {
        $objModelTasasModel = $this->getInstanciaModel();
        return $objModelTasasModel->ejecutarSelectAllTasas();
    }

    private function ejecutarGuardarTasa(string $codigoMoneda, float $tasa, string $fechaBcv)
    {
        $this->setCodigoMoneda($codigoMoneda);
        $this->setTasa($tasa);
        $this->setFechaBcv($fechaBcv);

        // Validar que la tasa no sea cero
        if ($tasa == 0) {
            error_log("Tasa no puede ser cero para {$this->getCodigoMoneda()} - {$this->getFechaBcv()}");
            return false;
        }

        $conexion = new Conexion();
        $db = $conexion->get_conectGeneral();

        try {
            $stmtCheck = $db->prepare("SELECT id FROM historial_tasas_bcv WHERE codigo_moneda = ? AND fecha_publicacion_bcv = ?");
            $stmtCheck->execute([$this->getCodigoMoneda(), $this->getFechaBcv()]);

            if ($stmtCheck->fetch()) {
                return 'duplicado';
            }

            $sql = "INSERT INTO historial_tasas_bcv (codigo_moneda, tasa_a_bs, fecha_publicacion_bcv, fecha_creacion)
                    VALUES (?,?,?,?)";

            $stmt = $db->prepare($sql);
            date_default_timezone_set('America/Caracas');
            $fechaActual = date('Y-m-d H:i:s');

            $exito = $stmt->execute([
                $this->getCodigoMoneda(),
                $this->getTasa(),
                $this->getFechaBcv(),
                $fechaActual
            ]);

            return $exito ? 'insertado' : false;

        } catch (PDOException $e) {
            error_log("TasasModel: Error de BD al guardar tasa para {$this->getCodigoMoneda()} - " . $e->getMessage());
            return false;
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarObtenerTasasPorMoneda(string $codigoMoneda, int $limite = 0)
    {
        $this->setCodigoMoneda($codigoMoneda);

        $conexion = new Conexion();
        $db = $conexion->get_conectGeneral();

        $sql = "SELECT codigo_moneda, tasa_a_bs AS tasa_a_ves, fecha_publicacion_bcv, fecha_creacion AS fecha_captura
                FROM historial_tasas_bcv
                WHERE codigo_moneda = ?
                ORDER BY fecha_publicacion_bcv DESC, fecha_creacion";

        if ($limite > 0) {
            $sql .= " LIMIT " . (int) $limite;
        }

        $arrData = [$codigoMoneda];

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($arrData);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("TasasModel: Error de BD al obtener tasas para {$codigoMoneda} - " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }

    private function ejecutarSelectAllTasas(): array
    {
        $conexion = new Conexion();
        $db = $conexion->get_conectGeneral();

        $sql = "SELECT id, codigo_moneda, tasa_a_bs AS tasa_a_ves, fecha_publicacion_bcv, fecha_creacion AS fecha_captura FROM historial_tasas_bcv ORDER BY fecha_publicacion_bcv DESC, fecha_creacion DESC";
        try {
            $stmt = $db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error de BD al seleccionar todas las tasas - " . $e->getMessage());
            return [];
        } finally {
            $conexion->disconnect();
        }
    }
}
?>