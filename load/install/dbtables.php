<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
$install_db = array 
(
	'db' => DB_NAME,
	'tables' => array 
	(
		'bill_in_wallet' => array 
		(
			'id' => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY', 
			'bill_number' => 'tinytext NOT NULL', 
			'bill_key' => 'tinytext NOT NULL', 
			'bill_pubkey' => 'tinytext NOT NULL', 
			'bill_sign' => 'text NOT NULL', 
			'bill_intention' => 'text NOT NULL', 
			'bill_algorithm' => 'tinytext NOT NULL', 
			'bill_denomination' => 'decimal(20,'.CENT_ACCURACY.') NOT NULL', 
			'bill_timestamp' => 'tinytext NOT NULL',
		),
		'local_core' => array 
		(
			'id' => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY', 
			'parameter' => 'tinytext NOT NULL', 
			'value' => 'text NOT NULL', 
			'table_rows' => array (),
		),
	),
);

// тестовые локальные данные. Сменятся после разработки админки CMS
array_push ($install_db['tables']['local_core']['table_rows'], ['foo','505221025f9701f8a05cc22cbafeec897598b2924a9d665cbc10f0073d66da20']);
array_push ($install_db['tables']['local_core']['table_rows'], ['bar','0e2787dc12b16df10d5ac03c6262b0a6dd7ab11941eb6658c32a6c5f9eb3ee12']);
?>