<?php

require_once(__DIR__ . '/excel_guardar.php');

$ruta = __DIR__ . '/../excel/Control_Total_Planta.xlsx';

if (!file_exists($ruta)) {
    crearEstructuraInicial($ruta);
}

if (ob_get_length()) ob_end_clean();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="Control_Total_Planta.xlsx"');
header('Cache-Control: max-age=0');

readfile($ruta);
exit;