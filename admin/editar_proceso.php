<?php
session_start();

if (!isset($_SESSION['tipo']) || 
   ($_SESSION['tipo'] !== 'admin' && $_SESSION['tipo'] !== 'inventario')) {
    die("Acceso denegado");
}

require_once("../db/conexion.php");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function e($t){
    return htmlspecialchars($t ?? '', ENT_QUOTES, 'UTF-8');
}

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    die("ID inválido");
}
$id = (int)$_GET['id'];

$mensaje = null;

$stmt = $conexion->prepare("
    SELECT puerto, peso
    FROM inventario_proceso
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$proceso = $stmt->get_result()->fetch_assoc();

if (!$proceso) {
    die("Registro no encontrado");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $puerto = (int)($_POST['puerto'] ?? 0);
    $peso   = (float)($_POST['peso'] ?? 0);

    if (!in_array($puerto, [1,2])) {
        $mensaje = "Puerto inválido.";
    } elseif ($peso <= 0) {
        $mensaje = "El peso debe ser mayor a cero.";
    } else {
        try {
            $conexion->begin_transaction();

            $stmt = $conexion->prepare("
                UPDATE inventario_proceso
                SET puerto = ?, peso = ?
                WHERE id = ?
                LIMIT 1
            ");
            $stmt->bind_param("idi", $puerto, $peso, $id);
            $stmt->execute();

            if ($stmt->affected_rows === 0) {
                throw new Exception("No se realizaron cambios.");
            }

            $conexion->commit();

            if ($_SESSION['tipo'] === 'admin') {
                header("Location: index.php?msg=editado");
            } else {
                header("Location: ../inventario/ver_todo.php?msg=editado");
            }
            exit;

        } catch (Exception $e) {
            $conexion->rollback();
            $mensaje = $e->getMessage();
        }
    }
}

$volver = ($_SESSION['tipo'] === 'admin')
    ? "index.php"
    : "../inventario/ver_todo.php";
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Proceso</title>
<link rel="icon" href="../admin/plas.jpg">
<style>
body{
    background:url("../admin/fondo.jpg");
    background-size:cover;
    background-attachment:fixed;
    font-family:Arial, Helvetica, sans-serif;
    padding:40px 20px;
}
.form-box{
    background:#ffffffee;
    width:100%;
    max-width:600px;
    margin:auto;
    padding:34px;
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
input, select{
    width:100%;
    height:48px;
    padding:0 14px;
    border-radius:14px;
    border:1px solid #bdbdbd;
    font-size:15px;
    background:#fff;
    box-sizing:border-box;
}
input:focus, select:focus{
    outline:none;
    border-color:#1B5E20;
    box-shadow:0 0 0 2px rgba(27,94,32,.15);
}
select{
    appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%23666' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
    background-repeat:no-repeat;
    background-position:right 14px center;
    padding-right:38px;
}
.form-actions{
    width:100%;
    display:flex;
    flex-direction:column;
    gap:10px;
    margin-top:10px;
}
button{
    width:100%;
    height:50px;
    border:none;
    border-radius:14px;
    font-size:16px;
    font-weight:bold;
    cursor:pointer;
    transition:.25s;
}
.btn-guardar{background:#1565C0;color:white;}
.btn-volver{background:#1B5E20;color:white;}
.alert{
    background:#f8d7da;
    color:#842029;
    padding:12px;
    border-radius:10px;
    margin-bottom:15px;
    font-weight:bold;
    text-align:center;
}
</style>
</head>
<body>

<div class="form-box">
<h2>Editar Proceso</h2>

<?php if($mensaje): ?>
<div class="alert"><?= e($mensaje) ?></div>
<?php endif; ?>

<form method="POST">
    <div class="form-group">
        <label>Puerto</label>
        <select name="puerto" required>
            <option value="1" <?= $proceso['puerto']==1?'selected':'' ?>>Puerto 1</option>
            <option value="2" <?= $proceso['puerto']==2?'selected':'' ?>>Puerto 2</option>
        </select>
    </div>

    <div class="form-group">
        <label>Peso (kg)</label>
        <input type="number" step="0.01" name="peso" value="<?= e($proceso['peso']) ?>" required>
    </div>

    <div class="form-actions">
        <button class="btn-guardar">Guardar cambios</button>
        <button type="button" class="btn-volver" onclick="location.href='<?= $volver ?>'">Volver</button>
    </div>
</form>
</div>
</body>
</html>
