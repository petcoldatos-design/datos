<?php
session_start();


$tipo = strtolower($_SESSION['tipo'] ?? '');


if ($tipo !== 'admin' && $tipo !== 'inventario') {
    die("Acceso denegado");
}

require_once("../db/conexion.php");


if ($tipo === 'admin') {
    $ruta_retorno = "index.php";
} else {
    $ruta_retorno = "../inventario/ver_todo.php";
}


$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    die("ID inválido");
}


$stmt = $conexion->prepare("
    DELETE FROM despachos_produccion
    WHERE id_despacho = ?
    LIMIT 1
");

if ($stmt === false) {
    die("Error SQL: " . $conexion->error);
}

$stmt->bind_param("i", $id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $stmt->close();
    header("Location: " . $ruta_retorno . "?msg=eliminado");
    exit;
} else {
    $stmt->close();
    die("No se encontró el despacho para eliminar");
}
?>