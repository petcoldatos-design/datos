<?php
session_start();
require_once("../db/conexion.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $residuo = $_POST['residuo'];
    $origen = $_POST['origen'];
    $peso = $_POST['peso'];
    $responsable = $_POST['responsable'];
    $observaciones = $_POST['observaciones'];

    $stmt = $conexion->prepare("
        INSERT INTO produccion_residuos
        (fecha,hora,residuo,origen,peso,responsable,observaciones)
        VALUES (?,?,?,?,?,?,?)
    ");

    $stmt->bind_param(
        "ssssdss",
        $fecha,
        $hora,
        $residuo,
        $origen,
        $peso,
        $responsable,
        $observaciones
    );

    $stmt->execute();
    $stmt->close();

    

    require_once("../admin/excel_guardar.php");

    $datos_excel = [
        $fecha,
        $hora,
        $residuo,
        $origen,
        $peso,
        $responsable,
        $observaciones,
        date("Y-m-d H:i:s")
    ];

    guardarEnExcel("RESIDUOS_PRODUCCION", $datos_excel);

    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registro Residuos Producción</title>
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
background:#ffffffee;
max-width:560px;
margin:auto;
padding:32px;
border-radius:20px;
box-shadow:0 10px 22px rgba(0,0,0,.18);
border-left:6px solid #1B5E20;
}

h2{
text-align:center;
color:#1B5E20;
margin-bottom:28px;
}

.form-group{
display:flex;
flex-direction:column;
gap:8px;
margin-bottom:22px;
}

label{
font-weight:600;
color:#1B5E20;
font-size:14px;
}

input,
textarea,
.hamburger-btn{
width:100%;
height:50px;
padding:0 14px;
border-radius:14px;
border:1px solid #bdbdbd;
font-size:15px;
box-sizing:border-box;
}

textarea{
height:80px;
padding:10px;
}

input:focus,textarea:focus{
outline:none;
border-color:#1B5E20;
box-shadow:0 0 0 2px rgba(27,94,32,.15);
}

.hamburger-select{
position:relative;
}

.hamburger-btn{
height:48px;
border-radius:14px;
border:1px solid #bdbdbd;
padding:0 14px;
display:flex;
align-items:center;
justify-content:space-between;
cursor:pointer;
background:#fff;
}

.hamburger-btn::after{
content:"☰";
font-size:18px;
}

.hamburger-options{
position:absolute;
top:52px;
left:0;
right:0;
background:#fff;
border-radius:14px;
box-shadow:0 12px 22px rgba(0,0,0,.18);
padding:8px;
display:none;
z-index:200;
}

.hamburger-options div{
padding:10px;
cursor:pointer;
border-radius:8px;
}

.hamburger-options div:hover{
background:#e8f5e9;
}

.form-actions{
display:flex;
flex-direction:column;
gap:10px;
margin-top:10px;
}

button{
height:50px;
border:none;
border-radius:14px;
font-size:16px;
font-weight:bold;
cursor:pointer;
}

.btn-guardar{
background:#1B5E20;
color:white;
}

.btn-volver{
background:#0d47a1;
color:white;
}

</style>
</head>

<body>

<div class="form-box">

<h2>Registro Residuos Producción</h2>

<form method="POST">

<div class="form-group">
<label>Fecha</label>
<input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required>
</div>

<div class="form-group">
<label>Hora</label>
<input type="time" name="hora" value="<?= date('H:i') ?>" required>
</div>


<div class="form-group hamburger-select">
<label>Residuo</label>

<input type="hidden" name="residuo" required>

<div class="hamburger-btn">Seleccione</div>

<div class="hamburger-options">
<div data-value="TAPA ENTERA">Tapa Entera</div>
<div data-value="TAPA Y ETIQUETA MOLIDA">Tapa y Etiqueta Molida</div>
<div data-value="POLVILLO">Polvillo</div>
<div data-value="ETIQUETA">Etiqueta</div>
</div>

</div>


<div class="form-group hamburger-select">

<label>Origen</label>

<input type="hidden" name="origen" required>

<div class="hamburger-btn">Seleccione</div>

<div class="hamburger-options">
<div data-value="LINEA 1">Linea 1</div>
<div data-value="LINEA 2">Linea 2</div>
<div data-value="TUNELES DE CALOR">Tuneles De Calor</div>
</div>

</div>


<div class="form-group">
<label>Peso (kg)</label>
<input type="number" step="0.01" name="peso" required>
</div>


<div class="form-group">
<label>Responsable</label>
<input name="responsable" required>
</div>

<div class="form-group">
<label>Observaciones</label>
<textarea name="observaciones"></textarea>
</div>

<div class="form-actions">
<button class="btn-guardar">Guardar</button>
<button type="button" class="btn-volver" onclick="location.href='../admin/'">Volver</button>
</div>

</form>
</div>


<script>

document.querySelectorAll(".hamburger-btn").forEach(btn=>{

btn.addEventListener("click",(e)=>{

e.stopPropagation();

const box = btn.nextElementSibling;

document.querySelectorAll(".hamburger-options")
.forEach(o=>{
if(o!==box) o.style.display="none";
});

box.style.display = box.style.display==="block" ? "none" : "block";

});

});


document.querySelectorAll(".hamburger-options div").forEach(opt=>{

opt.addEventListener("click",(e)=>{

e.stopPropagation();

const wrap = opt.closest(".hamburger-select");

const btn = wrap.querySelector(".hamburger-btn");
const input = wrap.querySelector("input");

btn.textContent = opt.textContent;
input.value = opt.dataset.value;

wrap.querySelector(".hamburger-options").style.display="none";

});

});


document.addEventListener("click",()=>{

document.querySelectorAll(".hamburger-options")
.forEach(o=>o.style.display="none");

});

</script>

</body>
</html>
