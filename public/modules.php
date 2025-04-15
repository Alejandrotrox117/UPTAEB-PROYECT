<?php
return [
    ['name' => 'Dashboard', 'icon' => 'home', 'link' => '#'],
    ['name' => 'Orders', 'icon' => 'file', 'link' => '#'],
    ['name' => 'Products', 'icon' => 'shopping-cart', 'link' => '#'],
    ['name' => 'Customers', 'icon' => 'users', 'link' => '#'],
    ['name' => 'Reports', 'icon' => 'bar-chart-2', 'link' => '#'],
    ['name' => 'Integrations', 'icon' => 'layers', 'link' => '#'],
];
?>



<!-- <?php
//cargar para bd
// $modules = [];
// $conn = new mysqli('localhost', 'root', '', 'project_db');
// if ($conn->connect_error) {
//     die("Conexión fallida: " . $conn->connect_error);
// }
// $sql = "SELECT name, icon, link FROM modules";
// $result = $conn->query($sql);
// if ($result->num_rows > 0) {
//     while ($row = $result->fetch_assoc()) {
//         $modules[] = $row;
//     }
// }
// $conn->close();
//?> -->