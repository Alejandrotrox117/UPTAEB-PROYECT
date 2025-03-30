
<?php
$modules = require __DIR__ . '../modules.php';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proyecto</title>
    <!-- Bootstrap CSS -->
    <link href="/project/public/assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky">
                    <ul class="nav flex-column">
                        <?php foreach ($modules as $module): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= $module['link']; ?>">
                                    <span data-feather="<?= $module['icon']; ?>"></span>
                                    <?= $module['name']; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </nav>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">


            