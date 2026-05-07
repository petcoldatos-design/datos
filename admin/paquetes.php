<?php
session_start();

$tipo = strtolower($_SESSION['tipo'] ?? '');

if ($tipo !== 'admin' && $tipo !== 'inventario') {
    die("Acceso denegado");
}

require_once("../db/conexion.php");

if ($tipo === 'admin') {
    $ruta_volver = "/Beta/admin/index.php";
} else {
    $ruta_volver = "/Beta/inventario/ver_todo.php";
}

$conexion->query("
    DELETE FROM paquetes
    WHERE id != 47
      AND id NOT IN (
          SELECT DISTINCT id_paquete
          FROM paquete_items
      )
");

$inventario_sql = $conexion->query("SELECT * FROM paquetes WHERE tipo = 'inventario' ORDER BY id DESC");
$proveedor_sql  = $conexion->query("SELECT * FROM paquetes WHERE tipo = 'proveedores' ORDER BY id DESC");
$proceso_sql    = $conexion->query("SELECT * FROM paquetes WHERE tipo = 'proceso' ORDER BY id DESC");
$produccion_sql = $conexion->query("SELECT * FROM paquetes WHERE tipo = 'produccion' ORDER BY id DESC");
$despacho_sql   = $conexion->query("SELECT * FROM paquetes WHERE tipo = 'despacho' ORDER BY id DESC");

if (!$inventario_sql || !$proveedor_sql || !$proceso_sql || !$produccion_sql || !$despacho_sql) {
    die("Error SQL al listar paquetes: " . $conexion->error);
}

function imprimir_tabla($sql, $idTabla, $paginar = true) {
    $contador = 0;
    $pagina = 1;
    $maxPorPagina = 10;
    if ($sql->num_rows == 0) {
        echo "<tr><td colspan='3' class='empty'>No hay registros</td></tr>";
        return;
    }
    while ($p = $sql->fetch_assoc()) {
        $contador++;
        if ($paginar && $contador > $maxPorPagina) {
            $contador = 1;
            $pagina++;
            echo "</tbody></table>";
            echo "<p style='text-align:center;font-weight:bold;'>Página $pagina</p>";
            echo "<table><tr><th>ID</th><th>Nombre</th><th>Acciones</th></tr><tbody>";
        }
        echo "<tr>";
        echo "<td>{$p['id']}</td>";
        echo "<td>" . htmlspecialchars($p['nombre_paquete']) . "</td>";
        echo "<td>";
        echo "<a class='btn-ver' href='ver_paquete.php?id={$p['id']}'>Ver</a>";
        if($idTabla !== 'tablaProveedores') {
            echo "<a class='btn-eliminar' href='eliminar_paquete.php?id={$p['id']}' onclick='return confirmarEliminacion()'>Eliminar</a>";
        }
        echo "</td>";
        echo "</tr>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Paquetes Guardados</title>
<link rel="icon" type="image/jpeg" href="plas.jpg?v=1">
<style>
/* --- estilos previos --- */
body { background: url("fondo.jpg"); background-size: cover; margin: 0; padding: 0; }
.box { background:#ffffffef; padding:25px; border-radius:18px; width:90%; max-width:900px; margin:40px auto; border-left:6px solid #1B5E20; box-shadow:0 5px 16px rgba(0,0,0,0.12); }
table { width:100%; border-collapse:collapse; margin-top:15px; }
th { background:#1B5E20; color:white; padding:12px; }
td { padding:12px; border-bottom:1px solid #d1d5db; }
.btn-ver { background:#1565C0; padding:6px 12px; color:white; border-radius:6px; text-decoration:none; font-weight:bold; }
.btn-ver:hover { background:#0D47A1; }
.btn-eliminar { background:#C62828; padding:6px 12px; color:white; border-radius:6px; text-decoration:none; font-weight:bold; margin-left:8px; }
.btn-eliminar:hover { background:#8E0000; }
.btn-volver { padding:14px 30px; background:#2E7D32; color:white; text-decoration:none; font-size:20px; font-weight:bold; border-radius:12px; }
.volver-container { margin: 30px 0; display: flex; justify-content: center; }
.section-title { font-size:22px; margin-top:35px; color:#1B5E20; }
.empty { text-align:center; padding:10px; color:#666; }
.search-box input { width:100%; padding:10px; border:1px solid #777; border-radius:6px; font-size:16px; margin-bottom:15px; }
</style>
<script>
function buscarEnTabla(idInput, idTabla) {
    let filter = document.getElementById(idInput).value.toLowerCase();
    let rows = document.querySelectorAll(`#${idTabla} tr`);
    rows.forEach(row => { row.style.display = row.innerText.toLowerCase().includes(filter) ? "" : "none"; });
}
function confirmarEliminacion() { return confirm("¿Seguro que deseas eliminar este paquete?"); }
</script>
</head>
<body>

<div class="box">
<h2>Paquetes Guardados</h2>

<a href="exportar_excel.php"><button>Descargar Excel Completo</button></a>

<h3 class="section-title">Inventario</h3>
<input class="search-box" id="buscarInventario" placeholder="Buscar inventario" onkeyup="buscarEnTabla('buscarInventario','tablaInventario')">
<table id="tablaInventario"><tr><th>ID</th><th>Nombre</th><th>Acciones</th></tr><tbody>
<?php imprimir_tabla($inventario_sql, 'tablaInventario', true); ?>
</tbody></table>

<h3 class="section-title">Proveedores</h3>
<input class="search-box" id="buscarProveedores" placeholder="Buscar proveedores" onkeyup="buscarEnTabla('buscarProveedores','tablaProveedores')">
<table id="tablaProveedores"><tr><th>ID</th><th>Nombre</th><th>Acciones</th></tr><tbody>
<?php imprimir_tabla($proveedor_sql, 'tablaProveedores', false); ?>
</tbody></table>

<h3 class="section-title">En Proceso</h3>
<input class="search-box" id="buscarProceso" placeholder="Buscar en proceso" onkeyup="buscarEnTabla('buscarProceso','tablaProceso')">
<table id="tablaProceso"><tr><th>ID</th><th>Nombre</th><th>Acciones</th></tr><tbody>
<?php imprimir_tabla($proceso_sql, 'tablaProceso', true); ?>
</tbody></table>

<h3 class="section-title">Producción</h3>
<input class="search-box" id="buscarProduccion" placeholder="Buscar producción" onkeyup="buscarEnTabla('buscarProduccion','tablaProduccion')">
<table id="tablaProduccion"><tr><th>ID</th><th>Nombre</th><th>Acciones</th></tr><tbody>
<?php imprimir_tabla($produccion_sql, 'tablaProduccion', true); ?>
</tbody></table>

<h3 class="section-title">Despachos</h3>
<input class="search-box" id="buscarDespacho" placeholder="Buscar despacho" onkeyup="buscarEnTabla('buscarDespacho','tablaDespacho')">
<table id="tablaDespacho"><tr><th>ID</th><th>Nombre del paquete</th><th>Acciones</th></tr><tbody>
<?php imprimir_tabla($despacho_sql, 'tablaDespacho', true); ?>
</tbody></table>

</div>

<div class="volver-container">
<a class="btn-volver" href="<?= $ruta_volver ?>">Volver</a>
</div>

</body>
</html>