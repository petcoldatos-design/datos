<?php
session_start();

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    die("Acceso denegado");
}

require_once("../db/conexion.php");


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID inválido");
}

$id = (int) $_GET['id'];


$stmt = $conexion->prepare("DELETE FROM proveedores WHERE id = ? LIMIT 1");

if ($stmt === false) {
    die("❌ ERROR SQL PREPARE: " . $conexion->error);
}

$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $stmt->close();
        header("Location: index.php?msg=eliminado");
        exit;
    } else {
        $stmt->close();
        die("⚠ No se encontró el proveedor para eliminar");
    }
} else {
    $error = htmlspecialchars($stmt->error);
    $stmt->close();
    die("❌ Error al eliminar el proveedor: $error");
}
?>
