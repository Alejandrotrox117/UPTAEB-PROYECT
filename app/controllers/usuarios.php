<?php
require_once "app/core/Controllers.php";
require_once "helpers/helpers.php";

class Usuarios extends Controllers
{

    public function __construct()
    {
        parent::__construct();

        session_start();
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
        }
        // getPermisos(7);

        $this->model = new usuariosModel();
    }

    public function getModel()
    {
        return $this->model;
    }

    // Método setter para establecer el valor de $model
    public function setModel(UsuariosModel $model)
    {
        $this->model = $model;
    }

    public function Usuarios()
    {

        $data['page_id'] = 7;
        $data['page_tag'] = "usuarios";
        $data['page_title'] = "USUARIOS <small>Tienda Virtual</small>";
        $data['page_name'] = "usuarios";
        //Llamar al archivo js al ingresar en esa vista.
        $data["page_functions_js"] = "functions_usuarios.js";
        $this->views->getView($this, "usuarios", $data);
    }


    // public function setUsuario()
    // {
    //     if (!empty($_POST)) {
    //         $idUsuario = intval($_POST['idusuario']);
    //         $nombreUsuario = ucwords(strClean($_POST['txtnombres']));
    //         $correoElectronico = strtolower($_POST['txtEmail']);
    //         $tipoId = intval(strClean($_POST['listRolid']));
    //         $estado = intval(strClean($_POST['txtstatus']));
    //         $contrasena = hash("SHA256", $_POST['txtpassword']); // Encriptación SHA256
    
    //         $camposRequeridos = array(
    //             'txtnombres' => 'Nombre de usuario',
    //             'txtEmail' => 'Correo electrónico',
    //             'txtstatus' => 'Estado',
    //             'listRolid' => 'Rol',
    //             'txtpassword' => 'Contraseña'
    //         );
    
    //         $reglas = [
    //             'txtnombres' => REGEX_NOMBRES,
    //             'txtEmail' => REGEX_CORREO,
    //             'txtpassword' => REGEX_PASSWORD,
    //         ];
    
    //         $campoVacio = detectarCampoVacio($camposRequeridos);
    //         if ($campoVacio) {
    //             $arrResponse = array("status" => false, "msg" => 'El campo ' . $campoVacio . ' está vacío.');
    //             echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    //             exit();
    //         }
    
    //         // Validar el formato con expresiones regulares
    //         if (!validarFormulario($_POST, $reglas)) {
    //             $arrResponse = array("status" => false, "msg" => 'Los datos enviados no son válidos. Verifique los campos.');
    //             echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    //             exit();
    //         }
    
    //         if ($idUsuario > 0) { // Actualizar usuario existente
    //             $usuarioActual = $this->model->getIdUser($idUsuario);
    
    //             if ($usuarioActual[0]['usuario'] === $nombreUsuario && $usuarioActual[0]['correo'] === $correoElectronico && $usuarioActual[0]['estado'] === $estado && $usuarioActual[0]['clave'] === $contrasena) {
    //                 $arrResponse = array("status" => true, "msg" => "No se han realizado cambios.");
    //                 echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    //                 exit();
    //             } else {
    //                 $request_user = $this->model->actualizarUsuario(
    //                     $idUsuario,
    //                     $tipoId,
    //                     $nombreUsuario,
    //                     $contrasena,
    //                     $estado,
    //                     $correoElectronico
    //                 );
    
    //                 if ($request_user) {
    //                     $arrResponse = array("status" => true, "msg" => "Usuario actualizado correctamente.");
    //                 } else {
    //                     $arrResponse = array("status" => false, "msg" => "Error al actualizar el usuario.");
    //                 }
    //             }
    //         } else { // Insertar nuevo usuario
    //             $request_user = $this->model->insertUsuario(
    //                 $tipoId,
    //                 $nombreUsuario,
    //                 $contrasena,
    //                 $estado,
    //                 $correoElectronico
    //             );
    
    //             if ($request_user === false) {
    //                 $arrResponse = array("status" => false, "msg" => '¡Atención! El usuario ya existe.');
    //             } else {
    //                 $arrResponse = array("status" => true, "msg" => "Usuario registrado correctamente.");
    //             }
    //         }
    
    //         echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    //         exit();
    //     }
    // }    
    // public function getUsuarios()
    // {
    //     $arrData = $this->model->selectUsuarios();
    //     for ($i = 0; $i < count($arrData); $i++) {
    //         //iniciamos valores vacios
    //         $btneEdit = "";
    //         $btnDel = "";
    //         if ($arrData[$i]['estado'] == 1) {
    //             $arrData[$i]['estado'] = '<span class="badge badge-success">Activo</span>';
    //         } else {
    //             $arrData[$i]['estado'] = '<span class="badge badge-danger">Inactivo</span>';
    //         }
    //         //si es verdadero toma el valor
    //         if ($_SESSION['permisoMod']['actualizar']) {
    //             if(($_SESSION['idusuario'] == 1 and $_SESSION['userData']['rolId'] == 1) ||
    //             ($_SESSION['userData']['rolId'] == 1 and $arrData[$i]['rolId'] != 1)){
    //                 $btneEdit = '<button class="btn btn-warning btn-sm " id="btnEditUsuario" onClick="fntEditUsuario(' . $arrData[$i]['idusuario'] . ')" " 
    //                 title="Editar Usuario"><i class="fas fa-pencil-alt"></i></button>';
    //             }else{
    //                 $btneEdit = '<button class="btn btn-warning btn-sm" disabled><i class="fas fa-pencil-alt"></i></button>';
    //             }
    //         }

    //         if ($_SESSION['permisoMod']['eliminar']) {
    //             if(($_SESSION['idusuario'] == 1 and $_SESSION['userData']['rolId'] == 1) ||
    //             ($_SESSION['userData']['rolId'] == 1 and $arrData[$i]['rolId'] != 1) and
    //             ($_SESSION['userData']['idusuario'] != $arrData[$i]['idusuario'])){
    //                 $btnDel = ' <button class="btn btn-danger btn-sm btnDelUsuario" id="btnDelUsuario" onClick="DeleteUsuario(' . $arrData[$i]['idusuario'] . ')" 
    //                 title="Eliminar Usuario"><i class="fas fa-trash-alt"></i></button>';
    //             }else{
    //                 $btnDel = ' <button class="btn btn-danger btn-sm" disabled><i class="fas fa-trash-alt"></i></button>';
    //             }
    //         }
    //         $arrData[$i]['acciones'] = '<div class="text-center">' . $btneEdit . '' . $btnDel . '</div>';
    //     }

    //     // Convertir a formato JSON

    //     echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
    //     // Finalizar el proceso
    //     exit();
    // }

    // public function getUsuario(int $idusuario)
    // {
    //     $idusuario = intval($idusuario);
    //     if ($idusuario > 0) {
    //         $arrData = $this->model->selectUsuarioPersona($idusuario);
    //         if (empty($arrData)) {
    //             $arrResponse = array('status' => false, 'msg' => 'Datos no encontrados.');
    //         } else {
    //             $arrResponse = array('status' => true, 'data' => $arrData);
    //         }
    //         echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    //     }
    //     die();
    // }
    // public function delUsuario()
    // {

    //     if (!empty($_POST)) {
    //         $idusuario = intval($_POST['idusuario']);
    //         $requestDelete = $this->model->deleteUsuario($idusuario);
    //         if ($requestDelete) {
    //             $arrResponse = array('status' => true, 'msg' => 'Se ha eliminado el usuario');
    //         } else {
    //             $arrResponse = array('status' > false, 'msg' => 'Error al eliminar el usuario.');
    //         }

    //         echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    //     }
    //     die();
    // }

    // public function insertUserPerson(){
    //     if($_POST){
    //         if(empty($_POST['idpersona']) || empty($_POST['txtnombres']) || empty($_POST['txtnombrespersonas']) || empty($_POST['txtapellidospersonas'])
    //         || empty($_POST['txtdireccionpersonas']) || empty($_POST['txttelefonopersonas']) || empty($_POST['txtEmail'])){
    //             $arrResponse = array('status'=> false,'msg'=> 'Debe completar los campos Obligatorios');
    //         }else{
    //             $idUsuario = $_POST['idusuario'];
    //             $idPersona = $_POST['idpersona'];
    //             $txtnombres = $_POST['txtnombres'];
    //             $txtnombrespersonas = $_POST['txtnombrespersonas'];
    //             $txtapellidospersonas = $_POST['txtapellidospersonas'];
    //             $txtdireccionpersonas = $_POST['txtdireccionpersonas'];
    //             $txttelefonopersonas = intval($_POST['txttelefonopersonas']);
    //             $txtEmail = $_POST['txtEmail'];
    //             $rolId = intval($_POST['listRolid']);
    //             $estado = $_POST['txtstatus'];
    //             if(!empty($_POST['txtpassword'])){
    //                 $txtpassword = hash("SHA256", $_POST['txtpassword']);
    //             }else{
    //                 $txtpassword = "";
    //             }
                

    //             if($idUsuario < 0){
    //                 //Validar si la persona ya existe en la base de datos
    //                 $persona = $this->model->selectPersonaByCedula($idPersona);
    //                 if (isset($persona[0]['personaId'])) {
    //                     $request_Persona = $persona[0]['personaId'];
    //                 } else {
    //                     $request_Persona = $this->model->insertPersona($idPersona, $txtnombrespersonas, $txtapellidospersonas, $txttelefonopersonas, $txtEmail, $txtdireccionpersonas);
    //                 }
    
    //                 if($request_Persona){
    //                     $request_user = $this->model->insertUsuario($rolId, $txtnombres, $txtpassword, $estado, $txtEmail, $request_Persona);
    //                     if ($request_user === false) {
    //                         $arrResponse = array("status" => false, "msg" => '¡Atención! El usuario ya existe.');
    //                     } else {
    //                         $arrResponse = array("status" => true, "msg" => "Usuario registrado correctamente.");
    //                     }
    //                 }else{
    //                     $arrResponse = array("status" => false, "msg" => '¡Atención! Error con Al Obtener Persona.');
    //                 }
    //             }else{
 
    //                 $request_user = $this->model->actualizarUsuario($idUsuario, $rolId, $txtnombres, $txtpassword, $estado, $txtEmail, $idPersona);
    //                 if ($request_user) {
    //                     $arrResponse = array("status" => true, "msg" => "Usuario actualizado correctamente.");
    //                 } else {
    //                     $arrResponse = array("status" => false, "msg" => "Error al actualizar el usuario.");
    //                 }
    //             }  
    //         }
    //         echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    //     }
    //     die();
    // }

    // public function perfil()
    // {
    //     $data['page_tag'] = "Perfil";
    //     $data['page_title'] = "Perfil de Usuario";
    //     $data['page_name'] = "perfil";
    //     //Llamar al archivo js al ingresar en esa vista.
    //     $data["page_functions_js"] = "functions_usuarios.js";
    //     $this->views->getView($this, "perfil", $data);
    // }

    // public function putPerfil(){
    //     if($_POST){
    //         if(empty($_POST['idpersona']) || empty($_POST['txtnombres']) || empty($_POST['txtnombrespersonas']) || empty($_POST['txtapellidospersonas'])
    //         || empty($_POST['txtdireccionpersonas']) || empty($_POST['txttelefonopersonas']) || empty($_POST['txtEmail'])){
    //             $arrResponse = array('status'=> false,'msg'=> 'Debe completar los campos Obligatorios');
    //         }else{
    //             $idpersona = $_POST['idpersona'];
    //             $txtnombres = $_POST['txtnombres'];
    //             $txtnombrespersonas = $_POST['txtnombrespersonas'];
    //             $txtapellidospersonas = $_POST['txtapellidospersonas'];
    //             $txtdireccionpersonas = $_POST['txtdireccionpersonas'];
    //             $txttelefonopersonas = intval($_POST['txttelefonopersonas']);
    //             $txtEmail = $_POST['txtEmail'];
    //             $txtpassword = "";
    //             if(!empty($_POST['txtpassword'])){
    //                 if ($_POST['txtpassword'] != $_POST['txtpasswordConfirm']){
    //                     die();
    //                 }else{
    //                     $txtpassword = hash("SHA256", $_POST['txtpassword']);
    //                     $request_user = $this->model->PutInfoPerson($idpersona, 
    //                                                         $txtnombres, 
    //                                                         $txtnombrespersonas, 
    //                                                         $txtapellidospersonas,
    //                                                         $txtdireccionpersonas,
    //                                                         $txttelefonopersonas,
    //                                                         $txtEmail,
    //                                                         $txtpassword);
    //                     if($request_user){
    //                         $arrResponse = array('status'=> true,'msg'=> 'Datos Actualizados Correctamente.');
    //                         sessionUser($_SESSION['idusuario']);
    //                         sessionPersona($_SESSION['userData']['personaId']);
    //                     }else{
    //                         $arrResponse = array('status'=> false,'msg'=> 'No es posible almacenar los datos.');
    //                     }   
    //                 }
    //             }else{
    //                 $request_user = $this->model->PutInfoPerson($idpersona, 
    //                                                         $txtnombres, 
    //                                                         $txtnombrespersonas, 
    //                                                         $txtapellidospersonas,
    //                                                         $txtdireccionpersonas,
    //                                                         $txttelefonopersonas,
    //                                                         $txtEmail,
    //                                                         $txtpassword);
    //                     if($request_user){
    //                         $arrResponse = array('status'=> true,'msg'=> 'Datos Actualizados Correctamente.');
    //                         sessionUser($_SESSION['idusuario']);
    //                         sessionPersona($_SESSION['userData']['personaId']);
    //                     }else{
    //                         $arrResponse = array('status'=> false,'msg'=> 'No es posible almacenar los datos.');
    //                     }   
    //             }
    //         }
    //         //dep($request_user);
    //         //echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    //     }
    //     die();
    // }

}
