<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта

// Загрузка распределенных данных
$arTables = array();
$arTemp = bc_data();
if (!empty($arTemp['miners'])) $arTables['miners'] = $arTemp['miners'];
return $arTables;
?>