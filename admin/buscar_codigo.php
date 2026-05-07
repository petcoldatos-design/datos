<?php

session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require_once("../db/conexion.php");
require_once("../vendor/autoload.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;


function guardarEnExcelProceso($data) {

    $ruta = __DIR__ . "/../excel/Control_Total_Planta.xlsx";

    if (!file_exists($ruta)) {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle("EN_PROCESO");
        $writer = new Xlsx($spreadsheet);
        $writer->save($ruta);
    }

    $spreadsheet = IOFactory::load($ruta);
    $sheet = $spreadsheet->getSheetByName("EN_PROCESO");

    if (!$sheet) {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle("EN_PROCESO");
    }

    
    if ($sheet->getHighestRow() == 1 && $sheet->getCell('A1')->getValue() == null) {
        $sheet->fromArray([
            "ID",
            "Hora",
            "Proveedor",
            "Codigo Proveedor",
            "Tipo Material",
            "Tipo Producto",
            "Peso",
            "Codigo Paca",
            "Puerto",
            "Fecha Inicio"
        ], null, 'A1');
    }

    $ultimaFila = $sheet->getHighestRow() + 1;

    $sheet->fromArray([
        $data['id'], 
        $data['hora'],
        $data['proveedor'],
        $data['codigo_proveedor'],
        $data['tipo_material'],
        $data['tipo_producto'],
        $data['peso'],
        $data['codigo_paca'],
        $data['puerto'],
        date("Y-m-d H:i:s")
    ], null, "A{$ultimaFila}");

    $writer = new Xlsx($spreadsheet);
    $writer->save($ruta);
}


$tipo_usuario = strtolower(trim($_SESSION['tipo'] ?? ''));

if ($tipo_usuario === '') {
    die("Acceso denegado: no hay usuario logueado.");
}

$is_admin   = ($tipo_usuario === 'admin');
$is_proceso = ($tipo_usuario === 'proceso');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conexion->set_charset("utf8");

function e($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

$codigo = strtoupper(trim($_POST['codigo'] ?? $_GET['codigo'] ?? ''));
$mensaje = null;
$resultado = null; 

if ($codigo !== '') {
    $resultado = buscar_codigo($conexion, $codigo);
}

function buscar_codigo(mysqli $conexion, string $codigo): ?array {

    $stmt = $conexion->prepare("
        SELECT codigo_paca, proveedor, codigo_proveedor,
               fecha_inicio AS fecha, puerto, 1 AS en_proceso
        FROM inventario_proceso
        WHERE codigo_paca = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($res) return $res;

    $stmt = $conexion->prepare("
        SELECT codigo_paca, proveedor, codigo_proveedor,
               fecha, tipo_material, NULL AS puerto, 0 AS en_proceso
        FROM inventario
        WHERE codigo_paca = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($res) return $res;

    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['empezar_proceso'])) {

    $puerto = $_POST['puerto'] ?? '';

    if ($codigo === '' || !in_array($puerto, ['1','2'], true)) {
        $mensaje = "Debe seleccionar Puerto 1 o Puerto 2.";
    } else {

        try {
            $conexion->begin_transaction();

            $stmt = $conexion->prepare("SELECT 1 FROM inventario_proceso WHERE codigo_paca = ?");
            $stmt->bind_param("s", $codigo);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Este código ya está en proceso.");
            }
            $stmt->close();

            $stmt = $conexion->prepare("
                SELECT hora, proveedor, codigo_proveedor,
                       tipo_producto, tipo_material, peso
                FROM inventario
                WHERE codigo_paca = ?
                LIMIT 1
            ");
            $stmt->bind_param("s", $codigo);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$data) {
                throw new Exception("El código no existe en inventario.");
            }

            $hora             = $data['hora'] ?? null;
            $proveedor        = $data['proveedor'] ?? null;
            $codigo_proveedor = $data['codigo_proveedor'] ?? null;
            $tipo_producto    = $data['tipo_producto'] ?? null;
            $tipo_material    = $data['tipo_material'] ?? 'DESCONOCIDO';
            $peso             = (float)($data['peso'] ?? 0);
            $codigo_paca      = $codigo;
            $puerto_val       = $puerto;

            $stmt = $conexion->prepare("
                INSERT INTO inventario_proceso
                (
                    hora,
                    proveedor,
                    codigo_proveedor,
                    tipo_material,
                    tipo_producto,
                    peso,
                    codigo_paca,
                    puerto,
                    fecha_inicio
                )
                VALUES (?,?,?,?,?,?,?,?,NOW())
            ");

            $stmt->bind_param(
                "sssssdss",
                $hora,
                $proveedor,
                $codigo_proveedor,
                $tipo_material,
                $tipo_producto,
                $peso,
                $codigo_paca,
                $puerto_val
            );

            $stmt->execute();

            $idInsertado = $conexion->insert_id;

            $stmt->close();

            guardarEnExcelProceso([
                'id' => $idInsertado,
                'hora' => $hora,
                'proveedor' => $proveedor,
                'codigo_proveedor' => $codigo_proveedor,
                'tipo_material' => $tipo_material,
                'tipo_producto' => $tipo_producto,
                'peso' => $peso,
                'codigo_paca' => $codigo_paca,
                'puerto' => $puerto_val
            ]);

            $del = $conexion->prepare("DELETE FROM inventario WHERE codigo_paca = ?");
            $del->bind_param("s", $codigo);
            $del->execute();
            $del->close();

            $conexion->commit();

            $mensaje = "Código {$codigo} guardado correctamente.";
            $codigo = '';
            $resultado = null;

        } catch (Exception $e) {
            $conexion->rollback();
            $mensaje = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Iniciar Proceso</title>
<style>
body{
    background:url("../admin/fondo.jpg");
    background-size:cover;
    background-attachment:fixed;
    font-family:Arial, Helvetica, sans-serif;
    padding:40px 20px;
    display:flex;
    justify-content:center;
}
.box{
    background:#ffffffef;
    width:100%;
    max-width:600px;
    padding:34px;
    border-radius:18px;
    box-shadow:0 6px 18px rgba(0,0,0,.18);
    border-left:6px solid #1B5E20;
}
h2{text-align:center;color:#1B5E20;margin-bottom:26px;}
label, .info{
    display:block;
    font-weight:bold;
    color:#1B5E20;
    margin-bottom:6px;
    font-size:14px;
}
input, select, textarea{
    width:100%;
    height:46px;
    padding:10px 14px;
    border-radius:12px;
    border:1px solid #999;
    font-size:15px;
    margin-bottom:14px;
    box-sizing:border-box;
}
input:focus, select:focus, textarea:focus{
    border-color:#0D47A1;
    box-shadow:0 0 0 2px rgba(13,71,161,.15);
}
textarea{height:90px; resize:none;}
select{
    appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%23666' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
    background-repeat:no-repeat;
    background-position:right 14px center;
    padding-right:38px;
}
.alert{
    background:#f8d7da;
    color:#842029;
    padding:12px;
    border-radius:10px;
    margin-bottom:15px;
    text-align:center;
    font-weight:bold;
}
.estado-inv{color:#0D47A1;font-weight:bold;}
.estado-proc{color:#F9A825;font-weight:bold;}
button{
    width:100%;
    padding:14px;
    border:none;
    border-radius:12px;
    font-size:16px;
    font-weight:bold;
    cursor:pointer;
    transition:.25s;
    margin-bottom:10px;
}
.btn{
    background:#0D47A1;
    color:white;
}
.btn:hover{
    background:#08306b;
}
.btn-volver{
    background:#1B5E20;
    color:white;
    border-radius:12px;
    font-weight:bold;
    text-decoration:none;
    display:inline-block;
    transition:.25s;
    padding:14px 0;
    width:auto;
    min-width:600px;
    text-align:center;
    margin:0 auto;
}
.btn-volver:hover{
    background:#103d15;
}
</style>
</head>
<body>
<div class="box">
    <h2>Iniciar Proceso</h2>

    <?php if ($mensaje): ?>
        <div class="alert"><?= e($mensaje) ?></div>
    <?php endif; ?>

    <?php if ($codigo === ""): ?>

        <form method="POST">
            <input type="text" name="codigo" placeholder="Ingrese código de paca" required>
            <button type="submit" class="btn">Buscar</button>
        </form>

    <?php elseif (!$resultado): ?>

        <p class="estado-inv">Código no encontrado.</p>

    <?php else: ?>

        <?php
        $estado = $resultado['en_proceso']
            ? '<span class="estado-proc">En proceso</span>'
            : '<span class="estado-inv">En inventario</span>';
        ?>

        <div class="section">
            <p class="info"><strong>Código:</strong> <?= e($resultado['codigo_paca']) ?></p>
            <p class="info"><strong>Proveedor en registro:</strong> <?= e($resultado['proveedor']) ?></p>
            <p class="info"><strong>Fecha:</strong> <?= e($resultado['fecha'] ?? '—') ?></p>
            <p class="info"><strong>Puerto:</strong>
                <?= $resultado['puerto'] ? "Puerto " . e($resultado['puerto']) : "No asignado" ?>
            </p>
            <p class="info"><strong>Estado:</strong> <?= $estado ?></p>
        </div>

        <?php if (!empty($datos_proveedor ?? null)): ?>
            <div class="section">
                <h4>Datos reales del proveedor</h4>
                <p class="info"><strong>Nombre:</strong> <?= e($datos_proveedor['nombre_proveedor']) ?></p>
                <p class="info"><strong>Material:</strong> <?= e($datos_proveedor['tipo_material']) ?></p>
                <p class="info"><strong>Tipo:</strong> <?= e($datos_proveedor['producto']) ?></p>
                <p class="info"><strong>Residuo:</strong> <?= e($datos_proveedor['residuo']) ?></p>
                <p class="info"><strong>Procedencia:</strong> <?= e($datos_proveedor['procedencia_tipo']) ?></p>
                <p class="info"><strong>Observaciones:</strong> <?= e($datos_proveedor['observaciones']) ?></p>
            </div>
        <?php endif; ?>

        <?php if (!$resultado['en_proceso']): ?>
            <form method="POST">
                <input type="hidden" name="codigo" value="<?= e($codigo) ?>">
                <select name="puerto" required>
                    <option value="">Seleccione puerto</option>
                    <option value="1">Puerto 1</option>
                    <option value="2">Puerto 2</option>
                </select>
                <button type="submit" name="empezar_proceso" class="btn">
                    Iniciar proceso
                </button>
            </form>
        <?php endif; ?>

    <?php endif; ?> 

    <?php if ($is_admin): ?>
        <a class="btn-volver" href="index.php">Volver</a>
    <?php endif; ?>

</div>
</body>
</html>