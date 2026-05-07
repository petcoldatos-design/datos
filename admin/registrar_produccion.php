<?php
session_start();

if (!isset($_SESSION['tipo'])) {
    header("Location: ../login/index.php");
    exit;
}

$roles_permitidos = ['admin', 'produccion', 'empleado'];
if (!in_array($_SESSION['tipo'], $roles_permitidos, true)) {
    header("Location: ../login/index.php");
    exit;
}

require_once("../db/conexion.php");
require_once("../vendor/autoload.php");
require_once(__DIR__ . '/excel_guardar.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

function guardarEnExcelProduccion($data) {

    $ruta = __DIR__ . "/../excel/Control_Total_Planta.xlsx";

    if (!file_exists($ruta)) {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle("PRODUCCION");
        $writer = new Xlsx($spreadsheet);
        $writer->save($ruta);
    }

    $spreadsheet = IOFactory::load($ruta);
    $sheet = $spreadsheet->getSheetByName("PRODUCCION");

    if (!$sheet) {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle("PRODUCCION");
    }

    if ($sheet->getHighestRow() == 1 && $sheet->getCell('A1')->getValue() == null) {
        $sheet->fromArray([
            "Fecha Produccion","Linea","Puerto","Usuario","Hora",
            "Presentacion","Turno","Lote","Operador",
            "Tipo Producto","Peso","Observaciones"
        ], null, 'A1');
    }

    $fila = $sheet->getHighestRow() + 1;

    $sheet->fromArray([
        $data['fecha_produccion'],
        $data['linea'],
        $data['puerto'],
        $data['usuario'],
        $data['hora'],
        $data['presentacion'],
        $data['turno'],
        $data['lote'],
        $data['operador'],
        $data['tipo_producto'],
        $data['peso'],
        $data['observaciones']
    ], null, "A{$fila}");

    $writer = new Xlsx($spreadsheet);
    $writer->save($ruta);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conexion->set_charset("utf8");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $conexion->begin_transaction();

        $fecha_hora    = $_POST['fecha_hora'] ?? null;
        $linea_simple  = $_POST['linea'] ?? null;
        $presentacion  = $_POST['presentacion'] ?? 'Sin especificar';
        $turno         = $_POST['turno'] ?? null;
        $peso          = isset($_POST['peso']) ? (float)$_POST['peso'] : 0;
        $observaciones = $_POST['observaciones'] ?? '';
        $operador      = $_POST['operador'] ?? null;
        $tipo_producto = $_POST['producto'] ?? null;
        $usuario       = $_SESSION['usuario_nombre'] ?? $_SESSION['tipo'];

        if (!$fecha_hora || !$linea_simple || !$turno || !$tipo_producto) {
            throw new Exception("Faltan datos obligatorios.");
        }

        if ($peso <= 0) {
            throw new Exception("El peso debe ser mayor a 0.");
        }



        $peso_restante = $peso;


        $sqlPaquetes = "
            SELECT pi.id, pi.datos
            FROM paquete_items pi
            INNER JOIN paquetes p ON p.id = pi.id_paquete
            WHERE LOWER(p.tipo) = 'proceso'
            ORDER BY pi.id ASC
            FOR UPDATE
        ";

        $res = $conexion->query($sqlPaquetes);

        while ($row = $res->fetch_assoc()) {

            if ($peso_restante <= 0) break;

            $id = $row['id'];
            $datos = json_decode($row['datos'], true);
            $peso_db = isset($datos['peso']) ? (float)$datos['peso'] : 0;

            if ($peso_db <= 0) continue;

            if ($peso_db > $peso_restante) {

                $datos['peso'] = $peso_db - $peso_restante;
                $nuevo_json = json_encode($datos, JSON_UNESCAPED_UNICODE);

                $stmt = $conexion->prepare("UPDATE paquete_items SET datos=? WHERE id=?");
                $stmt->bind_param("si", $nuevo_json, $id);
                $stmt->execute();
                $stmt->close();

                $peso_restante = 0;

            } else {

                $stmt = $conexion->prepare("DELETE FROM paquete_items WHERE id=?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();

                $peso_restante -= $peso_db;
            }
        }

        if ($peso_restante > 0) {

            $resProceso = $conexion->query("
                SELECT id, peso 
                FROM inventario_proceso
                ORDER BY id ASC
                FOR UPDATE
            ");

            while ($row = $resProceso->fetch_assoc()) {

                if ($peso_restante <= 0) break;

                $id = $row['id'];
                $peso_db = (float)$row['peso'];

                if ($peso_db > $peso_restante) {

                    $nuevo = $peso_db - $peso_restante;
                    $conexion->query("UPDATE inventario_proceso SET peso = $nuevo WHERE id = $id");
                    $peso_restante = 0;

                } else {

                    $conexion->query("DELETE FROM inventario_proceso WHERE id = $id");
                    $peso_restante -= $peso_db;
                }
            }
        }

        if ($peso_restante > 0) {
            throw new Exception("No hay suficiente peso disponible en proceso.");
        }



        if ($linea_simple === 'Línea de lavado 1') {
            $linea  = 'Línea de lavado 1';
            $puerto = 1;
            $letra  = 'A';
        } else {
            $linea  = 'Línea de lavado 2';
            $puerto = 2;
            $letra  = 'B';
        }

        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $fecha_hora);
        if (!$dt) throw new Exception("Formato de fecha inválido.");

        $fecha_produccion = $dt->format('Y-m-d');
        $hora = $dt->format('H:i:s');

        $base_lote = $letra . $dt->format('ymd') . $turno;

        $stmtLote = $conexion->prepare("
            SELECT lote
            FROM produccion
            WHERE lote LIKE CONCAT(?, '-%')
            ORDER BY lote DESC
            LIMIT 1
            FOR UPDATE
        ");
        $stmtLote->bind_param("s", $base_lote);
        $stmtLote->execute();
        $stmtLote->bind_result($ultimo_lote);

        $consecutivo = 1;
        if ($stmtLote->fetch() && !empty($ultimo_lote)) {
            $partes = explode('-', $ultimo_lote);
            $consecutivo = ((int) end($partes)) + 1;
        }
        $stmtLote->close();

        $lote = $base_lote . '-' . $consecutivo;

        $stmt = $conexion->prepare("
            INSERT INTO produccion (
                fecha_produccion,linea,puerto,usuario,hora,
                presentacion,turno,lote,operador,
                tipo_producto,peso,observaciones
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
        ");

        $stmt->bind_param(
            "ssisssssssds",
            $fecha_produccion,$linea,$puerto,$usuario,$hora,
            $presentacion,$turno,$lote,$operador,
            $tipo_producto,$peso,$observaciones
        );

$stmt->execute();


$id = $conexion->insert_id;

$stmt->close();


$created_at = date("Y-m-d H:i:s");


guardarEnExcel("PRODUCCION", [
    $id,
    $fecha_produccion,
    $linea,
    $puerto,
    $usuario,
    $hora,
    $presentacion,
    $turno,
    $lote,
    $operador,
    $tipo_producto,
    $peso,
    $observaciones,
    $created_at
]);

        $conexion->commit();

        header("Location: ".$_SERVER['PHP_SELF']."?ok=1");
        exit;

    } catch (Exception $e) {
        $conexion->rollback();
        die("❌ Error: ".$e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registro de Producción</title>
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

input,
textarea,
select{
    width:100%;
    height:46px;
    padding:10px 14px;
    border-radius:12px;
    border:1px solid #999;
    font-size:15px;
    margin-bottom:14px;
    box-sizing:border-box;
    background:#fff;
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

.btn-buscar{
    width:auto;
    background:#0D47A1;
    color:white;
    padding:8px 16px;
    border:none;
    border-radius:6px;
    font-weight:bold;
}
</style>

</head>
<body>

<div class="form-box">
<h2>Registro de Producción</h2>

<form method="POST" id="formProduccion">

<label>Fecha y hora</label>
<input type="datetime-local" name="fecha_hora" required value="<?= date('Y-m-d\TH:i') ?>">

<label>Línea</label>
<select name="linea" id="linea" required>
    <option value="">Seleccione</option>
    <option value="Línea de lavado 1">Línea 1</option>
    <option value="Línea de lavado 2">Línea 2</option>
</select>

<label>Producto</label>
<select name="producto" id="producto" required>
    <option value="">Seleccione</option>
    <option value="Hojuela de PET Transparente tipo A">Hojuela de PET Transparente tipo A</option>
    <option value="Hojuela de PET Transparente Beneficiado">Hojuela de PET Transparente Beneficiado</option>
    <option value="Hojuela de PET Verde">Hojuela de PET Verde</option>
    <option value="Hojuela de PET Ámbar">Hojuela de PET Ámbar</option>
    <option value="Hojuela de PET Aceite">Hojuela de PET Aceite</option>
</select>

<label>Presentacion</label>
<select name="presentacion" id="presentacion" required>
    <option value="">Seleccione</option>
<option value="Globo">Globo</option>
<option value="Bulto">Bulto</option>
</select>

<div class="grid-2">
<div>
<label>Turno</label>
<select name="turno" id="turno" required>
    <option value="">Seleccione</option>
    <option value="1">Día</option>
    <option value="2">Noche</option>
</select>
</div>
<div>
<label>Lote</label>
<input type="text" name="lote" id="lote" readonly>
</div>
</div>

<div class="grid-1">
<div></div>
<div style="width:100%;">
  <label>Peso (kg)</label>
  <input type="number" step="0.01" name="peso" required>
</div>
</div>

<label>Observaciones</label>
<textarea name="observaciones"></textarea>

<label>Operador</label>
<input type="text" name="operador" required>

<button type="submit" class="btn-guardar">Guardar Registro</button>
<?php if ($_SESSION['tipo'] === 'admin'): ?>
    <button type="button" class="btn-volver" onclick="location.href='index.php'">
        Volver
    </button>
<?php endif; ?>

</form>
</div>

<script>
const turno = document.getElementById('turno');
const fecha = document.querySelector('[name="fecha_hora"]');
const linea = document.getElementById('linea');
const lote  = document.getElementById('lote');
const form  = document.getElementById('formProduccion');


function generarLote() {
    if (!linea || !turno || !fecha || !lote) return;
    if (!linea.value || !turno.value || !fecha.value) {
        lote.value = '';
        return;
    }

    const letra = linea.value === 'Línea de lavado 1' ? 'A' : 'B';

    const f = new Date(fecha.value);
    if (isNaN(f.getTime())) {
        lote.value = '';
        return;
    }

    const yy = String(f.getFullYear()).slice(-2);
    const mm = String(f.getMonth() + 1).padStart(2, '0');
    const dd = String(f.getDate()).padStart(2, '0');

    lote.value = letra + yy + mm + dd + turno.value;
}


[linea, turno, fecha].forEach(el => {
    if (el) el.addEventListener('input', generarLote);
});


if (new URLSearchParams(window.location.search).has('ok')) {
    alert('✅ Producción registrada correctamente');

    if (form) form.reset();

    if (fecha) {
        const ahora = new Date();
        const yyyy = ahora.getFullYear();
        const mm = String(ahora.getMonth() + 1).padStart(2, '0');
        const dd = String(ahora.getDate()).padStart(2, '0');
        const hh = String(ahora.getHours()).padStart(2, '0');
        const min = String(ahora.getMinutes()).padStart(2, '0');
        fecha.value = `${yyyy}-${mm}-${dd}T${hh}:${min}`;
    }

    if (lote) lote.value = '';
}
</script>

</body>
</html>


