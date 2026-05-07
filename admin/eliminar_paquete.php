<?php
session_start();
require_once("../db/conexion.php");



$roles_permitidos = ['admin', 'inventario'];

if (empty($_SESSION['tipo']) || 
    !in_array(strtolower($_SESSION['tipo']), $roles_permitidos, true)) {
    die("Acceso denegado");
}



if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID inválido");
}

$id = (int) $_GET['id'];




$stmt = $conexion->prepare("DELETE FROM paquete_items WHERE id_paquete = ?");
if ($stmt === false) {
    die("❌ ERROR SQL PREPARE (paquete_items): " . $conexion->error);
}
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();


$stmt = $conexion->prepare("DELETE FROM paquetes WHERE id = ? LIMIT 1");
if ($stmt === false) {
    die("❌ ERROR SQL PREPARE (paquetes): " . $conexion->error);
}
$stmt->bind_param("i", $id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $stmt->close();
    header("Location: paquetes.php?msg=eliminado");
    exit;
} else {
    $stmt->close();
    die("⚠ No se encontró el paquete para eliminar");
}
?>
