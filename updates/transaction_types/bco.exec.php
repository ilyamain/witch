<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Смена владельца банкноты (BCO - bill change owner)

$wrong_items = false;
// Задание базовых параметров транзакции:
// 'ok' - если транзакция допустима
// 'is' - задание является транзакцией, а не намерением или эмиссией
// 'json' - запрошенная транзакция в формате JSON изначально пуста
$output['ok'] = false;
$output['is'] = 'transaction';
$output['json'] = '';
$output['type'] = 'bco';
// В случае возникновения ошибок при проверке транзакции, задаем изначальное 
// состояние входных и выходных банкнот, силы транзакции и размера комиссии
$output['fee'] = to_cent(0);
$output['denomination'] = to_cent(0);
$output['number'] = array();
$output['output'] = array();
$base = new cBase;
if ((is_array($input))&&(count($input) == 6)) 
{
	$number = $input[0];
	$old_key = $input[1];
	$new_sign = $input[2];
	$new_algorithm = $input[3];
	$new_timestamp = $input[4];
	$fee = $input[5];
	ksort($input);
	$json = transaction_code($transaction_name, $input);
	$output['fee'] = to_cent($fee);
	$output['json'] = $json;
	$output['entity'] = json_encode(array_map('to_string', $input));
	$input_error = false;
	if (!is_string($number)) 				$input_error = true;
	if (!is_string($old_key)) 				$input_error = true;
	if (!is_alphabet($new_sign)) 			$input_error = true;
	if (!is_alphabet($new_algorithm)) 		$input_error = true;
	if (!is_timestamp($new_timestamp)) 		$input_error = true;
	if (!is_denomination($fee)) 			$input_error = true;
	if (!$input_error) 
	{
		$old = $base->bill_get($number);
		// Проверка банкноты
		if (!empty($old)) 
		{
			$output['denomination'] = to_cent($old['denomination']); // сила транзакции - номинал входной банкноты
			array_push($output['number'], $number); // одна входная банкнота
			array_push($output['output'], $number); // одна выходная банкнота
			// Проверка прежнего пароля
			$bill_example = array 
			(
				'number' => $number, 
				'key' => $old_key, 
				'algorithm' => $old['algorithm'], 
				'denomination' => to_cent($old['denomination']), 
				'timestamp' => $old['timestamp'], 
				'entity' => $json, 
				'sign' => $old['sign'], 
			);
			if ((new cEncrypt($bill_example))->sign_proper) 
			{
				write('Пароль успешно прошел проверку.', 2);
			}
			else 
			{
				write('Транзакция отклонена. Пароль к банкноте указан неверно.', 2, 'error');
				$wrong_items = true;
			}
			// Проверка комиссии
			if (to_cent($fee) < to_cent($old['denomination'])) 
			{
				write('Указаный размер комиссии допустим.', 2);
			}
			else 
			{
				write('Транзакция отклонена. Указан недопустимый размер комиссии.', 2, 'error');
				$wrong_items = true;
			}
			// Временная метка
			if (($old['timestamp'] < $new_timestamp)||($new_timestamp == 0)) 
			{
				write('Временная метка транзакции допустима.', 2);
			}
			else 
			{
				write('Транзакция отклонена. Указана недопустимая временная метка.', 2, 'error');
				$wrong_items = true;
			}
			// Проверка намерений
			$arIntentions = $base->intentions_get($number);
			if (empty($arIntentions)) 
			{
				write('Обременяющие намерения на банкноте отсутствуют.', 2);
			}
			else 
			{
				$current = array();
				$current['args'] = $input;
				$current['example'] = array
				(
					'number' => $number, 
					'key' => $old_key, 
					'algorithm' => $old['algorithm'], 
					'denomination' => to_cent($old['denomination']), 
					'timestamp' => $old['timestamp'], 
					'entity' => $json, 
				);
				$current['intention'] = transaction_code($transaction_name, $current['args'], $current['example']);
				$current['pubkey'] = (new cEncrypt($current['example']))->pubkey;
				$current['in_list'] = false;
				foreach ($arIntentions as $iIntentions) 
				{
					if (($iIntentions['goal'] == $number)&&($iIntentions['pubkey'] == $current['pubkey'])&&($iIntentions['intention'] == $current['intention'])) 
					{
						$current['in_list'] = true;
					}
				}
				if ($current['in_list']) 
				{
					write('Намерение выполнения этой транзакции найдено', 2);
				}
				else 
				{
					write($number.'. Подходящее намерение в списке не найдено', 2, 'error');
					$wrong_items = true;
				}
			}
			// Проверка нового алгоритма шифрования
			if ((new cEncrypt(['algorithm'=>$new_algorithm]))->algorithm) 
			{
				write('Алгоритм шифрования для банкноты <b>'.$number.'</b> указан верно.', 2);
			}
			else 
			{
				write('Новый алгоритм шифрования не найден. Попробуйте обновить программу '.PROGRAM_NAME.'.', 2, 'error');
				$wrong_items = true;
			}
		}
		else 
		{
			write('Транзакция отклонена. В базе данных отсутствует банкнота с номером <b>'.$number.'</b>.', 2, 'error');
			$wrong_items = true;
		}
	}
	else 
	{
		write('Невозможно прочитать транзакцию. Неправильный формат.', 2, 'error');
		$wrong_items = true;
	}
}
else 
{
	write('Невозможно прочитать транзакцию. Неправильный формат.', 2, 'error');
	$wrong_items = true;
}

// Выполнение задания при отсутствии ошибок
if (!$wrong_items) 
{
	write('Все проверки успешно пройдены.', 2);
	if ($compile) 
	{
		write('Меняем пароль банкноты...', 2);
		$base->intentions_clear($number);
		$base->bill_payment($number, $new_sign, $new_algorithm, $new_timestamp, $fee);
	}
	else 
	{
		write('Транзакция смены пароля банкноты допустима.', 2, 'success');
	}
	$output['ok'] = true;
}
else 
{
	write('Транзакция отклонена. Банкнота не прошла проверку.', 2, 'error');
	$output['ok'] = false;
}

// Вывод параметров выполнения транзакции
return $output;
?>