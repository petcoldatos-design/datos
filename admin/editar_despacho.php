<?php
session_start();

$tipo = strtolower($_SESSION['tipo'] ?? '');

if ($tipo !== 'admin' && $tipo !== 'inventario') {
    die('Acceso denegado');
}

require_once("../db/conexion.php");
$conexion->set_charset("utf8");

if ($tipo === 'admin') {
    $ruta_retorno = "../admin/index.php";
} else {
    $ruta_retorno = "../inventario/ver_todo.php";
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID inválido");
}
$id = (int)$_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fecha          = $_POST['fecha'];
    $cliente        = $_POST['cliente'];
    $remision       = $_POST['remision'];
    $producto       = $_POST['producto'];
    $presentacion   = $_POST['presentacion'];
    $cantidad       = (float)$_POST['cantidad'];
    $lote           = $_POST['lote'];
    $despachado_por = $_POST['despachado_por'];
    $conductor      = $_POST['conductor'];
    $observaciones  = $_POST['observaciones'];

    $stmt = $conexion->prepare("
        UPDATE despachos_produccion SET
            fecha = ?,
            cliente = ?,
            remision = ?,
            producto = ?,
            presentacion = ?,
            cantidad_kg = ?,
            lote = ?,
            despachado_por = ?,
            conductor = ?,
            observaciones = ?
        WHERE id_despacho = ?
        LIMIT 1
    ");

    if ($stmt === false) {
        die("Error SQL: " . $conexion->error);
    }

    $stmt->bind_param(
        "sssssdssssi",
        $fecha,
        $cliente,
        $remision,
        $producto,
        $presentacion,
        $cantidad,
        $lote,
        $despachado_por,
        $conductor,
        $observaciones,
        $id
    );

    $stmt->execute();
    $stmt->close();

    header("Location: " . $ruta_retorno . "?msg=editado");
    exit;
}

$stmt = $conexion->prepare("
    SELECT * 
    FROM despachos_produccion 
    WHERE id_despacho = ? 
    LIMIT 1
");

if ($stmt === false) {
    die("Error SQL: " . $conexion->error);
}

$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if (!$row = $res->fetch_assoc()) {
    die("Despacho no encontrado");
}

$stmt->close();

function e($t){
    return htmlspecialchars($t ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Despacho</title>
<link rel="icon" href="../admin/plas.jpg">

<style>
body{
    background:url("../admin/fondo.jpg");
    background-size:cover;
    background-attachment:fixed;
    font-family:Arial;
    padding:30px;
}
.form-box{
    background:#ffffffef;
    width:540px;
    margin:auto;
    padding:32px;
    border-radius:18px;
    box-shadow:0 6px 18px rgba(0,0,0,.18);
}
h2{text-align:center;color:#1B5E20;margin-bottom:26px;}
label{display:block;font-weight:bold;color:#1B5E20;margin-bottom:6px;}
input, textarea{
    width:100%;
    height:46px;
    padding:10px 14px;
    border-radius:12px;
    border:1px solid #999;
    font-size:15px;
    margin-bottom:14px;
    box-sizing:border-box;
}
textarea{
    height:90px;
    resize:none;
}
.grid-2{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:14px;
}
button{
    width:100%;
    padding:14px;
    border:none;
    border-radius:12px;
    font-size:16px;
    font-weight:bold;
    cursor:pointer;
}
.btn-guardar{background:#0D47A1;color:white;}
.btn-volver{background:#1B5E20;color:white;margin-top:10px;}
</style>
</head>

<body>

<div class="form-box">
<h2>Editar Despacho</h2>

<form method="POST">

<label>Fecha</label>
<input type="date" name="fecha" required value="<?= e($row['fecha']) ?>">

<label>Cliente</label>
<input type="text" name="cliente" required value="<?= e($row['cliente']) ?>">

<label>Remisión</label>
<input type="text" name="remision" value="<?= e($row['remision']) ?>">

<label>Producto</label>
<input type="text" name="producto" value="<?= e($row['producto']) ?>">

<label>Presentación</label>
<input type="text" name="presentacion" value="<?= e($row['presentacion']) ?>">

<div class="grid-2">
<div>
<label>Cantidad (KG)</label>
<input type="number" step="0.01" name="cantidad" required value="<?= e($row['cantidad_kg']) ?>">
</div>
<div>
<label>Lote</label>
<input type="text" name="lote" value="<?= e($row['lote']) ?>">
</div>
</div>

<label>Despachado por</label>
<input type="text" name="despachado_por" value="<?= e($row['despachado_por']) ?>">

<label>Conductor</label>
<input type="text" name="conductor" value="<?= e($row['conductor']) ?>">

<label>Observaciones</label>
<textarea name="observaciones"><?= e($row['observaciones']) ?></textarea>

<button class="btn-guardar" type="submit">Guardar Cambios</button>
<button class="btn-volver" type="button" onclick="location.href='<?= $ruta_retorno ?>'">Volver</button>

</form>
</div>

</body>
</html>