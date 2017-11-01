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

// Вспомогательный класс для выполнения специализированных запросов к базе данных.
$base = new cBase;

class cBase 
{
	// Добавление таблицы в локальную базу данных
	public function add_tables ($arTables)
	{
		if ((!empty($arTables))&&(is_array($arTables))) 
		{
			foreach ($arTables as $table_name => $iTables) 
			{
				// Создаем таблицы базы данных
				$table_exist = q('SHOW TABLES LIKE \''.$table_name.'\'');
				if ($table_exist->num_rows==0) 
				{
					console_line('Создаем таблицу: '.$table_name, 1);
					if ((!empty($iTables))&&(is_array($iTables))) 
					{
						$sql = 'CREATE TABLE IF NOT EXISTS '.$table_name.' (';
						$row_names = '';
						foreach ($iTables as $row_name => $row_attributes) 
						{
							if ((!empty($row_name))&&(!empty($row_attributes))&&(!is_array($row_attributes))) $sql .= $row_name.' '.$row_attributes.', ';
							if ((!empty($row_name))&&(!empty($row_attributes))&&(!is_array($row_attributes))&&($row_name!='id')) $row_names .= $row_name.', ';
						}
						$row_names = substr ($row_names, 0, -2);
						$sql = substr ($sql, 0, -2).');';
						q($sql);
						// Добавляем строки
						if ((!empty($iTables['table_rows']))&&(is_array($iTables['table_rows']))) 
						{
							$sql = '';
							$row_values = '';
							foreach ($iTables['table_rows'] as $arRow) 
							{
								$row_values .= '(';
								foreach ($arRow as $iRow) 
								{
									if ($iRow =='NULL') $row_values .= $iRow.','; else $row_values .= '\''.$iRow.'\',';
								}
								$row_values = substr ($row_values, 0, -1).'),';
							}
							$row_values = substr ($row_values, 0, -1);
							$sql = 'INSERT INTO '.$table_name.' ('.$row_names.') VALUES '.$row_values;
							q($sql);
						}
					}
					else 
					{
						console_line('Невозможно прочитать формат таблиц', 1);
						return false;
					}
				}
				else 
				{
					console_line('Таблица уже существует', 1);
					return false;
				}
			}
		}
		else 
		{
			console_line('Невозможно прочитать формат таблиц', 1);
			return false;
		}
	}

	// Удаление таблицы из локальной базы данных
	public function del_tables ($arTables)
	{
		if ((!empty($arTables))&&(is_array($arTables))) 
		{
			foreach ($arTables as $table_name => $iTables) 
			{
				console_line('Удаляем: '.$table_name, 1);
				if ((!empty($iTables))&&(is_array($iTables))) 
				{
					$sql = 'DROP TABLE IF EXISTS '.$table_name.';';
					q($sql);
				}
				else 
				{
					console_line('Невозможно прочитать формат таблиц', 1);
					return false;
				}
			}
		}
		else 
		{
			console_line('Невозможно прочитать формат таблиц', 1);
			return false;
		}
	}

	// Получение локальных констант (new cBase())->local_core('miner_name')
	public function local_core ($parameter)
	{
		$output = '';
		if ((is_string($parameter))&&(!empty($parameter))) 
		{
			$arResult = array();
			$constants = q('SELECT * FROM local_core WHERE parameter=\''.$parameter.'\'');
			if (!empty($constants)) 
			{
				$arResult = $constants->fetch_assoc();
				if (!empty($arResult)) $output = $arResult['value'];
			}
		}
		return $output;
	}

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
	public function bill_change_owner ($number, $sign, $algorithm, $timestamp, $fee)
	{
		if ((!is_string($number))||(empty($number))||(!is_string($sign))||(empty($sign))||(!is_string($algorithm))||(empty($algorithm))||(!is_timestamp($timestamp))) 
		{
			console_line('Ошибочный формат обращения к базе данных.', 1, 'error');
			return false;
		}
		else 
		{
			$bill = $this->bill_get_row($number);
			if (!empty($bill)) 
			{
				$denomination = to_cent($bill['denomination']-$fee);
				if ($denomination>0) 
				{
					$sql =	'UPDATE bill_bills SET '
							.'sign = \''.$sign.'\''
							.', algorithm = \''.$algorithm.'\''
							.', timestamp = \''.$timestamp.'\''
							.', denomination = \''.$denomination.'\''
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
		if ((!is_string($number))||(empty($number))||(!is_string($sign))||(empty($sign))||(!is_string($algorithm))||(empty($algorithm))||($denomination<=0)||(!is_denomination($denomination))||(empty($denomination))||(!is_timestamp($timestamp))) 
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
				while ($iIntentions = $intentions->fetch_assoc()) array_push($arIntentions, $iIntentions);
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