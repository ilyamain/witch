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
if ((!empty($_COOKIE['console']))||($_COOKIE['console'] === '0')) 
{
	if (!defined('AJAX_FORM')) write('Уровень показа сообщений: '.$_COOKIE['console'], $_COOKIE['console']+1);
	define('CONSOLE_LEVEL', $_COOKIE['console']);
}
else 
{
	if (!defined('AJAX_FORM')) write('Уровень показа сообщений: '.$_COOKIE['console'], $default_console_level);
	define('CONSOLE_LEVEL', $default_console_level);
}

//*******************************
// Набор системных функций
//*******************************
// Запись строки в консоль
function write ($txt, $show_level = 0, $line_type = 'ok')
{
	global $console;
	if ((is_array($txt))||(is_object($txt))) $txt = print_r($txt, true);
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
	if ($show_level < CONSOLE_LEVEL) $line_class .= ' invisible-line';
	$line_class .= ' console-line-level-'.$show_level;
	$console = '<div class="'.$line_class.'" level="'.$show_level.'">'.$txt.'</div>'.$console;
}

// Задание алфавита
function alphabet ($lang = 'english')
{
	$output = '';
	switch ($lang) 
	{
		case 'english':
			$output = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
			break;
		case 'letters':
			$output = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
			break;
		case 'symbols':
			$output = '.,:;?!&%$^~+-*_=(){}[]';
			break;
		case 'hex':
			$output = '0123456789abcdef';
			break;
		case 'digits':
			$output = '0123456789';
			break;
		case 'full':
			$output = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789.,:;?!&%$^~+-*_=(){}[]';
			break;
		default:
			$output = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	}
	return $output;
}

// Проверка написано ли слово выбранным алфавитом
function is_alphabet ($input, $lang = 'english')
{
	$output = true;
	if ((!empty($input))&&(is_string($input))) 
	{
		$pattern = alphabet($lang);
		$arInput = str_split($input, 1);
		foreach ($arInput as $iInput) if (!stristr($pattern, $iInput)) $output = false;
	}
	else 
	{
		$output = false;
	}
	return $output;
}

// Генерация случайной абракадабры
function abra ($length = 10)
{
	$alphabet = alphabet('english');
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

// Считывание распределенных данных
function bc_data ()
{
	return (is_file(way(SCRIPTS.'bc_data.php'))) ? require(way(SCRIPTS.'bc_data.php')) : array();
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
	if (!is_numeric($input)) $input = 0;
	return number_format($input, CENT_ACCURACY, '.', '');
}

// Проверка является ли переменная номиналом
function is_denomination ($input)
{
	if (is_numeric($input)) return (to_cent($input) === $input) ? true : false; else return false;
}

// Проверка является ли переменная целым числом независимо от типа
function is_num ($input, $zero = false)
{
	if (($zero)&&($input == 0)) return true;
	return ((is_numeric($input))&&(round($input) == $input)) ? true : false;
}

// Сравнение чисел с плавающей запятой с точностью до сатоши
function float_equals ($float_a, $float_b, $accuracy = 10**(-CENT_ACCURACY))
{
	return (abs($float_a - $float_b) < $accuracy) ? true : false;
}

// Сравнение хэшей, вычисление степени различия между ними
function hash_difference ($hash_a, $hash_b)
{
	$array_a = array_map('array_hex_to_int', str_split($hash_a, 4));
	$array_b = array_map('array_hex_to_int', str_split($hash_b, 4));
	if (count($array_a) == count($array_b)) 
	{
		$result = max(array_map('array_difference_abs', $array_a, $array_b));
		return $result;
	}
	else 
	{
		return false;
	}
}

//**********************************************
// Функции для array_map
//**********************************************
// Преобразование в строки (рекурсия)
function to_string ($item)
{
	if ((is_array($item))||(is_object($item))) 
	{
		$output = array_map('to_string', $item);
	}
	else 
	{
		$output = strval($item);
	}
	return $output;
}

// Разность массивов
function array_difference ($item_a, $item_b)
{
	return $item_a - $item_b;
}

// Разность массивов по модулю
function array_difference_abs ($item_a, $item_b)
{
	return abs($item_a - $item_b);
}

// Пересчет шестнадцатеричного хэша в десятичный
function array_hex_to_int ($item)
{
	return intval($item, 16);
}

// Приведение баллов к арктангенциальной шкале
function array_arctg ($item)
{
	$result = 1+(atan($item)/M_PI)*2;
	return $result;
}
?>