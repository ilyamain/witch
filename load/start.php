<?
if (is_file(__DIR__.DIRECTORY_SEPARATOR.'system.php')) require_once(__DIR__.DIRECTORY_SEPARATOR.'system.php');

// Загружаем файл конфигурации, а если он не найден, то запускаем инсталлятор
$config_php = SCRIPTS.'config.php';
if (is_file($config_php)) 
{
	require_once($config_php);
	if (DEMO) ini_set('display_errors','Off'); // Показ ошибок отключен в демонстрационной версии
}
else 
{
	require_once(SCRIPTS.'install'.DS.'install.php');
}

// Запускаем поиск установленных модулей и подключаем их
if (is_file(SCRIPTS.'modules.php')) require_once(SCRIPTS.'modules.php');
if (empty($page->element['html'])) 
{
	console_line('Неизвестная ошибка. Невозможно инициировать систему', 4, 'error');
	$page->element['html'] = way(SCRIPTS.'tpl'.DS.'error.html.tpl.php');
}
?>