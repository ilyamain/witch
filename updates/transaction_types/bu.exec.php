<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Соединение банкнот (BU - bill unite)
// n - Номер каждой соединяемой банкноты
// k - Пароль к каждой соединяемой банкноте

$wrong_items = false;
// Задание базовых параметров транзакции:
// 'ok' - если транзакция допустима
// 'is' - задание является транзакцией, а не намерением или эмиссией
// 'json' - запрошенная транзакция в формате JSON изначально пуста
$output['ok'] = false;
$output['is'] = 'transaction';
$output['json'] = '';
$output['type'] = 'bu';
// В случае возникновения ошибок при проверке транзакции, задаем изначальное 
// состояние входных и выходных банкнот, силы транзакции и размера комиссии
$output['fee'] = to_cent(0);
$output['denomination'] = to_cent(0);
$output['number'] = array();
$output['output'] = array();
$base = new cBase;
if ((is_array($input))&&(count($input) == 6)) 
{
	$arBills = $input[0];
	$number = $input[1];
	$sign = $input[2];
	$algorithm = $input[3];
	$timestamp = $input[4];
	$fee = $input[5];
	$output['fee'] = to_cent($fee);
	$input_error = false;
	if (!is_array($arBills)) $input_error = true;
	if (!is_alphabet($number)) $input_error = true;
	if (!is_alphabet($sign)) $input_error = true;
	if (!is_alphabet($algorithm)) $input_error = true;
	if (!is_timestamp($timestamp)) $input_error = true;
	if (!is_denomination($fee)) $input_error = true;
	if (!$input_error) 
	{
		// Сортировка массивов
		usort($arBills, 'sort_numbers'); // сортировка входных банкнот
		foreach ($arBills as $key => $item) if (is_array($arBills[$key])) ksort($arBills[$key]); else $wrong_items = true; // сортировка k,n.
		$sorted_input = array
		(
			$arBills, 
			$number, 
			$sign, 
			$algorithm, 
			$timestamp, 
			$fee, 
		);
		$json = transaction_code($transaction_name, $sorted_input);
		$output['json'] = $json;
		$output['entity'] = json_encode(array_map('to_string', $sorted_input));
		$arNumbers = array();
		$sum = 0;
		// Проверка старых банкнот
		foreach ($arBills as $iBills) 
		{
			if ((is_string($iBills['n']))&&(is_string($iBills['k']))) 
			{
				// Проверка каждой старой банкноты
				$old = $base->bill_get($iBills['n']);
				if (!empty($old)) 
				{
					$sum += to_cent($old['denomination']);
					array_push($arNumbers, $iBills['n']);
					// Проверка прежнего пароля
					$bill_example = array
					(
						'number' => $iBills['n'], 
						'key' => $iBills['k'], 
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
					$arIntentions = $base->intentions_get($iBills['n']);
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
							'number' => $iBills['n'], 
							'key' => $iBills['k'], 
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
							if (($iIntentions['goal'] == $iBills['n'])&&($iIntentions['pubkey'] == $current['pubkey'])&&($iIntentions['intention'] == $current['intention'])) 
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
							write($iBills['n'].'. Подходящее намерение в списке не найдено', 2, 'error');
							$wrong_items = true;
						}
					}
				}
				else 
				{
					write('Транзакция отклонена. В базе данных отсутствует банкнота с номером <b>'.$iBills['n'].'</b>.', 2, 'error');
					$wrong_items = true;
				}
			}
			else 
			{
				write('Невозможно прочитать транзакцию. Неправильный формат старой банкноты.', 2, 'error');
				$wrong_items = true;
			}
		}
		$output['denomination'] = to_cent($sum); // сила транзакции - сумма номиналов всех входных банкнот
		$output['number'] = $arNumbers; // список всех входных банкнот
		array_push($output['output'], $number); // одна выходная банкнота
		// Проверка комиссии
		if (to_cent($fee) < to_cent($sum)) 
		{
			write('Указаный размер комиссии допустим.', 2);
		}
		else 
		{
			write('Транзакция отклонена. Указан недопустимый размер комиссии.', 2, 'error');
			$wrong_items = true;
		}
		// Вычитание комиссии
		$sum = to_cent($sum - $fee);
		// Проверка новой банкноты
		$new = $base->bill_get($number);
		if (empty($new)) 
		{
			write('Номер банкноты не занят.', 2);
			if ((new cEncrypt(['algorithm'=>$algorithm]))->algorithm) 
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
			write('Транзакция отклонена. Банкнота <b>'.$number.'</b> уже существует.', 2, 'error');
			$wrong_items = true;
		}
		// Проверка уникальности номеров всех банкнот
		array_push($arNumbers, $number);
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
		$base->bill_add($number, $sign, $algorithm, to_cent($sum), $timestamp);
		foreach ($arBills as $iBills) 
		{
			$base->intentions_clear($iBills['n']);
			$base->bill_del($iBills['n']);
		}
		write('Банкноты объединены.', 2, 'success');
	}
	else 
	{
		write('Транзакция объединения банкнот допустима.', 2, 'success');
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