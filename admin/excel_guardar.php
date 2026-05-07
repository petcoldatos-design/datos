<?php

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle;

use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;



function crearEstructuraInicial($ruta)
{

$spreadsheet = new Spreadsheet();

$hojas = [

"INVENTARIO" => [
"id","fecha","hora","placa","proveedor","codigo_proveedor",
"procedencia","tipo_material","tipo_resina","color",
"presentacion","procedencia_tipo","tipo_producto",
"tipo_residuo","historial","peso","remision",
"codigo_paca","creado_en"
],

"EN_PROCESO" => [
"id","hora","proveedor","codigo_proveedor",
"tipo_material","tipo_producto","peso",
"codigo_paca","puerto","fecha_inicio"
],

"PRODUCCION" => [
"id","fecha_produccion","linea","puerto","usuario",
"hora","presentacion","turno","lote","operador",
"tipo_producto","peso","observaciones","created_at"
],

"DESPACHO" => [
"id","fecha","cliente","remision","producto",
"presentacion","cantidad_kg","lote",
"despachado_por","conductor","observaciones","created_at"
],

"PROVEEDORES" => [
"id","nombre_proveedor","producto","residuo",
"procedencia","procedencia_tipo","tipo_material",
"tipo_resina","historial","observaciones",
"codigo_proveedor","fecha","creado_en"
],

"RESIDUOS_PRODUCCION" => [
"fecha","hora","residuo","origen","responsable",
"peso","observaciones","creado_en"
]

];


/* HOJA DASHBOARD */

$resumen = $spreadsheet->getActiveSheet();
$resumen->setTitle("RESUMEN");

$resumen->setCellValue('A1','DASHBOARD CONTROL PLANTA');

$resumen->setCellValue('A3','Inventario total');
$resumen->setCellValue('B3','=SUM(INVENTARIO!P:P)');

$resumen->setCellValue('A4','Producción total');
$resumen->setCellValue('B4','=SUM(PRODUCCION!L:L)');

$resumen->setCellValue('A5','Despachos');
$resumen->setCellValue('B5','=SUM(DESPACHO!G:G)');


$resumen->getStyle("A1:B5")->applyFromArray([
'font'=>['bold'=>true,'size'=>14],
'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER]
]);



/* GRAFICO 1 PRODUCCION */

$dataSeriesLabels = [
new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING,'RESUMEN!$A$4',null,1),
];

$xAxisTickValues = [
new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING,'RESUMEN!$A$3:$A$5',null,3),
];

$dataSeriesValues = [
new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER,'RESUMEN!$B$3:$B$5',null,3),
];

$series = new DataSeries(
DataSeries::TYPE_BARCHART,
DataSeries::GROUPING_CLUSTERED,
range(0,count($dataSeriesValues)-1),
$dataSeriesLabels,
$xAxisTickValues,
$dataSeriesValues
);

$plotArea = new PlotArea(null,[$series]);

$legend = new Legend(Legend::POSITION_RIGHT,null,false);

$title = new Title('Producción vs Inventario');

$chart = new Chart(
'chart1',
$title,
$legend,
$plotArea
);

$chart->setTopLeftPosition('D2');
$chart->setBottomRightPosition('L20');

$resumen->addChart($chart);



$index = 1;

foreach($hojas as $nombre=>$headers){

$sheet = $spreadsheet->createSheet();

$sheet->setTitle($nombre);

$sheet->fromArray($headers,null,'A1');

$lastColumn = $sheet->getHighestColumn();

$headerRange = "A1:{$lastColumn}1";

$sheet->getStyle($headerRange)->applyFromArray([

'font'=>[
'bold'=>true,
'color'=>['rgb'=>'FFFFFF']
],

'alignment'=>[
'horizontal'=>Alignment::HORIZONTAL_CENTER
],

'fill'=>[
'fillType'=>Fill::FILL_SOLID,
'startColor'=>['rgb'=>'0B8043']
],

'borders'=>[
'allBorders'=>[
'borderStyle'=>Border::BORDER_THIN
]
]

]);

$sheet->freezePane('A2');

foreach(range('A',$lastColumn) as $col){

$sheet->getColumnDimension($col)->setAutoSize(true);

}

$index++;

}

$writer = new Xlsx($spreadsheet);
$writer->setIncludeCharts(true);
$writer->save($ruta);

}




function guardarEnExcel($hoja,$datos)
{

$ruta = __DIR__."/../excel/Control_Total_Planta.xlsx";

if(!file_exists($ruta)){
crearEstructuraInicial($ruta);
}

$spreadsheet = IOFactory::load($ruta);

$sheet = $spreadsheet->getSheetByName(strtoupper($hoja));

if(!$sheet) return;

$fila = $sheet->getHighestRow()+1;

$sheet->fromArray($datos,null,"A{$fila}");

$lastColumn = $sheet->getHighestColumn();
$lastRow = $sheet->getHighestRow();

$dataRange = "A1:{$lastColumn}{$lastRow}";


for($i=2;$i<=$lastRow;$i++){

if($i%2==0){

$sheet->getStyle("A{$i}:{$lastColumn}{$i}")
->getFill()
->setFillType(Fill::FILL_SOLID)
->getStartColor()
->setRGB('E8F5E9');

}

}



$headers = $sheet->rangeToArray("A1:{$lastColumn}1")[0];

foreach($headers as $index=>$header){

$colLetter = Coordinate::stringFromColumnIndex($index+1);

if(in_array($header,['peso','cantidad_kg'])){

$sheet->getStyle("{$colLetter}2:{$colLetter}{$lastRow}")
->getNumberFormat()
->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

}

if(strpos($header,'fecha')!==false){

$sheet->getStyle("{$colLetter}2:{$colLetter}{$lastRow}")
->getNumberFormat()
->setFormatCode('yyyy-mm-dd');

}

}



$sheet->setAutoFilter($dataRange);


$sheet->getProtection()->setSheet(true);
$sheet->getProtection()->setSort(true);
$sheet->getProtection()->setAutoFilter(true);
$sheet->getProtection()->setPassword('empresa2025');


foreach(range('A',$lastColumn) as $col){
$sheet->getColumnDimension($col)->setAutoSize(true);
}


$writer = new Xlsx($spreadsheet);
$writer->setIncludeCharts(true);
$writer->save($ruta);

}