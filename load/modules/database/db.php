<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// q - query. Выполнение SQL запроса к локальной базе данных
function q ($sql) 
{
	console_line('Подключение к базе данных...', 0);
	$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if ($db->connect_error) 
	{
		console_line('Невозможно подключиться к базе данных: '.$db->connect_error, 0, 'error');
		$db->close();
		return false;
	} 
	else 
	{
		console_line('Успешное подключение к базе данных.', 0);
		$result = $db->query($sql);
		if ($db->error) 
		{
			console_line('Невозможно выполнить запрос: '.$sql, 0, 'error');
			$db->close();
			return false;
		} 
		else 
		{
			if (!empty($result)) 
			{
				$db->close();
				return $result;
			} 
			else 
			{
				$db->close();
				return false;
			}
		}
	}
}

// Вспомогательный класс для выполнения специализированных запросов к базе данных в транзакциях.
// Используется для ведения локального учета допустимости той или иной транзакции.
$base = new cBase;

class cBase 
{
	// Извлечение записи о банкноте форка BILL (BILL get row)
	public function bill_get_row ($number)
	{
		if ((!is_string($number))||(empty($number))) 
		{
			console_line('Ошибочный формат обращения к базе данных.', 1, 'error');
			return false;
		}
		else 
		{
			$arBill = array();
			$sql =	'SELECT * FROM bill_bills WHERE number=\''.$number.'\'';
			$bills = q($sql);
			if (!empty($bills)) 
			{
				$arBill = $bills->fetch_assoc();
				if (!empty($arBill)) 
				{
					console_line('<b>'.$number.':</b> данные получены.', 1);
					return $arBill;
				}
				else 
				{
					console_line('<b>'.$number.':</b> банкнота не найдена.', 1);
					return false;
				}
			}
			else 
			{
				console_line('<b>'.$number.':</b> ошибка при поиске банкноты.', 1);
				return false;
			}
		}
	}
	// Изменение владельца банкноты для форка BILL (BILL change owner)
	// Выполняется через замену подписи, алгоритма шифрования и таймштампа.
	public function bill_change_owner ($number, $sign, $algorithm, $timestamp)
	{
		if ((!is_string($number))||(empty($number))||(!is_string($sign))||(empty($sign))||(!is_string($algorithm))||(empty($algorithm))||(!is_timestamp($timestamp))) 
		{
			console_line('Ошибочный формат обращения к базе данных.', 1, 'error');
			return false;
		}
		else 
		{
			if (!empty($this->bill_get_row($number))) 
			{
				$sql =	'UPDATE bill_bills SET '
						.'sign = \''.$sign.'\''
						.', algorithm = \''.$algorithm.'\''
						.', timestamp = \''.$timestamp.'\''
						.' WHERE number = \''.$number.'\';';
				q($sql);
				console_line('<b>'.$number.':</b> пароль банкноты обновлен.', 1);
				return true;
			}
			else 
			{
				console_line('<b>'.$number.':</b> банкнота не может быть обновлена.', 1, 'error');
				return false;
			}
		}
	}
	// Удаление банкноты в форке BILL. (BILL remove bill)
	// В самих транзакциях проводится одновременно с созданием другой банкноты (или группы банкнот). 
	// Например, при объединении банкнот или их разделении.
	// За сохранение суммы номиналов отвечает алгоритм обработки транзакции
	public function bill_remove_bill ($number)
	{
		if ((!is_string($number))||(empty($number))) 
		{
			console_line('Ошибочный формат обращения к базе данных.', 1, 'error');
			return false;
		}
		else 
		{
			if (!empty($this->bill_get_row($number))) 
			{
				$sql = 'DELETE FROM bill_bills WHERE number=\''.$number.'\';';
				q($sql);
				console_line('<b>'.$number.':</b> банкнота удалена.', 1);
				return true;
			}
			else 
			{
				console_line('<b>'.$number.':</b> невозможно удалить банкноту.', 1, 'error');
				return false;
			}
		}
	}
	// Создание банкноты в форке BILL. (BILL create bill)
	// В самих транзакциях проводится одновременно с удалением другой банкноты (или группы банкнот). 
	// Например, при объединении банкнот или их разделении.
	// За сохранение суммы номиналов отвечает алгоритм обработки транзакции
	public function bill_create_bill ($number, $sign, $algorithm, $denomination, $timestamp)
	{
		if ((!is_string($number))||(empty($number))||(!is_string($sign))||(empty($sign))||(!is_string($algorithm))||(empty($algorithm))||($denomination<0)||(empty($denomination))||(!is_timestamp($timestamp))) 
		{
			console_line('Ошибочный формат обращения к базе данных.', 1, 'error');
			return false;
		}
		else 
		{
			if (empty($this->bill_get_row($number))) 
			{
				$sql =	'INSERT INTO bill_bills (number, sign, algorithm, denomination, timestamp) VALUES ('
						.'\''.$number.'\','
						.'\''.$sign.'\','
						.'\''.$algorithm.'\','
						.'\''.$denomination.'\','
						.'\''.$timestamp.'\')';
				q($sql);
				console_line('<b>'.$number.':</b> банкнота успешно создана.', 1);
				return true;
			}
			else 
			{
				console_line('<b>'.$number.':</b> банкнота с этим номером существует.', 1, 'error');
				return false;
			}
		}
	}
	// Извлечение записи о намерениях
	public function get_intentions ($goal)
	{
		if ((!is_string($goal))||(empty($goal))) 
		{
			console_line('Ошибочный формат обращения к базе данных.', 1, 'error');
			return false;
		}
		else 
		{
			$arIntentions = array();
			$sql =	'SELECT * FROM intentions WHERE goal=\''.$goal.'\'';
			$intentions = q($sql);
			if (!empty($intentions)) 
			{
				while ($iIntentions = $intentions->fetch_assoc()) 
				{
					array_push($arIntentions, $iIntentions);
				}
				if (!empty($arIntentions)) 
				{
					console_line('<b>'.$goal.':</b> намерения получены.', 1);
					return $arIntentions;
				}
				else 
				{
					console_line('<b>'.$goal.':</b> намерения не найдены.', 1);
					return false;
				}
			}
			else 
			{
				console_line('<b>'.$goal.':</b> ошибка при поиске намерений.', 1);
				return false;
			}
		}
	}
	// Добавление записи о намерениях для цели
	// Вся обработка намерений происходит в транзакциях и подмодуле намерения
	public function add_intention ($goal, $pubkey, $intention)
	{
		if ((!is_string($goal))||(empty($goal))||(!is_string($pubkey))||(empty($pubkey))||(!is_string($intention))||(empty($intention))) 
		{
			console_line('Ошибочный формат обращения к базе данных.', 1, 'error');
			return false;
		}
		else 
		{
			$sql =	'INSERT INTO intentions (goal, pubkey, intention) VALUES (\''.$goal.'\',\''.$pubkey.'\',\''.$intention.'\')';
			q($sql);
			console_line('Намерение для <b>'.$goal.'</b> опубликовано.', 1);
			return true;
		}
	}
	// Очистка записей о намерениях для цели
	// Вся обработка намерений происходит в транзакциях и подмодуле намерения
	public function empty_intentions ($goal)
	{
		if ((!is_string($goal))||(empty($goal))) 
		{
			console_line('Ошибочный формат обращения к базе данных.', 1, 'error');
			return false;
		}
		else 
		{
			if (!empty($this->get_intentions($goal))) 
			{
				$sql =	'DELETE FROM intentions WHERE goal=\''.$goal.'\';';
				q($sql);
				console_line('Намерения освобождены для <b>'.$goal.'</b>.', 1);
				return true;
			}
			else 
			{
				return false;
			}
		}
	}
}
?>