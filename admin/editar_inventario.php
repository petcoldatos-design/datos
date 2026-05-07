<?php
session_start();

if (
    !isset($_SESSION['tipo']) ||
    ($_SESSION['tipo'] !== 'admin' && $_SESSION['tipo'] !== 'inventario')
) {
    header("HTTP/1.1 403 Forbidden");
    exit("Acceso denegado");
}

if ($_SESSION['tipo'] === 'admin') {
    $ruta_volver = "../admin/index.php";
} else {
    $ruta_volver = "http://localhost/Beta/inventario/ver_todo.php";
}

require_once("../db/conexion.php");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conexion->set_charset("utf8");

function e($t){
    return htmlspecialchars($t ?? '', ENT_QUOTES, 'UTF-8');
}

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    exit("ID inválido");
}
$id = (int)$_GET['id'];

$stmt = $conexion->prepare("SELECT * FROM inventario WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$inv = $stmt->get_result()->fetch_assoc();

if (!$inv) {
    exit("Registro no encontrado");
}

$mensaje = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fecha            = trim($_POST['fecha'] ?? '');
    $hora             = trim($_POST['hora'] ?? '');
    $placa            = trim($_POST['placa'] ?? '');
    $proveedor        = trim($_POST['proveedor'] ?? '');
    $codigo_proveedor = trim($_POST['codigo_proveedor'] ?? '');
    $procedencia      = trim($_POST['procedencia'] ?? '');
    $tipo_material    = trim($_POST['tipo_material'] ?? '');
    $tipo_resina      = trim($_POST['tipo_resina'] ?? '');
    $color            = trim($_POST['color'] ?? '');
    $presentacion     = trim($_POST['presentacion'] ?? '');
    $procedencia_tipo = trim($_POST['procedencia_tipo'] ?? '');
    $tipo_producto    = trim($_POST['tipo_producto'] ?? '');
    $tipo_residuo     = trim($_POST['tipo_residuo'] ?? '');
    $historial        = trim($_POST['historial'] ?? '');
    $peso             = (float)($_POST['peso'] ?? 0);
    $remision         = trim($_POST['remision'] ?? '');
    $codigo_paca      = trim($_POST['codigo_paca_manual'] ?? '');

    if ($fecha === '' || $hora === '' || $proveedor === '') {
        $mensaje = "Fecha, hora y proveedor son obligatorios.";
    } elseif ($peso <= 0) {
        $mensaje = "El peso debe ser mayor a cero.";
    } else {

        if ($codigo_paca === '') {
            $fecha_cod = date('Ymd', strtotime($fecha));
            $base = $codigo_proveedor . '-' . $fecha_cod;
            $i = 1;
            do {
                $consecutivo = str_pad($i, 2, '0', STR_PAD_LEFT);
                $codigo_paca = $base . '-' . $consecutivo;
                $check = $conexion->prepare("SELECT 1 FROM inventario WHERE codigo_paca = ? AND id != ? LIMIT 1");
                $check->bind_param("si", $codigo_paca, $id);
                $check->execute();
                $existe = $check->get_result()->num_rows > 0;
                $check->close();
                $i++;
            } while ($existe);
        }

        try {
            $conexion->begin_transaction();

            $stmt2 = $conexion->prepare("
                UPDATE inventario SET
                    fecha=?, hora=?, placa=?, proveedor=?, codigo_proveedor=?, procedencia=?,
                    tipo_material=?, tipo_resina=?, color=?, presentacion=?, procedencia_tipo=?,
                    tipo_producto=?, tipo_residuo=?, historial=?, peso=?, remision=?, codigo_paca=?
                WHERE id=? LIMIT 1
            ");

$stmt2->bind_param(
    "ssssssssssssssdssi",
    $fecha,
    $hora,
    $placa,
    $proveedor,
    $codigo_proveedor,
    $procedencia,
    $tipo_material,
    $tipo_resina,
    $color,
    $presentacion,
    $procedencia_tipo,
    $tipo_producto,
    $tipo_residuo,
    $historial,
    $peso,
    $remision,
    $codigo_paca,
    $id
);


            $stmt2->execute();

            if ($stmt2->affected_rows === 0) {
                throw new Exception("No se realizaron cambios.");
            }

            $conexion->commit();
            header("Location: " . $ruta_volver . "?msg=editado");
            exit;

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
<title>Editar Inventario</title>
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
.hamburger-select{position:relative;}
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
.btn-guardar{background:#1B5E20;color:white;}
.btn-volver{background:#0d47a1;color:white;}
.alert{
    background:#f8d7da;
    color:#842029;
    padding:10px;
    border-radius:10px;
    margin-bottom:15px;
    font-weight:bold;
    text-align:center;
}
</style>
<body>
<div class="form-box">
<h2>Editar Inventario</h2>

<?php if($mensaje): ?>
<div class="alert"><?= e($mensaje) ?></div>
<?php endif; ?>

<form method="POST">
<div class="form-group">
<label>Fecha</label>
<input type="date" name="fecha" value="<?= e(substr($inv['fecha'],0,10)) ?>" required>
</div>
<div class="form-group">
<label>Hora</label>
<input type="time" name="hora" value="<?= e($inv['hora']) ?>" required>
</div>
<div class="form-group">
<label>Código del Proveedor</label>
<input name="codigo_proveedor" value="<?= e($inv['codigo_proveedor']) ?>" required>
</div>
<div class="form-group">
<label>Nombre del Proveedor</label>
<input name="proveedor" value="<?= e($inv['proveedor']) ?>" required>
</div>
<div class="form-group">
<label>Placa - Vehículo</label>
<input name="placa" value="<?= e($inv['placa']) ?>" required>
</div>
<div class="form-group">
<label>Ciudad / Municipio</label>
<input name="procedencia" value="<?= e($inv['procedencia']) ?>" required>
</div>


<?php
$hamburger_fields = [
    'tipo_material'=>'Tipo De Material',
    'tipo_resina'=>'Tipo De Resina',
    'color'=>'Color',
    'presentacion'=>'Presentación',
    'procedencia_tipo'=>'Procedencia',
    'tipo_producto'=>'Tipo De Producto',
    'tipo_residuo'=>'Tipo De Residuo',
    'historial'=>'Historial Del Residuo'
];
foreach($hamburger_fields as $name=>$label):
?>
<div class="form-group hamburger-select">
<label><?= $label ?></label>
<input type="hidden" name="<?= $name ?>" required value="<?= e($inv[$name]) ?>">
<div class="hamburger-btn"><?= e($inv[$name] ?: 'Seleccione') ?></div>
<div class="hamburger-options"></div>
</div>
<?php endforeach; ?>

<div class="form-group">
<label>Peso (Kg)</label>
<input type="number" step="0.01" name="peso" value="<?= e($inv['peso']) ?>" required>
</div>
<div class="form-group">
<label>Remisión</label>
<input name="remision" value="<?= e($inv['remision']) ?>" required>
</div>
<div class="form-group">
<label>Codigo De Paca (Automático)</label>
<input id="codigoPreview" name="codigo_paca_manual" value="<?= e($inv['codigo_paca']) ?>" style="background:#f1f8e9;font-weight:bold;color:#1B5E20">
</div>

<div class="form-actions">
<button class="btn-guardar">Actualizar</button>
<button type="button" class="btn-volver" onclick="location.href='<?= $ruta_volver ?>'">Volver</button>
</div>
</form>
</div>

<script>

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

const opciones = {
    "tipo_material":["ACEITE","AMBAR","BOLSA DE AZUCAR","CARTÓN","CRISTAL BENEFICIADO","CRISTAL ETIQUETA","CRISTAL POSINDUSTRIAL","ESTIBAS","GLOBOS","HIT","LAMINA DE FLORES","LONAS","MATERIAL DE COLORES","PET ASEO","PET BLANCO","PET ETIQUETA PVC","PET MOLIDO AMBAR","PET MOLIDO COLORES","PET MOLIDO NARANJA","PET MOLIDO TRANSPARENTE","PET MOLIDO VERDE","PET NARANJA MOLIDO","PET ROSADO","PLASTICO POLICOLOR","PLASTICO TRANSPARENTE","POLIPROPILENO (FRITURA)","POLVILLO","POLVILLO LIMPIO","POLVILLO SUCIO","PP LIMPIO","PREFORMA CRISTAL","PREFORMA ROSADA","PREFORMA VERDE","R PET AMBAR","REVUELTO","SCREEN","SOPLADO","SOPLADO MOLIDO BLANCO","TAPA PLASTICA","TAPA PLÁSTICA","TORTA","VERDE","VERDE-AMBAR","ZUNCHO","ZUNCHO PLASTICO VERDE"],
    "tipo_resina":["PET","Polietileno De Alta (HDPE)","Polietileno De Baja (LDPE)","Polipropileno (PP)","Otro"],
    "color":["Transparente","Verde","Ámbar","Azul","Policolor"],
    "presentacion":["Compactado","Suelto","Globo","Otro"],
    "procedencia_tipo":["Posconsumo","Preconsumo","Industrial"],
    "tipo_producto":["Botellas","Empaques Flexibles","Envases","Embalaje Alimentos","Otros"],
    "tipo_residuo":["Hogar","Industria","Comercio"],
    "historial":["Bebidas y refrescos","Empaque alimentos","Empaque / Embalaje","Empacado / Embalaje","Sustancias peligrosas","Otros"]
};

for(let key in opciones){
    const wrap = document.querySelector("input[name='"+key+"']").closest(".hamburger-select");
    const box = wrap.querySelector(".hamburger-options");
    opciones[key].forEach(val=>{
        const div = document.createElement("div");
        div.dataset.value = val;
        div.textContent = val;
        box.appendChild(div);
        div.addEventListener("click",()=>{
            wrap.querySelector("input").value = val;
            wrap.querySelector(".hamburger-btn").textContent = val;
            box.style.display="none";
            generarCodigoPreview();
        });
    });
}

document.addEventListener("click",()=>{document.querySelectorAll(".hamburger-options").forEach(o=>o.style.display="none");});


function generarCodigoPreview(){
    const campo = document.getElementById("codigoPreview");
    if(campo.value.trim() !== '') return;
    const proveedor = document.querySelector("input[name='codigo_proveedor']").value.trim();
    const fecha = document.querySelector("input[name='fecha']").value;
    if(!proveedor || !fecha){campo.value="";return;}
    const fechaCod = fecha.replace(/-/g,"");
    campo.value = proveedor+"-"+fechaCod+"-01";
}


document.querySelector("input[name='codigo_proveedor']")
.addEventListener("blur",function(){
    const codigo = this.value.trim();
    if(codigo==="") return;
    fetch(location.href,{
        method:"POST",
        headers:{ "Content-Type":"application/x-www-form-urlencoded" },
        body:"ajax=proveedor&codigo="+encodeURIComponent(codigo)
    })
    .then(r=>r.json())
    .then(d=>{
        if(!d || Object.keys(d).length===0){alert("Proveedor no encontrado");return;}
        document.querySelector("input[name='proveedor']").value = d.nombre_proveedor ?? "";
        document.querySelector("input[name='procedencia']").value = d.procedencia ?? "";
        for(let key in d){
            setHamburger(key,d[key]);
        }
        generarCodigoPreview();
    });
});

function setHamburger(name,value){
    if(!value) return;
    const wrap = document.querySelector("input[name='"+name+"']").closest(".hamburger-select");
    wrap.querySelector("input").value = value;
    wrap.querySelector(".hamburger-btn").textContent = value;
}
</script>
</body>
</html>
