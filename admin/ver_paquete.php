<?php
session_start();
require_once("../db/conexion.php");


if(!isset($_GET["id"])) die("ID no especificado");
$id = intval($_GET["id"]);


$stmt = $conexion->prepare("SELECT * FROM paquetes WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$paqueteQ = $stmt->get_result();
if($paqueteQ->num_rows==0) die("Paquete no encontrado");
$paquete = $paqueteQ->fetch_assoc();


$tipo = ($id == 47) ? "proveedores" : strtolower(trim($paquete["tipo"]));
$tituloTipo = strtoupper($tipo);


$stmt2 = $conexion->prepare("SELECT * FROM paquete_items WHERE id_paquete=? ORDER BY id ASC");
$stmt2->bind_param("i", $id);
$stmt2->execute();
$items = $stmt2->get_result();


function json_datos($json){
    $a = json_decode($json,true);
    return (is_array($a) && json_last_error()===JSON_ERROR_NONE)? $a : [];
}
function e($v){
    return htmlspecialchars($v ?? "",ENT_QUOTES,"UTF-8");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Paquete <?= $tituloTipo ?></title>
<style>
body{background:#f4f4f4;margin:0;font-family:Arial,sans-serif;}
.box{background:#fff;width:95%;max-width:1300px;margin:30px auto;padding:20px;border-radius:14px;box-shadow:0 4px 16px rgba(0,0,0,.15);}
h2{color:#1B5E20;margin:0;}
h3{margin:6px 0 16px;font-size:15px;color:#333;}
.table-responsive{overflow-x:auto;}
table{border-collapse:collapse;width:100%;min-width:1100px;}
th,td{padding:8px 10px;border-bottom:1px solid #ddd;font-size:14px;white-space:nowrap;}
th{background:#1B5E20;color:#fff;position:sticky;top:0;z-index:2;}
tr:hover td{background:#e6f5e9;}
.empty{text-align:center;padding:20px;color:#666;font-weight:bold;}
.btn-volver{display:inline-block;margin-top:18px;background:#1B5E20;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:bold;}
.btn-guardar{
    background:#1B5E20;
    color:white;
    border:none;
    padding:6px 12px;
    border-radius:6px;
    cursor:pointer;
    font-weight:bold;
}
</style>
</head>
<body>

<div class="box">
<h2>PAQUETE DE <?= $tituloTipo ?></h2>
<h3>Guardado: <?= e($paquete["fecha_guardado"]) ?></h3>

<div class="table-responsive">
<table>
<thead>
<tr>
<?php if($tipo=="inventario"): ?>
<th>Hora</th><th>Placa</th><th>Proveedor</th><th>Código</th><th>Procedencia</th>
<th>Material</th><th>Color</th><th>Presentación</th><th>Procedencia tipo</th>
<th>Producto</th><th>Residuo</th><th>Historial</th><th>Peso</th>
<th>Resina</th><th>Paca</th><th>Fecha</th>
 
<?php elseif($tipo=="proveedores"): ?>
<th>Fecha</th><th>Código</th><th>Proveedor</th><th>Tipo proveedor</th><th>Municipio</th>
<th>Producto</th><th>Procedencia</th><th>Material</th><th>Historial</th><th>Resina</th>
<th>Observaciones</th><th>Acciones</th>


<?php elseif($tipo=="proceso"): ?>

<th>Proveedor</th>
<th>Código</th>
<th>Producto</th>
<th>Peso</th>
<th>Paca</th>
<th>Puerto</th>
<th>Estado</th>
<th>Fecha Y hora</th>


<?php elseif($tipo=="produccion"): ?>
<th>Fecha produccion</th><th>Línea</th><th>Producto</th><th>Presentación</th>
<th>Turno</th><th>Lote</th><th>Número</th><th>Peso (kg)</th>
<th>Observaciones</th><th>Operador</th>

<?php elseif($tipo=="despacho"): ?>
<th>Fecha</th><th>Cliente</th><th>Remisión</th><th>Producto</th><th>Presentación</th>
<th>Cantidad (KG)</th><th>Lote</th><th>Despachado por</th><th>Conductor</th>
<th>Observaciones</th>
<?php endif; ?>
</tr>
</thead>

<tbody>
<?php if($items->num_rows==0): ?>
<tr><td colspan="20" class="empty">No hay datos en este paquete</td></tr>
<?php else: ?>
<?php while($r=$items->fetch_assoc()):
$d = json_datos($r["datos"]);
?>
<tr>
<?php if($tipo=="inventario"): ?>
<td><?= e($d["hora"]) ?></td><td><?= e($d["placa"]) ?></td><td><?= e($d["proveedor"]) ?></td>
<td><?= e($d["codigo_proveedor"]) ?></td><td><?= e($d["procedencia"]) ?></td>
<td><?= e($d["tipo_material"]) ?></td><td><?= e($d["color"]) ?></td><td><?= e($d["presentacion"]) ?></td>
<td><?= e($d["procedencia_tipo"]) ?></td><td><?= e($d["tipo_producto"]) ?></td>
<td><?= e($d["tipo_residuo"]) ?></td><td><?= e($d["historial"]) ?></td><td><?= e($d["peso"]) ?></td>
<td><?= e($d["tipo_resina"] ?? "") ?></td><td><?= e($d["codigo_paca"]) ?></td><td><?= e($d["fecha"]) ?></td>
<td>-</td>

<?php elseif($tipo=="proveedores"): ?>
<td><?= e($d["fecha"]) ?></td>
<td><?= e($d["codigo_proveedor"]) ?></td>
<td><?= e($d["nombre_proveedor"]) ?></td>
<td><?= e($d["tipo_proveedor"]) ?></td>
<td><?= e($d["municipio"]) ?></td>
<td><?= e($d["producto"]) ?></td>
<td><?= e($d["procedencia_tipo"]) ?></td>
<td><?= e($d["tipo_material"]) ?></td>
<td><?= e($d["historial"]) ?></td>
<td><?= e($d["tipo_resina"] ?? "") ?></td>
<td><?= e($d["observaciones"]) ?></td>
<td>

    <form style="display:inline;" method="get" action="editar_item_proveedor.php">
        <input type="hidden" name="item_id" value="<?= $r['id'] ?>">
        <button type="submit" class="btn-guardar" style="background:#0288d1;">Editar</button>
    </form>

    <form style="display:inline;" method="post" action="eliminar_item_proveedor.php" onsubmit="return confirm('¿Seguro que quieres eliminar este item?');">
        <input type="hidden" name="item_id" value="<?= $r['id'] ?>">
        <input type="hidden" name="paquete_id" value="<?= $id ?>">
        <button type="submit" class="btn-guardar" style="background:#d32f2f;">Eliminar</button>
    </form>
</td>


<?php elseif($tipo=="proceso"): ?>
<td><?= e($d["proveedor"] ?? "") ?></td>
<td><?= e($d["codigo_proveedor"] ?? "") ?></td>
<td><?= e($d["tipo_material"] ?? "") ?></td>
<td><?= e($d["peso"] ?? "") ?></td>
<td><?= e($d["codigo_paca"] ?? "") ?></td>
<td>Puerto <?= e($d["puerto"] ?? "") ?></td>
<td><b>En proceso</b></td>
<td><?= e($d["fecha_inicio"] ?? "") ?></td>
<td>
<form method="post" action="pasar_item_proceso.php">
<input type="hidden" name="item_id" value="<?= $r['id'] ?>">
<input type="hidden" name="paquete_id" value="<?= $id ?>">
</form>
</td>



<?php elseif($tipo=="produccion"): ?>

<?php
$fechaHora = "";

$fechaRaw = $d["fecha_produccion"] 
            ?? $d["fecha"] 
            ?? $d["fecha_inicio"] 
            ?? null;

if (!empty($fechaRaw)) {
    try {
        $fecha = new DateTime($fechaRaw);
        $fechaHora = $fecha->format("d/m/Y h:i A");
    } catch (Exception $e) {
        $fechaHora = e($fechaRaw); 
    }
}
?>

<td><?= e($fechaHora) ?></td>
<td><?= e($d["linea"] ?? "") ?></td>
<td><?= e($d["tipo_producto"] ?? "") ?></td>
<td><?= e($d["presentacion"] ?? "") ?></td>
<td><?= e($d["turno"] ?? "") ?></td>
<td><?= e($d["lote"] ?? "") ?></td>
<td><?= e($d["numero"] ?? "") ?></td>
<td><?= e($d["peso"] ?? "") ?></td>
<td><?= e($d["observaciones"] ?? "") ?></td>
<td><?= e($d["operador"] ?? "") ?></td>




<?php elseif($tipo=="despacho"): ?>
<td><?= e($d["fecha"]) ?></td><td><?= e($d["cliente"]) ?></td><td><?= e($d["remision"]) ?></td>
<td><?= e($d["producto"]) ?></td><td><?= e($d["presentacion"]) ?></td>
<td><?= e($d["cantidad_kg"]) ?></td><td><?= e($d["lote"]) ?></td>
<td><?= e($d["despachado_por"]) ?></td><td><?= e($d["conductor"]) ?></td>
<td><?= e($d["observaciones"]) ?></td>
<td>
<form method="post" action="devolver_item_despacho.php">
<input type="hidden" name="item_id" value="<?= $r['id'] ?>">
<input type="hidden" name="paquete_id" value="<?= $id ?>">

</form>
</td>

<?php endif; ?>
</tr>
<?php endwhile; ?>
<?php endif; ?>
</tbody>
</table>
</div>

<a class="btn-volver" href="paquetes.php">Volver</a>
</div>

</body>
</html>
