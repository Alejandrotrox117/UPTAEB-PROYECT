<?php
namespace App\Controllers;

use App\Core\Controllers;

class Errors extends Controllers{
        public function __construct(){
            parent::__construct();
        }


        public function notFound(){
            $this->views->getView($this, "error");
        }

        public function index(){
            $this->views->getView($this, "error");
        }

    }
