<?
// Устанавливаем UTC time zone по умолчанию для всех пользователей.
// Так как многие функции зависят от таймштампа, не рекомендуется 
// менять это значение. Потому что при его изменении, Ваши транзакции 
// и блоки будут отклонены другими участниками сети. Если требуется 
// установить на пользовательскую часть другой временной пояс, то 
// необходимо это делать именно в пользовательской части.
date_default_timezone_set('UTC');

// Определяем системные константы (кроме базы данных, она в файле конфигурации)
// Наименование программы
define('PROGRAM_NAME', 'Blockchain CMS Witch');
// В зависимости от ОС, выбирается прямой или обратный слэш для указания пути к файлу
define('DS', DIRECTORY_SEPARATOR);
// Все прямые и обратные слэши приводятся к единому значению
define('DR', way($_SERVER[DOCUMENT_ROOT]));
// Задаем разрядность токена
define('CENT_ACCURACY', 8);
// Указываем путь к скриптам программы
define('SCRIPTS', way(DR.DS.'load'.DS));
// Инициируем консоль
$console = '';
// Уровень показа сообщений в консоли
$default_console_level = 3;
if ((!empty($_COOKIE['console']))||($_COOKIE['console']==='0')) 
{
	if (!defined('AJAX_FORM')) console_line('Уровень показа сообщений: '.$_COOKIE['console'], $_COOKIE['console']+1);
	define('CONSOLE_LEVEL', $_COOKIE['console']);
}
else 
{
	if (!defined('AJAX_FORM')) console_line('Уровень показа сообщений: '.$_COOKIE['console'], $default_console_level);
	define('CONSOLE_LEVEL', $default_console_level);
}

//*******************************
// Набор системных функций
//*******************************
// Запись строки в консоль
function console_line ($txt, $show_level = 0, $line_type = 'ok') 
{
	global $console;
	if ($show_level >= CONSOLE_LEVEL) 
	{
		$line_class = 'console-line';
		switch ($line_type) 
		{
			case 'ok':
				$line_class .= '';
				break;
			case 'error':
				$line_class .= ' error-line';
				break;
			case 'attract':
				$line_class .= ' attract-line';
				break;
			case 'success':
				$line_class .= ' success-line';
				break;
			case 'array':
				$line_class .= ' array-line';
				break;
			default:
				$line_class .= '';
		}
		$console = '<div class="'.$line_class.'">'.$txt.'</div>'.$console;
	}
}

// Генерация случайной абракадабры
function abra ($length = 10) 
{
	$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	$result = '';
	$alphabet_length = strlen($alphabet)-1;
	while (strlen($result) < $length) $result .= $alphabet[random_int(0, $alphabet_length)];
	return $result;
}

// Замена неправильных для ОС слэшей/бэкслэшей на правильные
function way ($txt) 
{
	return str_replace (array('/', '\\'), DS, $txt);
}

// Безопасная загрузка файлов в шаблон
function section ($element) 
{
	if (is_file($element)) require($element);
}

// Проверка является ли переменная таймштампом (для унификации проверки с is_string, is_array и т.д.)
function is_timestamp ($timestamp) 
{
	return checkdate(date('m', $timestamp) ,date('d', $timestamp) ,date('Y', $timestamp));
}

// Перевод числового значения в номинал
function to_cent ($input) 
{
	return number_format($input, CENT_ACCURACY, '.', '');
}

// Проверка является ли переменная номиналом
function is_denomination ($input) 
{
	if (is_numeric($input)) return (to_cent($input)===$input) ? true : false; else return false;
}

// Проверка является ли переменная целым числом независимо от типа
function is_num ($input) 
{
	return ((is_numeric($input))&&(round($input)==$input)) ? true : false;
}

// Сравнение чисел с плавающей запятой с точностью до сатоши
function float_equals ($float_a, $float_b, $accuracy = 10**(-CENT_ACCURACY)) 
{
	return (abs($float_a-$float_b)<$accuracy) ? true : false;
}
?>