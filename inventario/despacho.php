<?php
session_start();
require_once("../db/conexion.php");
require_once("../vendor/autoload.php");
require_once(__DIR__ . '/../admin/excel_guardar.php');
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conexion->set_charset("utf8");



$buscar       = '';
$producto     = '';
$presentacion = '';
$lote         = '';
$cantidad     = 0.00;
$mensaje      = '';



if (!isset($_SESSION['tipo']) || !isset($_SESSION['usuario'])) {
    header('Location: ../login/index.php');
    exit;
}



$buscar = $_GET['buscar'] ?? '';

if (!empty($buscar)) {

    $stmt = $conexion->prepare("
        SELECT tipo_producto, presentacion, lote, peso
        FROM produccion
        WHERE lote = ?
        LIMIT 1
    ");

    $stmt->bind_param("s", $buscar);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($res) {
        $producto     = $res['tipo_producto'];
        $presentacion = $res['presentacion'];
        $lote         = $res['lote'];

        $cantidad = round((float)$res['peso'], 2);
    } else {
        $mensaje = "Lote no encontrado";
    }
}



function guardarEnExcelDespacho($data) {

    $ruta = __DIR__ . "/../excel/Control_Total_Planta.xlsx";

    if (!file_exists($ruta)) {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle("DESPACHO");
        (new Xlsx($spreadsheet))->save($ruta);
    }

    $spreadsheet = IOFactory::load($ruta);
    $sheet = $spreadsheet->getSheetByName("DESPACHO");

    if (!$sheet) {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle("DESPACHO");
    }

    if ($sheet->getHighestRow() == 1 && $sheet->getCell('A1')->getValue() == null) {
        $sheet->fromArray([
            "Fecha","Cliente","Remision","Producto",
            "Presentacion","Cantidad KG","Lote",
            "Despachado Por","Conductor","Observaciones"
        ], null, 'A1');
    }

    $fila = $sheet->getHighestRow() + 1;

    $sheet->fromArray([
        $data['fecha'],
        $data['cliente'],
        $data['remision'],
        $data['producto'],
        $data['presentacion'],
        $data['cantidad'],
        $data['lote'],
        $data['despachado_por'],
        $data['conductor'],
        $data['observaciones']
    ], null, "A{$fila}");

    (new Xlsx($spreadsheet))->save($ruta);
}

 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $lote          = trim($_POST['lote'] ?? '');
    $cliente       = trim($_POST['cliente'] ?? '');
    $remision      = trim($_POST['remision'] ?? '');
    $conductor     = trim($_POST['conductor'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    $fecha         = date("Y-m-d");
    $despachado_por = $_SESSION['usuario'];

    if (empty($lote)) {
        die("Lote inválido");
    }

    try {

        $conexion->begin_transaction();

        $stmt = $conexion->prepare("
            SELECT tipo_producto, presentacion, peso
            FROM produccion
            WHERE lote = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $lote);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$data) {
            throw new Exception("Lote no encontrado");
        }

        $producto     = $data['tipo_producto'];
        $presentacion = $data['presentacion'];

        
        $cantidad = round((float)$data['peso'], 2);

        $insert = $conexion->prepare("
            INSERT INTO despachos_produccion
            (fecha, cliente, remision, producto, presentacion,
             cantidad_kg, lote, despachado_por, conductor, observaciones)
            VALUES (?,?,?,?,?,?,?,?,?,?)
        ");

        $insert->bind_param(
            "sssssdssss",
            $fecha,
            $cliente,
            $remision,
            $producto,
            $presentacion,
            $cantidad,
            $lote,
            $despachado_por,
            $conductor,
            $observaciones
        );

$insert->execute();


$id = $conexion->insert_id;

$insert->close();


$delete = $conexion->prepare("DELETE FROM produccion WHERE lote = ?");
$delete->bind_param("s", $lote);
$delete->execute();
$delete->close();


$created_at = date("Y-m-d H:i:s");


guardarEnExcel("DESPACHO", [
    $id,
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
    $created_at
]);

        $conexion->commit();

        echo "<script>alert('Despacho registrado correctamente'); window.location.href='index.php';</script>";
        exit;

    } catch (Exception $e) {
        $conexion->rollback();
        die("Error: ".$e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Despacho Producto Terminado</title>
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
h2{
    text-align:center;
    color:#1B5E20;
    margin-bottom:26px;
}
label{
    display:block;
    font-weight:bold;
    color:#1B5E20;
    margin-bottom:6px;
}
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
input[readonly]{
    background:#fff;
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
.btn-guardar{
    background:#0D47A1;
    color:white;
}
.btn-volver{
    background:#1B5E20;
    color:white;
    margin-top:10px;
}
.btn-buscar{
    background:#0D47A1;
    color:white;
    padding:8px 12px;
    border:none;
    border-radius:6px;
}
.buscar-form{
    text-align:center;
    margin-bottom:20px;
}
.buscar-input{
    width:200px;
    padding:8px;
    border-radius:6px;
    border:1px solid #999;
}
</style>
</head>

<body>

<div class="form-box">
<h2>Despacho Producto Terminado</h2>

<div class="buscar-form">
<form method="GET">
<input type="text" name="buscar" class="buscar-input"
placeholder="Buscar Lote"
value="<?= htmlspecialchars($buscar) ?>" required>
<button class="btn-buscar">Buscar</button>
</form>
</div>

<?php if(!empty($mensaje)){ ?>
<div style="color:red;text-align:center;font-weight:bold;margin-bottom:10px;">
<?= $mensaje ?>
</div>
<?php } ?>

<form method="POST">

<label>Fecha</label>
<input type="date" name="fecha" required value="<?= date('Y-m-d') ?>">

<label>Cliente</label>
<input type="text" name="cliente">

<label>Remisión</label>
<input type="text" name="remision">

<label>Producto</label>
<input type="text" name="producto" readonly value="<?= htmlspecialchars($producto) ?>">

<label>Presentación</label>
<input type="text" name="presentacion" readonly value="<?= htmlspecialchars($presentacion) ?>">

<div class="grid-2">
<div>
<label>Cantidad (KG)</label>
<input type="number" 
       name="cantidad" 
       value="<?= htmlspecialchars($cantidad) ?>" 
       step="0.01" 
       readonly>
</div>
<div>
<label>Lote</label>
<input type="text" name="lote" readonly value="<?= htmlspecialchars($lote) ?>">
</div>
</div>

<label>Despachado por</label>
<input type="text" name="despachado_por">

<label>Conductor</label>
<input type="text" name="conductor">

<label>Observaciones</label>
<textarea name="observaciones"></textarea>

<button class="btn-guardar" type="submit">Guardar Despacho</button>
<button class="btn-volver" type="button" onclick="location.href='index.php'">Volver</button>

</form>
</div>

</body>
</html>
