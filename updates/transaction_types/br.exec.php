<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Перемешивание банкнот (BR - bill resort)
// $arOld[$key]['n'] - Номер каждой старой банкноты
// $arOld[$key]['k'] - Пароль каждой старой банкноты
// $arNew[$key]['a'] - Алгоритм шифрования каждой новой банкноты
// $arNew[$key]['n'] - Номер каждой новой банкноты
// $arNew[$key]['d'] - Номинал каждой новой банкноты
// $arNew[$key]['s'] - Подпись каждой новой банкноты

$wrong_items = false;
// Задание базовых параметров транзакции:
// 'ok' - если транзакция допустима
// 'is' - задание является транзакцией, а не намерением или эмиссией
// 'json' - запрошенная транзакция в формате JSON изначально пуста
$output['ok'] = false;
$output['is'] = 'transaction';
$output['json'] = '';
$output['type'] = 'br';
// В случае возникновения ошибок при проверке транзакции, задаем изначальное 
// состояние входных и выходных банкнот, силы транзакции и размера комиссии
$output['fee'] = to_cent(0);
$output['denomination'] = to_cent(0);
$output['number'] = array();
$output['output'] = array();
$base = new cBase;
if ((is_array($input))&&(count($input) == 4)) 
{
	$arOld = $input[0];
	$arNew = $input[1];
	$timestamp = $input[2];
	$fee = $input[3];
	$output['fee'] = to_cent($fee);
	$input_error = false;
	if (!is_array($arOld)) $input_error = true;
	if (!is_array($arNew)) $input_error = true;
	if (!is_timestamp($timestamp)) $input_error = true;
	if (!is_denomination($fee)) $input_error = true;
	if (!$input_error) 
	{
		// Сортировка массивов
		usort($arOld, 'sort_numbers'); // сортировка входных банкнот
		foreach ($arOld as $key => $item) if (is_array($arOld[$key])) ksort($arOld[$key]); else $wrong_items = true; // сортировка k,n.
		usort($arNew, 'sort_numbers'); // сортировка выходных банкнот
		foreach ($arNew as $key => $item) if (is_array($arNew[$key])) ksort($arNew[$key]); else $wrong_items = true; // сортировка a,d,n,s.
		$sorted_input = array
		(
			$arOld, 
			$arNew, 
			$timestamp, 
			$fee, 
		);
		$json = transaction_code($transaction_name, $sorted_input);
		$output['json'] = $json;
		$output['entity'] = json_encode(array_map('to_string', $sorted_input));
		$arNumbers = array();
		$old_sum = 0;
		$new_sum = $fee;
		// Проверка старых банкнот
		foreach ($arOld as $iOld) 
		{
			if ((is_string($iOld['n']))&&(is_string($iOld['k']))) 
			{
				// Проверка каждой старой банкноты
				$old = $base->bill_get($iOld['n']);
				if (!empty($old)) 
				{
					$old_sum += to_cent($old['denomination']);
					array_push($arNumbers, $iOld['n']);
					// Проверка прежнего пароля
					$bill_example = array
					(
						'number' => $iOld['n'], 
						'key' => $iOld['k'], 
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
					$arIntentions = $base->intentions_get($iOld['n']);
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
							'number' => $iOld['n'], 
							'key' => $iOld['k'], 
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
							if (($iIntentions['goal'] == $iOld['n'])&&($iIntentions['pubkey'] == $current['pubkey'])&&($iIntentions['intention'] == $current['intention'])) 
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
							write($iOld['n'].'. Подходящее намерение в списке не найдено', 2, 'error');
							$wrong_items = true;
						}
					}
				}
				else 
				{
					write('Транзакция отклонена. В базе данных отсутствует банкнота с номером <b>'.$iOld['n'].'</b>.', 2, 'error');
					$wrong_items = true;
				}
			}
			else 
			{
				write('Невозможно прочитать транзакцию. Неправильный формат старой банкноты.', 2, 'error');
				$wrong_items = true;
			}
		}
		$output['denomination'] = to_cent($old_sum); // сила транзакции - сумма номиналов всех входных банкнот
		$output['number'] = $arNumbers; // список всех входных банкнот
		// Проверка новых банкнот
		foreach ($arNew as $iNew) 
		{
			// Проверка формата записи новой банкноты
			if ((is_alphabet($iNew['n']))&&(is_alphabet($iNew['s']))&&(is_alphabet($iNew['a']))) 
			{
				write('Подходящий формат банкноты.', 2);
				array_push($arNumbers, $iNew['n']);
				array_push($output['output'], $iNew['n']);
				// Проверка занятости номера новой банкноты
				$new = $base->bill_get($iNew['n']);
				if (empty($new)) 
				{
					write('Номер банкноты не занят.', 2);
				}
				else 
				{
					write('Транзакция отклонена. Банкнота <b>'.$iNew['n'].'</b> уже существует.', 2, 'error');
					$wrong_items = true;
				}
				// Проверка существования алгоритма шифрования
				if ((new cEncrypt(['algorithm'=>$iNew['a']]))->algorithm) 
				{
					write('Алгоритм шифрования для банкноты <b>'.$iNew['n'].'</b> указан верно.', 2);
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
			if ((!float_equals($iNew['d'], 0))&&($iNew['d'] > 0)&&(is_denomination($iNew['d']))) 
			{
				write('Подходящий номинал банкноты.', 2);
				$new_sum += $iNew['d'];
			}
			else 
			{
				write('Недопустимый номинал банкноты.', 2, 'error');
				$wrong_items = true;
			}
		}
		// Проверка комиссии
		if (to_cent($fee) < to_cent($old_sum)) 
		{
			write('Указаный размер комиссии допустим.', 2);
		}
		else 
		{
			write('Транзакция отклонена. Указан недопустимый размер комиссии.', 2, 'error');
			$wrong_items = true;
		}
		// Проверка сохранения сумм
		if (!float_equals($old_sum, $new_sum)) 
		{
			write('Транзакция отклонена. Указаны неверные номиналы банкнот.', 2, 'error');
			$wrong_items = true;
		}
		// Проверка уникальности номеров всех банкнот
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
		foreach ($arNew as $iNew) 
		{
			$base->bill_add($iNew['n'], $iNew['s'], $iNew['a'], $iNew['d'], $timestamp);
		}
		foreach ($arOld as $iOld) 
		{
			$base->intentions_clear($iOld['n']);
			$base->bill_del($iOld['n']);
		}
		write('Банкноты перемешаны.', 2, 'success');
	}
	else 
	{
		write('Транзакция перемешивания банкнот допустима.', 2, 'success');
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