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



$consulta = $conexion->query("SELECT * FROM despachos_produccion");

if (!$consulta) {
    die("Error al leer despachos: " . $conexion->error);
}

if ($consulta->num_rows === 0) {
    header("Location: " . $ruta_retorno . "?error=vacio_despachos");
    exit;
}


$stmt_paquete = $conexion->prepare("
    INSERT INTO paquetes (tipo, nombre_paquete, fecha_guardado)
    VALUES ('despacho', ?, NOW())
");

if (!$stmt_paquete) {
    die("Error SQL paquete: " . $conexion->error);
}

$nombre_paquete = "Paquete Despachos " . date("Y-m-d H:i:s");
$stmt_paquete->bind_param("s", $nombre_paquete);
$stmt_paquete->execute();

$id_paquete = $stmt_paquete->insert_id;
$stmt_paquete->close();

if ($id_paquete <= 0) {
    die("No se pudo crear el paquete de despachos");
}


$stmt_item = $conexion->prepare("
    INSERT INTO paquete_items (id_paquete, id_registro, datos)
    VALUES (?, ?, ?)
");

if (!$stmt_item) {
    die("Error SQL items: " . $conexion->error);
}

while ($row = $consulta->fetch_assoc()) {

    $id_registro = (int)$row['id'];

    $datos = [
        "fecha" => $row["fecha"],
        "cliente" => $row["cliente"],
        "remision" => $row["remision"],
        "producto" => $row["producto"],
        "presentacion" => $row["presentacion"],
        "cantidad_kg" => $row["cantidad_kg"],
        "lote" => $row["lote"],
        "despachado_por" => $row["despachado_por"],
        "conductor" => $row["conductor"],
        "observaciones" => $row["observaciones"]
    ];

    $json = json_encode($datos, JSON_UNESCAPED_UNICODE);

    $stmt_item->bind_param("iis", $id_paquete, $id_registro, $json);
    $stmt_item->execute();
}

$stmt_item->close();
$consulta->close();

if (!$conexion->query("DELETE FROM despachos_produccion")) {
    die("Error borrando despachos: " . $conexion->error);
}


header("Location: " . $ruta_retorno . "?ok=1&borrado_despachos=1");
exit;
?>
