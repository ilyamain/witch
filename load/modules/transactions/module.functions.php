<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
//*******************************
// Функции работы с транзакциями.
//*******************************
// Сортировка транзакций по типам и номерам первых банкнот при чтении блока
function transaction_sort ($input)
{
	$output = array();
	if ((is_array($input))&&(!empty($input))) 
	{
		foreach ($input as $transaction) 
		{
			if ($transaction['key']=='bu') usort($transaction['parameters']['0'], 'sort_numbers');
			if ($transaction['key']=='bs') usort($transaction['parameters']['2'], 'sort_numbers');
			if ($transaction['key']=='br') usort($transaction['parameters']['0'], 'sort_numbers');
			if ($transaction['key']=='br') usort($transaction['parameters']['1'], 'sort_numbers');
			array_push($output, $transaction);
		}
		usort($output, 'sort_transactions');
	}
	return $output;
}
// Извлечение статистической информации о транзакции или о намерении
function transaction_test ($transaction_name, $input)
{
	$transaction = new cTransactions;
	$intention = new cIntention;
	$result = array();
	$result['is_ok'] = false;
	$result['number'] = array();
	$result['fee'] = 0;
	switch ($transaction_name) 
	{
		case 'bgi':
			$result['is_ok'] = $intention->bill_group_intention ($input[0], false);
			$result['denomination'] = to_cent(0);
			foreach ($input[0] as $item) 
			{
				$arTemp = (new cBase)->bill_get_row($item['n']);
				$result['denomination'] += to_cent($arTemp['denomination']);
				array_push($result['number'], $arTemp['number']);
			}
			break;
		case 'bai':
			$result['is_ok'] = $intention->bill_add_intention ($input[0], $input[1], $input[2], false);
			$arTemp = (new cBase)->bill_get_row($input[0]);
			$result['denomination'] = to_cent($arTemp['denomination']);
			array_push($result['number'], $arTemp['number']);
			break;
		case 'bco':
			$result['is_ok'] = $transaction->bill_change_owner ($input[0], $input[1], $input[2], $input[3], $input[4], $input[5], false);
			$arTemp = (new cBase)->bill_get_row($input[0]);
			$result['denomination'] = to_cent($arTemp['denomination']);
			array_push($result['number'], $arTemp['number']);
			$result['fee'] = $input[5];
			break;
		case 'bu':
			$result['is_ok'] = $transaction->bill_unite ($input[0], $input[1], $input[2], $input[3], $input[4], $input[5], false);
			$result['denomination'] = to_cent(0);
			foreach ($input[0] as $item) 
			{
				$arTemp = (new cBase)->bill_get_row($item['n']);
				$result['denomination'] += to_cent($arTemp['denomination']);
				array_push($result['number'], $arTemp['number']);
			}
			$result['fee'] = $input[5];
			break;
		case 'bs':
			$result['is_ok'] = $transaction->bill_split ($input[0], $input[1], $input[2], $input[3], $input[4], false);
			$arTemp = (new cBase)->bill_get_row($input[0]);
			$result['denomination'] = to_cent($arTemp['denomination']);
			array_push($result['number'], $arTemp['number']);
			$result['fee'] = $input[4];
			break;
		case 'br':
			$result['is_ok'] = $transaction->bill_resort ($input[0], $input[1], $input[2], $input[3], false);
			$result['denomination'] = to_cent(0);
			foreach ($input[0] as $item) 
			{
				$arTemp = (new cBase)->bill_get_row($item['n']);
				$result['denomination'] += to_cent($arTemp['denomination']);
				array_push($result['number'], $arTemp['number']);
			}
			$result['fee'] = $input[3];
			break;
		default:
			return $result;
	}
	return $result;
}
// Кодирование транзакции в формат JSON или хэширование транзакции
// (если задан экземпляр 'example')
function transaction_code ($identifier, $arInput, $example = array())
{
	$output = $string = '';
	$string .= $identifier.':';
	$string .= json_encode($arInput);
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

//*******************************
// Функции сортировки транзакций.
//*******************************
// Предназначена для сортировки однотипных транзакций по 
// номеру первой банкноты в ней. Используется как хэндлер.
function sort_transactions ($item_a, $item_b)
{
	$result = strnatcmp($item_a['key'],$item_b['key']);
	if (!$result) 
	{
		if ((is_array($item_a['parameters']))&&(is_array($item_b['parameters']))) 
		{
			if ((is_string($item_a['parameters'][0]))&&(is_string($item_b['parameters'][0]))) 
			{
				$result = strnatcmp($item_a['parameters'][0], $item_b['parameters'][0]);
			}
			elseif ((is_array($item_a['parameters'][0]))&&(is_array($item_b['parameters'][0]))) 
			{
				if ((is_array($item_a['parameters'][0][0]))&&(is_array($item_b['parameters'][0][0]))) 
				{
					if ((is_string($item_a['parameters'][0][0]['n']))&&(is_string($item_b['parameters'][0][0]['n']))) 
					{
						$result = strnatcmp($item_a['parameters'][0][0]['n'], $item_b['parameters'][0][0]['n']);
					}
				}
			}
		}
	}
	if (!$result) 
	{
		if ((is_array($item_a['parameters']))&&(is_array($item_b['parameters']))) 
		{
			if ((is_string($item_a['parameters'][1]))&&(is_string($item_b['parameters'][1]))) 
			{
				$result = strnatcmp($item_a['parameters'][1], $item_b['parameters'][1]);
			}
			elseif ((is_array($item_a['parameters'][1]))&&(is_array($item_b['parameters'][1]))) 
			{
				if ((is_array($item_a['parameters'][1][0]))&&(is_array($item_b['parameters'][1][0]))) 
				{
					if ((is_string($item_a['parameters'][1][0]['n']))&&(is_string($item_b['parameters'][1][0]['n']))) 
					{
						$result = strnatcmp($item_a['parameters'][1][0]['n'], $item_b['parameters'][1][0]['n']);
					}
				}
			}
		}
	}
	return $result;
}
// Предназначена для сортировки банкнот по номеру 
// в множественных массивах перед обработкой транзакции.
// Используется как хэндлер.
function sort_numbers($item_a, $item_b)
{
	return strnatcmp($item_a['n'], $item_b['n']);
}
?>