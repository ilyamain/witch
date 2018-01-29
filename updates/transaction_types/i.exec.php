<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Эмиссия банкнот для формирования блока.

$wrong_items = false;
// Задание базовых параметров эмиссии:
// 'ok' - если эмиссия допустима
// 'is' - задание является эмиссией
// 'json' - запрошенная эмиссия в формате JSON изначально пуста
$output['ok'] = false;
$output['is'] = 'issue';
$output['json'] = '';
$output['type'] = 'i';
// В случае возникновения ошибок при проверке эмиссии, задаем изначальное 
// состояние входных и выходных банкнот, суммы эмиссии и размера комиссии
$output['fee'] = to_cent(0);
$output['denomination'] = to_cent(0);
$output['number'] = array();
$output['output'] = array();
$base = new cBase;
if ((is_array($input))&&(count($input) == 5)) 
{
	// В качестве входных данных используется ассоциативный массив
	$number = $input['number'];
	$sign = $input['sign'];
	$algorithm = $input['algorithm'];
	$denomination = $input['denomination'];
	$timestamp = $input['timestamp'];
	// Сортировка не требуется, так как формат записи эмиссии проверяется в модуле проверки блоков
	$output['json'] = transaction_code($transaction_name, $input);
	$output['entity'] = json_encode(array_map('to_string', $input));
	$output['denomination'] = to_cent($denomination); // сумма эмиссии
	array_push($output['number'], $number); // одна входная банкнота
	array_push($output['output'], $number); // одна выходная банкнота
	// Проверка занятости номера банкноты
	$bill = $base->bill_get($number);
	if (empty($bill)) 
	{
		if (!is_alphabet($number)) $wrong_items = true;
		if (!is_alphabet($sign)) $wrong_items = true;
		if (!is_alphabet($algorithm)) $wrong_items = true;
		if (!(new cEncrypt(['algorithm'=>$algorithm]))->algorithm) $wrong_items = true;
		if (empty($denomination)) $wrong_items = true;
		if (!is_denomination($denomination)) $wrong_items = true;
		if (($denomination <= 0)||(float_equals($denomination, 0))) $wrong_items = true;
		if ((!is_timestamp($timestamp))&&($timestamp != 0)) $wrong_items = true;
	}
	else 
	{
		write('Неверная информация об эмиссии. Банкнота с таким номером уже существует.', 2, 'error');
		$wrong_items = true;
	}
}
else 
{
	write('Невозможно прочитать информацию об эмиссии. Неправильный формат.', 2, 'error');
	$wrong_items = true;
}

// Выполнение задания при отсутствии ошибок
if (!$wrong_items) 
{
	write('Все проверки успешно пройдены.', 2);
	if ($compile) 
	{
		write('Банкнота эмитирована', 2, 'success');
		$base->bill_add($number, $sign, $algorithm, to_cent($denomination), $timestamp);
	}
	else 
	{
		write('Эмиссия допустима', 2, 'success');
	}
	$output['ok'] = true;
}
else 
{
	write('Неверная информация об эмиссии', 2, 'error');
	$output['ok'] = false;
}

// Вывод параметров выполнения эмиссии
return $output;
?>