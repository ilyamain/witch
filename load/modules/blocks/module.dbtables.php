<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта

// Загрузка распределенных данных
$arTables = array();
$arTemp = bc_data();
if (!empty($arTemp['bc_blocks'])) $arTables['bc_blocks'] = $arTemp['bc_blocks'];
return $arTables;
?>