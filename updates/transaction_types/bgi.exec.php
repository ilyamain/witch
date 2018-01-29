<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Публикация одиночного намерения к банкноте (BAI - bill add intention)

$wrong_items = false;
// Задание базовых параметров намерения:
// 'ok' - если намерения допустимо
// 'is' - задание является намерением
// 'json' - запрошенное намерение в формате JSON изначально пусто
$output['ok'] = false;
$output['is'] = 'intention';
$output['json'] = '';
$output['type'] = 'bgi';
// В случае возникновения ошибок при проверке намерения, задаем изначальное 
// состояние входных и выходных банкнот, силы намерения и размера комиссии
$output['fee'] = to_cent(0);
$output['denomination'] = to_cent(0);
$output['number'] = array();
$output['output'] = array();
$base = new cBase;
if (is_array($input)) 
{
	// Сортировка массивов
	usort($input, function ($item_a, $item_b) {return strnatcmp($item_a['0'], $item_b['0']);});
	foreach ($input as $key => $item) if (is_array($input[$key])) ksort($input[$key]); else $wrong_items = true;
	$output['json'] = transaction_code($transaction_name, $input);
	$output['entity'] = json_encode(array_map('to_string', $input));
	write('Формат намерения указан верно.', 2);
	$arNumbers = array();
	// Проверка всех намерений группы
	foreach ($input as $item) 
	{
		$number = $item[0];
		$pubkey = $item[1];
		$intention = $item[2];
		array_push($arNumbers, $number);
		if ((is_string($number))&&(!empty($number))&&(is_alphabet($pubkey))&&(is_alphabet($intention))) 
		{
			write('Формат намерения указан верно.', 2);
			$bill = $base->bill_get($number);
			if (!empty($bill)) 
			{
				$sum += to_cent($bill['denomination']);
				// Проверка публичного ключа
				$bill_example = array
				(
					'number' => $number, 
					'pubkey' => $pubkey, 
					'algorithm' => $bill['algorithm'], 
					'denomination' => to_cent($bill['denomination']), 
					'timestamp' => $bill['timestamp'], 
					'entity' => $intention, 
					'entity_encrypted' => true, 
					'sign' => $bill['sign'], 
				);
				$encrypt = new cEncrypt($bill_example);
				if ($encrypt->sign_proper) 
				{
					write('Публичный ключ успешно прошел проверку.', 2);
				}
				else 
				{
					write('Неверно указан публичный ключ.', 2, 'error');
					$wrong_items = true;
				}
				// Проверка намерения на наличие дубликатов
				$intentions = $base->intentions_get($number);
				if ((is_array($intentions))&&(!empty($intentions))) 
				{
					foreach ($intentions as $iIntentions) 
					{
						if (($iIntentions['goal'] == $number)&&($iIntentions['pubkey'] == $pubkey)&&($iIntentions['intention'] == $intention)) 
						{
							write('Данное намерение уже опубликовано.', 2, 'error');
							$wrong_items = true;
						}
					}
				}
				else 
				{
					write('Данное намерение еще не опубликовано.', 2);
				}
			}
			else 
			{
				write('Намерение не может быть опубликовано для несуществующей банкноты.', 2, 'error');
				$wrong_items = true;
			}
		}
		else 
		{
			write('Ошибочно указан формат намерения.', 2, 'error');
			$wrong_items = true;
		}
	}
	$output['number'] = $arNumbers; // список всех банкнот группы намерений
	$output['output'] = $arNumbers; // список всех банкнот группы намерений
	$output['denomination'] = to_cent($sum); // сила намерения - сумма номиналов всех банкнот
	// Проверка уникальности номеров всех банкнот
	if ($arNumbers != array_unique($arNumbers)) 
	{
		write('Группа намерений отклонена. Обнаружены одинаковые номера банкнот.', 2, 'error');
		$wrong_items = true;
	}
}
else 
{
	write('Ошибочно указан формат намерения.', 2, 'error');
	$wrong_items = true;
}

// Выполнение задания при отсутствии ошибок
if (!$wrong_items) 
{
	write('Все проверки успешно пройдены.', 2);
	if ($compile) 
	{
		foreach ($input as $item) 
		{
			$number = $item[0];
			$pubkey = $item[1];
			$intention = $item[2];
			$base->intention_add($number, $pubkey, $intention);
		}
		write('Вся группа намерений успешно опубликована.', 2, 'success');
	}
	else 
	{
		write('Вся группа намерений может быть опубликована.', 2, 'success');
	}
	$output['ok'] = true;
}
else 
{
	write('Ошибка при публикации группы намерений.', 2, 'error');
	$output['ok'] = false;
}

// Вывод параметров выполнения намерения
return $output;
?>