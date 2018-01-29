<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Работа с пулом транзакций

class cPool 
{
	// Получение полного списка команд, находящихся в пуле
	public function full_list ()
	{
		$output = array();
		// Получение полного списка команд
		$arList = (new cBase)->pool_list();
		if (!empty($arList)) 
		{
			// Удаление повторяющихся значений сущностей
			// У одной команды может быть несколько входных банкнот
			// В этом случае сущности команд будут повторяться
			$arEntity = array();
			foreach ($arList as $iList) array_push($arEntity, $iList['entity']);
			$output = array_unique($arEntity);
		}
		return $output;
	}

	// Получение транзакции из пула по заданной сущности транзакции
	public function read ($entity, $autoupdate = false)
	{
		if (!is_string($entity)) 
		{
			write('Ошибка запроса к базе данных.', 2, 'error');
			return false;
		}
		else 
		{
			$base = new cBase;
			// Получение списка транзакций по сущности
			$arList = $base->pool_get(['entity' => $entity]);
			if (!empty($arList)) 
			{
				$arNumbers = array();
				$update_need = false;
				$type = transaction_split($entity, true);
				$json = transaction_split($entity, false);
				// Проверка допустимости транзакций
				foreach ($arList as $key => $iList) 
				{
					$duplicate = false;
					$bill = $base->bill_get($iList['number']);
					if (in_array($iList['number'], $arNumbers)) $duplicate = true; else array_push($arNumbers, $iList['number']);
					$test_type = transaction_split($iList['entity'], true);
					$test_json = transaction_split($iList['entity'], false);
					$arTest = transaction_test($test_type, json_decode($test_json, true));
					// Удаление недопустимых транзакций из результатов выдачи
					if (!$arTest['ok']) write('Транзакция содержит ошибки.', 2);
					if (!in_array($iList['number'], $arTest['number'])) write('Банкнота отсутствует на входе транзакции.', 2);
					if (empty($bill)) write('Банкнота '.$iList['number'].' не найдена.', 2);
					if ((!$arTest['ok'])||(!in_array($iList['number'], $arTest['number']))||($duplicate)||(empty($bill))) $update_need = true;
				}
				// Проверка соответствия списка банкнот данной транзакции списку в базе данных
				$arControl = transaction_test($type, json_decode($json, true));
				sort($arControl['number']);
				sort($arNumbers);
				if ($arControl['number'] != $arNumbers) $update_need = true;
				// Удаление недопустимых транзакций из базы
				if (($autoupdate)&&($update_need)) $this->add($entity);
				// Выдача результата
				if ($update_need) 
				{
					write('В пуле для указанной транзакции содержатся ошибки.', 2);
					return false;
				}
				else 
				{
					write('Получение транзакции из пула.', 2);
					return $arList;
				}
			}
			else 
			{
				write('В пуле отсутствуют указанные транзакции.', 2);
				return false;
			}
		}
	}

	// Получение списка транзакций из пула с заданным номером банкноты
	public function show ($number, $specify = '')
	{
		$result = false;
		if (!is_string($number)) 
		{
			write('Ошибка запроса к базе данных.', 2, 'error');
			$result = false;
		}
		else 
		{
			$base = new cBase;
			// Проверка существования банкноты
			$bill = $base->bill_get($number);
			if (!empty($bill)) 
			{
				// Получение списка транзакций для банкноты
				$arList = $base->pool_get(['number' => $number]);
				if (!empty($arList)) 
				{
					// Проверка допустимости транзакций
					foreach ($arList as $key => $iList) 
					{
						// Разделение сущности транзакции на тип и JSON
						$type = transaction_split($iList['entity'], true);
						$json = transaction_split($iList['entity'], false);
						$arTest = transaction_test($type, json_decode($json, true));
						// Исключение недопустимых транзакций
						if ((!$arTest['ok'])||(!in_array($iList['number'], $arTest['number']))) unset($arList[$key]);
						// Исключение транзакций, тип которых не указан
						// (например, если указаны $specify = 'intention' то удаляются 'transaction')
						if (($specify != '')&&($arTest['is'] != $specify)) unset($arList[$key]);
					}
					// Получение списка
					$result = $arList;
				}
				else 
				{
					write('В пуле отсутствуют транзакции для банкноты <b>'.$number.'</b>.', 2);
					$result = false;
				}
			}
			else 
			{
				write('Запрос отклонен. В базе данных отсутствует банкнота с номером <b>'.$number.'</b>.', 2, 'error');
				$result = false;
			}
		}
		return $result;
	}

	// Добавление транзакции в пул
	public function add ($entity)
	{
		$wrong_items = false;
		$type = transaction_split($entity, true);
		$json = transaction_split($entity, false);
		$base = new cBase;
		// Проверка допустимости транзакции
		$arTest = transaction_test($type, json_decode($json, true));
		// Удаление из базы всех транзакций с такой же сущностью (транзакция будет восстановлена в случае отсутствия ошибок)
		$this->del($entity);
		if (empty($arTest)) 
		{
			write('При проверке транзакции произошла ошибка.', 2, 'error');
			$wrong_items = true;
		}
		else 
		{
			// Проверка отсутствия более приоритетных транзакций
			if ((!empty($arTest['number']))&&($arTest['ok'])) 
			{
				foreach ($arTest['number'] as $number) 
				{
					$arTemp = $base->pool_get(['number' => $number]);
					if (!empty($arTemp)) 
					{
						foreach ($arTemp as $iTemp) 
						{
							$temp_type = transaction_split($iTemp['entity'], true);
							$temp_json = transaction_split($iTemp['entity'], false);
							$temp_result = transaction_test($temp_type, json_decode($temp_json, true));
							// Если есть транзакция или контракт, то не добавлять намерение
							if (($arTest['is'] == 'intention')&&($temp_result['is'] == 'transaction')) $wrong_items = true;
							// Удаление менее приоритетных транзакций из пула
							if (($arTest['is'] == 'transaction')&&($temp_result['is'] == 'intention')) $this->del($iTemp['entity']);
						}
					}
					else 
					{
						write('Для выбранной банкноты нет транзакций в пуле.', 2);
					}
				}
			}
			else 
			{
				write('При проверке транзакции в пуле произошла ошибка.', 2, 'error');
				$wrong_items = true;
			}
		}
		// Добавление ранее удаленной транзакции в пул
		$add_errors = false;
		if (!$wrong_items) 
		{
			foreach ($arTest['number'] as $number) 
			{
				if (!$base->pool_add($number, $entity)) $add_errors = true;
			}
		}
		return (($wrong_items)||($add_errors)) ? false : true;
	}

	// Удаление транзакции из пула (для обращения майнером к пулу транзакций вместо обращения к базе)
	public function del ($input, $by_number = false)
	{
		$base = new cBase;
		if (is_string($input)) 
		{
			if (!$by_number) $base->pool_del(['entity' => $input]); else $base->pool_del(['number' => $input]);
			return true;
		}
		else 
		{
			write('Ошибочно указан объект для удаления.', 2, 'error');
			return false;
		}
	}

	// Очистка пула от намерений при наличии транзакции и удаление невалидных транзакций
	// Применяется в тех случаях, когда транзакция находится внутри пула
	public function clean_by_number ($number)
	{
		$clean_intentions = false;
		if (!is_string($number)) 
		{
			write('Ошибка запроса к базе данных.', 2, 'error');
			return false;
		}
		else 
		{
			$base = new cBase;
			// Проверка существования банкноты
			$bill = $base->bill_get($number);
			if (!empty($bill)) 
			{
				// Получение списка транзакций для банкноты
				$arList = $base->pool_get(['number' => $number]);
				if (!empty($arList)) 
				{
					// Проверка банкноты на наличие транзакций в пуле
					foreach ($arList as $iList) 
					{
						$type = transaction_split($iList['entity'], true);
						$json = transaction_split($iList['entity'], false);
						$temp_result = transaction_test($type, json_decode($json, true));
						if (($temp_result['ok'])&&($temp_result['is'] == 'transaction')) $clean_intentions = true;
						// Удаление невалидных транзакций
						// Валидные остаются, так как блок может стать орфаном
						if (!$temp_result['ok']) $this->del($iList['entity']);
					}
					// Очистка намерений банкноты при наличии транзакций в пуле
					if ($clean_intentions) 
					{
						foreach ($arList as $key => $iList) 
						{
							$type = transaction_split($iList['entity'], true);
							$json = transaction_split($iList['entity'], false);
							$temp_result = transaction_test($type, json_decode($json, true));
							if ($temp_result['is'] == 'intention') $this->del($iList['entity']);
						}
						write('Намерения для банкноты <b>'.$number.'</b> очищены.', 2);
						return true;
					}
					else 
					{
						write('Из-за отсутствия транзакций, намерения для банкноты <b>'.$number.'</b> не могут быть очищены.', 2);
						return false;
					}
				}
				else 
				{
					write('В пуле отсутствуют транзакции для банкноты <b>'.$number.'</b>.', 2);
					return false;
				}
			}
			else 
			{
				$this->del($number, true);
				write('Запрос отклонен. В базе данных отсутствует банкнота с номером <b>'.$number.'</b>.', 2, 'error');
				return false;
			}
		}
	}

	// Очистка пула от намерений при чтении блока с транзакцией
	// Применяется в тех случаях, когда создается новый блок или 
	// когда принимается блок от другого майнера
	public function clean_by_entity ($entity)
	{
		if (is_string($entity)) 
		{
			// Проверяем поступившую транзакцию
			$type = transaction_split($entity, true);
			$json = transaction_split($entity, false);
			$arTest = transaction_test($type, json_decode($json, true));
			if (($arTest['ok'])&&($arTest['is'] == 'transaction')) 
			{
				$base = new cBase;
				foreach ($arTest['number'] as $number) 
				{
					$arList = $base->pool_get(['number' => $number]);
					if (!empty($arList)) 
					{
						// Удаление намерений для всех банкнот, входящих в транзакцию
						foreach ($arList as $iList) 
						{
							$type = transaction_split($iList['entity'], true);
							$json = transaction_split($iList['entity'], false);
							$temp_result = transaction_test($type, json_decode($json, true));
							if ($temp_result['is'] == 'intention') $this->del($iList['entity']);
							if (!$temp_result['ok']) $this->del($iList['entity']);
						}
						write('Намерения для банкноты <b>'.$number.'</b> очищены.', 2);
						return true;
					}
					else 
					{
						write('В пуле отсутствуют намерения для банкноты <b>'.$number.'</b>.', 2, 'error');
						return false;
					}
				}
			}
			else 
			{
				write('Это намерение или проверяемая транзакция невалидна.', 2, 'error');
				return false;
			}
		}
		else 
		{
			write('Невозможно распознать запрос на очистку пула от намерений.', 2, 'error');
			return false;
		}
	}
}
?>