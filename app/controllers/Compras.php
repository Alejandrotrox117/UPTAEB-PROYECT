<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";
class Compras extends Controllers 
{


     // Método setter para establecer el valor de $model
     public function set_model($model)
     {
         $this->model = $model;
     }
 
     public function get_model()
     {
         return $this->model;
     }

    public function __construct() {
        parent::__construct(); // Llama al constructor de la clase base
    }

    public function index() {
        $data['page_title'] = "Gestión de compras";
        $data['page_name'] = "Compra de materiales";
        $data['page_functions_js'] = "functions_compras.js";
        $this->views->getView($this, "compras", $data);
    }

    public function getComprasData() {

        $arrData = $this->get_model()->SelectAllCompras();
    
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
 
        exit();
    }

    public function setCompra() {
      
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            $proveedor = $_POST['proveedor'] ?? null;
            $tipo_material = $_POST['tipo_material'] ?? null;
            $peso_bruto = $_POST['peso_bruto'] ?? null;
            $peso_neto = $_POST['peso_neto'] ?? null;
            $peso_vehiculo = $_POST['peso_vehiculo'] ?? null;
            $subtotal = $_POST['subtotal'] ?? null;
            $porcentaje_descuento = $_POST['porcentaje_descuento'] ?? null;
            $total = $_POST['total'] ?? null;
    
            
            if (empty($proveedor) || empty($tipo_material) || empty($peso_bruto) || empty($peso_neto) || empty($total)) {
                $response = array("status" => false, "message" => "Datos incompletos. Por favor, llena todos los campos obligatorios.");
                echo json_encode($response);
                return;
            }
            $insertData = $this->get_model()->insertCompra([
                "proveedor" => $proveedor,
                "tipo_material" => $tipo_material,
                "peso_bruto" => $peso_bruto,
                "peso_neto" => $peso_neto,
                "peso_vehiculo" => $peso_vehiculo,
                "subtotal" => $subtotal,
                "porcentaje_descuento" => $porcentaje_descuento,
                "total" => $total,
            ]);

            if ($insertData) {
                $response = array("status" => true, "message" => "Compra registrada correctamente.");
            } else {
                $response = array("status" => false, "message" => "Error al registrar la compra. Intenta nuevamente.");
            }
    
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } else {
            $response = array("status" => false, "message" => "Método no permitido.");
            echo json_encode($response);
        }
    
        exit();
    }
    



}