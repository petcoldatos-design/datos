<?php
session_start();
if(!isset($_SESSION['tipo']) || $_SESSION['tipo']!=='admin'){
    die("Acceso denegado");
}

require_once("../db/conexion.php");


if(!isset($_POST['item_id'])) die("Proveedor no especificado");
$item_id = intval($_POST['item_id']);


$stmt = $conexion->prepare("SELECT id_paquete, id_registro FROM paquete_items WHERE id=?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows === 0) die("Item no encontrado");

$row = $result->fetch_assoc();
$id_paquete = $row['id_paquete'];
$id_registro = $row['id_registro'] ?? null;


$stmt2 = $conexion->prepare("DELETE FROM paquete_items WHERE id=?");
$stmt2->bind_param("i", $item_id);
$stmt2->execute();



echo "<script>
    alert('Proveedor eliminado del paquete');
    location.href='ver_paquete.php?id={$id_paquete}';
</script>";
exit;
