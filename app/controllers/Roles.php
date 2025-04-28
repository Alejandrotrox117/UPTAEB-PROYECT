<?php 
class Roles extends Controllers
{
    public function index()
    {
        $this->views->getView($this, "roles");
    }
}
?>