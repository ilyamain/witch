<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта

// Загрузка параметров модуля
module_config (__DIR__);

class cTransactions 
{
	// Смена владельца банкноты (BILL change owner)
	public function bill_change_owner ($number, $old_key, $new_sign, $new_algorithm, $new_timestamp, $fee, $compile = false)
	{
		$transaction_entity = array
		(
			$number, 
			$old_key, 
			$new_sign, 
			$new_algorithm, 
			$new_timestamp, 
			$fee, 
		);
		$transaction_json = transaction_code('bco', $transaction_entity);
		if ((!is_string($number))||(!is_string($old_key))||(!is_alphabet($new_sign))||(!is_alphabet($new_algorithm))||(!is_timestamp($new_timestamp))||(!is_denomination($fee))) 
		{
			console_line('Невозможно прочитать транзакцию. Неправильный формат.', 2, 'error');
			return false;
		}
		else 
		{
			$wrong_items = false;
			$base = new cBase;
			$old = $base->bill_get_row($number);
			// Проверка банкноты
			if (!empty($old)) 
			{
				// Шифрование и пароль
				$bill_example = array
				(
					'number' => $number,
					'key' => $old_key,
					'algorithm' => $old['algorithm'],
					'denomination' => to_cent($old['denomination']),
					'timestamp' => $old['timestamp'],
					'entity' => $transaction_json,
					'sign' => $old['sign'],
				);
				if ((new cEncrypt($bill_example))->sign_proper) 
				{
					console_line('Пароль успешно прошел проверку.', 2);
				}
				else 
				{
					console_line('Транзакция отклонена. Пароль к банкноте указан неверно.', 2, 'error');
					$wrong_items = true;
					return false;
				}
				// Проверка комиссии
				if (to_cent($fee) < to_cent($old['denomination'])) 
				{
					console_line('Указаный размер комиссии допустим.', 2);
				}
				else 
				{
					console_line('Транзакция отклонена. Указан недопустимый размер комиссии.', 2, 'error');
					$wrong_items = true;
					return false;
				}
				// Временная метка
				if (($old['timestamp'] < $new_timestamp)||($new_timestamp == 0)) 
				{
					console_line('Временная метка транзакции допустима.', 2);
				}
				else 
				{
					console_line('Транзакция отклонена. Указана недопустимая временная метка.', 2, 'error');
					$wrong_items = true;
					return false;
				}
				// Проверка намерений.
				$arIntentions = $base->get_intentions($number);
				if (empty($arIntentions)) 
				{
					console_line('Обременяющие намерения на банкноте отсутствуют.', 2);
				}
				else 
				{
					$current = array();
					$current['args'] = $transaction_entity;
					$current['example'] = array
					(
						'number' => $number,
						'key' => $old_key,
						'algorithm' => $old['algorithm'],
						'denomination' => to_cent($old['denomination']),
						'timestamp' => $old['timestamp'],
						'entity' => $transaction_json,
					);
					$current['intention'] = transaction_code('bco', $current['args'], $current['example']);
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
						console_line('Намерение выполнения этой транзакции найдено', 2, 'success');
					}
					else 
					{
						console_line('Подходящее намерение в списке не найдено', 2, 'error');
						$wrong_items = true;
						return false;
					}
				}
			}
			else 
			{
				console_line('Транзакция отклонена. В базе данных отсутствует банкнота с номером <b>'.$number.'</b>.', 2, 'error');
				$wrong_items = true;
				return false;
			}
			// Проверка нового алгоритма шифрования
			if ((new cEncrypt(['algorithm'=>$new_algorithm]))->algorithm) 
			{
				console_line('Алгоритм шифрования для банкноты <b>'.$number.'</b> указан верно.', 2);
			}
			else 
			{
				console_line('Новый алгоритм шифрования не найден. Попробуйте обновить программу '.PROGRAM_NAME.'.', 2, 'error');
				$wrong_items = true;
				return false;
			}
			// Выполнение задания
			if (!$wrong_items) 
			{
				console_line('Все проверки успешно пройдены.', 2);
				if ($compile) 
				{
					console_line('Меняем пароль банкноты...', 2);
					$base->empty_intentions($number);
					$base->bill_change_owner($number, $new_sign, $new_algorithm, $new_timestamp, $fee);
					return true;
				}
				else 
				{
					console_line('Транзакция смены пароля банкноты допустима.', 2, 'success');
					return true;
				}
			}
			else 
			{
				console_line('Транзакция отклонена. Банкнота не прошла проверку.', 2, 'error');
				return false;
			}
		}
	}
	// Соединение банкнот (BILL unite)
	// n - Номер каждой соединяемой банкноты
	// k - Пароль к каждой соединяемой банкноте
	public function bill_unite ($arBills, $number, $sign, $algorithm, $timestamp, $fee, $compile = false)
	{
		$transaction_entity = array
		(
			$arBills, 
			$number, 
			$sign, 
			$algorithm, 
			$timestamp, 
			$fee, 
		);
		$transaction_json = transaction_code('bu', $transaction_entity);
		if ((!is_array($arBills))||(!is_alphabet($number))||(!is_alphabet($sign))||(!is_alphabet($algorithm))||(!is_timestamp($timestamp))||(!is_denomination($fee))) 
		{
			console_line('Невозможно прочитать транзакцию. Неправильный формат.', 2, 'error');
			return false;
		}
		else 
		{
			$arNumbers = array();
			$wrong_items = false;
			$sum = 0;
			usort($arBills, 'sort_numbers');
			$base = new cBase;
			// Проверка старых банкнот
			foreach ($arBills as $iBills) 
			{
				// Проверка каждой старой банкноты
				$old = $base->bill_get_row($iBills['n']);
				if (!empty($old)) 
				{
					// Шифрование и пароль
					$bill_example = array
					(
						'number' => $iBills['n'], 
						'key' => $iBills['k'], 
						'algorithm' => $old['algorithm'], 
						'denomination' => to_cent($old['denomination']),
						'timestamp' => $old['timestamp'],
						'entity' => $transaction_json,
						'sign' => $old['sign'],
					);
					if ((new cEncrypt($bill_example))->sign_proper) 
					{
						console_line('Пароль успешно прошел проверку.', 2);
					}
					else 
					{
						console_line('Транзакция отклонена. Пароль к банкноте указан неверно.', 2, 'error');
						$wrong_items = true;
						return false;
					}
					// Временная метка
					if (($old['timestamp'] < $timestamp)||($timestamp == 0)) 
					{
						console_line('Временная метка транзакции допустима.', 2);
						$sum += to_cent($old['denomination']);
						array_push($arNumbers, $iBills['n']);
					}
					else 
					{
						console_line('Транзакция отклонена. Указана недопустимая временная метка.', 2, 'error');
						$wrong_items = true;
						return false;
					}
					// Проверка намерений.
					$arIntentions = $base->get_intentions($iBills['n']);
					if (empty($arIntentions)) 
					{
						console_line('Обременяющие намерения на банкноте отсутствуют.', 2);
					}
					else 
					{
						$current = array();
						$current['args'] = $transaction_entity;
						$current['example'] = array
						(
							'number' => $iBills['n'], 
							'key' => $iBills['k'], 
							'algorithm' => $old['algorithm'],
							'denomination' => to_cent($old['denomination']),
							'timestamp' => $old['timestamp'],
							'entity' => $transaction_json,
						);
						$current['intention'] = transaction_code('bu', $current['args'], $current['example']);
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
							console_line('Намерение выполнения этой транзакции найдено', 2, 'success');
						}
						else 
						{
							console_line('Подходящее намерение в списке не найдено', 2, 'error');
							$wrong_items = true;
							return false;
						}
					}
				}
				else 
				{
					console_line('Транзакция отклонена. В базе данных отсутствует банкнота с номером <b>'.$iBills['n'].'</b>.', 2, 'error');
					$wrong_items = true;
					return false;
				}
			}
			// Проверка комиссии
			if (to_cent($fee) < to_cent($sum)) 
			{
				console_line('Указаный размер комиссии допустим.', 2);
			}
			else 
			{
				console_line('Транзакция отклонена. Указан недопустимый размер комиссии.', 2, 'error');
				$wrong_items = true;
				return false;
			}
			// Вычитание комиссии
			$sum = $sum-$fee;
			// Проверка новой банкноты
			$new = $base->bill_get_row($number);
			if (empty($new)) 
			{
				console_line('Номер банкноты не занят.', 2);
			}
			else 
			{
				console_line('Транзакция отклонена. Банкнота <b>'.$number.'</b> уже существует.', 2, 'error');
				$wrong_items = true;
				return false;
			}
			if ((new cEncrypt(['algorithm'=>$algorithm]))->algorithm) 
			{
				console_line('Алгоритм шифрования для банкноты <b>'.$iBills['n'].'</b> указан верно.', 2);
			}
			else 
			{
				console_line('Новый алгоритм шифрования не найден. Попробуйте обновить программу '.PROGRAM_NAME.'.', 2, 'error');
				$wrong_items = true;
				return false;
			}
			// Проверка уникальности номеров всех банкнот
			array_push($arNumbers, $number);
			if ($arNumbers != array_unique($arNumbers)) 
			{
				console_line('Транзакция отклонена. Обнаружены одинаковые номера банкнот.', 2, 'error');
				$wrong_items = true;
				return false;
			}
			// Выполнение задания
			if (!$wrong_items) 
			{
				console_line('Все проверки успешно пройдены.', 2);
				if ($compile) 
				{
					$base->bill_create_bill($number, $sign, $algorithm, $sum, $timestamp);
					foreach ($arBills as $iBills) 
					{
						$base->empty_intentions($iBills['n']);
						$base->bill_remove_bill($iBills['n']);
					}
					console_line('Банкноты объединены.', 2, 'success');
					return true;
				}
				else 
				{
					console_line('Транзакция объединения банкнот допустима.', 2, 'success');
					return true;
				}
			}
			else 
			{
				console_line('Транзакция отклонена. Некоторые банкноты не прошли проверку.', 2, 'error');
				return false;
			}
		}
	}
	// Размен банкноты (BILL split)
	// a - Алгоритм шифрования каждой новой банкноты
	// n - Номер каждой новой банкноты
	// d - Номинал каждой новой банкноты
	// s - Подпись каждой новой банкноты
	public function bill_split ($old_number, $old_key, $arBills, $timestamp, $fee, $compile = false)
	{
		$transaction_entity = array
		(
			$old_number, 
			$old_key, 
			$arBills, 
			$timestamp, 
			$fee, 
		);
		$transaction_json = transaction_code('bs', $transaction_entity);
		if ((!is_string($old_number))||(!is_string($old_key))||(!is_array($arBills))||(!is_timestamp($timestamp))||(!is_denomination($fee))) 
		{
			console_line('Невозможно прочитать транзакцию. Неправильный формат.', 2, 'error');
			return false;
		}
		else 
		{
			$wrong_items = false;
			$sum = $fee;
			$arNumbers = array();
			usort($arBills, 'sort_numbers');
			$base = new cBase;
			$old = $base->bill_get_row($old_number);
			// Проверка старой банкноты
			if (!empty($old)) 
			{
				// Шифрование и пароль
				$bill_example = array
				(
					'number' => $old_number,
					'key' => $old_key,
					'algorithm' => $old['algorithm'],
					'denomination' => to_cent($old['denomination']),
					'timestamp' => $old['timestamp'],
					'entity' => $transaction_json,
					'sign' => $old['sign'],
				);
				if ((new cEncrypt($bill_example))->sign_proper) 
				{
					console_line('Пароль успешно прошел проверку.', 2);
				}
				else 
				{
					console_line('Транзакция отклонена. Пароль к банкноте указан неверно.', 2, 'error');
					$wrong_items = true;
					return false;
				}
				// Временная метка
				if (($old['timestamp'] < $timestamp)||($timestamp == 0)) 
				{
					console_line('Временная метка транзакции допустима.', 2);
				}
				else 
				{
					console_line('Транзакция отклонена. Указана недопустимая временная метка.', 2, 'error');
					$wrong_items = true;
					return false;
				}
				// Проверка намерений.
				$arIntentions = $base->get_intentions($old_number);
				if (empty($arIntentions)) 
				{
					console_line('Обременяющие намерения на банкноте отсутствуют.', 2);
				}
				else 
				{
					$current = array();
					$current['args'] = $transaction_entity;
					$current['example'] = array
					(
						'number' => $old_number,
						'key' => $old_key,
						'algorithm' => $old['algorithm'],
						'denomination' => to_cent($old['denomination']),
						'timestamp' => $old['timestamp'],
						'entity' => $transaction_json,
					);
					$current['intention'] = transaction_code('bs', $current['args'], $current['example']);
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
						console_line('Намерение выполнения этой транзакции найдено', 2, 'success');
					}
					else 
					{
						console_line('Подходящее намерение в списке не найдено', 2, 'error');
						$wrong_items = true;
						return false;
					}
				}
			}
			else 
			{
				console_line('Транзакция отклонена. В базе данных отсутствует банкнота с номером <b>'.$old_number.'</b>.', 2, 'error');
				$wrong_items = true;
				return false;
			}
			// Проверка новых банкнот
			foreach ($arBills as $iBills) 
			{
				if ((!is_alphabet($iBills['n']))||(!is_alphabet($iBills['s']))||(!is_alphabet($iBills['a']))) 
				{
					console_line('Недопустимый формат банкноты.', 2, 'error');
					$wrong_items = true;
					return false;
				}
				else 
				{
					console_line('Подходящий формат банкноты.', 2);
				}
				if ((float_equals($iBills['d'], 0))||($iBills['d']<0)) 
				{
					console_line('Недопустимый номинал банкноты.', 2, 'error');
					$wrong_items = true;
					return false;
				}
				else 
				{
					console_line('Подходящий номинал банкноты.', 2);
				}
				$new = $base->bill_get_row($iBills['n']);
				if (empty($new)) 
				{
					console_line('Номер банкноты не занят.', 2);
					$sum += $iBills['d'];
					array_push($arNumbers, $iBills['n']);
				}
				else 
				{
					console_line('Транзакция отклонена. Банкнота <b>'.$iBills['n'].'</b> уже существует.', 2, 'error');
					$wrong_items = true;
					return false;
				}
				if ((new cEncrypt(['algorithm'=>$iBills['a']]))->algorithm) 
				{
					console_line('Алгоритм шифрования для банкноты <b>'.$iBills['n'].'</b> указан верно.', 2);
				}
				else 
				{
					console_line('Новый алгоритм шифрования не найден. Попробуйте обновить программу '.PROGRAM_NAME.'.', 2, 'error');
					$wrong_items = true;
					return false;
				}
			}
			// Проверка комиссии
			if (to_cent($fee) < to_cent($old['denomination'])) 
			{
				console_line('Указаный размер комиссии допустим.', 2);
			}
			else 
			{
				console_line('Транзакция отклонена. Указан недопустимый размер комиссии.', 2, 'error');
				$wrong_items = true;
				return false;
			}
			// Проверка сохранения сумм
			if (!float_equals($old['denomination'], $sum)) 
			{
				console_line('Транзакция отклонена. Указаны неверные номиналы банкнот.', 2, 'error');
				$wrong_items = true;
				return false;
			}
			// Проверка уникальности номеров всех банкнот
			array_push($arNumbers, $old_number);
			if ($arNumbers != array_unique($arNumbers)) 
			{
				console_line('Транзакция отклонена. Обнаружены одинаковые номера банкнот.', 2, 'error');
				$wrong_items = true;
				return false;
			}
			// Выполнение задания
			if (!$wrong_items) 
			{
				console_line('Все проверки успешно пройдены.', 2);
				if ($compile) 
				{
					foreach ($arBills as $iBills) 
					{
						$base->bill_create_bill($iBills['n'], $iBills['s'], $iBills['a'], $iBills['d'], $timestamp);
					}
					$base->empty_intentions($old_number);
					$base->bill_remove_bill($old_number);
					console_line('Банкнота разменяна.', 2, 'success');
					return true;
				}
				else 
				{
					console_line('Транзакция размена банкнот допустима.', 2, 'success');
					return true;
				}
			}
			else 
			{
				console_line('Транзакция отклонена. Некоторые банкноты не прошли проверку.', 2, 'error');
				return false;
			}
		}
	}
	// Перемешивание банкнот (BILL resort)
	// $arOld[$key]['n'] - Номер каждой старой банкноты
	// $arOld[$key]['k'] - Пароль каждой старой банкноты
	// $arNew[$key]['a'] - Алгоритм шифрования каждой новой банкноты
	// $arNew[$key]['n'] - Номер каждой новой банкноты
	// $arNew[$key]['d'] - Номинал каждой новой банкноты
	// $arNew[$key]['s'] - Подпись каждой новой банкноты
	public function bill_resort ($arOld, $arNew, $timestamp, $fee, $compile = false)
	{
		$transaction_entity = array
		(
			$arOld, 
			$arNew, 
			$timestamp, 
			$fee, 
		);
		$transaction_json = transaction_code('br', $transaction_entity);
		if ((!is_array($arOld))||(!is_array($arNew))||(!is_timestamp($timestamp))||(!is_denomination($fee))) 
		{
			console_line('Невозможно прочитать транзакцию. Неправильный формат.', 2, 'error');
			return false;
		}
		else 
		{
			$base = new cBase;
			$wrong_items = false;
			$old_sum = 0;
			$new_sum = $fee;
			$arNumbers = array();
			usort($arOld, 'sort_numbers');
			usort($arNew, 'sort_numbers');
			// Проверка старых банкнот
			foreach ($arOld as $iOld) 
			{
				// Проверка каждой старой банкноты
				$old = $base->bill_get_row($iOld['n']);
				if (!empty($old)) 
				{
					// Шифрование и пароль
					$bill_example = array
					(
						'number' => $iOld['n'],
						'key' => $iOld['k'],
						'algorithm' => $old['algorithm'],
						'denomination' => to_cent($old['denomination']),
						'timestamp' => $old['timestamp'],
						'entity' => $transaction_json,
						'sign' => $old['sign'],
					);
					if ((new cEncrypt($bill_example))->sign_proper) 
					{
						console_line('Пароль успешно прошел проверку.', 2);
					}
					else 
					{
						console_line('Транзакция отклонена. Пароль к банкноте указан неверно.', 2, 'error');
						$wrong_items = true;
						return false;
					}
					// Временная метка
					if (($old['timestamp'] < $timestamp)||($timestamp == 0)) 
					{
						console_line('Временная метка транзакции допустима.', 2);
						$old_sum += to_cent($old['denomination']);
						array_push($arNumbers, $iOld['n']);
					}
					else 
					{
						console_line('Транзакция отклонена. Указана недопустимая временная метка.', 2, 'error');
						$wrong_items = true;
						return false;
					}
					// Проверка намерений.
					$arIntentions = $base->get_intentions($iOld['n']);
					if (empty($arIntentions)) 
					{
						console_line('Обременяющие намерения на банкноте отсутствуют.', 2);
					}
					else 
					{
						$current = array();
						$current['args'] = $transaction_entity;
						$current['example'] = array
						(
							'number' => $iOld['n'], 
							'key' => $iOld['k'], 
							'algorithm' => $old['algorithm'],
							'denomination' => to_cent($old['denomination']),
							'timestamp' => $old['timestamp'],
							'entity' => $transaction_json,
						);
						$current['intention'] = transaction_code('br', $current['args'], $current['example']);
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
							console_line('Намерение выполнения этой транзакции найдено', 2, 'success');
						}
						else 
						{
							console_line('Подходящее намерение в списке не найдено', 2, 'error');
							$wrong_items = true;
							return false;
						}
					}
				}
				else 
				{
					console_line('Транзакция отклонена. В базе данных отсутствует банкнота с номером <b>'.$iOld['n'].'</b>.', 2, 'error');
					$wrong_items = true;
					return false;
				}
			}
			// Проверка новых банкнот
			foreach ($arNew as $iNew) 
			{
				if ((!is_alphabet($iNew['n']))||(!is_alphabet($iNew['s']))||(!is_alphabet($iNew['a']))) 
				{
					console_line('Недопустимый формат банкноты.', 2, 'error');
					$wrong_items = true;
					return false;
				}
				else 
				{
					console_line('Подходящий формат банкноты.', 2);
				}
				if ((float_equals($iNew['d'], 0))||($iNew['d']<0)) 
				{
					console_line('Недопустимый номинал банкноты.', 2, 'error');
					$wrong_items = true;
					return false;
				}
				else 
				{
					console_line('Подходящий номинал банкноты.', 2);
				}
				$new = $base->bill_get_row($iNew['n']);
				if (empty($new)) 
				{
					console_line('Номер банкноты не занят.', 2);
					$new_sum += $iNew['d'];
					array_push($arNumbers, $iNew['n']);
				}
				else 
				{
					console_line('Транзакция отклонена. Банкнота <b>'.$iNew['n'].'</b> уже существует.', 2, 'error');
					$wrong_items = true;
					return false;
				}
				if ((new cEncrypt(['algorithm'=>$iNew['a']]))->algorithm) 
				{
					console_line('Алгоритм шифрования для банкноты <b>'.$iNew['n'].'</b> указан верно.', 2);
				}
				else 
				{
					console_line('Новый алгоритм шифрования не найден. Попробуйте обновить программу '.PROGRAM_NAME.'.', 2, 'error');
					$wrong_items = true;
					return false;
				}
			}
			// Проверка комиссии
			if (to_cent($fee) < to_cent($old_sum)) 
			{
				console_line('Указаный размер комиссии допустим.', 2);
			}
			else 
			{
				console_line('Транзакция отклонена. Указан недопустимый размер комиссии.', 2, 'error');
				$wrong_items = true;
				return false;
			}
			// Проверка сохранения сумм
			if (!float_equals($new_sum, $old_sum)) 
			{
				console_line('Транзакция отклонена. Указаны неверные номиналы банкнот.', 2, 'error');
				$wrong_items = true;
				return false;
			}
			// Проверка уникальности номеров всех банкнот
			if ($arNumbers != array_unique($arNumbers)) 
			{
				console_line('Транзакция отклонена. Обнаружены одинаковые номера банкнот.', 2, 'error');
				$wrong_items = true;
				return false;
			}
			// Выполнение задания
			if (!$wrong_items) 
			{
				console_line('Все проверки успешно пройдены.', 2);
				if ($compile) 
				{
					foreach ($arNew as $iNew) 
					{
						$base->bill_create_bill($iNew['n'], $iNew['s'], $iNew['a'], $iNew['d'], $timestamp);
					}
					foreach ($arOld as $iOld) 
					{
						$base->empty_intentions($iOld['n']);
						$base->bill_remove_bill($iOld['n']);
					}
					console_line('Банкноты перемешаны.', 2, 'success');
					return true;
				}
				else 
				{
					console_line('Транзакция перемешивания банкнот допустима.', 2, 'success');
					return true;
				}
			}
			else 
			{
				console_line('Транзакция отклонена. Некоторые банкноты не прошли проверку.', 2, 'error');
				return false;
			}
		}
	}
}
?>