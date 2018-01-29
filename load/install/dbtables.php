<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта

// Создание базы данных
$arTables = array
(
	'db' => DB_NAME,
	'tables' => array
	(
		'wallet' => array
		(
			'id' => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY', 
			'bill_number' => 'tinytext NOT NULL', 
			'bill_key' => 'tinytext NOT NULL', 
			'busy' => 'BOOLEAN NOT NULL', 
		),
		'wallet_stack' => array
		(
			'id' => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY', 
			'bill_number' => 'tinytext NOT NULL', 
			'bill_key' => 'tinytext NOT NULL', 
			'timestamp' => 'tinytext NOT NULL', 
		),
		'actions' => array
		(
			'type' => 'tinytext NOT NULL', 
			'entity' => 'text NOT NULL', 
			'executed' => 'BOOLEAN NOT NULL', 
		),
		'constants' => array
		(
			'id' => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY', 
			'name' => 'tinytext NOT NULL', 
			'parameter' => 'tinytext NOT NULL', 
			'value' => 'text NOT NULL', 
			'table_rows' => array(),
		),
	),
);

// Загрузка локальных данных
array_push ($arTables['tables']['constants']['table_rows'], ['Miner name','miner_name',abra(10)]);
array_push ($arTables['tables']['constants']['table_rows'], ['Miner type','miner_type','curl']);
array_push ($arTables['tables']['constants']['table_rows'], ['Miner link','miner_link','']);
array_push ($arTables['tables']['constants']['table_rows'], ['Optimal hash score','hash_score','255']);
array_push ($arTables['tables']['constants']['table_rows'], ['Maximum simultaneous remote connections','connections','15']);
array_push ($arTables['tables']['constants']['table_rows'], ['Get passive information from network. Enable only if you have white IP','white_ip','0']);
array_push ($arTables['tables']['constants']['table_rows'], ['Ban other miner on this rate level','ban_miner','20']);

// Загрузка распределенных данных
foreach (bc_data() as $key => $table) $arTables['tables'][$key] = $table;

// Выдача результата
return $arTables;
?>