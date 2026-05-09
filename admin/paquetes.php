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
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Paquetes Guardados - PlastyPetco</title>
<link rel="icon" type="image/jpeg" href="plas.jpg?v=1">
<style>
    /* ===== RESET Y VARIABLES ===== */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    :root {
        --color-primary: #1B5E20;
        --color-primary-dark: #145A32;
        --color-secondary: #2E7D32;
        --color-azul: #1565C0;
        --color-azul-dark: #0D47A1;
        --color-peligro: #C62828;
        --color-peligro-dark: #8E0000;
        --color-texto: #ffffff;
        --color-texto-oscuro: #212529;
        --sombra: 0 10px 30px rgba(0, 0, 0, 0.15);
        --sombra-hover: 0 15px 35px rgba(0, 0, 0, 0.2);
        --border-radius: 20px;
    }

    body { 
        background: url("fondo.jpg") center/cover fixed no-repeat;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        padding: 40px 20px;
        min-height: 100vh;
    }

    /* ===== CONTENEDOR PRINCIPAL ===== */
    .box { 
        background: rgba(255, 255, 255, 0.97);
        backdrop-filter: blur(2px);
        padding: 35px;
        border-radius: var(--border-radius);
        width: 95%;
        max-width: 1300px;
        margin: 0 auto;
        border-left: 6px solid var(--color-primary);
        box-shadow: var(--sombra);
        transition: all 0.3s ease;
        animation: fadeIn 0.5s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* ===== TÍTULO PRINCIPAL ===== */
    .box h2 {
        color: var(--color-primary);
        font-size: 32px;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 3px solid var(--color-primary);
        display: inline-block;
        font-weight: 700;
    }

    /* ===== BOTÓN DESCARGAR EXCEL ===== */
    .box > a {
        display: inline-block;
        float: right;
        margin-top: 5px;
    }

    .box > a button {
        background: linear-gradient(135deg, var(--color-secondary), var(--color-primary));
        color: white;
        border: none;
        padding: 12px 28px;
        font-size: 15px;
        font-weight: 600;
        border-radius: 40px;
        cursor: pointer;
        transition: all 0.25s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        font-family: inherit;
    }

    .box > a button:hover {
        background: linear-gradient(135deg, #388E3C, var(--color-secondary));
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(0,0,0,0.2);
    }

    .box::after {
        content: "";
        display: table;
        clear: both;
    }

    /* ===== TÍTULOS DE SECCIÓN ===== */
    .section-title { 
        font-size: 24px;
        margin-top: 40px;
        margin-bottom: 20px;
        color: var(--color-primary);
        font-weight: 700;
        padding-left: 15px;
        border-left: 5px solid var(--color-secondary);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .section-title:first-of-type {
        margin-top: 10px;
    }

    /* ===== CAJA DE BÚSQUEDA ===== */
    .search-box {
        margin-bottom: 20px;
    }

    .search-box input { 
        width: 100%;
        max-width: 350px;
        padding: 12px 20px;
        border: 2px solid #e0e0e0;
        border-radius: 40px;
        font-size: 15px;
        transition: all 0.25s ease;
        background: white;
        font-family: inherit;
    }

    .search-box input:focus {
        outline: none;
        border-color: var(--color-secondary);
        box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1);
    }

    .search-box input::placeholder {
        color: #999;
        font-size: 14px;
    }

    /* ===== TABLAS ===== */
    .table-wrapper {
        overflow-x: auto;
        border-radius: 16px;
        margin-bottom: 10px;
    }

    table { 
        width: 100%; 
        border-collapse: collapse; 
        margin-top: 5px;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }

    th { 
        background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
        color: white; 
        padding: 14px 16px; 
        font-weight: 700;
        font-size: 15px;
        text-align: left;
    }

    td { 
        padding: 14px 16px; 
        border-bottom: 1px solid #e9ecef;
        background-color: white;
        color: var(--color-texto-oscuro);
        transition: background 0.2s ease;
        font-size: 14.5px;
    }

    tbody tr:hover td {
        background-color: #f8f9fa;
    }

    /* Columna ID */
    td:first-child {
        font-weight: 600;
        color: var(--color-primary);
        width: 80px;
    }

    /* Columna de acciones */
    .col-acciones {
        white-space: nowrap;
        text-align: center;
        width: 150px;
    }

    /* ===== BOTONES DE ACCIÓN ===== */
    .btn-ver { 
        background: linear-gradient(135deg, var(--color-azul), var(--color-azul-dark));
        padding: 7px 16px;
        color: white;
        border-radius: 25px;
        text-decoration: none;
        font-weight: 600;
        font-size: 13px;
        transition: all 0.2s ease;
        display: inline-block;
        margin-right: 8px;
    }

    .btn-ver:hover { 
        background: linear-gradient(135deg, #1E88E5, var(--color-azul));
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(21, 101, 192, 0.3);
    }

    .btn-eliminar { 
        background: linear-gradient(135deg, var(--color-peligro), var(--color-peligro-dark));
        padding: 7px 16px;
        color: white;
        border-radius: 25px;
        text-decoration: none;
        font-weight: 600;
        font-size: 13px;
        transition: all 0.2s ease;
        display: inline-block;
    }

    .btn-eliminar:hover { 
        background: linear-gradient(135deg, #E53935, var(--color-peligro));
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(198, 40, 40, 0.3);
    }

    /* ===== MENSAJE VACÍO ===== */
    .empty { 
        text-align: center; 
        padding: 35px; 
        color: #6c757d;
        font-style: italic;
        background: #f8f9fa;
        font-size: 16px;
    }

    /* ===== BOTÓN VOLVER ===== */
    .volver-container { 
        margin: 40px auto 20px;
        display: flex;
        justify-content: center;
    }

    .btn-volver { 
        padding: 14px 40px;
        background: linear-gradient(135deg, var(--color-secondary), var(--color-primary));
        color: white;
        text-decoration: none;
        font-size: 18px;
        font-weight: 700;
        border-radius: 50px;
        transition: all 0.25s ease;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }

    .btn-volver::before {
        content: "←";
        font-size: 20px;
        font-weight: bold;
    }

    .btn-volver:hover {
        background: linear-gradient(135deg, #388E3C, var(--color-secondary));
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.25);
    }

    .btn-volver:active {
        transform: translateY(0);
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
        body {
            padding: 20px 10px;
        }
        
        .box {
            padding: 20px;
            width: 100%;
        }
        
        .box h2 {
            font-size: 24px;
            display: block;
            text-align: center;
        }
        
        .box > a {
            float: none;
            display: block;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 20px;
            margin-top: 30px;
        }
        
        th, td {
            padding: 10px 12px;
        }
        
        .btn-ver, .btn-eliminar {
            padding: 5px 12px;
            font-size: 12px;
        }
        
        .btn-volver {
            padding: 12px 30px;
            font-size: 16px;
        }

        .col-acciones {
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: auto;
        }
        
        .btn-ver, .btn-eliminar {
            text-align: center;
            margin-right: 0;
        }
    }

    /* ===== SCROLLBAR PERSONALIZADA ===== */
    ::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }

    ::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb {
        background: var(--color-secondary);
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: var(--color-primary);
    }

    .table-wrapper::-webkit-scrollbar {
        height: 8px;
    }

    .table-wrapper::-webkit-scrollbar-track {
        background: #e8e8e8;
        border-radius: 10px;
    }

    .table-wrapper::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, var(--color-secondary), var(--color-primary));
        border-radius: 10px;
    }
</style>
<script>
function buscarEnTabla(idInput, idTabla) {
    let filter = document.getElementById(idInput).value.toLowerCase();
    let table = document.getElementById(idTabla);
    let rows = table.getElementsByTagName("tr");
    
    for (let i = 1; i < rows.length; i++) {
        let row = rows[i];
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? "" : "none";
    }
}

function confirmarEliminacion() { 
    return confirm("⚠️ ¿Seguro que deseas eliminar este paquete? Esta acción no se puede deshacer."); 
}
</script>
</head>
<body>

<div class="box">
<h2>📋 Paquetes Guardados</h2>

<a href="exportar_excel.php"><button>📎 Descargar Excel Completo</button></a>

<!-- Inventario -->
<h3 class="section-title">📦 Inventario</h3>
<div class="search-box">
    <input type="text" id="buscarInventario" placeholder="🔍 Buscar por ID o nombre..." onkeyup="buscarEnTabla('buscarInventario','tablaInventario')">
</div>
<div class="table-wrapper">
    <table id="tablaInventario">
        <thead>
            <tr><th>ID</th><th>Nombre</th><th>Acciones</th></tr>
        </thead>
        <tbody>
            <?php 
            if($inventario_sql->num_rows == 0): 
                echo "<tr><td colspan='3' class='empty'>📭 No hay registros</td></tr>";
            else:
                while($p = $inventario_sql->fetch_assoc()): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><?= htmlspecialchars($p['nombre_paquete']) ?></td>
                        <td class='col-acciones'>
                            <a class='btn-ver' href='ver_paquete.php?id=<?= $p['id'] ?>'>👁️ Ver</a>
                            <a class='btn-eliminar' href='eliminar_paquete.php?id=<?= $p['id'] ?>' onclick='return confirmarEliminacion()'>🗑️ Eliminar</a>
                        </td>
                    </tr>
            <?php 
                endwhile;
            endif;
            ?>
        </tbody>
    </table>
</div>

<!-- Proveedores -->
<h3 class="section-title">🏭 Proveedores</h3>
<div class="search-box">
    <input type="text" id="buscarProveedores" placeholder="🔍 Buscar por ID o nombre..." onkeyup="buscarEnTabla('buscarProveedores','tablaProveedores')">
</div>
<div class="table-wrapper">
    <table id="tablaProveedores">
        <thead>
            <tr><th>ID</th><th>Nombre</th><th>Acciones</th></tr>
        </thead>
        <tbody>
            <?php 
            if($proveedor_sql->num_rows == 0): 
                echo "<tr><td colspan='3' class='empty'>📭 No hay registros</td></tr>";
            else:
                while($p = $proveedor_sql->fetch_assoc()): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><?= htmlspecialchars($p['nombre_paquete']) ?></td>
                        <td class='col-acciones'>
                            <a class='btn-ver' href='ver_paquete.php?id=<?= $p['id'] ?>'>👁️ Ver</a>
                        </td>
                    </tr>
            <?php 
                endwhile;
            endif;
            ?>
        </tbody>
    </table>
</div>

<!-- En Proceso -->
<h3 class="section-title">⚙️ En Proceso</h3>
<div class="search-box">
    <input type="text" id="buscarProceso" placeholder="🔍 Buscar por ID o nombre..." onkeyup="buscarEnTabla('buscarProceso','tablaProceso')">
</div>
<div class="table-wrapper">
    <table id="tablaProceso">
        <thead>
            <tr><th>ID</th><th>Nombre</th><th>Acciones</th></tr>
        </thead>
        <tbody>
            <?php 
            if($proceso_sql->num_rows == 0): 
                echo "<tr><td colspan='3' class='empty'>📭 No hay registros</td></tr>";
            else:
                while($p = $proceso_sql->fetch_assoc()): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><?= htmlspecialchars($p['nombre_paquete']) ?></td>
                        <td class='col-acciones'>
                            <a class='btn-ver' href='ver_paquete.php?id=<?= $p['id'] ?>'>👁️ Ver</a>
                            <a class='btn-eliminar' href='eliminar_paquete.php?id=<?= $p['id'] ?>' onclick='return confirmarEliminacion()'>🗑️ Eliminar</a>
                        </td>
                    </tr>
            <?php 
                endwhile;
            endif;
            ?>
        </tbody>
    </table>
</div>

<!-- Producción -->
<h3 class="section-title">✅ Producción</h3>
<div class="search-box">
    <input type="text" id="buscarProduccion" placeholder="🔍 Buscar por ID o nombre..." onkeyup="buscarEnTabla('buscarProduccion','tablaProduccion')">
</div>
<div class="table-wrapper">
    <table id="tablaProduccion">
        <thead>
            <tr><th>ID</th><th>Nombre</th><th>Acciones</th></tr>
        </thead>
        <tbody>
            <?php 
            if($produccion_sql->num_rows == 0): 
                echo "<tr><td colspan='3' class='empty'>📭 No hay registros</td></tr>";
            else:
                while($p = $produccion_sql->fetch_assoc()): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><?= htmlspecialchars($p['nombre_paquete']) ?></td>
                        <td class='col-acciones'>
                            <a class='btn-ver' href='ver_paquete.php?id=<?= $p['id'] ?>'>👁️ Ver</a>
                            <a class='btn-eliminar' href='eliminar_paquete.php?id=<?= $p['id'] ?>' onclick='return confirmarEliminacion()'>🗑️ Eliminar</a>
                        </td>
                    </tr>
            <?php 
                endwhile;
            endif;
            ?>
        </tbody>
    </table>
</div>

<!-- Despachos -->
<h3 class="section-title">🚚 Despachos</h3>
<div class="search-box">
    <input type="text" id="buscarDespacho" placeholder="🔍 Buscar por ID o nombre..." onkeyup="buscarEnTabla('buscarDespacho','tablaDespacho')">
</div>
<div class="table-wrapper">
    <table id="tablaDespacho">
        <thead>
            <tr><th>ID</th><th>Nombre del paquete</th><th>Acciones</th></tr>
        </thead>
        <tbody>
            <?php 
            if($despacho_sql->num_rows == 0): 
                echo "<tr><td colspan='3' class='empty'>📭 No hay registros</td></tr>";
            else:
                while($p = $despacho_sql->fetch_assoc()): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><?= htmlspecialchars($p['nombre_paquete']) ?></td>
                        <td class='col-acciones'>
                            <a class='btn-ver' href='ver_paquete.php?id=<?= $p['id'] ?>'>👁️ Ver</a>
                            <a class='btn-eliminar' href='eliminar_paquete.php?id=<?= $p['id'] ?>' onclick='return confirmarEliminacion()'>🗑️ Eliminar</a>
                        </td>
                    </tr>
            <?php 
                endwhile;
            endif;
            ?>
        </tbody>
    </table>
</div>

</div>

<div class="volver-container">
<a class="btn-volver" href="<?= $ruta_volver ?>">Volver al Panel</a>
</div>

</body>
</html>