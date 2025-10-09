<?php
require_once "app/core/Conexion.php";

class PesoModel
{
    public function obtenerUltimoPeso()
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            $sql = "SELECT idromana, peso, fecha, estatus, fecha_creacion FROM historial_romana ORDER BY idromana DESC LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                return [
                    'status' => true,
                    'data' => [
                        'idromana' => (int) $row['idromana'],
                        'peso' => (float) $row['peso'],
                        'fecha' => $row['fecha'],
                        'estatus' => $row['estatus'],
                        'fecha_creacion' => $row['fecha_creacion'],
                    ],
                ];
            }

            return [
                'status' => false,
                'message' => 'No se encontraron registros de la romana.',
            ];
        } catch (PDOException $e) {
            error_log('PesoModel::obtenerUltimoPeso - Error: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al consultar el último peso.',
            ];
        } finally {
            $conexion->disconnect();
        }
    }
}
