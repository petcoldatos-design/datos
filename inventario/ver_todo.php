<?php
session_start();
require_once('../db/conexion.php');


if (!isset($_SESSION['tipo'])) {
    header('Location: ../login/index.php');
    exit;
}


$tipo_usuario = strtolower(trim($_SESSION['tipo']));


$roles_permitidos = ['admin', 'inventario'];
if (!in_array($tipo_usuario, $roles_permitidos, true)) {
    die('Acceso denegado');
}


$display_name = htmlspecialchars(
    $_SESSION['usuario_nombre'] ?? ucfirst($tipo_usuario),
    ENT_QUOTES,
    'UTF-8'
);
?>

<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Panel de Tablas | Plasty Petco</title>
<link rel="icon" href="plas.jpg">

<style>
body{
    margin:0;
    background: url("/beta/admin/fondo.jpg") center/cover fixed no-repeat;
}

/* TITULO PRINCIPAL */
h1{
    text-align:center;
    margin:25px 0;
    color:#ffffff;
    text-shadow:0 2px 6px rgba(0,0,0,0.6);
}

.bienvenida{
    text-align:center;
    margin-bottom:30px;
    font-size:18px;
    font-weight:bold;
    color:#fff;
}

.table-box{
    background:rgba(20,40,25,.45);
    backdrop-filter:blur(12px);
    border-radius:26px;
    padding:36px;
    width:94%;
    max-width:1850px;
    margin:45px auto;
    border:1px solid rgba(255,255,255,.25);
    color:#fff;
}

.search-box{text-align:right;margin-bottom:20px;}

.search-box input{
    padding:12px 18px;
    font-size:16px;
    width:320px;
    border-radius:16px;
    border:1px solid rgba(255,255,255,.4);
    background:rgba(255,255,255,.65);
    color:#111;
}

.table-container{
    overflow-x:auto;
    border-radius:18px;
}

table{
    width:100%;
    min-width:1500px;
    border-collapse:separate;
    border-spacing:0;
    font-size:15px;
}

th{
    background:rgba(27,94,32,.95);
    padding:18px;
    white-space:nowrap;
}

td{
    background:#ffffff;
    color:#000;
    padding:18px;
    line-height:1.4;
    font-size:15.5px;
    border-bottom:1px solid #e0e0e0;
    white-space:nowrap;
    max-width:300px;
    overflow:hidden;
    text-overflow:ellipsis;
}

tbody tr:hover td{
    background:#f5f5f5;
}

.col-acciones{text-align:center;}

.btn-accion{
    padding:7px 16px;
    font-size:14px;
    font-weight:600;
    color:#fff;
    text-decoration:none;
    border-radius:18px;
}

.btn-editar{background:#2E7D32;}
.btn-eliminar{background:#C62828;}

.barra-botones{
    display:flex;
    justify-content:center;
    gap:25px;
    margin-top:30px;
    flex-wrap:wrap;
}

.btn-principal{
    padding:16px 46px;
    border-radius:34px;
    font-weight:700;
    font-size:15.5px;
    text-decoration:none;
    color:#ffffff;
    background:linear-gradient(135deg, #1565C0, #0D47A1);
    box-shadow:0 6px 18px rgba(0,0,0,.35);
    transition:all .25s ease;
}

.btn-principal:hover{
    background:linear-gradient(135deg, #1E88E5, #1565C0);
    transform:translateY(-2px);
    box-shadow:0 10px 24px rgba(0,0,0,.45);
}

.btn-principal:active{
    transform:translateY(0);
    box-shadow:0 4px 12px rgba(0,0,0,.4);
}

.btn-volver{
    padding:12px 28px;
    border-radius:24px;
    font-weight:700;
    font-size:15px;
    text-decoration:none;
    color:#fff;
    background:#145A32;
    transition:all .25s ease;
}

.btn-volver:hover{
    background:#1B5E20;
    transform:translateY(-2px);
}
</style>

</head>
<body>

<h1>Panel de Tablas</h1>
<p class="bienvenida">Bienvenido, <strong><?= $display_name ?></strong></p>

<div class="barra-botones">

<a href="/Beta/inventario/index.php" class="btn-volver">
 Volver a Inventario
</a>

<a href="/Beta/admin/paquetes.php" class="btn-principal">
 📦 Ver Paquetes
</a>

</div>
<div class="table-box">
<h2>Inventario</h2>

<div class="search-box">
    <input id="buscarInv" onkeyup="buscarInventario()" placeholder="Buscar inventario">
</div>

<div class="table-container">
<table>
<thead>
<tr>
    <th>Hora</th>
    <th>Placa</th>
    <th>Proveedor</th>
    <th>Código</th>
    <th>Remisión</th>
    <th>Procedencia</th>
    <th>Material</th>
    <th>Color</th>
    <th>Presentación</th>
    <th>Tipo Proc.</th>
    <th>Producto</th>
    <th>Residuo</th>
    <th>Historial</th>
    <th>Peso</th>
    <th>Paca</th>
    <th>Fecha</th>
    <th>Resina</th>
    <th>Acciones</th>
</tr>
</thead>

<tbody id="tablaInv">
<?php
$r = $conexion->query("SELECT * FROM inventario ORDER BY fecha DESC");

if ($r && $r->num_rows > 0) {
    while ($f = $r->fetch_assoc()) {

        
        $fecha_valida = ($f['fecha'] && $f['fecha'] !== '0000-00-00') ? $f['fecha'] : date('Y-m-d');
        $fecha = date('d/m/Y', strtotime($fecha_valida));

        $resina = $f['tipo_resina'] ?? '';

        echo "
        <tr>
 <td>{$f['hora']}</td>
    <td>{$f['placa']}</td>
    <td>{$f['proveedor']}</td>
    <td>{$f['codigo_proveedor']}</td>
    <td>{$f['remision']}</td>
    <td>{$f['procedencia']}</td>
    <td>{$f['tipo_material']}</td>
    <td>{$f['color']}</td>
    <td>{$f['presentacion']}</td>
    <td>{$f['procedencia_tipo']}</td>
    <td>{$f['tipo_producto']}</td>
    <td>{$f['tipo_residuo']}</td>
    <td>{$f['historial']}</td>
    <td>{$f['peso']}</td>
    <td>{$f['codigo_paca']}</td>
    <td>{$fecha}</td>
    <td>{$resina}</td>
    <td class='col-acciones'>
        <a class='btn-accion btn-editar'href='/Beta/admin/editar_inventario.php?id={$f['id']}' >Editar</a>
        <a class='btn-accion btn-eliminar'
         href='/Beta/admin/eliminar_inventario.php?id={$f['id']}'
           onclick=\"return confirm('¿Eliminar este inventario?')\">
           Eliminar
        </a>
    </td>
</tr>";
    }
} else {
    echo "
    <tr>
        <td colspan='18' style='text-align:center;font-weight:700;padding:25px;'>
            📭 No hay inventario registrado
        </td>
    </tr>";
}
?>
</tbody>
</table>
</div>

<?php
$hayInventario = ($conexion->query("SELECT id FROM inventario LIMIT 1")->num_rows > 0);
?>

<div class="barra-botones">
    <form action="guardar_paquete_inventario.php" method="POST"
          onsubmit="return validarInventario();">
        <button type="submit" class="btn-principal">
            💾 Guardar Inventario
        </button>
    </form>
</div>

<script>
function validarInventario(){
    <?php if(!$hayInventario): ?>
        alert("❌ No hay inventario para guardar");
        return false;
    <?php endif; ?>
    return true;
}
</script>

</div>

</div>
</div>




<div class="table-box">
<h2>En Proceso</h2>


<div class="search-box">
<input id="buscarProceso" onkeyup="buscarProceso()" placeholder="Buscar proceso">
</div>

<div class="table-container">
<table>
<thead>
<tr>
<th>Hora</th><th>Proveedor</th><th>Código</th>
<th>Producto</th><th>Peso</th><th>Paca</th>
<th>Puerto</th><th>Estado</th><th>Fecha inicio</th><th>Acciones</th>
</tr>
</thead>

<tbody id="tablaProceso">
<?php
$proc = $conexion->query("SELECT * FROM inventario_proceso ORDER BY fecha_inicio DESC");

if ($proc->num_rows > 0) {
    while ($p = $proc->fetch_assoc()) {
        echo "<tr>
        <td>{$p['hora']}</td>

        <td>{$p['proveedor']}</td>
        <td>{$p['codigo_proveedor']}</td>
        <td>{$p['tipo_material']}</td>
        <td>{$p['peso']}</td>
        <td>{$p['codigo_paca']}</td>
        <td>Puerto {$p['puerto']}</td>
        <td><span class='estado-proceso'>En proceso</span></td>
        <td>".date('d/m/Y H:i', strtotime($p['fecha_inicio']))."</td>
        <td class='col-acciones'>
            <a class='btn-accion btn-editar' href='/Beta/admin/editar_proceso.php?id={$p['id']}'>Editar</a>
            <a class='btn-accion btn-eliminar'
              href='/Beta/admin/eliminar_proceso.php?id={$p['id']}'
               onclick=\"return confirm('¿Eliminar este proceso?\')\">Eliminar</a>
        </td>
        </tr>";
    }
} else {
    echo "<tr>
        <td colspan='11' style='text-align:center;font-weight:700;padding:25px;'>
            ⏳ No hay procesos en curso
        </td>
    </tr>";
}
?>

</tbody>
</table>
<?php
$hayProceso = ($conexion->query("SELECT id FROM inventario_proceso LIMIT 1")->num_rows > 0);
?>
<div class="barra-botones">
    <form action="guardar_paquete_proceso.php" method="POST"
          onsubmit="return validarProceso();">
        <button type="submit" class="btn-principal">
            💾 Guardar Proceso
        </button>
    </form>
</div>


<script>
function validarProceso(){
    <?php if(!$hayProceso): ?>
        alert("❌ No hay procesos para guardar");
        return false;
    <?php endif; ?>
    return true;
}

</script>

</div>
</div>



<div class="table-box">
<h2>Producción</h2>


<div class="search-box">
<input id="buscarProduccion" onkeyup="buscarProduccion()" placeholder="Buscar producción">
</div>

<div class="table-container">
<table>
<thead>
<tr>
<th>Fecha y hora</th>
<th>Línea</th>
<th>Producto</th>
<th>Presentación</th>
<th>Turno</th>
<th>Lote</th>
<th>Peso (kg)</th>
<th>Operador</th>
<th>Observaciones</th>
<th>Acciones</th>
</tr>
</thead>



<tbody id="tablaProduccion">
<?php
$prod = $conexion->query("SELECT * FROM produccion ORDER BY fecha_produccion DESC, hora DESC");

if ($prod->num_rows > 0) {
    while ($p = $prod->fetch_assoc()) {
        echo "<tr>
            <td>".date('d/m/Y H:i', strtotime($p['fecha_produccion'].' '.$p['hora']))."</td>
            <td>Puerto {$p['puerto']}</td>
            <td>{$p['tipo_producto']}</td>
            <td>{$p['presentacion']}</td>
            <td>{$p['turno']}</td>
            <td>{$p['lote']}</td>
            <td>{$p['peso']}</td>
            <td>{$p['operador']}</td>
            <td>{$p['observaciones']}</td>
            <td class='col-acciones'>
                <a class='btn-accion btn-editar'href='/Beta/admin/editar_produccion.php?id={$p['id']}' >Editar</a>
                <a class='btn-accion btn-eliminar' href='/Beta/admin/eliminar_produccion.php?id={$p['id']}' onclick=\"return confirm('¿Eliminar este registro de producción?')\">Eliminar</a>
            </td>
        </tr>";
    }
} else {
    echo "<tr>
        <td colspan='10' style='text-align:center;font-weight:700;padding:25px;'>
            🏭 No hay producción registrada
        </td>
    </tr>";
}
?>
</tbody>

</tbody>
</table>
<?php
$hayProduccion = ($conexion->query("SELECT id FROM produccion LIMIT 1")->num_rows > 0);
?>

<div class="barra-botones">
    <form action="guardar_paquete_produccion.php" method="POST"
          onsubmit="return validarProduccion();">
        <button type="submit" class="btn-principal">
            💾 Guardar Producción
        </button>
    </form>
</div>

<script>
function validarProduccion(){
    <?php if(!$hayProduccion): ?>
        alert("❌ No hay producción para guardar");
        return false;
    <?php endif; ?>
    return true;
}
</script>

</div>
</div>


<script>
function buscarProduccion() {
    let input = document.getElementById("buscarProduccion").value.toLowerCase();
    let filas = document.querySelectorAll("#tablaProduccion tr");

    filas.forEach(fila => {
        let texto = fila.innerText.toLowerCase();
        fila.style.display = texto.includes(input) ? "" : "none";
    });
}
</script>

<div class="table-box">
<h2>Despachos</h2>

<div class="search-box">
<input id="buscarDespacho" onkeyup="buscarDespacho()" placeholder="Buscar despacho">
</div>

<div class="table-container">
<table>
<thead>
<tr>
<th>Fecha</th>
<th>Cliente</th>
<th>Remisión</th>
<th>Producto</th>
<th>Presentación</th>
<th>Cantidad (KG)</th>
<th>Lote</th>
<th>Despachado por</th>
<th>Conductor</th>
<th>Observaciones</th>
<th>Acciones</th>
</tr>
</thead>

<tbody id="tablaDespacho">
<?php
$despachos = $conexion->query("SELECT * FROM despachos_produccion ORDER BY fecha DESC");

if ($despachos->num_rows > 0) {
    while ($d = $despachos->fetch_assoc()) {
        echo "<tr>
            <td>".date('d/m/Y', strtotime($d['fecha']))."</td>
            <td>{$d['cliente']}</td>
            <td>{$d['remision']}</td>
            <td>{$d['producto']}</td>
            <td>{$d['presentacion']}</td>
            <td>{$d['cantidad_kg']}</td>
            <td>{$d['lote']}</td>
            <td>{$d['despachado_por']}</td>
            <td>{$d['conductor']}</td>
            <td>{$d['observaciones']}</td>
            <td class='col-acciones'>
 <a class='btn-accion btn-editar' href='/Beta/admin/editar_produccion.php?id={$p['id']}'>Editar</a>
<a class='btn-accion btn-eliminar'href='/Beta/admin/eliminar_despacho.php?id={$d['id_despacho']}'>Eliminar</a>
            </td>
        </tr>";
    }
} else {
    echo "<tr>
        <td colspan='11' style='text-align:center;font-weight:700;padding:25px;'>
            📭 No hay despachos registrados
        </td>
    </tr>";
}

$hayDespachos = ($despachos->num_rows > 0);
?>
</tbody>
</table>
</div> 

<div class="barra-botones">
    <form action="guardar_paquete_despacho.php" method="POST" onsubmit="return validarDespachos();">
        <button type="submit" class="btn-principal">
            💾 Guardar Despachos
        </button>
    </form>
</div>

<script>
function validarDespachos(){
    <?php if(!$hayDespachos): ?>
        alert("❌ No hay despachos para guardar");
        return false;
    <?php endif; ?>
    return true;
}
</script>




</tbody>
</table>
</div>
</div>


<script>
function buscarInventario() {
    let input = document.getElementById("buscarInv").value.toLowerCase();
    document.querySelectorAll("#tablaInv tr").forEach(fila => {
        fila.style.display = fila.innerText.toLowerCase().includes(input) ? "" : "none";
    });
}

function buscarProceso() {
    let input = document.getElementById("buscarProceso").value.toLowerCase();
    document.querySelectorAll("#tablaProceso tr").forEach(fila => {
        fila.style.display = fila.innerText.toLowerCase().includes(input) ? "" : "none";
    });
}

function buscarProduccion() {
    let input = document.getElementById("buscarProduccion").value.toLowerCase();
    document.querySelectorAll("#tablaProduccion tr").forEach(fila => {
        fila.style.display = fila.innerText.toLowerCase().includes(input) ? "" : "none";
    });
}

function buscarDespacho() {
    let input = document.getElementById("buscarDespacho").value.toLowerCase();
    document.querySelectorAll("#tablaDespacho tr").forEach(fila => {
        fila.style.display = fila.innerText.toLowerCase().includes(input) ? "" : "none";
    });

}
</script>
</body>
</html>
