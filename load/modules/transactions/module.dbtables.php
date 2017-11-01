<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
$arTables = array
(
	'bill_bills' => array
	(
		'id' => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY', 
		'number' => 'tinytext NOT NULL', 
		'sign' => 'text NOT NULL', 
		'algorithm' => 'tinytext NOT NULL', 
		'denomination' => 'decimal(20,'.CENT_ACCURACY.') NOT NULL', 
		'timestamp' => 'tinytext NOT NULL',
		'table_rows' => array (),
	),
	'intentions' => array
	(
		'id' => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY', 
		'goal' => 'tinytext NOT NULL', 
		'pubkey' => 'tinytext NOT NULL', 
		'intention' => 'tinytext NOT NULL', 
	),
	'contracts' => array
	(
		'id' => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY', 
		'contract_number' => 'tinytext NOT NULL', 
		'entity' => 'tinytext NOT NULL', 
	),
	'transactions_pool' => array
	(
		'id' => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY', 
		'number' => 'tinytext NOT NULL', 
		'type' => 'tinytext NOT NULL', 
		'entity' => 'text NOT NULL', 
	),
);

// тестовые банкноты для блоков. К моменту завершения разработки, банкноты сменятся на реальные
array_push ($arTables['bill_bills']['table_rows'], ['pbulSaQT6Twl0Tsk','972246e51324d48c88cbf9761826d2ea232c3f62acf0b24dfa0c8977fb673477','ar','20000.00000000','1505685817']);// KMk1HUvrt5Qg0CqfUjVNG7J09ToKsqTOj5z0ufhoS4DkJBDGe3RYibuEc2klFT6M
array_push ($arTables['bill_bills']['table_rows'], ['pOkQad5UzSwAaIKW','ba19aedcaccc6f7f5dce699dcb01ab689255c3321ad2614f875e17bab34a98ab','ar','20000.00000000','1505685817']);// DyT5I6Qqcwn5Y1yi2lG9WQ85zvjt8j1QE6OVKq1djRNrmlCW8TpLlRn41zCM2QX6
array_push ($arTables['bill_bills']['table_rows'], ['pQeJ6yhniKFs4E6j','bbec3b55b64748c0de1d2e9b89eee47469624c9afff021503a3e87c742d5fe49','ar','20000.00000000','1505685817']);// 94Zbg09hvhBXWmdYVV9RSZ4nHtW6lbrs6kmUDF98HvUP3QGfylR4GIeAlOJAeZ0z
array_push ($arTables['bill_bills']['table_rows'], ['pr84iY9GAnXJ6akt','190136af33845a0c67d29330f4a948c8edf0d111fa3fa24b1c814dad2b2ba8c2','ar','20000.00000000','1505685817']);// MmAvYhb8iM49VxuyGFb9IuKKj7eMfiIPqXw7lAz1yS5wjojMlRr0cg4dTtOYfVwh
array_push ($arTables['bill_bills']['table_rows'], ['pfhOfMKes36xNTJh','5938db7b50d47c05ff82ccbe9aaf5075a7d52776e70b9787c741a2503926f8c5','ar','20000.00000000','1505685817']);// FGZRSWpbRj5uOJD06VbfMxCUZZkNE1DZ5tvcuuvOHSRlOBLZqEOBemW94DQ0Ojg5
array_push ($arTables['bill_bills']['table_rows'], ['pya13BVsA2fybgrf','fa1ffb64bfb0c55c055773ea79ca02642c2b4876396e2e6af127230467beae62','ar','20000.00000000','1505685817']);// uJI13heZxgsEIQDqq5PYmv8uwng8d3qZPeh5O0b0IZ7M0h4Glo13QD9DbBe04pjf
array_push ($arTables['bill_bills']['table_rows'], ['p0MhMW71AQnAsXwz','d3200a31205368ac0832ae70967b5e609f79a143ddf6c8053794ec7e00b18ffd','ar','20000.00000000','1505685817']);// Uh6dgHVOtrJsb3MJfokuEuTFt90ZFxyvAMXH4eN6gQJH7LutCkqRnhllMop4FBEk
array_push ($arTables['bill_bills']['table_rows'], ['pW13cbnAyn1iEigN','3ef7239ec8122177fc1dab4b940b29a2ffd27c544ef542c863b37be3ee040b8e','ar','20000.00000000','1505685817']);// rykPm006SpEk1srhATSIZQU3Ep9aEb90JVrYf4HdhPCsFkK6UM3ODOIDf90kLPT9
array_push ($arTables['bill_bills']['table_rows'], ['p06QK2K8My8OqkBL','15817704dd0000330a6ad599ab4ca61f4302275955497e7782d534cb085e57fe','ar','20000.00000000','1505685817']);// Dn9z6wE5hEXzldZFuadbjviI5jo7XBr5jMs4EQQaaJ0gfvO6atVfFxNZG1R6MjtS
?>