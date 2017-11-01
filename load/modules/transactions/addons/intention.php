<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Обработка намерений в транзакциях.

class cIntention 
{
	// Публикация группы намерений к банкнотам BILL
	// n - Номер банкноты
	// p - Публичный ключ банкноты
	// i - Намерение банкноты
	public function bill_group_intention ($arGroup, $compile = false)
	{
		$wrong_items = false;
		$output = false;
		if (!is_array($arGroup)) 
		{
			console_line('Ошибочно указан формат намерения.', 2, 'error');
			$wrong_items = true;
		}
		else 
		{
			// Проверка всех намерений группы
			foreach ($arGroup as $iGroup) 
			{
				$intention = $this->bill_add_intention ($iGroup['n'], $iGroup['p'], $iGroup['i'], false);
				if (!$intention) $wrong_items = true;
			}
			// Выполнение задания
			if ((!$wrong_items)&&($compile)) 
			{
				foreach ($arGroup as $iGroup) $this->bill_add_intention ($iGroup['n'], $iGroup['p'], $iGroup['i'], true);
				console_line('Вся группа намерений успешно опубликована.', 2, 'success');
				$output = true;
			}
			elseif ((!$wrong_items)&&(!$compile)) 
			{
				console_line('Вся группа намерений может быть опубликована.', 2, 'success');
				$output = true;
			}
			else 
			{
				console_line('Ошибка при публикации группы намрений.', 2, 'error');
			}
		}
		return $output;
	}

	// Публикация одиночного намерения к банкноте BILL
	public function bill_add_intention ($number, $pubkey, $intention, $compile = false)
	{
		$wrong_items = false;
		if ((!is_string($number))||(!is_alphabet($pubkey))||(!is_alphabet($intention))) 
		{
			console_line('Ошибочно указан формат намерения.', 2, 'error');
			$wrong_items = true;
			return false;
		}
		else 
		{
			console_line('Формат намерения указан верно.', 2);
			$base = new cBase;
			$bill = $base->bill_get_row($number);
			if (!empty($bill)) 
			{
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
					console_line('Публичный ключ успешно прошел проверку.', 2);
				}
				else 
				{
					console_line('Неверно указан публичный ключ.', 2, 'error');
					$wrong_items = true;
					return false;
				}
				// Проверка намерения на наличие дубликатов
				$intentions = $base->get_intentions($number);
				if (is_array($intentions)) 
				{
					foreach ($base->get_intentions($number) as $iIntentions) 
					{
						if (($iIntentions['goal']==$number)&&($iIntentions['pubkey']==$pubkey)&&($iIntentions['intention']==$intention)) 
						{
							console_line('Данное намерение уже опубликовано.', 2, 'error');
							$wrong_items = true;
							return false;
						}
					}
				}
				else 
				{
					console_line('Данное намерение еще не опубликовано.', 2);
				}
				// Выполнение задания
				if (!$wrong_items) 
				{
					console_line('Все проверки успешно пройдены.', 2);
					if ($compile) 
					{
						$base->add_intention($number, $pubkey, $intention);
						console_line('Намерение успешно опубликовано.', 2, 'success');
						return true;
					}
					else 
					{
						console_line('Публикация намерения допустима.', 2, 'success');
						return true;
					}
				}
				else 
				{
					console_line('Ошибка при публикации намрения.', 2, 'error');
					return false;
				}
			}
			else 
			{
				console_line('Намерение не может быть опубликовано для несуществующей банкноты.', 2, 'error');
				return false;
			}
		}
	}
}
?>