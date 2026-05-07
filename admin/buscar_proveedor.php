<?php
require_once("../db/conexion.php");

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');

if ($q === '') {
    echo json_encode([]);
    exit;
}

$stmt = $conexion->prepare("
    SELECT proveedor, codigo_proveedor
    FROM proveedores_base
    WHERE proveedor LIKE CONCAT('%', ?, '%')
       OR codigo_proveedor LIKE CONCAT('%', ?, '%')
    ORDER BY proveedor ASC
    LIMIT 10
");

if (!$stmt) {
    echo json_encode([]);
    exit;
}

$stmt->bind_param("ss", $q, $q);
$stmt->execute();

$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = [
        "proveedor" => $row["proveedor"],
        "codigo_proveedor" => $row["codigo_proveedor"]
    ];
}

$stmt->close();

echo json_encode($data);
