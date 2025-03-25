<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
</head>
<body>

    
    <h1><?php echo isset($data['page_name']) ? $data['page_name'] : 'Gestión de Peso'; 

        include './public/header.php';?>
        </h1><p>Bienvenido a la página de gestión de peso.</p>
         <p>Etiqueta de la página: <?php echo isset($data['tag_page']) ? $data['tag_page'] : ''; ?></p>

    <?php
include './public/footer.php'; 
?>
   
</body>
</html>
