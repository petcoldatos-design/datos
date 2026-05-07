<?php
session_start();

if (
    !isset($_SESSION['tipo']) ||
    ($_SESSION['tipo'] !== 'admin' && $_SESSION['tipo'] !== 'inventario')
) {
    header("HTTP/1.1 403 Forbidden");
    exit("Acceso denegado");
}

require_once("../db/conexion.php");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conexion->set_charset("utf8");

if (!isset($_GET['item_id']) || !ctype_digit($_GET['item_id'])) {
    exit("Proveedor no especificado");
}
$item_id = (int)$_GET['item_id'];

$stmt = $conexion->prepare("
    SELECT id_paquete, datos
    FROM paquete_items
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    exit("Proveedor no encontrado");
}

$row = $result->fetch_assoc();
$id_paquete = $row['id_paquete'];

$datos = json_decode($row['datos'], true);
if (!is_array($datos)) $datos = [];

function e($v){
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre_proveedor = trim($_POST['nombre_proveedor'] ?? '');
    $codigo_proveedor = trim($_POST['codigo_proveedor'] ?? '');
    $fecha            = trim($_POST['fecha'] ?? '');

    if ($nombre_proveedor === '' || $codigo_proveedor === '' || $fecha === '') {
        exit("Campos obligatorios incompletos");
    }

    $tipo_proveedor   = ($_POST['tipo_proveedor'] === 'Otro')
        ? trim($_POST['tipo_proveedor_otro'] ?? '')
        : trim($_POST['tipo_proveedor'] ?? '');

    $producto         = trim($_POST['tipo_producto'] ?? '');
    $producto_dos     = trim($_POST['tipo_producto_dos'] ?? '');
    $producto_tres    = trim($_POST['tipo_producto_tres'] ?? '');
    $residuo          = trim($_POST['tipo_residuo'] ?? '');
    $procedencia      = trim($_POST['procedencia'] ?? '');
    $procedencia_tipo = trim($_POST['procedencia_tipo'] ?? '');
    $material         = trim($_POST['material'] ?? '');
    $tipo_resina      = trim($_POST['tipo_resina'] ?? '');
    $historial        = trim($_POST['historial'] ?? '');
    $observaciones    = trim($_POST['observaciones'] ?? '');

    $datos_nuevos = json_encode([
        "fecha" => $fecha,
        "nombre_proveedor" => $nombre_proveedor,
        "codigo_proveedor" => $codigo_proveedor,
        "tipo_proveedor" => $tipo_proveedor,
        "producto" => $producto,
        "producto_dos" => $producto_dos,
        "producto_tres" => $producto_tres,
        "residuo" => $residuo,
        "municipio" => $procedencia,
        "procedencia_tipo" => $procedencia_tipo,
        "material" => $material,
        "tipo_resina" => $tipo_resina,
        "historial" => $historial,
        "observaciones" => $observaciones
    ], JSON_UNESCAPED_UNICODE);

    $stmt2 = $conexion->prepare("
        UPDATE paquete_items 
        SET datos = ?
        WHERE id = ?
        LIMIT 1
    ");
    $stmt2->bind_param("si", $datos_nuevos, $item_id);

    if (!$stmt2->execute()) {
        exit("Error al actualizar: " . $stmt2->error);
    }

    header("Location: ver_paquete.php?id={$id_paquete}&msg=editado");
    exit;
}
?>



<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Modificar Proveedor en Paquete</title>
<link rel="icon" href="../admin/plas.jpg">
<style>
body{background:url("../admin/fondo.jpg");background-size:cover;background-attachment:fixed;font-family:Arial;padding:30px;}
.form-box{background:#ffffffef;width:520px;margin:auto;padding:30px;border-radius:18px;box-shadow:0 5px 16px rgba(0,0,0,.15);border-left:6px solid #1B5E20;}
h2{text-align:center;color:#1B5E20;margin-bottom:25px;}
label{display:block;font-weight:bold;color:#1B5E20;margin-bottom:6px;}
input, select, textarea{width:100%;height:46px;padding:10px 14px;border-radius:12px;border:1px solid #999;font-size:15px;box-sizing:border-box;}
textarea{height:90px;resize:none;}
select{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%23666' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center;padding-right:38px;}
.otro-input{margin-top:8px;display:none;}
button{width:100%;padding:14px;border:none;border-radius:12px;font-size:16px;font-weight:bold;cursor:pointer;}
.btn-guardar{background:#1B5E20;color:white;}
.btn-volver{background:#0d47a1;color:white;margin-top:10px;}
</style>
</head>
<body>

<div class="form-box">
<h2>Modificar Proveedor en Paquete</h2>
<form method="POST">

<label>Fecha</label>
<input type="date" name="fecha" value="<?= e($datos['fecha'] ?? '') ?>" required>

<label>Código del proveedor</label>
<input name="codigo_proveedor" value="<?= e($datos['codigo_proveedor'] ?? '') ?>" required>

<label>Nombre del proveedor</label>
<input name="nombre_proveedor" value="<?= e($datos['nombre_proveedor'] ?? '') ?>" required>

<label>Tipo de proveedor</label>
<select name="tipo_proveedor" onchange="mostrarOtro(this,'tp_otro')" required>
<option value="">Seleccione</option>
<option <?= ($datos['tipo_proveedor'] ?? '')==='Gesto De Reciclaje'?'selected':'' ?>>Gesto De Reciclaje</option>
<option <?= ($datos['tipo_proveedor'] ?? '')==='Cooperativa Re Reciclaje'?'selected':'' ?>>Cooperativa Re Reciclaje</option>
<option value="Otro" <?= !in_array($datos['tipo_proveedor'] ?? '', ['Gesto De Reciclaje','Cooperativa Re Reciclaje'])?'selected':'' ?>>Otro</option>
</select>
<input class="otro-input" id="tp_otro" name="tipo_proveedor_otro" value="<?= !in_array($datos['tipo_proveedor'] ?? '', ['Gesto De Reciclaje','Cooperativa Re Reciclaje'])?e($datos['tipo_proveedor']):'' ?>">

<label>Ciudad / Municipio</label>
<input name="procedencia" value="<?= e($datos['municipio'] ?? '') ?>" required>

<label>Tipos de producto</label>
<input name="tipo_producto" value="<?= e($datos['producto'] ?? '') ?>" required>


<label>Tipo de residuo</label>
<input name="tipo_residuo" value="<?= e($datos['residuo'] ?? '') ?>" required>

<label>Procedencia tipo</label>
<input name="procedencia_tipo" value="<?= e($datos['procedencia_tipo'] ?? '') ?>" required>

<label>Material</label>
<input name="material" value="<?= e($datos['material'] ?? '') ?>" required>

<label>Tipo de resina</label>
<input name="tipo_resina" value="<?= e($datos['tipo_resina'] ?? '') ?>" required>

<label>Historial</label>
<input name="historial" value="<?= e($datos['historial'] ?? '') ?>" required>

<label>Observaciones</label>
<textarea name="observaciones"><?= e($datos['observaciones'] ?? '') ?></textarea>

<button class="btn-guardar">Actualizar</button>
<button type="button" class="btn-volver" onclick="location.href='ver_paquete.php?id=<?= $id_paquete ?>'">Volver</button>
</form>
</div>

<script>
function mostrarOtro(select, id){
    const input = document.getElementById(id);
    if(select.value === 'Otro'){
        input.style.display = 'block';
        input.required = true;
    }else{
        input.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}
</script>

</body>
</html>
