<?php
session_start();

if (!isset($_SESSION['tipo'])) {
    header("Location: ../login/index.php");
    exit;
}

$roles_permitidos = ['admin','produccion','empleado'];
if (!in_array($_SESSION['tipo'], $roles_permitidos, true)) {
    die("Acceso denegado");
}

require_once("../db/conexion.php");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function e($t){
    return htmlspecialchars($t ?? '', ENT_QUOTES, 'UTF-8');
}

if(!isset($_GET['id']) || !ctype_digit($_GET['id'])){
    die("ID inválido");
}

$id = (int)$_GET['id'];

$stmt = $conexion->prepare("SELECT * FROM produccion WHERE id=? LIMIT 1");
$stmt->bind_param("i",$id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();

if(!$p){
    die("Registro no encontrado");
}

$mensaje = "";

if($_SERVER['REQUEST_METHOD']==='POST'){

    $fecha_hora   = $_POST['fecha_hora'] ?? '';
    $linea        = $_POST['linea'] ?? '';
    $producto     = $_POST['producto'] ?? '';
    $presentacion = $_POST['presentacion'] ?? '';
    $turno        = $_POST['turno'] ?? '';
    $peso         = (float)($_POST['peso'] ?? 0);
    $operador     = $_POST['operador'] ?? '';
    $observaciones= $_POST['observaciones'] ?? '';

    if(!$fecha_hora || !$linea || !$producto){
        $mensaje = "Faltan datos obligatorios";
    } elseif($peso<=0){
        $mensaje = "El peso debe ser mayor a 0";
    } else {

        try{

            $conexion->begin_transaction();

            $dt = new DateTime($fecha_hora);

            $fecha_produccion = $dt->format('Y-m-d');
            $hora = $dt->format('H:i:s');

            if ($linea === 'Línea de lavado 1') {
                $puerto = 1;
            } else {
                $puerto = 2;
            }

            $stmt = $conexion->prepare("
                UPDATE produccion SET
                fecha_produccion=?,
                linea=?,
                puerto=?,
                hora=?,
                presentacion=?,
                turno=?,
                operador=?,
                tipo_producto=?,
                peso=?,
                observaciones=?
                WHERE id=?
            ");

            $stmt->bind_param(
                "ssissssssdi",
                $fecha_produccion,
                $linea,
                $puerto,
                $hora,
                $presentacion,
                $turno,
                $operador,
                $producto,
                $peso,
                $observaciones,
                $id
            );

            $stmt->execute();

            $conexion->commit();

            header("Location: index.php?editado=1");
            exit;

        }catch(Exception $e){

            $conexion->rollback();
            $mensaje = $e->getMessage();
        }
    }
}


$linea_texto = ($p['puerto']==1) ? "Línea de lavado 1" : "Línea de lavado 2";


$fecha_hora = date('Y-m-d\TH:i', strtotime($p['fecha_produccion']." ".$p['hora']));
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Producción</title>
<link rel="icon" href="../admin/plas.jpg">

<style>

body{
background:url("../admin/fondo.jpg");
background-size:cover;
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

input,textarea,select{
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

.alert{
background:#f8d7da;
color:#842029;
padding:12px;
border-radius:10px;
margin-bottom:15px;
text-align:center;
font-weight:bold;
}

</style>
</head>
<body>

<div class="form-box">

<h2>Editar Producción</h2>

<?php if($mensaje): ?>
<div class="alert"><?=e($mensaje)?></div>
<?php endif; ?>

<form method="POST">

<label>Fecha y Hora</label>
<input type="datetime-local" name="fecha_hora" value="<?=$fecha_hora?>" required>

<label>Línea</label>
<select name="linea" required>
<option value="Línea de lavado 1" <?=$linea_texto=="Línea de lavado 1"?'selected':''?>>Línea 1</option>
<option value="Línea de lavado 2" <?=$linea_texto=="Línea de lavado 2"?'selected':''?>>Línea 2</option>
</select>

<label>Producto</label>
<select name="producto" required>

<?php
$productos=[
"Hojuela de PET Transparente tipo A",
"Hojuela de PET Transparente Beneficiado",
"Hojuela de PET Verde",
"Hojuela de PET Ámbar",
"Hojuela de PET Aceite"
];

foreach($productos as $prod){

$sel = ($p['tipo_producto']==$prod)?'selected':'';

echo "<option $sel>".e($prod)."</option>";
}
?>

</select>

<label>Presentación</label>
<select name="presentacion" required>

<option value="Globo" <?=$p['presentacion']=="Globo"?'selected':''?>>Globo</option>
<option value="Bulto" <?=$p['presentacion']=="Bulto"?'selected':''?>>Bulto</option>

</select>

<div class="grid-2">

<div>

<label>Turno</label>

<select name="turno" required>
<option value="1" <?=$p['turno']==1?'selected':''?>>Día</option>
<option value="2" <?=$p['turno']==2?'selected':''?>>Noche</option>
</select>

</div>

<div>

<label>Lote</label>
<input value="<?=e($p['lote'])?>" readonly>

</div>

</div>

<label>Peso (kg)</label>
<input type="number" step="0.01" name="peso" value="<?=e($p['peso'])?>" required>

<label>Observaciones</label>
<textarea name="observaciones"><?=e($p['observaciones'])?></textarea>

<label>Operador</label>
<input name="operador" value="<?=e($p['operador'])?>" required>

<button class="btn-guardar">Guardar cambios</button>

<button type="button" class="btn-volver" onclick="location.href='index.php'">
Volver
</button>

</form>

</div>

</body>
</html>