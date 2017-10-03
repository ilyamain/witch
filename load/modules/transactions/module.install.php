<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Загружаем устанавливаемую базу данных
if (is_file(way(__DIR__.DS.'module.dbtables.php'))) require_once(way(__DIR__.DS.'module.dbtables.php'));

$file_content = '<?'.PHP_EOL;
$file_content .= 'if (!defined(\'PROGRAM_NAME\')) die(); // Защита от прямого вызова скрипта'.PHP_EOL;
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