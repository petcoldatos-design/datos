<?php

$conexion = new mysqli(
    "turntable.proxy.rlwy.net", 
    "root",
    "VXJyGiWHXHiZEYagQMHAOgNXZRTtSeSA",        
    "railway",
    59111                           
);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");
?>
