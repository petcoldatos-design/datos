<?php
session_start();
require_once("../db/conexion.php");


if (!isset($_SESSION['tipo'])) {
    header('Location: ../login/index.php');
    exit;
}

$rol = strtolower(trim($_SESSION['tipo']));
if (!in_array($rol, ['admin', 'inventario'], true)) {
    die('Acceso denegado');
}


$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $fecha            = $_POST['fecha'] ?? date('Y-m-d');
    $codigo_proveedor = trim($_POST['codigo_proveedor'] ?? '');
    $proveedor        = trim($_POST['proveedor'] ?? '');
    $procedencia      = trim($_POST['procedencia'] ?? '');

    $tipo_material    = $_POST['tipo_material'] ?? '';
    $tipo_resina      = $_POST['tipo_resina'] ?? '';
    $procedencia_tipo = $_POST['procedencia_tipo'] ?? '';
    $tipo_producto    = $_POST['tipo_producto'] ?? '';
    $tipo_residuo     = $_POST['tipo_residuo'] ?? '';
    $historial        = trim((string)($_POST['historial'] ?? ''));

    if (empty($errores)) {

        $conexion->begin_transaction();

        try {

            $fecha_cod = date('Ymd', strtotime($fecha));
            $base = $codigo_proveedor . '-' . $fecha_cod;

            $stmt = $conexion->prepare("
                SELECT codigo_paca 
                FROM inventario
                WHERE codigo_paca LIKE CONCAT(?, '-%')
                ORDER BY codigo_paca DESC
                LIMIT 1
                FOR UPDATE
            ");

            if (!$stmt) throw new Exception($conexion->error);

            $stmt->bind_param("s", $base);
            $stmt->execute();
            $stmt->bind_result($ultimo_codigo);

            $nuevo_numero = 1;

            if ($stmt->fetch()) {
                $partes = explode('-', $ultimo_codigo);
                $nuevo_numero = ((int) end($partes)) + 1;
            }

            $stmt->close();

            $codigo_paca = $base . '-' . $nuevo_numero;

            $stmt = $conexion->prepare("
                INSERT INTO inventario (
                    hora, placa, proveedor, codigo_proveedor,
                    procedencia, tipo_material, tipo_resina, color,
                    presentacion, procedencia_tipo, tipo_producto,
                    tipo_residuo, historial, peso, remision, codigo_paca
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ");

            if (!$stmt) throw new Exception($conexion->error);

            $stmt->bind_param(
                "sssssssssssssdss",
                $_POST['hora'],
                $_POST['placa'],
                $proveedor,
                $codigo_proveedor,
                $procedencia,
                $tipo_material,
                $tipo_resina,
                $_POST['color'],
                $_POST['presentacion'],
                $procedencia_tipo,
                $tipo_producto,
                $tipo_residuo,
                $historial,
                $_POST['peso'],
                $_POST['remision'],
                $codigo_paca
            );

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            $idInsertado = $conexion->insert_id;

            $stmt->close();
            $conexion->commit();

            

            require_once("../admin/excel_guardar.php");

            $headers = [
                "fecha","hora","placa","proveedor","codigo_proveedor",
                "procedencia","tipo_material","tipo_resina","color",
                "presentacion","procedencia_tipo","tipo_producto",
                "tipo_residuo","historial","peso","remision","codigo_paca"
            ];

            $datos_excel = [
                $idInsertado,
                $fecha,
                $_POST['hora'] ?? '',
                $_POST['placa'] ?? '',
                $proveedor,
                $codigo_proveedor,
                $procedencia,
                $tipo_material,
                $tipo_resina,
                $_POST['color'] ?? '',
                $_POST['presentacion'] ?? '',
                $procedencia_tipo,
                $tipo_producto,
                $tipo_residuo,
                $historial,
                $_POST['peso'] ?? '',
                $_POST['remision'] ?? '',
                $codigo_paca,
                date("Y-m-d H:i:s")
            ];

            guardarEnExcel("INVENTARIO", $datos_excel);

            header("Location: index.php");
            exit;

        } catch (Exception $e) {
            $conexion->rollback();
            die("Error al guardar: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registrar Residuo</title>
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
    padding:32px 34px;
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
    gap:6px;
    margin-bottom:18px;
}

label{
    font-weight:600;
    color:#1B5E20;
    font-size:14.5px;
}


input{
    width:100%;
    height:48px;
    padding:0 14px;
    border-radius:14px;
    border:1px solid #bdbdbd;
    font-size:15px;
    background:#fff;
    box-sizing:border-box;
}

input:focus{
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
    font-size:15px;
}

.hamburger-btn::after{
    content:"☰";
    font-size:18px;
    color:#555;
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
    max-height:240px;
    overflow-y:auto;
}

.hamburger-options div{
    padding:10px 12px;
    border-radius:10px;
    cursor:pointer;
    font-size:14.5px;
}

.hamburger-options div:hover{
    background:#e8f5e9;
}


.hamburger-options::-webkit-scrollbar{
    width:6px;
}

.hamburger-options::-webkit-scrollbar-thumb{
    background:#999;
    border-radius:5px;
}


.form-actions{
    width:100%;
    display:flex;
    flex-direction:column;
    gap:10px;
    margin-top:10px;
}

.form-actions button{
    width:100%;
    height:50px;
    border:none;
    border-radius:14px;
    font-size:16px;
    font-weight:bold;
    cursor:pointer;
}
#listaProveedores div:hover{
background:#e8f5e9;
}

#listaProveedores div{
border-bottom:1px solid #eee;
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

<body>

<div class="form-box">
<h2>Registrar Nuevo Residuo</h2>

<form method="POST">

<div class="form-group">
<label>Fecha</label>
<input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required>
</div>

<div class="form-group">
<label>Hora</label>
<input type="time" name="hora" required>
</div>
<div class="form-group" style="position:relative;">
<label>Nombre Del Proveedor</label>
<input id="proveedorInput" name="proveedor" autocomplete="off" required>

<div id="listaProveedores" style="
position:absolute;
background:white;
border:1px solid #ccc;
width:100%;
display:none;
max-height:180px;
overflow-y:auto;
z-index:500;
"></div>
</div>
<div class="form-group">
<label>Código Del Proveedor</label>
<input name="codigo_proveedor" id="codigoProveedor" readonly required>
</div>

<div class="form-group">
<label>Placa - Vehículo</label>
<input name="placa" required>
</div>

<div class="form-group">
<label>Ciudad / Municipio</label>
<input name="procedencia" required>
</div>

<div class="form-group hamburger-select">
<label>Tipo De Material</label>
<input type="hidden" name="tipo_material" required>
<div class="hamburger-btn">Seleccione</div>
<div class="hamburger-options">
<div data-value="ACEITE">Aceite</div>
<div data-value="AMBAR">Ambar</div>
<div data-value="BOLSA DE AZUCAR">Bolsa De Azucar</div>
<div data-value="CARTÓN">Cartón</div>
<div data-value="CRISTAL BENEFICIADO">Cristal Beneficiado</div>
<div data-value="CRISTAL ETIQUETA">Cristal Etiqueta</div>
<div data-value="CRISTAL POSINDUSTRIAL">Cristal Posindustrial</div>
<div data-value="ESTIBAS">Estibas</div>
<div data-value="GLOBOS">Globos</div>
<div data-value="HIT">Hit</div>
<div data-value="LAMINA DE FLORES">Lamina De Flores</div>
<div data-value="LONAS">Lonas</div>
<div data-value="MATERIAL DE COLORES">Material De Colores</div>
<div data-value="PET ASEO">Pet Aseo</div>
<div data-value="PET BLANCO">Pet Blanco</div>
<div data-value="PET ETIQUETA PVC">Pet Etiqueta Pvc</div>
<div data-value="PET MOLIDO AMBAR">Pet Molido Ambar</div>
<div data-value="PET MOLIDO COLORES">Pet Molido Colores</div>
<div data-value="PET MOLIDO NARANJA">Pet Molido Naranja</div>
<div data-value="PET MOLIDO TRANSPARENTE">Pet Molido Transparente</div>
<div data-value="PET MOLIDO VERDE">Pet Molido Verde</div>
<div data-value="PET NARANJA MOLIDO">Pet Naranja Molido</div>
<div data-value="PET ROSADO">Pet Rosado</div>
<div data-value="PLASTICO POLICOLOR">Plastico Policolor</div>
<div data-value="PLASTICO TRANSPARENTE">Plastico Transparente</div>
<div data-value="POLIPROPILENO (FRITURA)">Polipropileno (Fritura)</div>
<div data-value="POLVILLO">Polvillo</div>
<div data-value="POLVILLO LIMPIO">Polvillo Limpio</div>
<div data-value="POLVILLO SUCIO">Polvillo Sucio</div>
<div data-value="PP LIMPIO">Pp Limpio</div>
<div data-value="PREFORMA CRISTAL">Preforma Cristal</div>
<div data-value="PREFORMA ROSADA">Preforma Rosada</div>
<div data-value="PREFORMA VERDE">Preforma Verde</div>
<div data-value="R PET AMBAR">R Pet Ambar</div>
<div data-value="REVUELTO">Revuelto</div>
<div data-value="SCREEN">Screen</div>
<div data-value="SOPLADO">Soplado</div>
<div data-value="SOPLADO MOLIDO BLANCO">Soplado Molido Blanco</div>
<div data-value="TAPA PLASTICA">Tapa Plastica</div>
<div data-value="TAPA PLÁSTICA">Tapa Plástica</div>
<div data-value="TORTA">Torta</div>
<div data-value="VERDE">Verde</div>
<div data-value="VERDE-AMBAR">Verde-Ambar</div>
<div data-value="ZUNCHO">Zuncho</div>
<div data-value="ZUNCHO PLASTICO VERDE">Zuncho Plastico Verde</div>
</div>
</div>

<div class="form-group hamburger-select">
<label>Tipo De Resina</label>
<input type="hidden" name="tipo_resina" required>
<div class="hamburger-btn">Seleccione</div>

<div class="hamburger-options">
<div data-value="PET">PET</div>
<div data-value="Polietileno De Alta (HDPE)">Polietileno De Alta (HDPE)</div>
<div data-value="Polietileno De Baja (LDPE)">Polietileno De Baja (LDPE)</div>
<div data-value="Polipropileno (PP)">Polipropileno (PP)</div>
<div data-value="Otro">Otro</div>
</div>
</div>


<div class="form-group hamburger-select">
<label>Color</label>
<input type="hidden" name="color" required>
<div class="hamburger-btn">Seleccione</div>
<div class="hamburger-options">
<div data-value="Transparente">Transparente</div>
<div data-value="Verde">Verde</div>
<div data-value="Ámbar">Ámbar</div>
<div data-value="Azul">Azul</div>
<div data-value="Policolor">Policolor</div>
</div>
</div>

<div class="form-group hamburger-select">
<label>Presentación</label>
<input type="hidden" name="presentacion" required>
<div class="hamburger-btn">Seleccione</div>
<div class="hamburger-options">
<div data-value="Compactado">Compactado</div>
<div data-value="Suelto">Suelto</div>
<div data-value="Globo">Globo</div>
<div data-value="Otro">Otro</div>
</div>
</div>

<div class="form-group hamburger-select">
<label>Procedencia</label>
<input type="hidden" name="procedencia_tipo" required>
<div class="hamburger-btn">Seleccione</div>
<div class="hamburger-options">
<div data-value="Posconsumo">Posconsumo</div>
<div data-value="Preconsumo">Preconsumo</div>
<div data-value="Industrial">Industrial</div>
</div>
</div>
.
<div class="form-group hamburger-select">
<label>Tipo De Producto</label>
<input type="hidden" name="tipo_producto" required>
<div class="hamburger-btn">Seleccione</div>
<div class="hamburger-options">
<div data-value="Botellas">Botellas</div>
<div data-value="Empaques Flexibles">Empaques Flexibles</div>
<div data-value="Envases">Envases</div>
<div data-value="Embalaje Alimentos">Embalaje Alimentos</div>
<div data-value="Otros">Otros</div>
</div>
</div>

<div class="form-group hamburger-select">
<label>Tipo De Residuo</label>
<input type="hidden" name="tipo_residuo" required>
<div class="hamburger-btn">Seleccione</div>
<div class="hamburger-options">
<div data-value="Hogar">Hogar</div>
<div data-value="Industria">Industria</div>
<div data-value="Comercio">Comercio</div>
</div>
</div>

<div class="form-group hamburger-select">
<label>Historial Del Residuo</label>
<input type="hidden" name="historial" required>
<div class="hamburger-btn">Seleccione</div>
<div class="hamburger-options">
<div data-value="Bebidas y refrescos">De Bebidas Y Refrescos</div>
<div data-value="Empaque alimentos">Empaque De Alimentos</div>
<div data-value="Empaque / Embalaje">Empaque De Productos De Aseo</div>
<div data-value="Empacado / Embalaje">Empacado / Embalaje De Mercancias</div>
<div data-value="Sustancias peligrosas">Sustancias Peligrosas</div>
<div data-value="Otros">Otros</div>
</div>
</div>

<div class="form-group">
<label>Peso (Kg)</label>
<input type="number" step="0.01" name="peso" required>
</div>

<div class="form-group">
<label>Remisión</label>
<input name="remision">
</div>

<div class="form-group">
<label>Codigo De Paca (Automático)</label>
<input id="codigoPreview"
       type="text"
       readonly
       style="background:#f1f8e9;font-weight:bold;color:#1B5E20">
</div>




<div class="form-actions">
    <button class="btn-guardar">Guardar</button>
    <button type="button" class="btn-volver" onclick="location.href='index.php'">Volver</button>
</div>



</form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function(){


document.querySelectorAll(".hamburger-btn").forEach(btn=>{
    btn.addEventListener("click",(e)=>{
        e.stopPropagation();

        const box = btn.nextElementSibling;

        document.querySelectorAll(".hamburger-options").forEach(o=>{
            if(o!==box) o.style.display="none";
        });

        box.style.display = box.style.display==="block" ? "none" : "block";
    });
});

document.querySelectorAll(".hamburger-options div").forEach(opt=>{
    opt.addEventListener("click",(e)=>{
        e.stopPropagation();

        const wrap = opt.closest(".hamburger-select");
        if(!wrap) return;

        const btn = wrap.querySelector(".hamburger-btn");
        const input = wrap.querySelector("input");

        if(btn) btn.textContent = opt.textContent;
        if(input) input.value = opt.dataset.value;

        wrap.querySelector(".hamburger-options").style.display="none";

        generarCodigoPreview();
    });
});

document.addEventListener("click",()=>{
    document.querySelectorAll(".hamburger-options")
        .forEach(o=>o.style.display="none");
});




function generarCodigoPreview(){

    const campo = document.getElementById("codigoPreview");
    const proveedorInput = document.querySelector("input[name='codigo_proveedor']");
    const fechaInput = document.querySelector("input[name='fecha']");

    if(!campo || !proveedorInput || !fechaInput) return;

    const proveedor = proveedorInput.value.trim();
    const fecha = fechaInput.value;

    if(!proveedor || !fecha){
        campo.value = "";
        return;
    }

    const fechaCod = fecha.replace(/-/g,"");
    campo.value = proveedor + "-" + fechaCod + "-1";
}




const proveedorInput = document.getElementById("proveedorInput");
const lista = document.getElementById("listaProveedores");
const codigoProveedor = document.getElementById("codigoProveedor");

let resultados = [];
let indice = -1;
let debounceTimer = null;

if(proveedorInput){

proveedorInput.addEventListener("keyup", function(e){

    const teclasControl = ["ArrowDown","ArrowUp","Enter"];

    if(teclasControl.includes(e.key)){
        manejarTeclado(e);
        return;
    }

    clearTimeout(debounceTimer);

    debounceTimer = setTimeout(()=>{
        buscarProveedor(this.value);
    },300);

});

}


function buscarProveedor(texto){

    if(texto.length < 1){
        lista.style.display="none";
        return;
    }

    fetch("buscar_proveedor.php?q=" + encodeURIComponent(texto))
    .then(res => res.json())
    .then(data => {

        resultados = data;
        indice = -1;

        lista.innerHTML="";

        if(data.length === 0){
            lista.style.display="none";
            return;
        }

        data.forEach((p,i)=>{

            let item = document.createElement("div");

            item.textContent = p.codigo_proveedor + " — " + p.proveedor;

            item.style.padding="8px";
            item.style.cursor="pointer";

            item.onclick = ()=>seleccionarProveedor(i);

            lista.appendChild(item);

        });

        lista.style.display="block";

    });

}


function manejarTeclado(e){

    if(resultados.length === 0) return;

    if(e.key === "ArrowDown"){
        indice++;
        if(indice >= resultados.length) indice = 0;
        resaltar();
    }

    if(e.key === "ArrowUp"){
        indice--;
        if(indice < 0) indice = resultados.length - 1;
        resaltar();
    }

    if(e.key === "Enter"){
        if(indice >= 0){
            seleccionarProveedor(indice);
        }
    }

}


function resaltar(){

    const items = lista.querySelectorAll("div");

    items.forEach((item,i)=>{
        item.style.background = i === indice ? "#e8f5e9" : "white";
    });

}


function seleccionarProveedor(i){

    const p = resultados[i];

    proveedorInput.value = p.proveedor;
    codigoProveedor.value = p.codigo_proveedor;

    lista.style.display="none";

    generarCodigoPreview();
}

});
</script>



