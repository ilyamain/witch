<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта

// Загружаем устанавливаемую базу данных
$install_db_file = way(__DIR__.DS.'module.dbtables.php');
$install_db = (is_file($install_db_file)) ? require($install_db_file) : array();

$file_content = '<?'.PHP_EOL;
$file_content .= 'if (!defined(\'PROGRAM_NAME\')) die(); // Защита от прямого вызова скрипта'.PHP_EOL;
$file_content .= 'define(\'BLOCK_MAIN_STORE\', \'db\');'.PHP_EOL;
$file_content .= 'define(\'BLOCK_FILE_STORE\', \'file\');'.PHP_EOL;
$file_content .= 'define(\'BLOCK_FILE_DIR\', way(DR.DS.\'backup_blocks\'.DS));'.PHP_EOL;
$file_content .= 'define(\'BLOCK_TIME\', 600);'.PHP_EOL;
$file_content .= 'define(\'BLOCK_FREE\', 6);'.PHP_EOL;
$file_content .= 'define(\'GENESIS_ID\', 0);'.PHP_EOL;
$file_content .= 'define(\'GENESIS_TIME\', \'1512086400\');'.PHP_EOL;
$file_content .= 'define(\'GENESIS_ISSUE\', \'*i:["genesis","ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff","0.00000001"]\');'.PHP_EOL;
$file_content .= 'define(\'GENESIS_QUEST\', \'*q:["10","10","10","10","10","10","10","10","10","10"]\');'.PHP_EOL;
$file_content .= 'define(\'GENESIS_TRANSACTIONS\', \'>no:[]\');'.PHP_EOL;
$file_content .= 'define(\'GENESIS_INTENTIONS\', \'@no:[]\');'.PHP_EOL;
$file_content .= 'define(\'EASE_LENGTH\', 144);'.PHP_EOL;
$file_content .= 'define(\'EASE_CORRECTION\', 65535);'.PHP_EOL;
$file_content .= '// Сложность рассчитывается исходя из разности хэшей блока и эмиссии за последние сутки'.PHP_EOL;
$file_content .= 'define(\'ISSUE_CONST\', 8);'.PHP_EOL;
$file_content .= 'define(\'ISSUE_MULTIPLIER\', 48.914783);'.PHP_EOL;
$file_content .= 'define(\'ISSUE_REDUCTION\', 0.999996967991735);'.PHP_EOL;
$file_content .= '// В блоке 7.358.400 (приблизительно через 140 лет с момента запуска) будет в постоянной'.PHP_EOL;
$file_content .= '// и переменной составляющих в сумме эмитировано около 75.000.000 нумерат'.PHP_EOL;
$file_content .= 'define(\'QUEST_SUM\', 100);'.PHP_EOL;
$file_content .= 'define(\'QUEST_NUM\', 10);'.PHP_EOL;
$file_content .= 'define(\'QUEST_MIN\', 5);'.PHP_EOL;
$file_content .= 'define(\'QUEST_MAX\', 15);'.PHP_EOL;
$file_content .= '?>';
$base = new cBase;

if ($mode == 'install') 
{
	if (!is_file(way(__DIR__.DS.'module.config.php'))) 
	{
		write('Заполняем базу данных.', 5);
		$base->tables_create($install_db);
		write('Создаем файл конфигурации.', 5);
		file_put_contents(way(__DIR__.DS.'module.config.php'), $file_content);
	}
	else 
	{
		write('Файл конфигурации уже существует. Невозможно установить модуль.', 5, 'error');
	}
}

if ($mode == 'uninstall') 
{
	if (is_file(way(__DIR__.DS.'module.config.php'))) 
	{
		write('Освобождаем записи базы данных.', 5);
		$base->tables_del_list($install_db);
		write('Удаляем файл конфигурации.', 5);
		if (is_file(way(__DIR__.DS.'module.config.php'))) unlink(way(__DIR__.DS.'module.config.php'));
	}
	else 
	{
		write('Файл конфигурации отсутствует. Невозможно удалить модуль.', 5, 'error');
	}
}
?>