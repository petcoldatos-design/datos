<?php
session_start();


$tipo = strtolower($_SESSION['tipo'] ?? '');


if ($tipo !== 'admin' && $tipo !== 'inventario') {
    die("Acceso denegado");
}

require_once("../db/conexion.php");


if ($tipo === 'admin') {
    $ruta_retorno = "/Beta/admin/index.php";
} else {
    $ruta_retorno = "/Beta/inventario/ver_todo.php";
}



$consulta = $conexion->query("SELECT * FROM inventario");

if (!$consulta) {
    die("Error al leer inventario: " . $conexion->error);
}

if ($consulta->num_rows === 0) {
    header("Location: " . $ruta_retorno . "?error=vacio");
    exit;
}


$stmt_paquete = $conexion->prepare("
    INSERT INTO paquetes (tipo, nombre_paquete, fecha_guardado)
    VALUES ('inventario', ?, NOW())
");

if ($stmt_paquete === false) {
    die("Error SQL paquete: " . $conexion->error);
}

$nombre_paquete = "Paquete Inventario " . date("Y-m-d H:i:s");
$stmt_paquete->bind_param("s", $nombre_paquete);
$stmt_paquete->execute();

$id_paquete = $stmt_paquete->insert_id;
$stmt_paquete->close();

if ($id_paquete <= 0) {
    die("No se pudo crear el paquete");
}


$stmt_item = $conexion->prepare("
    INSERT INTO paquete_items (id_paquete, id_registro, datos)
    VALUES (?, ?, ?)
");

if ($stmt_item === false) {
    die("Error SQL items: " . $conexion->error);
}

while ($row = $consulta->fetch_assoc()) {

    $id_registro = (int)$row['id'];

    $datos = [
        "hora" => $row["hora"],
        "placa" => $row["placa"],
        "proveedor" => $row["proveedor"],
        "codigo_proveedor" => $row["codigo_proveedor"],
        "remision" => $row["remision"],
        "procedencia" => $row["procedencia"],
        "tipo_material" => $row["tipo_material"],
        "color" => $row["color"],
        "presentacion" => $row["presentacion"],
        "procedencia_tipo" => $row["procedencia_tipo"],
        "tipo_producto" => $row["tipo_producto"],
        "tipo_residuo" => $row["tipo_residuo"],
        "historial" => $row["historial"],
        "peso" => $row["peso"],
        "codigo_paca" => $row["codigo_paca"],
        "fecha" => $row["fecha"],
        "tipo_resina" => $row["tipo_resina"] ?? null
    ];

    $json = json_encode($datos, JSON_UNESCAPED_UNICODE);

    $stmt_item->bind_param("iis", $id_paquete, $id_registro, $json);
    $stmt_item->execute();
}

$stmt_item->close();


if (!$conexion->query("DELETE FROM inventario")) {
    die("Error borrando inventario: " . $conexion->error);
}

header("Location: " . $ruta_retorno . "?ok=1&borrado=1");
exit;
?>
