<?php
session_start();


$tipo = strtolower($_SESSION['tipo'] ?? '');


if ($tipo !== 'admin' && $tipo !== 'inventario') {
    die("Acceso denegado");
}

require_once("../db/conexion.php");


if ($tipo === 'admin') {
    $ruta_guardado = "../admin/index.php";
    $ruta_volver   = "../admin/index.php";
} else {
    $ruta_guardado = "../inventario/ver_todo.php";
    $ruta_volver   = "../inventario/ver_todo.php";
}


if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}


$id = isset($_POST['id']) ? intval($_POST['id']) : intval($_GET['id'] ?? 0);

if ($id <= 0) {
    die("ID inválido");
}

$mensaje = "";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        die("CSRF inválido");
    }

    $puerto = intval($_POST['puerto'] ?? 0);
    $peso   = floatval($_POST['peso'] ?? 0);

    if (!in_array($puerto, [1, 2])) {
        die("Puerto inválido");
    }

    if ($peso <= 0) {
        die("Peso inválido");
    }

    $sql = "UPDATE inventario_proceso 
            SET puerto = ?, peso = ? 
            WHERE id = ? 
            LIMIT 1";

    $stmt = $conexion->prepare($sql);

    if ($stmt === false) {
        die("ERROR SQL UPDATE: " . $conexion->error);
    }

    $stmt->bind_param("idi", $puerto, $peso, $id);
    $stmt->execute();

    if ($stmt->error) {
        $mensaje = "Error al guardar: " . htmlspecialchars($stmt->error);
    } elseif ($stmt->affected_rows > 0) {

        header("Location: " . $ruta_guardado . "?msg=editado");
        exit;

    } else {
        $mensaje = "No hubo cambios en los datos";
    }

    $stmt->close();
}


$sql = "SELECT id, puerto, peso 
        FROM inventario_proceso 
        WHERE id = ? 
        LIMIT 1";

$stmt = $conexion->prepare($sql);

if ($stmt === false) {
    die("ERROR SQL SELECT: " . $conexion->error);
}

$stmt->bind_param("i", $id);
$stmt->execute();
$proceso = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$proceso) {
    die("Proceso no encontrado");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Proceso</title>
<style>
body{
    background:url("../admin/fondo.jpg") center/cover fixed;
    font-family:Arial;
    padding:40px;
}
.box{
    background:#fff;
    max-width:520px;
    margin:auto;
    padding:30px;
    border-radius:18px;
}
h2{ text-align:center; color:#1B5E20; }
label{ display:block; margin-top:15px; font-weight:bold; }
select,input{
    width:100%;
    padding:12px;
    border-radius:10px;
    border:1px solid #ccc;
}
.btn{
    width:100%;
    margin-top:20px;
    background:#1565C0;
    color:#fff;
    padding:14px;
    border:none;
    border-radius:12px;
    font-weight:bold;
    cursor:pointer;
    text-decoration:none;
    display:block;
    text-align:center;
}
.btn-volver{
    background:#555;
}
.mensaje{
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
<div class="box">
<h2>Editar Proceso</h2>

<?php if ($mensaje): ?>
<div class="mensaje"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<form method="POST">
<input type="hidden" name="id" value="<?= htmlspecialchars($proceso['id']) ?>">
<input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">

<label>Puerto</label>
<select name="puerto" required>
    <option value="1" <?= $proceso['puerto']==1?'selected':'' ?>>Puerto 1</option>
    <option value="2" <?= $proceso['puerto']==2?'selected':'' ?>>Puerto 2</option>
</select>

<label>Peso (kg)</label>
<input type="number" step="0.01" name="peso" value="<?= htmlspecialchars($proceso['peso']) ?>" required>

<button class="btn">Guardar cambios</button>
</form>

<a href="<?= $ruta_volver ?>" class="btn btn-volver">Volver</a>

</div>
</body>
</html>
