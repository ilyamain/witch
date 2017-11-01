<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Загружаем устанавливаемую базу данных
if (is_file(way(__DIR__.DS.'module.dbtables.php'))) require_once(way(__DIR__.DS.'module.dbtables.php'));

$file_content = '<?'.PHP_EOL;
$file_content .= 'if (!defined(\'PROGRAM_NAME\')) die(); // Защита от прямого вызова скрипта'.PHP_EOL;
$file_content .= 'define(\'BLOCKS_DIR\', way(__DIR__.DS.\'blocks\'.DS));'.PHP_EOL;
$file_content .= 'define(\'ISSUE_MULTIPLIER\', 30.230920611119);'.PHP_EOL;
$file_content .= 'define(\'REDUCTION\', 0.999994066784307);'.PHP_EOL;
$file_content .= 'define(\'ISSUE_CONST\', 19);'.PHP_EOL;
$file_content .= '// В блоке 3.679.200 (приблизительно через 140 лет с момента запуска) будет в постоянной'.PHP_EOL;
$file_content .= '// и переменной составляющих в сумме эмитировано около 75.000.000 у.е.'.PHP_EOL;
$file_content .= 'define(\'BLOCK_TIME\', 1200);'.PHP_EOL;
$file_content .= 'define(\'SUM_QUEST\', 100);'.PHP_EOL;
$file_content .= 'define(\'NUM_QUEST\', 10);'.PHP_EOL;
$file_content .= 'define(\'MIN_QUEST\', 5);'.PHP_EOL;
$file_content .= 'define(\'MAX_QUEST\', 15);'.PHP_EOL;
$file_content .= '?>';
$base = new cBase;

if ($mode=='install') 
{
	if (!is_file(way(__DIR__.DS.'module.config.php'))) 
	{
		console_line('Заполняем базу данных.', 5);
		$base->add_tables($arTables);
		console_line('Создаем файл конфигурации.', 5);
		file_put_contents(way(__DIR__.DS.'module.config.php'), $file_content);
	}
	else 
	{
		console_line('Файл конфигурации уже существует. Невозможно установить модуль.', 5, 'error');
	}
}

if ($mode=='uninstall') 
{
	if (is_file(way(__DIR__.DS.'module.config.php'))) 
	{
		console_line('Освобождаем записи базы данных.', 5);
		$base->del_tables($arTables);
		console_line('Удаляем файл конфигурации.', 5);
		if (is_file(way(__DIR__.DS.'module.config.php'))) unlink(way(__DIR__.DS.'module.config.php'));
	}
	else 
	{
		console_line('Файл конфигурации отсутствует. Невозможно удалить модуль.', 5, 'error');
	}
}
?>