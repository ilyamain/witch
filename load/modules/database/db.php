<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Выполнение запросов к базе данных

class cBase 
{
	////////////////////////////////////////////////////////////////////////////////////////
	// Работа с бэкапами распределенных данных *********************************************
	////////////////////////////////////////////////////////////////////////////////////////
	// Создание бэкапа таблиц с распределенными данными
	public function backup_create ($goal = 'bc_', $backup = 'back_')
	{
		$result = true;
		$sql = 'SELECT table_name FROM information_schema.tables WHERE table_schema=\''.DB_NAME.'\' AND table_name LIKE \''.$goal.'%\';';
		$tables = q($sql);
		while ($table_old = $tables->fetch_assoc()) 
		{
			$table_old = $table_old['table_name'];
			$table_new = $backup.mb_substr($table_old, strlen($goal));
			$sql = array
			(
				'CREATE TABLE IF NOT EXISTS '.$table_new.' LIKE '.$table_old.';',
				'TRUNCATE TABLE '.$table_new.';',
				'INSERT INTO '.$table_new.' SELECT * FROM '.$table_old.';',
			);
			if (!q($sql)) $result = false;
		}
		return $result;
	}

	// Восстановление из бэкапа таблиц с распределенными данными
	public function backup_restore ($goal = 'bc_', $backup = 'back_')
	{
		$result = true;
		$sql = 'SELECT table_name FROM information_schema.tables WHERE table_schema=\''.DB_NAME.'\' AND table_name LIKE \''.$backup.'%\';';
		$tables = q($sql);
		while ($table_old = $tables->fetch_assoc()) 
		{
			$table_old = $table_old['table_name'];
			$table_new = $goal.mb_substr($table_old, strlen($backup));
			$sql = array
			(
				'CREATE TABLE IF NOT EXISTS '.$table_new.' LIKE '.$table_old.';',
				'TRUNCATE TABLE '.$table_new.';',
				'INSERT INTO '.$table_new.' SELECT * FROM '.$table_old.';',
			);
			if (!q($sql)) $result = false;
		}
		return $result;
	}

	// Удаление бэкапа таблиц с распределенными данными
	public function backup_empty ($backup = 'back_')
	{
		$result = true;
		$sql = 'SELECT table_name FROM information_schema.tables WHERE table_schema=\''.DB_NAME.'\' AND table_name LIKE \''.$backup.'%\';';
		$tables = q($sql);
		while ($table_name = $tables->fetch_assoc()) 
		{
			$table_name = $table_name['table_name'];
			$sql = 'DROP TABLE IF EXISTS '.$table_name.';';
			if (!q($sql)) $result = false;
		}
		return $result;
	}

	////////////////////////////////////////////////////////////////////////////////////////
	// Работа с таблицами в базе данных ****************************************************
	////////////////////////////////////////////////////////////////////////////////////////
	// Добавление таблицы в локальную базу данных
	public function tables_create ($arTables)
	{
		$result = true;
		if ((!empty($arTables))&&(is_array($arTables))) 
		{
			foreach ($arTables as $table_name => $iTables) 
			{
				// Создаем таблицы базы данных
				$sql = 'SHOW TABLES LIKE \''.$table_name.'\'';
				$table_exist = q($sql);
				if ($table_exist->num_rows == 0) 
				{
					write('Создаем таблицу: '.$table_name, 1);
					if ((!empty($iTables))&&(is_array($iTables))) 
					{
						$sql = 'CREATE TABLE IF NOT EXISTS '.$table_name.' (';
						$row_names = '';
						foreach ($iTables as $row_name => $row_attributes) 
						{
							if ((!empty($row_name))&&(!empty($row_attributes))&&(!is_array($row_attributes))) $sql .= $row_name.' '.$row_attributes.', ';
							if ((!empty($row_name))&&(!empty($row_attributes))&&(!is_array($row_attributes))&&($row_name != 'id')) $row_names .= $row_name.', ';
						}
						$row_names = substr ($row_names, 0, -2);
						$sql = substr ($sql, 0, -2).');';
						if (q($sql)) 
						{
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
										if ($iRow == 'NULL') $row_values .= $iRow.','; else $row_values .= '\''.$iRow.'\',';
									}
									$row_values = substr ($row_values, 0, -1).'),';
								}
								$row_values = substr ($row_values, 0, -1);
								$sql = 'INSERT INTO '.$table_name.' ('.$row_names.') VALUES '.$row_values;
								if (!q($sql)) $result = false;
							}
						}
						else 
						{
							write('Невозможно создать таблицу.', 1);
							$result = false;
						}
					}
					else 
					{
						write('Невозможно прочитать формат таблиц.', 1);
						$result = false;
					}
				}
				else 
				{
					write('Таблица уже существует.', 1);
					$result = false;
				}
			}
		}
		else 
		{
			write('Невозможно прочитать формат таблиц.', 1);
			$result = false;
		}
		return $result;
	}

	// Удаление списка таблиц из локальной базы данных
	public function tables_del_list ($arTables)
	{
		if ((!empty($arTables))&&(is_array($arTables))) 
		{
			foreach ($arTables as $table_name => $iTables) 
			{
				write('Удаляем: '.$table_name, 1);
				if ((!empty($iTables))&&(is_array($iTables))) 
				{
					$sql = 'DROP TABLE IF EXISTS '.$table_name.';';
					q($sql);
				}
				else 
				{
					write('Невозможно прочитать формат таблиц', 1);
					return false;
				}
			}
		}
		else 
		{
			write('Невозможно прочитать формат таблиц', 1);
			return false;
		}
	}

	// Удаление таблицы из локальной базы данных по маске (new cBase())->tables_del_mask('bc_')
	public function tables_del_mask ($table_name)
	{
		$result = true;
		if ((!empty($table_name))&&(is_string($table_name))) 
		{
			$sql = 'SELECT table_name FROM information_schema.tables WHERE table_schema=\''.DB_NAME.'\' AND table_name LIKE \''.$table_name.'%\';';
			$tables = q($sql);
			$sql = array();
			while ($table = $tables->fetch_assoc()) array_push($sql, 'DROP TABLE IF EXISTS '.$table['table_name'].';');
			if (!q($sql)) $result = false;
		}
		else 
		{
			write('Невозможно прочитать название таблиц', 1);
			$result = false;
		}
		return $result;
	}

	// Очистка таблицы в локальной базе данных по маске (new cBase())->tables_empty('bc_')
	public function tables_empty ($table_name)
	{
		$result = true;
		if ((!empty($table_name))&&(is_string($table_name))) 
		{
			$sql = 'SELECT table_name FROM information_schema.tables WHERE table_schema=\''.DB_NAME.'\' AND table_name LIKE \''.$table_name.'%\';';
			$tables = q($sql);
			$sql = array();
			while ($table = $tables->fetch_assoc()) array_push($sql, 'TRUNCATE TABLE '.$table['table_name'].';');
			if (!q($sql)) $result = false;
		}
		else 
		{
			write('Невозможно прочитать название таблиц', 1);
			$result = false;
		}
		return $result;
	}

	////////////////////////////////////////////////////////////////////////////////////////
	// Работа с локальными константами, принятыми на данной ноде ***************************
	////////////////////////////////////////////////////////////////////////////////////////
	// Получение списка локальных констант (new cBase())->constant_list()
	public function constant_list ()
	{
		$output = array();
		$sql = 'SELECT * FROM constants';
		$constants = q($sql);
		if (!empty($constants)) 
		{
			$arConstants = array();
			while ($iConstants = $constants->fetch_assoc()) array_push($arConstants, $iConstants);
			if (!empty($arConstants)) $output = $arConstants; else $output = false;
		}
		else 
		{
			$output = false;
		}
		return $output;
	}

	// Получение локальной константы (new cBase())->constant_get('miner_name')
	public function constant_get ($parameter)
	{
		$output = '';
		if ((is_string($parameter))&&(!empty($parameter))) 
		{
			$sql = 'SELECT * FROM constants WHERE parameter=\''.$parameter.'\'';
			$arResult = array();
			$constants = q($sql);
			if (!empty($constants)) 
			{
				$arResult = $constants->fetch_assoc();
				if (!empty($arResult)) $output = $arResult['value'];
			}
		}
		return $output;
	}

	// Обновление локальной константы (new cBase())->constant_set('miner_name', 'my_name')
	public function constant_set ($parameter, $value)
	{
		$output = false;
		if ((is_string($parameter))&&(!empty($parameter))&&(is_string($value))&&(!empty($value))) 
		{
			$sql = 'UPDATE constants SET value = \''.$value.'\' WHERE parameter = \''.$parameter.'\';';
			$output = (q($sql)) ? true : false;
		}
		return $output;
	}

	// Добавление локальной константы (new cBase())->constant_add('miner_name', 'my_name')
	public function constant_add ($parameter, $value)
	{
		$output = false;
		if ((is_string($parameter))&&(!empty($parameter))&&(is_string($value))&&(!empty($value))) 
		{
			$sql = 'INSERT INTO constants (parameter, value) VALUES (\''.$parameter.'\',\''.$value.'\')';
			$output = (q($sql)) ? true : false;
		}
		return $output;
	}


	// Удаление локальной константы (new cBase())->constant_del('miner_name')
	public function constant_del ($parameter)
	{
		$output = false;
		if ((is_string($parameter))&&(!empty($parameter))) 
		{
			$sql = 'DELETE FROM bc_bills WHERE parameter=\''.$parameter.'\';';
			$output = (q($sql)) ? true : false;
		}
		return $output;
	}

	////////////////////////////////////////////////////////////////////////////////////////
	// Взаимодействие с другими майнерами **************************************************
	////////////////////////////////////////////////////////////////////////////////////////
	// Получение списка имеющихся майнеров (new cBase())->miners_get_all()
	public function miners_get_all ($limit = 0)
	{
		$output = array();
		$sql = (!empty($limit)) ? 'SELECT * FROM miners ORDER BY miner_rate DESC, RAND() LIMIT '.$limit : 'SELECT * FROM miners';
		$miners = q($sql);
		if (!empty($miners)) 
		{
			$arMiners = array();
			while ($iMiners = $miners->fetch_assoc()) array_push($arMiners, $iMiners);
			if (!empty($arMiners)) 
			{
				write('Получен список майнеров.', 1);
				$output = $arMiners;
			}
			else 
			{
				write('Майнеры не найдены.', 1);
				$output = false;
			}
		}
		else 
		{
			write('Майнеры не найдены.', 1);
			$output = false;
		}
		return $output;
	}

	// Получение информации о майнере (new cBase())->miners_get($name), (new cBase())->miners_get('', $link)
	public function miners_get ($name = '', $link = '')
	{
		$output = array();
		if ((is_string($name))&&(is_string($link))) 
		{
			if (!empty($name)) $sql = 'SELECT * FROM miners WHERE miner_name=\''.$name.'\'';
			if (!empty($link)) $sql = 'SELECT * FROM miners WHERE miner_link=\''.$link.'\'';
			$arResult = array();
			$miners = q($sql);
			if (!empty($miners)) $arResult = $miners->fetch_assoc();
			$output = $arResult;
		}
		return $output;
	}

	// Добавление нового майнера (new cBase())->miners_add($name, $type, $link, $rate)
	public function miners_add ($name, $type, $link, $rate)
	{
		$output = false;
		if ((is_string($name))&&(!empty($name))&&(is_string($type))&&(!empty($type))&&(is_string($link))&&(!empty($link))&&(is_num($rate, true))) 
		{
			$sql = 'INSERT INTO miners (miner_name, miner_type, miner_link, miner_rate) VALUES (\''.$name.'\',\''.$type.'\',\''.$link.'\',\''.$rate.'\')';
			$output = (q($sql)) ? true : false;
		}
		return $output;
	}

	// Обновление майнера (new cBase())->miners_update($name, $type, $link, $rate)
	public function miners_update ($name, $type, $link, $rate)
	{
		$output = false;
		if ((is_string($name))&&(!empty($name))&&(is_string($type))&&(!empty($type))&&(is_string($link))&&(!empty($link))&&(is_num($rate, true))) 
		{
			$sql = 'UPDATE miners SET miner_type = \''.$type
					.'\', miner_link = \''.$link
					.'\', miner_rate = \''.$rate
					.'\' WHERE miner_name = \''.$name.'\';';
			$output = (q($sql)) ? true : false;
		}
		return $output;
	}

	// Удаление майнера (new cBase())->miners_del($name)
	public function miners_del ($name)
	{
		$output = false;
		if ((is_string($name))&&(!empty($name))) 
		{
			$sql = 'DELETE FROM miners WHERE miner_name=\''.$name.'\';';
			$output = (q($sql)) ? true : false;
		}
		return $output;
	}

	////////////////////////////////////////////////////////////////////////////////////////
	// Работа с банкнотами в кошельке ******************************************************
	////////////////////////////////////////////////////////////////////////////////////////
	// Получение списка всех локальных банкнот (new cBase())->wallet_get_all()
	public function wallet_get_all ()
	{
		$output = array();
		$sql = 'SELECT * FROM wallet';
		$bills = q($sql);
		if (!empty($bills)) 
		{
			$arBills = array();
			while ($iBills = $bills->fetch_assoc()) array_push($arBills, $iBills);
			if (!empty($arBills)) 
			{
				write('Получен список банкнот.', 1);
				$output = $arBills;
			}
			else 
			{
				write('Банкноты в кошельке не найдены.', 1);
				$output = false;
			}
		}
		else 
		{
			write('Банкноты в кошельке не найдены.', 1);
			$output = false;
		}
		return $output;
	}

	// Получение локальной банкноты (new cBase())->wallet_get($bill_number)
	public function wallet_get ($bill_number)
	{
		$output = array();
		if ((is_string($bill_number))&&(!empty($bill_number))) 
		{
			$sql = 'SELECT * FROM wallet WHERE bill_number=\''.$bill_number.'\'';
			$arResult = array();
			$bills = q($sql);
			if (!empty($bills)) $arResult = $bills->fetch_assoc();
			$output = $arResult;
		}
		return $output;
	}

	// Добавление локальной банкноты (new cBase())->wallet_add($bill_number, $bill_key)
	public function wallet_add ($bill_number, $bill_key)
	{
		$output = false;
		if ((is_string($bill_number))&&(!empty($bill_number))&&(is_string($bill_key))&&(!empty($bill_key))) 
		{
			$sql = 'INSERT INTO wallet (bill_number, bill_key, busy) VALUES (\''.$bill_number.'\',\''.$bill_key.'\',\'0\')';
			$output = (q($sql)) ? true : false;
		}
		return $output;
	}

	// Удаление локальной банкноты (new cBase())->wallet_del($bill_number)
	public function wallet_del ($bill_number)
	{
		$output = false;
		if ((is_string($bill_number))&&(!empty($bill_number))) 
		{
			$sql = 'DELETE FROM wallet WHERE bill_number=\''.$bill_number.'\';';
			$output = (q($sql)) ? true : false;
		}
		return $output;
	}

	// Блокирование локальной банкноты на время выполнения команды
	public function wallet_busy ($bill_number)
	{
		$output = false;
		if ((is_string($bill_number))&&(!empty($bill_number))) 
		{
			$sql = 'UPDATE wallet SET busy = \'1\' WHERE bill_number = \''.$bill_number.'\';';
			$output = (q($sql)) ? true : false;
		}
		return $output;
	}

	// Освобождение локальной банкноты после выполнения команды
	public function wallet_free ($bill_number)
	{
		$output = false;
		if ((is_string($bill_number))&&(!empty($bill_number))) 
		{
			$sql = 'UPDATE wallet SET busy = \'0\' WHERE bill_number = \''.$bill_number.'\';';
			$output = (q($sql)) ? true : false;
		}
		return $output;
	}

	// Локальное резервное копирование старых паролей банкноты 
	// для исключения потерь банкнот по ошибке
	public function wallet_stack ($bill_number, $bill_key, $timestamp)
	{
		$output = false;
		if ((is_string($bill_number))&&(!empty($bill_number))&&(is_string($bill_key))&&(!empty($bill_key))&&(is_timestamp($timestamp))) 
		{
			$sql = 'INSERT INTO wallet_stack (bill_number, bill_key, timestamp) VALUES (\''.$bill_number.'\',\''.$bill_key.'\',\''.$timestamp.'\')';
			$output = (q($sql)) ? true : false;
		}
		return $output;
	}

	// Чтение локальной резервной копии старых паролей банкноты (для исключения потерь банкнот по ошибке)
	public function wallet_stack_read ($bill_number = '')
	{
		$output = array();
		$sql = 'SELECT * FROM wallet_stack';
		if (!empty($bill_number)) $sql .= ' WHERE bill_number=\''.$bill_number.'\'';
		$keys = q($sql);
		if (!empty($keys)) 
		{
			$arBills = array();
			while ($iBills = $keys->fetch_assoc()) array_push($arBills, $iBills);
			if (!empty($arBills)) 
			{
				write('Получен список прежних паролей.', 1);
				$output = $arBills;
			}
			else 
			{
				write('Прежние пароли не найдены.', 1);
				$output = false;
			}
		}
		else 
		{
			write('Прежние пароли не найдены.', 1);
			$output = false;
		}
		return $output;
	}

	// Список локальных команд
	public function action_list ($only_free = false)
	{
		$output = array();
		$sql = ($only_free) ? 'SELECT * FROM actions WHERE executed=\'0\'' : 'SELECT * FROM actions';
		$bills = q($sql);
		if (!empty($bills)) 
		{
			$arBills = array();
			while ($iBills = $bills->fetch_assoc()) array_push($arBills, $iBills);
			$output = (!empty($arBills)) ? $arBills : false;
		}
		else 
		{
			$output = false;
		}
		return $output;
	}

	// Добавление локальной команды
	public function action_add ($type, $entity)
	{
		$output = false;
		if ((is_string($type))&&(!empty($type))&&(is_string($entity))&&(!empty($entity))) 
		{
			$sql = 'INSERT INTO actions (type, entity, executed) VALUES (\''.$type.'\',\''.$entity.'\',\'0\')';
			$output = (q($sql)) ? true : false;
		}
		return $output;
	}

	// Локальная отметка о выполнении команды
	public function action_execute ($entity)
	{
		$output = false;
		if ((is_string($entity))&&(!empty($entity))) 
		{
			$sql = 'UPDATE actions SET executed = \'1\' WHERE entity = \''.$entity.'\';';
			$output = (q($sql)) ? true : false;
		}
		return $output;
	}

	////////////////////////////////////////////////////////////////////////////////////////
	// Работа с банкнотами в распределенной части базы данных ******************************
	////////////////////////////////////////////////////////////////////////////////////////
	// Получение записи о банкноте (bill get)
	public function bill_get ($number)
	{
		if ((!is_string($number))||(empty($number))) 
		{
			write('Ошибочный формат обращения к базе данных.', 1, 'error');
			return false;
		}
		else 
		{
			$arBill = array();
			$sql = 'SELECT * FROM bc_bills WHERE number=\''.$number.'\'';
			$bills = q($sql);
			if (!empty($bills)) 
			{
				$arBill = $bills->fetch_assoc();
				if (!empty($arBill)) 
				{
					write('<b>'.$number.':</b> данные получены.', 1);
					return $arBill;
				}
				else 
				{
					write('<b>'.$number.':</b> банкнота не найдена.', 1);
					return false;
				}
			}
			else 
			{
				write('<b>'.$number.':</b> ошибка при поиске банкноты.', 1);
				return false;
			}
		}
	}

	// Обновление банкноты (bill update). Применяется при исполнении контрактов или при переводе токенов между банкнотами
	public function bill_update ($number, $sign, $algorithm, $denomination, $timestamp)
	{
		if ((!is_string($number))||(empty($number))||(!is_string($sign))||(empty($sign))||(!is_string($algorithm))||(empty($algorithm))||(!is_denomination($denomination))||($denomination <= 0)||(!is_timestamp($timestamp))) 
		{
			write('Ошибочный формат обращения к базе данных.', 1, 'error');
			return false;
		}
		else 
		{
			$bill = $this->bill_get($number);
			if (!empty($bill)) 
			{
				$sql = 'UPDATE bc_bills SET '
						.'sign = \''.$sign.'\''
						.', algorithm = \''.$algorithm.'\''
						.', denomination = \''.$denomination.'\''
						.', timestamp = \''.$timestamp.'\''
						.' WHERE number = \''.$number.'\';';
				if (q($sql)) 
				{
					write('<b>'.$number.':</b> банкнота обновлена.', 1);
					return true;
				}
				else 
				{
					write('Ошибка в базе данных.', 1, 'error');
					return false;
				}
			}
			else 
			{
				write('<b>'.$number.':</b> банкнота не может быть обновлена.', 1, 'error');
				return false;
			}
		}
	}

	// Изменение владельца банкноты (bill payment). Применяется в транзакциях
	// Специализированная bill_update. Выполняется через замену подписи, алгоритма шифрования и таймштампа
	public function bill_payment ($number, $sign, $algorithm, $timestamp, $fee)
	{
		if ((!is_string($number))||(empty($number))||(!is_string($sign))||(empty($sign))||(!is_string($algorithm))||(empty($algorithm))||(!is_timestamp($timestamp))||(!is_denomination($fee))) 
		{
			write('Ошибочный формат обращения к базе данных.', 1, 'error');
			return false;
		}
		else 
		{
			$bill = $this->bill_get($number);
			if (!empty($bill)) 
			{
				$denomination = to_cent($bill['denomination']-$fee);
				if ($denomination>0) 
				{
					$sql = 'UPDATE bc_bills SET '
							.'sign = \''.$sign.'\''
							.', algorithm = \''.$algorithm.'\''
							.', timestamp = \''.$timestamp.'\''
							.', denomination = \''.$denomination.'\''
							.' WHERE number = \''.$number.'\';';
					if (q($sql)) 
					{
						write('<b>'.$number.':</b> пароль банкноты обновлен.', 1);
						return true;
					}
					else 
					{
						write('Ошибка в базе данных.', 1, 'error');
						return false;
					}
				}
				else 
				{
					write('<b>'.$number.':</b> банкнота не может быть обновлена.', 1, 'error');
					return false;
				}
			}
			else 
			{
				write('<b>'.$number.':</b> банкнота не может быть обновлена.', 1, 'error');
				return false;
			}
		}
	}

	// Создание банкноты (bill add)
	// В самих транзакциях проводится одновременно с удалением другой банкноты (или группы банкнот)
	// Например, при объединении банкнот или их разделении
	// За сохранение суммы номиналов отвечает алгоритм обработки транзакции
	public function bill_add ($number, $sign, $algorithm, $denomination, $timestamp)
	{
		if ((!is_string($number))||(empty($number))||(!is_string($sign))||(empty($sign))||(!is_string($algorithm))||(empty($algorithm))||($denomination <= 0)||(!is_denomination($denomination))||(empty($denomination))||(!is_timestamp($timestamp))) 
		{
			write('Ошибочный формат обращения к базе данных.', 1, 'error');
			return false;
		}
		else 
		{
			if (empty($this->bill_get($number))) 
			{
				$sql = 'INSERT INTO bc_bills (number, sign, algorithm, denomination, timestamp) VALUES ('
						.'\''.$number.'\','
						.'\''.$sign.'\','
						.'\''.$algorithm.'\','
						.'\''.$denomination.'\','
						.'\''.$timestamp.'\')';
				if (q($sql)) 
				{
					write('<b>'.$number.':</b> банкнота успешно создана.', 1);
					return true;
				}
				else 
				{
					write('Ошибка в базе данных.', 1, 'error');
					return false;
				}
			}
			else 
			{
				write('<b>'.$number.':</b> банкнота с этим номером существует.', 1, 'error');
				return false;
			}
		}
	}

	// Удаление банкноты (bill delete)
	// В самих транзакциях проводится одновременно с созданием другой банкноты (или группы банкнот)
	// Например, при объединении банкнот или их разделении
	// За сохранение суммы номиналов отвечает алгоритм обработки транзакции
	public function bill_del ($number)
	{
		if ((!is_string($number))||(empty($number))) 
		{
			write('Ошибочный формат обращения к базе данных.', 1, 'error');
			return false;
		}
		else 
		{
			if (!empty($this->bill_get($number))) 
			{
				$sql = 'DELETE FROM bc_bills WHERE number=\''.$number.'\';';
				if (q($sql)) 
				{
					write('<b>'.$number.':</b> банкнота удалена.', 1);
					return true;
				}
				else 
				{
					write('Ошибка в базе данных.', 1, 'error');
					return false;
				}
			}
			else 
			{
				write('<b>'.$number.':</b> невозможно удалить банкноту.', 1, 'error');
				return false;
			}
		}
	}

	////////////////////////////////////////////////////////////////////////////////////////
	// Работа с намерениями для банкнот ****************************************************
	////////////////////////////////////////////////////////////////////////////////////////
	// Извлечение записи о намерениях
	public function intentions_get ($goal)
	{
		if ((!is_string($goal))||(empty($goal))) 
		{
			write('Ошибочный формат обращения к базе данных.', 1, 'error');
			return false;
		}
		else 
		{
			$arIntentions = array();
			$sql = 'SELECT * FROM bc_intentions WHERE goal=\''.$goal.'\'';
			$intentions = q($sql);
			if (!empty($intentions)) 
			{
				while ($iIntentions = $intentions->fetch_assoc()) array_push($arIntentions, $iIntentions);
				if (!empty($arIntentions)) 
				{
					write('<b>'.$goal.':</b> намерения получены.', 1);
					return $arIntentions;
				}
				else 
				{
					write('<b>'.$goal.':</b> намерения не найдены.', 1);
					return false;
				}
			}
			else 
			{
				write('<b>'.$goal.':</b> ошибка при поиске намерений.', 1);
				return false;
			}
		}
	}

	// Добавление записи о намерениях для цели
	// Вся обработка намерений происходит в транзакциях
	public function intention_add ($goal, $pubkey, $intention)
	{
		if ((!is_string($goal))||(empty($goal))||(!is_string($pubkey))||(empty($pubkey))||(!is_string($intention))||(empty($intention))) 
		{
			write('Ошибочный формат обращения к базе данных.', 1, 'error');
			return false;
		}
		else 
		{
			$sql = 'INSERT INTO bc_intentions (goal, pubkey, intention) VALUES (\''.$goal.'\',\''.$pubkey.'\',\''.$intention.'\')';
			if (q($sql)) 
			{
				write('Намерение для <b>'.$goal.'</b> опубликовано.', 1);
				return true;
			}
			else 
			{
				write('Ошибка в базе данных.', 1, 'error');
				return false;
			}
		}
	}

	// Очистка записей о намерениях для цели
	// Вся обработка намерений происходит в транзакциях
	public function intentions_clear ($goal)
	{
		if ((!is_string($goal))||(empty($goal))) 
		{
			write('Ошибочный формат обращения к базе данных.', 1, 'error');
			return false;
		}
		else 
		{
			if (!empty($this->intentions_get($goal))) 
			{
				$sql = 'DELETE FROM bc_intentions WHERE goal=\''.$goal.'\';';
				if (q($sql)) 
				{
					write('Намерения освобождены для <b>'.$goal.'</b>.', 1);
					return true;
				}
				else 
				{
					write('Ошибка в базе данных.', 1, 'error');
					return false;
				}
			}
			else 
			{
				return false;
			}
		}
	}

	////////////////////////////////////////////////////////////////////////////////////////
	// Работа с обработчиком контрактов ****************************************************
	////////////////////////////////////////////////////////////////////////////////////////
	// Получение данных о сущности контракта из базы
	public function contract_get ($contract_number)
	{
		if ((!is_string($contract_number))||(empty($contract_number))) 
		{
			write('Ошибочный формат обращения к базе данных.', 1, 'error');
			return false;
		}
		else 
		{
			$arContracts = array();
			$sql = 'SELECT * FROM bc_contracts WHERE contract_number=\''.$contract_number.'\'';
			$contract = q($sql);
			if (!empty($contract)) 
			{
				$arContracts = $contract->fetch_assoc();
				if (!empty($arContracts)) 
				{
					write('<b>'.$contract_number.':</b> контракт получен.', 1);
					return $arContracts;
				}
				else 
				{
					write('<b>'.$contract_number.':</b> контракт не найден.', 1);
					return false;
				}
			}
			else 
			{
				write('<b>'.$contract_number.':</b> ошибка при поиске контракта.', 1);
				return false;
			}
		}
	}

	// Добавление в базу данных записи о контракте
	public function contract_add ($contract_number, $entity)
	{
		if ((!is_string($contract_number))||(empty($contract_number))||(!is_string($entity))||(empty($entity))) 
		{
			write('Ошибочный формат обращения к базе данных.', 1, 'error');
			return false;
		}
		else 
		{
			$contract = $this->contract_get($contract_number);
			if (empty($contract)) 
			{
				$sql = 'INSERT INTO bc_contracts (contract_number, entity) VALUES (\''.$contract_number.'\',\''.$entity.'\')';
				if (q($sql)) 
				{
					write('Контракт <b>'.$contract_number.'</b> создан.', 1);
					return true;
				}
				else 
				{
					write('Ошибка в базе данных.', 1, 'error');
					return false;
				}
			}
			else 
			{
				write('Контракт с таким номером уже существует.', 1, 'error');
				return false;
			}
		}
	}

	////////////////////////////////////////////////////////////////////////////////////////
	// Работа с пулом команд ***************************************************************
	////////////////////////////////////////////////////////////////////////////////////////
	// Получение команд, находящихся в пуле (new cBase())->pool_list()
	public function pool_list ()
	{
		$sql = 'SELECT * FROM bc_pool';
		$arPool = array();
		$pool = q($sql);
		if (!empty($pool)) while ($iPool = $pool->fetch_assoc()) array_push($arPool, $iPool);
		return $arPool;
	}

	// Получение транзакции из пула (по номеру банкноты или по сущности транзакции)
	// $input['number'] - номер банкноты, по которой выбираются транзакции
	// $input['entity'] - сущность транзакции, по которой выбираются строки с банкнотами, входящими в транзакцию
	// при указании обоих параметров выбирается только строка, содержащая как банкноту, так и сущность транзакции
	public function pool_get ($input = array('number' => '', 'entity' => ''))
	{
		if ((!is_array($input))||(empty($input))) 
		{
			write('Ошибочный формат обращения к базе данных.', 1, 'error');
			return false;
		}
		else 
		{
			if ((empty($input['number']))||(!is_string($input['number']))) 
			{
				if ((empty($input['entity']))||(!is_string($input['entity']))) 
				{
					write('Ошибочный формат обращения к базе данных.', 1, 'error');
					$sql = '';
					return false;
				}
				else 
				{
					$sql = 'SELECT * FROM bc_pool WHERE entity=\''.$input['entity'].'\'';
				}
			}
			else 
			{
				if ((empty($input['entity']))||(!is_string($input['entity']))) 
				{
					$sql = 'SELECT * FROM bc_pool WHERE number=\''.$input['number'].'\'';
				}
				else 
				{
					$sql = 'SELECT * FROM bc_pool WHERE number=\''.$input['number'].'\' AND entity=\''.$input['entity'].'\'';
				}
			}
			$arPool = array();
			$pool = q($sql);
			if (!empty($pool)) 
			{
				while ($iPool = $pool->fetch_assoc()) array_push($arPool, $iPool);
				if (!empty($arPool)) 
				{
					write('Информация о транзакции получена.', 1);
					return $arPool;
				}
				else 
				{
					write('Информация о транзакции в пуле не найдена.', 1);
					return false;
				}
			}
			else 
			{
				write('Ошибка при поиске транзакции в пуле.', 1);
				return false;
			}
		}
	}

	// Добавление транзакции в пул
	public function pool_add ($number, $entity)
	{
		if ((!is_string($number))||(empty($number))||(!is_string($entity))||(empty($entity))) 
		{
			write('Ошибочный формат обращения к базе данных.', 1, 'error');
			return false;
		}
		else 
		{
			$sql = 'INSERT INTO bc_pool (number, entity) VALUES (\''.$number.'\',\''.$entity.'\')';
			if (q($sql)) 
			{
				write('Запись о транзакции добавлена в пул.', 1);
				return true;
			}
			else 
			{
				write('Ошибка в базе данных.', 1, 'error');
				return false;
			}
		}
	}

	// Удаление транзакции из пула (по номеру банкноты или по сущности транзакции)
	// $input['number'] - номер банкноты, для которой нужно удалить транзакции
	// $input['entity'] - сущность удаляемой транзакции
	// при указании обоих параметров удаляется только строка, содержащая как банкноту, так и сущность транзакции
	public function pool_del ($input = array('number' => '', 'entity' => ''))
	{
		if ((!is_array($input))||(empty($input))) 
		{
			write('Ошибочный формат обращения к базе данных.', 1, 'error');
			return false;
		}
		else 
		{
			if (!empty($this->pool_get($input))) 
			{
				if ((empty($input['number']))||(!is_string($input['number']))) 
				{
					if ((empty($input['entity']))||(!is_string($input['entity']))) 
					{
						write('Ошибочный формат обращения к базе данных.', 1, 'error');
						$sql = '';
						return false;
					}
					else 
					{
						$sql = 'DELETE FROM bc_pool WHERE entity=\''.$input['entity'].'\';';
					}
				}
				else 
				{
					if ((empty($input['entity']))||(!is_string($input['entity']))) 
					{
						$sql = 'DELETE FROM bc_pool WHERE number=\''.$input['number'].'\';';
					}
					else 
					{
						$sql = 'DELETE FROM bc_pool WHERE number=\''.$input['number'].'\' AND entity=\''.$input['entity'].'\';';
					}
				}
				if (q($sql)) 
				{
					write('Запись о транзакции удалена.', 1);
					return true;
				}
				else 
				{
					write('Ошибка в базе данных.', 1, 'error');
					return false;
				}
			}
			else 
			{
				write('В пуле не найдена транзакция с указанными параметрами.', 1);
				return false;
			}
		}
	}
}

// q - query. Выполнение SQL запроса к локальной базе данных
function q ($sql)
{
	$result = true;
	$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if ($db->connect_error) 
	{
		write('Невозможно подключиться к базе данных: '.$db->connect_error, 0, 'error');
		$db->close();
		$result = false;
	}
	else 
	{
		write('Успешное подключение к базе данных.', 0);
		if (is_array($sql)) foreach ($sql as $sql_row) $db->query($sql_row); else $result = $db->query($sql);
		if ($db->error) 
		{
			$query = '';
			if (is_array($sql)) foreach ($sql as $sql_row) $query .= $sql_row.PHP_EOL; else $query = $sql;
			write($db->error, 0, 'error');
			write('Невозможно выполнить запрос: '.$query, 0, 'error');
			$db->rollback();
			$db->close();
			$result = false;
		}
		else 
		{
			if (empty($result)) $result = false;
			$db->close();
		}
	}
	return $result;
}
?>