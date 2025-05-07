<?php

class Home extends Controllers 
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

    public function index()
    {
        $this->views->getView($this, "home");
    }


    public function home($params = null) {
        $data['page_id'] = 1;
        $data["page_title"] = "Pagina principal";
        $data["tag_page"] = "La pradera de pavia";
        $data["page_name"] = "Home";
        
        // Verifica si hay parámetros
        if ($params) {
            echo "Parámetros recibidos: " . $params;
        }
    
        $this->views->getView($this, "home", $data);
    }
    

    

}   
?>