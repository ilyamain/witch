<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта

// Загружаем устанавливаемую базу данных
$install_db_file = way(__DIR__.DS.'module.dbtables.php');
$install_db = (is_file($install_db_file)) ? require($install_db_file) : array();

$file_content = '<?'.PHP_EOL;
$file_content .= 'if (!defined(\'PROGRAM_NAME\')) die(); // Защита от прямого вызова скрипта'.PHP_EOL;
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