<?
if (is_file(__DIR__.DIRECTORY_SEPARATOR.'system.php')) require_once(__DIR__.DIRECTORY_SEPARATOR.'system.php');

// ini_set('display_errors','Off'); // Отключить показ ошибок
set_time_limit(0); // Отключить лимит выполнения скрипта
function local ()
{
	return (($_SERVER['REMOTE_ADDR'] == '127.0.0.1')||($_SERVER['REMOTE_ADDR'] == '128.72.215.203')) ? true : false;
}

// Загружаем файл конфигурации, а если он не найден, то запускаем инсталлятор
$config_php = SCRIPTS.'config.php';
if (is_file($config_php)) require_once($config_php); else require_once(SCRIPTS.'install'.DS.'install.php');

// Запускаем поиск установленных модулей и подключаем их
if (is_file(SCRIPTS.'modules.php')) require_once(SCRIPTS.'modules.php');
if (empty($page->element['html'])) 
{
	write('Неизвестная ошибка. Невозможно инициировать систему', 4, 'error');
	$page->element['html'] = way(SCRIPTS.'tpl'.DS.'error.html.tpl.php');
}
?>