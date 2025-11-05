<?php
require_once "app/core/conexion.php";

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

    public function guardarPesoRomana($peso, $fecha = null)
    {
        $conexion = new Conexion();
        $conexion->connect();
        $db = $conexion->get_conectGeneral();

        try {
            // Si no se proporciona fecha, usar la actual
            if ($fecha === null) {
                date_default_timezone_set('America/Caracas');
                $fecha = date('Y-m-d H:i:s');
            }

            // Verificar si ya existe un peso muy similar en los últimos 10 segundos
            $sqlCheck = "SELECT idromana, peso, fecha 
                        FROM historial_romana 
                        WHERE fecha >= DATE_SUB(NOW(), INTERVAL 10 SECOND)
                        AND ABS(peso - ?) < 0.5
                        ORDER BY idromana DESC 
                        LIMIT 1";
            
            $stmtCheck = $db->prepare($sqlCheck);
            $stmtCheck->execute([$peso]);
            $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            // Si ya existe un peso similar reciente, no duplicar
            if ($existing) {
                return [
                    'status' => true,
                    'message' => 'Peso ya registrado recientemente',
                    'duplicado' => true,
                    'idromana' => (int) $existing['idromana']
                ];
            }

            // Insertar nuevo peso
            $sql = "INSERT INTO historial_romana (peso, fecha, estatus, fecha_creacion) 
                    VALUES (?, ?, 'ACTIVO', NOW())";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$peso, $fecha]);
            
            $idromana = (int) $db->lastInsertId();

            return [
                'status' => true,
                'message' => 'Peso guardado correctamente',
                'duplicado' => false,
                'idromana' => $idromana
            ];

        } catch (PDOException $e) {
            error_log('PesoModel::guardarPesoRomana - Error: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error al guardar el peso: ' . $e->getMessage()
            ];
        } finally {
            $conexion->disconnect();
        }
    }
}
