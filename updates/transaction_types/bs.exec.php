<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Размен банкноты (BS - bill split)
// a - Алгоритм шифрования каждой новой банкноты
// n - Номер каждой новой банкноты
// d - Номинал каждой новой банкноты
// s - Подпись каждой новой банкноты

$wrong_items = false;
// Задание базовых параметров транзакции:
// 'ok' - если транзакция допустима
// 'is' - задание является транзакцией, а не намерением или эмиссией
// 'json' - запрошенная транзакция в формате JSON изначально пуста
$output['ok'] = false;
$output['is'] = 'transaction';
$output['json'] = '';
$output['type'] = 'bs';
// В случае возникновения ошибок при проверке транзакции, задаем изначальное 
// состояние входных и выходных банкнот, силы транзакции и размера комиссии
$output['fee'] = to_cent(0);
$output['denomination'] = to_cent(0);
$output['number'] = array();
$output['output'] = array();
$base = new cBase;
if ((is_array($input))&&(count($input) == 5)) 
{
	$old_number = $input[0];
	$old_key = $input[1];
	$arBills = $input[2];
	$timestamp = $input[3];
	$fee = $input[4];
	$output['fee'] = to_cent($fee);
	$input_error = false;
	if (!is_string($old_number)) $input_error = true;
	if (!is_string($old_key)) $input_error = true;
	if (!is_array($arBills)) $input_error = true;
	if (!is_timestamp($timestamp)) $input_error = true;
	if (!is_denomination($fee)) $input_error = true;
	if (!$input_error) 
	{
		// Сортировка массивов
		usort($arBills, 'sort_numbers'); // сортировка выходных банкнот
		foreach ($arBills as $key => $item) if (is_array($arBills[$key])) ksort($arBills[$key]); else $wrong_items = true; // сортировка a,d,n,s.
		$sorted_input = array
		(
			$old_number, 
			$old_key, 
			$arBills, 
			$timestamp, 
			$fee, 
		);
		$json = transaction_code($transaction_name, $sorted_input);
		$output['json'] = $json;
		$output['entity'] = json_encode(array_map('to_string', $sorted_input));
		$arNumbers = array();
		$sum = $fee;
		$old = $base->bill_get($old_number);
		// Проверка старой банкноты
		if (!empty($old)) 
		{
			$output['denomination'] = to_cent($old['denomination']); // сила транзакции - номинал входной банкноты
			array_push($output['number'], $old_number); // одна входная банкнота
			// Проверка прежнего пароля
			$bill_example = array
			(
				'number' => $old_number, 
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
			if (($old['timestamp'] < $timestamp)||($timestamp == 0)) 
			{
				write('Временная метка транзакции допустима.', 2);
			}
			else 
			{
				write('Транзакция отклонена. Указана недопустимая временная метка.', 2, 'error');
				$wrong_items = true;
			}
			// Проверка намерений
			$arIntentions = $base->intentions_get($old_number);
			if (empty($arIntentions)) 
			{
				write('Обременяющие намерения на банкноте отсутствуют.', 2);
			}
			else 
			{
				$current = array();
				$current['args'] = $sorted_input;
				$current['example'] = array
				(
					'number' => $old_number, 
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
					if (($iIntentions['goal'] == $old_number)&&($iIntentions['pubkey'] == $current['pubkey'])&&($iIntentions['intention'] == $current['intention'])) 
					{
						$current['in_list'] = true;
					}
				}
				if ($current['in_list']) 
				{
					write('Намерение выполнения этой транзакции найдено', 2, 'success');
				}
				else 
				{
					write($old_number.'. Подходящее намерение в списке не найдено', 2, 'error');
					$wrong_items = true;
				}
			}
		}
		else 
		{
			write('Транзакция отклонена. В базе данных отсутствует банкнота с номером <b>'.$old_number.'</b>.', 2, 'error');
			$wrong_items = true;
		}
		// Проверка новых банкнот
		foreach ($arBills as $iBills) 
		{
			// Проверка формата записи новой банкноты
			if ((is_alphabet($iBills['n']))&&(is_alphabet($iBills['s']))&&(is_alphabet($iBills['a']))) 
			{
				write('Подходящий формат банкноты.', 2);
				array_push($arNumbers, $iBills['n']);
				array_push($output['output'], $iBills['n']);
				// Проверка занятости номера новой банкноты
				$new = $base->bill_get($iBills['n']);
				if (empty($new)) 
				{
					write('Номер банкноты не занят.', 2);
				}
				else 
				{
					write('Транзакция отклонена. Банкнота <b>'.$iBills['n'].'</b> уже существует.', 2, 'error');
					$wrong_items = true;
				}
				// Проверка существования алгоритма шифрования
				if ((new cEncrypt(['algorithm'=>$iBills['a']]))->algorithm) 
				{
					write('Алгоритм шифрования для банкноты <b>'.$iBills['n'].'</b> указан верно.', 2);
				}
				else 
				{
					write('Новый алгоритм шифрования не найден. Попробуйте обновить программу '.PROGRAM_NAME.'.', 2, 'error');
					$wrong_items = true;
				}
			}
			else 
			{
				write('Недопустимый формат банкноты.', 2, 'error');
				$wrong_items = true;
			}
			// Проверка номинала новой банкноты
			if ((!float_equals($iBills['d'], 0))&&($iBills['d'] > 0)&&(is_denomination($iBills['d']))) 
			{
				write('Подходящий номинал банкноты.', 2);
				$sum += $iBills['d'];
			}
			else 
			{
				write('Недопустимый номинал банкноты.', 2, 'error');
				$wrong_items = true;
			}
		}
		// Проверка сохранения сумм
		if (!float_equals($old['denomination'], $sum)) 
		{
			write('Транзакция отклонена. Указаны неверные номиналы банкнот.', 2, 'error');
			$wrong_items = true;
		}
		// Проверка уникальности номеров всех банкнот
		array_push($arNumbers, $old_number);
		if ($arNumbers != array_unique($arNumbers)) 
		{
			write('Транзакция отклонена. Обнаружены одинаковые номера банкнот.', 2, 'error');
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
		foreach ($arBills as $iBills) 
		{
			$base->bill_add($iBills['n'], $iBills['s'], $iBills['a'], to_cent($iBills['d']), $timestamp);
		}
		$base->intentions_clear($old_number);
		$base->bill_del($old_number);
		write('Банкнота разменяна.', 2, 'success');
	}
	else 
	{
		write('Транзакция размена банкнот допустима.', 2, 'success');
	}
	$output['ok'] = true;
}
else 
{
	write('Транзакция отклонена. Некоторые банкноты не прошли проверку.', 2, 'error');
	$output['ok'] = false;
}

// Вывод параметров выполнения транзакции
return $output;
?>