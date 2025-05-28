<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="/project/app/assets/img/favicon.svg" type="image/x-icon">
    <title>Recuperadora</title>
    <meta name="description" content="Recuperadora de materiales reciclables">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/project/app/assets/styles/styles.css">

    <link href="/project/app/assets/fontawesome/css/all.min.css" rel="stylesheet">
    <link href="/project/app/assets/fontawesome/css/solid.css" rel="stylesheet">
    <link href="/project/app/assets/fontawesome/css/fontawesome.css" rel="stylesheet">
    <link href="/project/app/assets/fontawesome/css/brands.css" rel="stylesheet">
    <link href="/project/app/assets/DataTables/datatables.css" rel="stylesheet">
    <!-- <link href="/project/app/assets/DataTables/responsive.dataTables.css" rel="stylesheet"> -->
    <link rel="stylesheet" href="/project/app/assets/sweetAlert/sweetalert2.min.css">
</head>

<body id="bodyLogin">
    <div class="login-container">
        <img src="/project/app/assets/img/favicon.svg" class="mx-auto" alt="Logo Empresa">
        <h2 class="text-">Bienvenido</h2>
        <form action="<?= base_url() ?>/login/loginUser" method="POST" id="formLogin">
            <label for="txtEmail" >Correo Electrónico:</label>
            <input type="email" id="txtEmail" name="txtEmail">
            <div id="error-email-vacio" class="text-red-500 text-sm mt-1 hidden">
            </div>
            <div id="error-email-formato" class="text-red-500 text-sm mt-1 hidden">

            </div>
            <label for="txtPass">Contraseña:</label>
            <div class="password-container">
                <input type="password" id="txtPass" name="txtPass">

                <div id="error-txtPass-vacio" class="text-red-500 text-sm mt-1 hidden">
                </div>
                <div id="error-txtPass-formato" class="text-red-500 text-sm mt-1 hidden">

                </div>
               
            </div>
            <button type="submit">Iniciar Sesión</button>
        </form>
        <a href="resetpass.php">¿Olvidaste tu contraseña?</a>
    </div>

    </div>

<?php footerAdmin($data); ?>
