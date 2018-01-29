<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Обращение к файлам типа транзакций
function updates_transaction_types ($transaction_name, $input, $compile = false)
{
	$output = array
	(
		'ok' => false, 
		'is' => '', 
		'json' => '', 
		'type' => '', 
		'entity' => '', 
		'fee' => to_cent(0), 
		'denomination' => to_cent(0), 
		'number' => array(), 
		'output' => array(), 
	);
	$file['func'] = way(DR.DS.'updates'.DS.'transaction_types'.DS.$transaction_name.'.func.php');
	$file['exec'] = way(DR.DS.'updates'.DS.'transaction_types'.DS.$transaction_name.'.exec.php');
	if (is_file($file['func'])) require_once($file['func']);
	if (is_file($file['exec'])) $output = require($file['exec']);
	return $output;
}

//*******************************
// Функции работы с транзакциями.
//*******************************
// Извлечение статистической информации о транзакции или намерении
function transaction_test ($transaction_name, $input, $compile = false)
{
	$result = array
	(
		'ok' => false, 
		'is' => '', 
		'json' => '', 
		'type' => $transaction_name, 
		'entity' => '', 
		'fee' => to_cent(0), 
		'denomination' => to_cent(0), 
		'number' => array(), 
		'output' => array(), 
	);
	if ((!empty($input))&&(is_array($input))) 
	{
		$result = (new cTransactions)->execute($transaction_name, $input, $compile);
		if ($result['json'] != ($transaction_name.':'.json_encode(array_map('to_string', $input)))) $result['ok'] = false;
		if (!$result['ok']) write('Произошла ошибка при проверке команды '.$transaction_name.'.', 2, 'error');
	}
	return $result;
}

// Кодирование транзакции в формат JSON или хэширование транзакции
// (если задан экземпляр 'example')
function transaction_code ($identifier, $arInput, $example = array())
{
	$output = $string = '';
	$string .= $identifier.':';
	$string .= json_encode(array_map('to_string', $arInput));
	if (!empty($example)) 
	{
		$example['key'] = $string;
		$output = (new cEncrypt($example))->sign;
	}
	else 
	{
		$output = $string;
	}
	return $output;
}

// Разделение сущности транзакции на тип и JSON-формат
function transaction_split ($input, $get_type = false)
{
	if ($get_type) 
	{
		$output = substr($input, 0, strpos($input, ':'));
	}
	else 
	{
		$output = substr($input, strpos($input, ':')+1, strlen($input));
	}
	return $output;
}

//*******************************
// Функции сортировки транзакций.
//*******************************
// Сортировка транзакций по типам и номерам первых банкнот при чтении блока
function sort_transaction ($input)
{
	$output = array();
	if ((is_array($input))&&(!empty($input))) 
	{
		usort($input, function ($item_a, $item_b)
		{
			if ($item_a['key'] == $item_b['key']) 
			{
				$parameter_a = $item_a['parameters'];
				$parameter_b = $item_b['parameters'];
				// Сортировка транзакций по входным параметрам
				if ((is_array($parameter_a))&&(is_array($parameter_b))) 
				{
					foreach ($parameter_a as $key => $sort_elem_a) 
					{
						$sort_elem_b = $parameter_b[$key];
						if ((!empty($sort_elem_a))&&(!empty($sort_elem_b))&&($sort_elem_a != $sort_elem_b)) 
						{
							if ((is_string($sort_elem_a))&&(is_string($sort_elem_b))) return strnatcmp($sort_elem_a, $sort_elem_b);
							if ((is_numeric($sort_elem_a))&&(is_numeric($sort_elem_b))) return ($sort_elem_a < $sort_elem_b) ? -1 : 1;
							// Если параметр является массивом, то сортировка по первым 
							// элементам массива. Например, в транзакциях типа 'bgi'
							if ((is_array($sort_elem_a))&&(is_array($sort_elem_b))) 
							{
								foreach ($sort_elem_a as $elem_key => $elem_a) 
								{
									$elem_b = $sort_elem_b[$elem_key];
									if ((!empty($elem_a))&&(!empty($elem_b))&&($elem_a != $elem_b)) 
									{
										if ((is_string($elem_a))&&(is_string($elem_b))) return strnatcmp($elem_a, $elem_b);
										if ((is_numeric($elem_a))&&(is_numeric($elem_b))) return ($elem_a < $elem_b) ? -1 : 1;
										// Если внутри массива параметр также является массивом, то сортировка либо по 
										// первому элементу (например, в контрактах) либо по номеру (например, bu, bs, br)
										if ((is_array($elem_a))&&(is_array($elem_b))) 
										{
											if ((!empty($elem_a['n']))&&(!empty($elem_b['n']))) return strnatcmp($elem_a['n'], $elem_b['n']);
											if ((!empty($elem_a['0']))&&(!empty($elem_b['0']))) return strnatcmp($elem_a['0'], $elem_b['0']);
										}
									}
								}
							}
						}
					}
				}
				return 0; // если транзакции равны
			}
			else 
			{
				// Если типы транзакций отличаются, то сортировка по названию типа
				return strnatcmp($item_a['key'], $item_b['key']);
			}
		});
		$output = $input;
	}
	return $output;
}

// Предназначена для сортировки банкнот по номеру 
// в множественных массивах перед обработкой транзакции
// Используется как хэндлер
function sort_numbers($item_a, $item_b)
{
	return strnatcmp($item_a['n'], $item_b['n']);
}
?>