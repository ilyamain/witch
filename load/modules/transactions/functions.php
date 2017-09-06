<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
//*******************************
// Функции работы с транзакциями.
//*******************************
// Сортировка транзакций по типам и номерам первых банкнот при чтении блока
function transaction_sort ($input) 
{
	$output = array();
	foreach ($input as $transaction) 
	{
		if ($transaction['key']=='bu') usort($transaction['parameters']['0'], 'sort_numbers');
		if ($transaction['key']=='bs') usort($transaction['parameters']['2'], 'sort_numbers');
		if ($transaction['key']=='br') usort($transaction['parameters']['0'], 'sort_numbers');
		if ($transaction['key']=='br') usort($transaction['parameters']['1'], 'sort_numbers');
		array_push($output, $transaction);
	}
	usort($output, 'sort_transactions');
	return $output;
}
// Извлечение статистической информации о транзакции
function transaction_test ($transaction_name, $input) 
{
	$transaction = new cTransactions;
	$result = array();
	$result['is_ok'] = false;
	$result['number'] = array();
	switch ($transaction_name) {
		case 'bco':
			$result['is_ok'] = $transaction->bill_change_owner ($input[0], $input[1], $input[2], $input[3], $input[4], false);
			$arTemp = cBase::bill_get_row($input[0]); // в разработке (new cBase)->bill_get_row($input[0]);
			$result['denomination'] = $arTemp['denomination'];
			array_push($result['number'], $arTemp['number']);
			break;
		case 'bu':
			$result['is_ok'] = $transaction->bill_unite ($input[0], $input[1], $input[2], $input[3], $input[4], false);
			$result['denomination'] = 0;
			foreach ($input[0] as $item) {
				$arTemp = cBase::bill_get_row($item['n']); // в разработке (new cBase)->bill_get_row($item['n']);
				$result['denomination'] += $arTemp['denomination'];
				array_push($result['number'], $arTemp['number']);
			}
			break;
		case 'bs':
			$result['is_ok'] = $transaction->bill_split ($input[0], $input[1], $input[2], $input[3], false);
			$arTemp = cBase::bill_get_row($input[0]); // в разработке (new cBase)->bill_get_row($input[0]);
			$result['denomination'] = $arTemp['denomination'];
			array_push($result['number'], $arTemp['number']);
			break;
		case 'br':
			$result['is_ok'] = $transaction->bill_resort ($input[0], $input[1], false);
			$result['denomination'] = 0;
			foreach ($input[0] as $item) {
				$arTemp = cBase::bill_get_row($item['n']); // в разработке (new cBase)->bill_get_row($item['n']);
				$result['denomination'] += $arTemp['denomination'];
				array_push($result['number'], $arTemp['number']);
			}
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
	if ((!$result)&&(is_string($item_a['parameters'][0]))&&(is_string($item_b['parameters'][0]))) 
		$result = strnatcmp($item_a['parameters'][0], $item_b['parameters'][0]);
	if ((!$result)&&(is_string($item_a['parameters'][0][0]['n']))&&(is_string($item_b['parameters'][0][0]['n']))) 
		$result = strnatcmp($item_a['parameters'][0][0]['n'], $item_b['parameters'][0][0]['n']);
	if ((!$result)&&(is_string($item_a['parameters'][1]))&&(is_string($item_b['parameters'][1]))) 
		$result = strnatcmp($item_a['parameters'][1], $item_b['parameters']['0']);
	if ((!$result)&&(is_string($item_a['parameters'][1][0]['n']))&&(is_string($item_b['parameters'][1][0]['n']))) 
		$result = strnatcmp($item_a['parameters'][1][0]['n'], $item_b['parameters'][1][0]['n']);
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