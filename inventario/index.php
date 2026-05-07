<?php
session_start();



if (!isset($_SESSION['tipo'])) {
    header('Location: ../login/index.php');
    exit;
}


$tipo_usuario = strtolower(trim($_SESSION['tipo']));


$roles_permitidos = ['admin', 'inventario'];

if (!in_array($tipo_usuario, $roles_permitidos, true)) {
    header('Location: ../login/index.php');
    exit;    
}


$display_name = htmlspecialchars(
    $_SESSION['usuario_nombre'] ?? ucfirst($tipo_usuario),
    ENT_QUOTES,
    'UTF-8'
);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Inventario - PlastyPetco</title>
<link rel="icon" href="../admin/plas.jpg">

<style>
body{
    margin:0;
    padding:0;
    font-family: Arial;
    background: url("../admin/fondo.jpg") no-repeat center center fixed;
    background-size: cover;
}

.container{
    background: rgba(255,255,255,0.95);
    width: 900px;
    margin: 50px auto;
    padding: 30px;
    border-radius: 18px;
    box-shadow: 0 6px 18px rgba(0,0,0,.25);
}

h1{
    text-align:center;
    color:#1B5E20;
    margin-bottom:10px;
}

.bienvenida{
    text-align:center;
    margin-bottom:30px;
    font-weight:bold;
}

.menu{
    display:grid;
    grid-template-columns: repeat(3, 1fr);
    gap:20px;
}

.card{
    background:#f5f5f5;
    border-radius:14px;
    padding:30px;
    text-align:center;
    cursor:pointer;
    transition:.2s;
    box-shadow:0 4px 10px rgba(0,0,0,.15);
}

.card:hover{
    background:#e0f2f1;
    transform: translateY(-3px);
}

.card h2{
    margin:0;
    color:#0D47A1;
}

.card p{
    margin-top:8px;
    color:#333;
}

.logout{
    margin-top:30px;
    text-align:center;
}

.logout a{
    text-decoration:none;
    background:#c62828;
    color:white;
    padding:12px 20px;
    border-radius:10px;
    font-weight:bold;
}
.menu-bottom{
    display:grid;
    grid-template-columns: repeat(2, 1fr);
    gap:20px;
    width:100%;
    margin-top:20px;
}

</style>
</head>
<body>

<div class="container">
    <h1>Inventario / Despacho / Proveedores / Residuos</h1>
    <p class="bienvenida">Bienvenido, <?= $display_name ?></p>

    <div class="menu">
        <div class="card" onclick="location.href='inventario.php'">
            <h2>📦 Inventario</h2>
            <p>Entrada de materiales</p>
        </div>

        <div class="card" onclick="location.href='despacho.php'">
            <h2>🚚 Despacho</h2>
            <p>Salidas de producto</p>
        </div>


        <div class="card" onclick="location.href='proveedores.php'">
            <h2>🏭 Proveedores</h2>
            <p>Gestión de proveedores</p>
        </div>
    </div>
<div class="menu-bottom">

    <div class="card" onclick="location.href='residuos.php'">
        <h2>🗑️ Residuos</h2>
        <p>Residuos De Producción</p>
    </div>

    <div class="card" onclick="location.href='ver_todo.php'">
        <h2>📊 Ver Todas las Tablas</h2>
        <p>Inventario / Proceso / Producción / Despachos</p>
    </div>

</div>

    
    <div class="logout">
        <a href="../login/index.php">Cerrar sesión</a>
    </div>
</div>

</body>
</html>
