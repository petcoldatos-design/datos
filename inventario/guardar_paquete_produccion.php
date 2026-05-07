<?php
session_start();


$tipo = strtolower($_SESSION['tipo'] ?? '');


if ($tipo !== 'admin' && $tipo !== 'inventario') {
    die("Acceso denegado");
}

require_once("../db/conexion.php");


$ruta_retorno = $tipo === 'admin' ? "/Beta/admin/index.php" : "/Beta/inventario/ver_todo.php";


$consulta = $conexion->query("SELECT * FROM produccion");

if (!$consulta) {
    die("Error al leer producción: " . $conexion->error);
}

if ($consulta->num_rows === 0) {
    header("Location: " . $ruta_retorno . "?error=produccion_vacia");
    exit;
}


$stmt_paquete = $conexion->prepare("
    INSERT INTO paquetes (tipo, nombre_paquete, fecha_guardado)
    VALUES ('produccion', ?, NOW())
");

if (!$stmt_paquete) {
    die("Error SQL paquete: " . $conexion->error);
}

$nombre_paquete = "Paquete Producción " . date("Y-m-d H:i:s");
$stmt_paquete->bind_param("s", $nombre_paquete);
$stmt_paquete->execute();

$id_paquete = $stmt_paquete->insert_id;
$stmt_paquete->close();

if ($id_paquete <= 0) {
    die("No se pudo crear el paquete de producción");
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
        "fecha_inicio"  => $row["fecha_produccion"] ?? '',
        "linea"         => $row["linea"] ?? '',
        "tipo_producto" => (string)($row["tipo_producto"] ?? ''), 
        "presentacion"  => (string)($row["presentacion"] ?? ''),
        "turno"         => $row["turno"] ?? '',
        "lote"          => $row["lote"] ?? '',
        "numero"        => $row["numero"] ?? '',
        "peso"          => (float)($row["peso"] ?? 0),
        "observaciones" => (string)($row["observaciones"] ?? ''),
        "operador"      => (string)($row["operador"] ?? '')
    ];

    $json = json_encode($datos, JSON_UNESCAPED_UNICODE);

    $stmt_item->bind_param("iis", $id_paquete, $id_registro, $json);
    $stmt_item->execute();
}

$stmt_item->close();
$consulta->close();


if (!$conexion->query("DELETE FROM produccion")) {
    die("Error borrando producción: " . $conexion->error);
}


header("Location: " . $ruta_retorno . "?ok=produccion_guardada");
exit;
?>