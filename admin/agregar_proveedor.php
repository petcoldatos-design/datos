<?php
session_start();
require_once("../db/conexion.php");
require_once("../vendor/autoload.php");
require_once(__DIR__ . '/excel_guardar.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;


function guardarEnExcelProveedores($data) {

    $ruta = __DIR__ . "/../excel/Control_Total_Planta.xlsx";

    if (!file_exists($ruta)) {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle("PROVEEDORES");
        $writer = new Xlsx($spreadsheet);
        $writer->save($ruta);
    }

    $spreadsheet = IOFactory::load($ruta);
    $sheet = $spreadsheet->getSheetByName("PROVEEDORES");

    if (!$sheet) {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle("PROVEEDORES");
    }

    if ($sheet->getHighestRow() == 1 && $sheet->getCell('A1')->getValue() == null) {
        $sheet->fromArray([
            "Fecha",
            "Codigo",
            "Nombre",
            "Tipo Proveedor",
            "Producto 1",
            "Producto 2",
            "Producto 3",
            "Residuo",
            "Municipio",
            "Procedencia Tipo",
            "Tipo Material",
            "Tipo Resina",
            "Historial",
            "Observaciones"
        ], null, 'A1');
    }

    $fila = $sheet->getHighestRow() + 1;

    $sheet->fromArray([
        $data['fecha'],
        $data['codigo'],
        $data['nombre'],
        $data['tipo_proveedor'],
        $data['producto'],
        $data['producto_dos'],
        $data['producto_tres'],
        $data['residuo'],
        $data['municipio'],
        $data['procedencia_tipo'],
        $data['tipo_material'],
        $data['tipo_resina'],
        $data['historial'],
        $data['observaciones']
    ], null, "A{$fila}");

    $writer = new Xlsx($spreadsheet);
    $writer->save($ruta);
}



if (!isset($_SESSION['tipo'])) {
    header('Location: ../login/index.php');
    exit;
}

$rol = strtolower(trim($_SESSION['tipo']));

if (!in_array($rol, ['admin', 'inventario'], true)) {
    die('Rol no autorizado');
}

$id_paquete = 47;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    function clean($v){
        return trim(strip_tags($v ?? ''));
    }

    $fecha            = clean($_POST['fecha']);
    $codigo_proveedor = clean($_POST['codigo_proveedor']);
    $nombre_proveedor = clean($_POST['nombre_proveedor']);

    $tipo_proveedor = ($_POST['tipo_proveedor'] === 'Otro')
        ? clean($_POST['tipo_proveedor_otro'])
        : clean($_POST['tipo_proveedor']);

    $producto = ($_POST['tipo_producto'] === 'Otro')
        ? clean($_POST['tipo_producto_otro'])
        : clean($_POST['tipo_producto']);

    $producto_dos = !empty($_POST['tipo_producto_dos'])
        ? (($_POST['tipo_producto_dos'] === 'Otro')
            ? clean($_POST['tipo_producto_dos_otro'])
            : clean($_POST['tipo_producto_dos']))
        : "";

    $producto_tres = !empty($_POST['tipo_producto_tres'])
        ? (($_POST['tipo_producto_tres'] === 'Otro')
            ? clean($_POST['tipo_producto_tres_otro'])
            : clean($_POST['tipo_producto_tres']))
        : "";

    $residuo          = clean($_POST['tipo_residuo']);
    $procedencia      = clean($_POST['procedencia']);
    $procedencia_tipo = clean($_POST['procedencia_tipo']);

    $tipo_material = ($_POST['material'] === 'Otro')
        ? clean($_POST['material_otro'])
        : clean($_POST['material']);

    $tipo_resina = ($_POST['tipo_resina'] === 'Otro')
        ? clean($_POST['tipo_resina_otro'])
        : clean($_POST['tipo_resina']);

    $historial = ($_POST['historial'] === 'Otro')
        ? clean($_POST['historial_otro'])
        : clean($_POST['historial']);

    $observaciones = clean($_POST['observaciones']);

    $conexion->begin_transaction();

    try {

        
        $stmt = $conexion->prepare("
            INSERT INTO proveedores
            (
                nombre_proveedor,
                producto,
                residuo,
                procedencia,
                procedencia_tipo,
                tipo_material,
                tipo_resina,
                historial,
                observaciones,
                codigo_proveedor,
                fecha
            )
            VALUES (?,?,?,?,?,?,?,?,?,?,?)
        ");

        $stmt->bind_param(
            "sssssssssss",
            $nombre_proveedor,
            $producto,
            $residuo,
            $procedencia,
            $procedencia_tipo,
            $tipo_material,
            $tipo_resina,
            $historial,
            $observaciones,
            $codigo_proveedor,
            $fecha
        );

        $stmt->execute();
        $id_proveedor = $conexion->insert_id;
        $stmt->close();

       
        $datos = [
            "fecha" => $fecha,
            "nombre_proveedor" => $nombre_proveedor,
            "codigo_proveedor" => $codigo_proveedor
        ];

        $json = json_encode($datos, JSON_UNESCAPED_UNICODE);

        $stmt2 = $conexion->prepare("
            INSERT INTO paquete_items (id_paquete, id_registro, datos)
            VALUES (?, ?, ?)
        ");

        $stmt2->bind_param("iis", $id_paquete, $id_proveedor, $json);
        $stmt2->execute();
        $id_proveedor = $conexion->insert_id;
        $stmt2->close();

$created_at = date("Y-m-d H:i:s");

guardarEnExcel("PROVEEDORES", [
    $id_proveedor,
    $nombre_proveedor,  
    $producto,          
    $residuo,           
    $procedencia,       
    $procedencia_tipo,  
    $tipo_material,     
    $tipo_resina,        
    $historial,         
    $observaciones,     
    $codigo_proveedor,  
    $fecha,             
    $created_at         
]);

        $conexion->commit();

        echo "<script>
            alert('✅ Proveedor registrado correctamente');
            location.href='index.php';
        </script>";
        exit;

    } catch (Exception $e) {

        $conexion->rollback();

        die("<script>
            alert('❌ ".$e->getMessage()."');
            history.back();
        </script>");
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registrar Proveedor</title>
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
    width:520px;
    margin:auto;
    padding:30px;
    border-radius:18px;
    box-shadow:0 5px 16px rgba(0,0,0,.15);
    border-left:6px solid #1B5E20;
}

h2{
    text-align:center;
    color:#1B5E20;
    margin-bottom:25px;
}

label{
    display:block;
    font-weight:bold;
    color:#1B5E20;
    margin-bottom:6px;
}

input, select, textarea{
    width:100%;
    height:46px;
    padding:10px 14px;
    border-radius:12px;
    border:1px solid #999;
    font-size:15px;
    box-sizing:border-box;
}

textarea{
    height:90px;
    resize:none;
}

select{
    appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%23666' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
    background-repeat:no-repeat;
    background-position:right 14px center;
    padding-right:38px;
}

.otro-input{
    margin-top:8px;
    display:none;
}


.productos-grid{
    display:grid;
    grid-template-columns:
    gap:12px;
}

.productos-grid select,
.productos-grid input{
    height:46px;
}

@media(max-width:700px){
    .productos-grid{
        grid-template-columns:1fr;
    }
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
    background:#1B5E20;
    color:white;
}

.btn-volver{
    background:#0d47a1;
    color:white;
    margin-top:10px;
}
</style>
</head>

<body>

<div class="form-box">
<h2>Registrar Proveedor</h2>

<form method="POST">

<label>Fecha</label>
<input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required>

<label>Código del proveedor</label>
<input name="codigo_proveedor" required>

<label>Nombre del proveedor</label>
<input name="nombre_proveedor" required>

<label>Tipo de proveedor</label>
<select name="tipo_proveedor" onchange="mostrarOtro(this,'tp_otro')" required>
<option value="">Seleccione</option>
<option>Gesto De Reciclaje</option>
<option>Cooperativa Re Reciclaje</option>
<option value="Otro">Productor</option>
<option value="Otro">Otro</option>
</select>
<input class="otro-input" id="tp_otro" name="tipo_proveedor_otro">

<label>Ciudad / Municipio</label>
<input name="procedencia" required>

<label>Tipos de producto</label>
<div class="productos-grid">

<div>
<select name="tipo_producto" onchange="mostrarOtro(this,'prod_otro')" required>
<option value="">Seleccione</option>
<option>Botellas</option>
<option>Empaques flexibles</option>
<option>Envases</option>
<option>Embalaje de alimentos</option>
<option value="Otro">Otros Articulos</option>
</select>
<input class="otro-input" id="prod_otro" name="tipo_producto_otro">
</div>

</div>

<label>Tipo de residuo</label>
<select name="tipo_residuo" required>
<option value="">Seleccione</option>
<option>Hogar</option>
<option>Industria</option>
<option>Comercio</option>
</select>

<label>Procedencia</label>
<select name="procedencia_tipo" required>
<option value="">Seleccione</option>
<option>Posconsumo</option>
<option>Preconsumo</option>
<option>Industrial</option>
</select>

<label>Tipo de material</label>
<select name="material" required>
<option value="">Seleccione</option>
<option>ACEITE</option>
<option>AMBAR</option>
<option>BOLSA DE AZUCAR</option>
<option>CARTÓN</option>
<option>CRISTAL BENEFICIADO</option>
<option>CRISTAL ETIQUETA</option>
<option>CRISTAL POSINDUSTRIAL</option>
<option>ESTIBAS</option>
<option>GLOBOS</option>
<option>HIT</option>
<option>LAMINA DE FLORES</option>
<option>LONAS</option>
<option>MATERIAL DE COLORES</option>
<option>PET ASEO</option>
<option>PET BLANCO</option>
<option>PET ETIQUETA PVC</option>
<option>PET MOLIDO AMBAR</option>
<option>PET MOLIDO COLORES</option>
<option>PET MOLIDO NARANJA</option>
<option>PET MOLIDO TRANSPARENTE</option>
<option>PET MOLIDO VERDE</option>
<option>PET NARANJA MOLIDO</option>
<option>PET ROSADO</option>
<option>PLASTICO POLICOLOR</option>
<option>PLASTICO TRANSPARENTE</option>
<option>POLIPROPILENO (FRITURA)</option>
<option>POLVILLO</option>
<option>POLVILLO LIMPIO</option>
<option>POLVILLO SUCIO</option>
<option>PP LIMPIO</option>
<option>PREFORMA CRISTAL</option>
<option>PREFORMA ROSADA</option>
<option>PREFORMA VERDE</option>
<option>R PET AMBAR</option>
<option>REVUELTO</option>
<option>SCREEN</option>
<option>SOPLADO</option>
<option>SOPLADO MOLIDO BLANCO</option>
<option>TAPA PLASTICA</option>
<option>TAPA PLÁSTICA</option>
<option>TORTA</option>
<option>VERDE</option>
<option>VERDE-AMBAR</option>
<option>ZUNCHO</option>
<option>ZUNCHO PLASTICO VERDE</option>
</select>


<label>Tipo de resina</label>
<select name="tipo_resina" onchange="mostrarOtro(this,'resina_otro')" required>
<option value="">Seleccione</option>
<option>PET</option>
<option>Polietileno de Alta (HDPE)</option>
<option>Polietileno de Baja (LDPE)</option>
<option>Polipropileno (PP)</option>
<option value="Otro">Otro</option>
</select>
<input class="otro-input" id="resina_otro" name="tipo_resina_otro">

<label>Historial de residuo</label>
<select name="historial" onchange="mostrarOtro(this,'his_otro')" required>
<option value="">Seleccione</option>
<option>De bebidas y refrescos</option>
<option>Empaque de alimentos</option>
<option>Empacado /EMBALAJE DE MERCANCIAS</option>
<option>Empaque De Productos De Aseo</option>
<option>Sustancias peligrosas</option>
<option value="Otro">Otro</option>
</select>
<input class="otro-input" id="his_otro" name="historial_otro">

<label>Observaciones</label>
<textarea name="observaciones"></textarea>

<button class="btn-guardar">Guardar</button>
<button type="button" class="btn-volver" onclick="location.href='index.php'">Volver</button>

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
