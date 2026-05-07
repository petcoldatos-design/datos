<?php
session_start();


$tipo = strtolower($_SESSION['tipo'] ?? '');


if ($tipo !== 'admin' && $tipo !== 'inventario') {
    die("Acceso denegado");
}

require_once("../db/conexion.php");


if ($tipo === 'admin') {
    $ruta_retorno = "../admin/index.php";
} else {
    $ruta_retorno = "../inventario/ver_todo.php";
}


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID inválido");
}

$id = (int) $_GET['id'];


$stmt = $conexion->prepare("DELETE FROM inventario WHERE id = ? LIMIT 1");

if ($stmt === false) {
    die("Error SQL: " . $conexion->error);
}

$stmt->bind_param("i", $id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $stmt->close();
    header("Location: " . $ruta_retorno . "?msg=eliminado");
    exit;
} else {
    $stmt->close();
    die("No se encontró el registro para eliminar");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Eliminar Registro</title>
<style>
body{
    background:url("../admin/fondo.jpg") center/cover fixed;
    font-family:Arial;
    padding:40px;
}
.box{
    background:#fff;
    max-width:500px;
    margin:auto;
    padding:30px;
    border-radius:18px;
    text-align:center;
}
h2{ color:#B71C1C; }
.btn{
    display:block;
    width:100%;
    margin-top:15px;
    padding:14px;
    border:none;
    border-radius:12px;
    font-weight:bold;
    cursor:pointer;
    text-decoration:none;
    text-align:center;
}
.btn-danger{
    background:#C62828;
    color:white;
}
.btn-volver{
    background:#555;
    color:white;
}
.mensaje{
    background:#f8d7da;
    color:#842029;
    padding:12px;
    border-radius:10px;
    margin-bottom:15px;
}
</style>
</head>

<body>

<div class="box">
<h2>¿Eliminar registro?</h2>

<?php if ($mensaje): ?>
<div class="mensaje"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<p>Esta acción no se puede deshacer.</p>

<form method="POST">
    <button type="submit" class="btn btn-danger">Sí, eliminar</button>
</form>

<a href="<?= $ruta_retorno ?>" class="btn btn-volver">Cancelar</a>

</div>

</body>
</html>
