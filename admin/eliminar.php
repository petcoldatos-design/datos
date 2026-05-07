<?php
session_start();


if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    die("No tienes permisos para eliminar.");
}

require_once('../db/conexion.php');


$id = intval($_GET['id']);


$stmt = $conexion->prepare("DELETE FROM inventario WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: index.php");
    exit;
} else {
    $error = htmlspecialchars($stmt->error);
    $stmt->close();
    die("Error al eliminar: $error");
}
?>
