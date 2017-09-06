<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Обработка намерений в транзакциях.

class cIntention 
{
	// Публикация намерения к банкноте BILL
	public function bill_add_intention ($number, $pubkey, $intention)
	{
		$wrong_items = false;
		$base = new cBase;
		$bill = $base->bill_get_row($number);
		if (!empty($bill))
		{
			$bill_example = array
			(
				'number' => $number,
				'pubkey' => $pubkey,
				'algorithm' => $bill['algorithm'],
				'denomination' => $bill['denomination'],
				'timestamp' => $bill['timestamp'],
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
			if (!$wrong_items) 
			{
				$base->add_intention($number, $pubkey, $intention);
				console_line('Намерение успешно опубликовано.', 2, 'success');
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
?>