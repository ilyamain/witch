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
$output['type'] = 'bai';
// В случае возникновения ошибок при проверке намерения, задаем изначальное 
// состояние входных и выходных банкнот, силы намерения и размера комиссии
$output['fee'] = to_cent(0);
$output['denomination'] = to_cent(0);
$output['number'] = array();
$output['output'] = array();
$base = new cBase;
if ((is_array($input))&&(count($input) == 3)) 
{
	$number = $input[0];
	$pubkey = $input[1];
	$intention = $input[2];
	ksort($input);
	$output['json'] = transaction_code($transaction_name, $input);
	$output['entity'] = json_encode(array_map('to_string', $input));
	$input_error = false;
	if (!is_string($number)) 		$input_error = true;
	if (!is_alphabet($pubkey)) 		$input_error = true;
	if (!is_alphabet($intention)) 	$input_error = true;
	if (!$input_error) 
	{
		write('Формат намерения указан верно.', 2);
		$bill = $base->bill_get($number);
		if (!empty($bill)) 
		{
			$output['denomination'] = to_cent($bill['denomination']); // сила намерения - номинал банкноты
			array_push($output['number'], $number); // одна входная банкнота
			array_push($output['output'], $number); // одна выходная банкнота
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
		$base->intention_add($number, $pubkey, $intention);
		write('Намерение успешно опубликовано.', 2, 'success');
	}
	else 
	{
		write('Публикация намерения допустима.', 2, 'success');
	}
	$output['ok'] = true;
}
else 
{
	write('Ошибка при публикации намрения.', 2, 'error');
	$output['ok'] = false;
}

// Вывод параметров выполнения намерения
return $output;
?>