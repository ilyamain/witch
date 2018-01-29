<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта

// Загрузка распределенных данных
$arTables = array();
$arTemp = bc_data();
if (!empty($arTemp['bc_bills'])) $arTables['bc_bills'] = $arTemp['bc_bills'];
if (!empty($arTemp['bc_intentions'])) $arTables['bc_intentions'] = $arTemp['bc_intentions'];
if (!empty($arTemp['bc_contracts'])) $arTables['bc_contracts'] = $arTemp['bc_contracts'];
if (!empty($arTemp['bc_vendor_code'])) $arTables['bc_vendor_code'] = $arTemp['bc_vendor_code'];
if (!empty($arTemp['bc_orders'])) $arTables['bc_orders'] = $arTemp['bc_orders'];
if (!empty($arTemp['bc_pool'])) $arTables['bc_pool'] = $arTemp['bc_pool'];
return $arTables;
?>