<?php
session_start();


$tipo = strtolower($_SESSION['tipo'] ?? '');


if ($tipo !== 'admin' && $tipo !== 'inventario') {
    die("Acceso denegado");
}

require_once("../db/conexion.php");


$ruta_retorno = $tipo === 'admin' 
    ? "/Beta/admin/index.php" 
    : "/Beta/inventario/ver_todo.php";


$consulta = $conexion->query("SELECT * FROM inventario_proceso");

if (!$consulta) {
    die("Error al leer procesos: " . $conexion->error);
}

if ($consulta->num_rows === 0) {
    header("Location: " . $ruta_retorno . "?error=proceso_vacio");
    exit;
}

$nombre_paquete = "Paquete Proceso " . date("Y-m-d H:i:s");

$stmt_paquete = $conexion->prepare("
    INSERT INTO paquetes (tipo, nombre_paquete, fecha_guardado)
    VALUES ('proceso', ?, NOW())
");

if (!$stmt_paquete) {
    die("Error SQL paquete: " . $conexion->error);
}

$stmt_paquete->bind_param("s", $nombre_paquete);
$stmt_paquete->execute();

$id_paquete = $stmt_paquete->insert_id;
$stmt_paquete->close();

if ($id_paquete <= 0) {
    die("No se pudo crear el paquete de proceso");
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
        "hora" => $row["hora"],
        "placa" => $row["placa"],
        "proveedor" => $row["proveedor"],
        "codigo_proveedor" => $row["codigo_proveedor"],
        "tipo_material" => $row["tipo_material"],
        "tipo_producto" => $row["tipo_producto"],
        "peso" => $row["peso"],
        "codigo_paca" => $row["codigo_paca"],
        "puerto" => $row["puerto"],
        "estado" => "En proceso",
        "fecha_inicio" => $row["fecha_inicio"]
    ];

    $json = json_encode($datos, JSON_UNESCAPED_UNICODE);

    $stmt_item->bind_param("iis", $id_paquete, $id_registro, $json);
    $stmt_item->execute();
}

$stmt_item->close();
$consulta->close();


if (!$conexion->query("DELETE FROM inventario_proceso")) {
    die("Error borrando inventario_proceso: " . $conexion->error);
}

header("Location: " . $ruta_retorno . "?ok=proceso_guardado");
exit;
?>