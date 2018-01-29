<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта

// Загрузка параметров модуля
module_config (__DIR__);

class cBlocks 
{
	private $id;
	private $store;
	private $ok = false;
	private $commited = false;
	private $genesis = false;
	private $fee = 0;
	private $content = '';
	private $previous;
	private $head = array();
	private $transactions = array();
	private $intentions = array();
	private $issue = array();
	private $quest = array();
	private $proof = array();
	private $indicators = array();
	private $bills = array // списки банкнот в незафиксированных блоках
	(
		'input' => array
		(
			'transactions' => array(),
			'intentions' => array()
		),
		'output' => array
		(
			'transactions' => array(),
			'intentions' => array()
		),
	);

	public function cBlocks ($block_id)
	{
		$this->id = intval($block_id);
	}

	// Чтение текста блока. Распознавание и упорядочивание разделов блока из выбранного источника
	// Например: $this->read(), $this->read('file', $filename), $this->read('text', $text)
	public function read ($store = BLOCK_MAIN_STORE, $request = '')
	{
		$this->store = $store;
		// Читаем блок из хранилища (с возможностью указания хранилища и запроса к нему)
		$arBlock = updates_blocks_stores($this->store, '', $this->id, $request);
		if (!empty($arBlock)) 
		{
			$this->head =                          block_section_decode('*', block_section($arBlock, [], ['*b']));
			$this->transactions = sort_transaction(block_section_decode('>', block_section($arBlock, [], ['>'])));
			$this->indicators['0'] = count($this->transactions); // количество транзакций
			$this->intentions =   sort_transaction(block_section_decode('@', block_section($arBlock, [], ['@'])));
			$this->indicators['1'] = count($this->intentions); // количество намерений
			$this->indicators['2'] = block_percent_indicators($this->indicators['0'], $this->indicators['1']); // соотношение количества намерений и транзакций
			$this->issue =                         block_section_decode('*', block_section($arBlock, [], ['*i']));
			$this->quest =                         block_section_decode('*', block_section($arBlock, [], ['*q']));
			$this->content = 
				 block_section_encode('*', $this->head)
				.block_section_encode('>', $this->transactions)
				.block_section_encode('@', $this->intentions)
				.block_section_encode('*', $this->issue);
			$this->indicators['6'] = strlen($this->content); // длина содержимого блока
			$this->proof =                         block_section_decode('*', block_section($arBlock, [], ['*p']));
			$this->indicators['8'] = $this->proof['parameters']['8']; // алгоритм хэширования блока
			$this->indicators['7'] = block_hash($this->content, $this->head, $this->issue, $this->indicators['8']); // хэш блока
		}
		else 
		{
			$this->head = array();
			$this->transactions = array();
			$this->intentions = array();
			$this->issue = array();
			$this->quest = array();
			$this->content = '';
			$this->proof = array();
			$this->indicators = array();
		}
	}

	// Тестирование всех разделов блока
	public function test ($previous_block = array())
	{
		$result = false;
		$this->commited = updates_blocks_stores($this->store, 'commited', $this->id);
		if ($this->commited) 
		{
			// Уже принятый и зафиксированный блок
			if (empty($this->content)) $this->read();
			$this->ok = true;
			$this->indicators = $this->proof['parameters'];
			$this->genesis = ($this->head['parameters']['n'] == GENESIS_ID) ? true : false;
			if (is_num($this->head['parameters']['p'])) 
			{
				// Загружаем предыдущий блок
				$this->previous = new cBlocks($this->head['parameters']['p']);
				if (empty($previous_block['previous_store'])) $previous_block['previous_store'] = BLOCK_MAIN_STORE;
				if (empty($previous_block['previous_request'])) $previous_block['previous_request'] = '';
				$this->previous->read($previous_block['previous_store'], $previous_block['previous_request']);
			}
			write('Блок уже принят и зафиксирован. В проверке не нуждается.', 3);
			$result = true;
		}
		else 
		{
			// Список проверок. Блок считается валидным, если по окончании тестирования все параметры установлены в true
			$arTest = array
			(
				'head'=>false, 
				'transactions'=>false,
				'intentions'=>false,
				'issue'=>false,
				'quest'=>false,
				'proof'=>false,
				'bills'=>false,
			);
			// Загружаем текущий блок
			if (empty($this->content)) $this->read();
			if ((!empty($this->head))&&(is_array($this->head))) 
			{
				if ((!empty($this->head['parameters']))&&(is_array($this->head['parameters']))) 
				{
					$block_head = $this->head['parameters'];
					if ((is_num($block_head['p']))&&($block_head['p'] >= GENESIS_ID)) 
					{
						$this->genesis = false;
						// Загружаем предыдущий блок
						$this->previous = new cBlocks($block_head['p']);
						if (empty($previous_block['previous_store'])) $previous_block['previous_store'] = BLOCK_MAIN_STORE;
						if (empty($previous_block['previous_request'])) $previous_block['previous_request'] = '';
						$this->previous->read($previous_block['previous_store'], $previous_block['previous_request']);
						// Проверяем разделы
						$arTest['head'] = $this->test_head();
						$this->bills['input']['transactions'] = array();
						$this->bills['input']['intentions'] = array();
						$this->bills['output']['transactions'] = array();
						$this->bills['output']['intentions'] = array();
						$arTest['transactions'] = $this->test_transactions();
						$arTest['intentions'] = $this->test_intentions();
						$this->indicators['5'] = block_percent_indicators($this->indicators['3'], $this->indicators['4']); // соотношение силы намерений и транзакций
						$this->indicators['9'] = $this->count_bills(); // количество банкнот в транзакциях и намерениях
						$arTest['issue'] = $this->test_issue();
						$arTest['quest'] = $this->test_quest();
						$arTest['proof'] = $this->test_proof();
						$arTest['bills'] = $this->test_bills();
					}
					// Проверка генезис-блока
					elseif (($block_head['n'] == GENESIS_ID)&&($block_head['n'] == $this->id)&&($block_head['p'] == 'genesis')) 
					{
						$this->genesis = true;
						// Заголовок
						$arTest['head'] = (($block_head['h'] == 'genesis')&&($block_head['t'] == GENESIS_TIME)) ? true : false;
						// Транзакции и намерения
						$arTest['transactions'] = (block_section_encode('>', $this->transactions) == GENESIS_TRANSACTIONS) ? true : false;
						$arTest['intentions'] = (block_section_encode('@', $this->intentions) == GENESIS_INTENTIONS) ? true : false;
						$this->test_transactions(); // Тестирование для определения $this->indicators['3']
						$this->test_intentions(); // Тестирование для определения $this->indicators['4']
						$this->indicators['5'] = block_percent_indicators($this->indicators['3'], $this->indicators['4']); // соотношение силы намерений и транзакций
						$this->indicators['9'] = $this->count_bills(); // количество банкнот в транзакциях и намерениях
						// Эмиссия премайна для инвесторов
						$arTest['issue'] = (block_section_encode('*', $this->issue) == GENESIS_ISSUE) ? true : false;
						// Первоначальное задание на следующий блок
						$arTest['quest'] = (block_section_encode('*', $this->quest) == GENESIS_QUEST) ? true : false;
						$arTest['proof'] = $this->test_proof();
						$arTest['bills'] = $this->test_bills();
					}
					else 
					{
						write('Блок '.$this->id.'. Невозможно прочитать заголовок блока.', 3, 'error');
						$result = false;
					}
					// Подводим итог проверок
					$this->ok = (in_array(false, $arTest)) ? false : true;
					if ($this->ok) write('Блок '.$this->id.' прошел проверку.', 3, 'success');
					$result = $this->ok;
				}
				else 
				{
					write('Блок '.$this->id.'. Невозможно прочитать заголовок блока.', 3, 'error');
					$result = false;
				}
			}
			else 
			{
				write('Блок '.$this->id.'. Невозможно прочитать заголовок блока', 3, 'error');
				$result = false;
			}
		}
		return $result;
	}

	// Расчет баллов блока
	public function score ()
	{
		$output = 0;
		if (!$this->genesis) 
		{
			if ($this->ok) 
			{
				$arIndicators = tally_indicators($this->proof['parameters'], $this->id);
				$arQuest = $this->previous->quest['parameters'];
				if ((count($arQuest) == QUEST_NUM)&&(count($arIndicators) == QUEST_NUM)) 
				{
					foreach ($arIndicators as $key => $iIndicators) $output += $iIndicators*$arQuest[$key];
					write('Сумма баллов блока: '.$output, 3);
				}
				else 
				{
					write('Блок не проверен или содержит ошибки', 3, 'error');
				}
			}
			else 
			{
				write('Блок не проверен или содержит ошибки', 3, 'error');
			}
		}
		else 
		{
			$output = 0;
			write('Сумма баллов блока: '.$output, 3);
		}
		return $output;
	}

	// Формирование нового блока
	// $issue - задается в виде строки о транзакции. Сама строка формируется модулем майнинга
	// $algorithm - код выбранного алгоритма шифрования (применяется к эмитируемой банкноте и к блоку)
	// $arTransactions - массив транзакций блока (если не указан, то в блоке проставляется отметка об отсутствии транзакций)
	// $arIntentions - массив намерений блока блока (если не указан, то в блоке проставляется отметка об отсутствии намерений)
	public function shaping ($issue, $algorithm, $arTransactions = array(), $arIntentions = array())
	{
		$wrong_items = false;
		$output = '';
		if ((is_string($issue))&&(is_string($algorithm))&&(is_array($arTransactions))&&(is_array($arIntentions))) 
		{
			// добавление заголовка блока
			$prev_id = $this->id-1;
			$this->previous = new cBlocks($prev_id);
			$this->previous->read();
			if (!$this->previous->test()) 
			{
				write('Не удается создать заголовок блока.', 3, 'error');
				$wrong_items = true;
				return false;
			}
			if ((empty($this->head))||(!$this->test_head())) 
			{
				$this->head['key'] = 'b';
				$this->head['parameters'] = array
				(
					'n' => strval($this->id), 
					'p' => strval($prev_id), 
					'h' => strval($this->previous->proof['parameters']['7']), 
					't' => strval($this->previous->head['parameters']['t']+BLOCK_TIME), 
				);
				ksort($this->head['parameters']);
			}
			if (!$this->test_head()) 
			{
				write('Не удается создать заголовок блока.', 3, 'error');
				$wrong_items = true;
				return false;
			}
			// добавление раздела транзакций блока
			if (!empty($arTransactions)) 
			{
				foreach ($arTransactions as $key => $iTransactions) $arTransactions[$key] = '>'.$iTransactions;
				$this->transactions = sort_transaction(block_section_decode('>', $arTransactions));
			}
			else 
			{
				$this->transactions = block_section_decode('>', '>no:[]');
			}
			if (!$this->test_transactions()) 
			{
				write('Не удается создать раздел транзакций блока.', 3, 'error');
				$wrong_items = true;
				return false;
			}
			// добавление раздела намерений блока
			if (!empty($arIntentions)) 
			{
				foreach ($arIntentions as $key => $iIntentions) $arIntentions[$key] = '@'.$iIntentions;
				$this->intentions = sort_transaction(block_section_decode('@', $arIntentions));
			}
			else 
			{
				$this->intentions = block_section_decode('@', '>no:[]');
			}
			if (!$this->test_intentions()) 
			{
				write('Не удается создать раздел намерений блока.', 3, 'error');
				$wrong_items = true;
				return false;
			}
			// добавление строки об эмиссии
			$this->proof['parameters']['8'] = $algorithm; // определение алгоритма шифрования
			$this->issue = block_section_decode('*', array($issue));
			if (!$this->test_issue()) 
			{
				write('Не удается создать раздел эмиссии.', 3, 'error');
				$wrong_items = true;
				return false;
			}
			// вычисление содержимого блока
			$this->content  = block_section_encode('*', $this->head);
			$this->content .= block_section_encode('>', $this->transactions);
			$this->content .= block_section_encode('@', $this->intentions);
			$this->content .= block_section_encode('*', $this->issue);
			// вычисление показателей блока
			$this->indicators['0'] = count($this->transactions); // количество транзакций
			$this->indicators['1'] = count($this->intentions); // количество намерений
			$this->indicators['2'] = block_percent_indicators($this->indicators['0'], $this->indicators['1']);
			//$this->indicators['3'] расcчитан при проверке транзакций
			//$this->indicators['4'] расcчитан при проверке намерений
			$this->indicators['5'] = block_percent_indicators($this->indicators['3'], $this->indicators['4']);
			$this->indicators['6'] = strlen($this->content); // длина содержимого блока
			$this->indicators['7'] = block_hash($this->content, $this->head, $this->issue, $algorithm); // хэш блока
			$this->indicators['8'] = $algorithm; // алгоритм хэширования блока
			$this->indicators['9'] = $this->count_bills(); // количество банкнот в транзакциях и намерениях
			ksort($this->indicators);
			// добавление раздела проверки Proof of Points
			$this->proof['key'] = 'p';
			foreach ($this->indicators as $key => $item) $this->proof['parameters'][$key] = strval($item);
			ksort($this->proof['parameters']);
			if (!$this->test_proof()) 
			{
				write('Не удается создать раздел проверки Proof of Points.', 3, 'error');
				$wrong_items = true;
				return false;
			}
			// добавление раздела с заданием на следующий блок
			$this->quest['key'] = 'q';
			foreach ($this->calculate_quest() as $key => $item) $this->quest['parameters'][$key] = strval($item);
			ksort($this->quest['parameters']);
			if (!$this->test_quest()) 
			{
				write('Не удается создать раздел с заданием на следующий блок.', 3, 'error');
				$wrong_items = true;
				return false;
			}
			// Формируем текст блока
			$this->test(); // итоговая проверка блока
			if ($this->ok) $output = $this->assemble();
		}
		else 
		{
			write('Ошибочно указаны параметры блока.', 3, 'error');
			$wrong_items = true;
			return false;
		}
		if ($wrong_items) $output = '';
		return $output;
	}

	// Сборка блока в строковый формат. Предназначена для сохранения блока в 
	// базу данных, в файл или для передачи по сети
	public function assemble ()
	{
		$output  = block_section_encode('*', $this->head, true);
		$output .= block_section_encode('>', $this->transactions, true);
		$output .= block_section_encode('@', $this->intentions, true);
		$output .= block_section_encode('*', $this->issue, true);
		$output .= block_section_encode('*', $this->quest, true);
		$output .= block_section_encode('*', $this->proof, true);
		return $output;
	}

	// Расщепление блока на транзакции. Запись транзакций в пул
	public function orphan ()
	{
		$pool = new cPool;
		foreach ($this->transactions as $item) 
		{
			$entity = block_section_encode('', $item);
			$pool->add($entity);
		}
		foreach ($this->intentions as $item) 
		{
			$entity = block_section_encode('', $item);
			$pool->add($entity);
		}
	}

	// Очистка пула от команд блока
	public function empty_pool ()
	{
		if ($this->ok) 
		{
			$pool = new cPool;
			foreach ($this->transactions as $item) 
			{
				$entity = block_section_encode('', $item);
				$pool->clean_by_entity($entity);
				$pool->del($entity);
			}
			foreach ($this->intentions as $item) 
			{
				$entity = block_section_encode('', $item);
				$pool->del($entity);
			}
		}
	}

	// Фиксация нового блока
	public function compile ()
	{
		if ($this->ok) 
		{
			// Принятие намерений блока
			foreach ($this->intentions as $intention) transaction_test($intention['key'], $intention['parameters'], true);
			// Принятие транзакций блока
			foreach ($this->transactions as $transaction) transaction_test($transaction['key'], $transaction['parameters'], true);
			// Эмиссия банкноты блока
			$issue_example = array
			(
				'number' => $this->issue['parameters'][0],
				'sign' => $this->issue['parameters'][1],
				'algorithm' => $this->proof['parameters'][8],
				'denomination' => to_cent($this->issue['parameters'][2]),
				'timestamp' => $this->head['parameters']['t'],
			);
			transaction_test($this->issue['key'], $issue_example, true);
			$this->empty_pool();
			return true;
		}
		else 
		{
			return false;
		}
	}

	// Получение размера эмиссии блока
	public function calculate_issue ()
	{
		return to_cent(issue_value($this->id)+$this->fee);
	}

	// Получение задания для следующего блока
	public function calculate_quest ()
	{
		$wrong_items = false;
		if (count($this->indicators) == QUEST_NUM) ksort($this->indicators); else $wrong_items = true;
		$block_score = tally_indicators($this->indicators, $this->id);
		$prev_score = tally_indicators($this->previous->proof['parameters'], $this->previous->id);
		if ((count($block_score) == QUEST_NUM)&&(count($prev_score) == QUEST_NUM)&&(count($this->previous->quest['parameters']) == QUEST_NUM)) 
		{
			$diff_score = array_map('array_difference', $block_score, $prev_score);
			$quest = array_map('quests_calculate', $diff_score, $this->previous->quest['parameters']);
			while (array_sum($quest) != QUEST_SUM) 
			{
				foreach ($quest as $key => $item) $arQuest[$key] = array('k'=>$key, 'quest'=>$quest[$key], 'difference'=>$diff_score[$key]);
				usort($arQuest, 'quests_sort');
				if (array_sum($quest) < QUEST_SUM) 
				{
					$level = 0;
					$is_updated = false;
					while (!$is_updated) 
					{
						if ($arQuest[$level]['quest'] < QUEST_MAX) 
						{
							$arQuest[$level]['quest']++;
							$is_updated = true;
						}
						$level++;
					}
				}
				if (array_sum($quest) > QUEST_SUM) 
				{
					$level = QUEST_NUM-1;
					$is_updated = false;
					while (!$is_updated) 
					{
						if ($arQuest[$level]['quest'] > QUEST_MIN) 
						{
							$arQuest[$level]['quest']--;
							$is_updated = true;
						}
						$level--;
					}
				}
				foreach ($arQuest as $item) $quest[$item['k']] = strval($item['quest']);
			}
			$quest = array_map('strval', $quest);
		}
		else 
		{
			write('Ошибка определения задания на следующий блок', 3);
			return false;
		}
		return $quest;
	}

	// Получение разности хэшей блока и эмиссии (выполняется в цепочке)
	public function test_hashes ()
	{
		$result = hash_difference($this->proof['parameters'][7], $this->issue['parameters'][1]);
		return ($result !== false) ? $result : false;
	}

	// Чтение параметров блока
	public function get ($option)
	{
		return (property_exists($this, $option)) ? $this->$option : false;
	}

	// Тестирование заголовка блока
	private function test_head ()
	{
		if ((!empty($this->head))&&(is_array($this->head))) 
		{
			$wrong_items = false;
			if ($this->head['parameters']['n'] != $this->id) 
			{
				write('Ошибочный номер блока.', 3);
				$wrong_items = true;
			}
			if ($this->head['parameters']['p'] != $this->previous->head['parameters']['n']) 
			{
				write('Номер предыдущего блока указан неверно.', 3);
				$wrong_items = true;
			}
			if ($this->head['parameters']['h'] != $this->previous->proof['parameters']['7']) 
			{
				write('Хэш предыдущего блока указан неверно.', 3);
				$wrong_items = true;
			}
			if (!is_timestamp($this->head['parameters']['t'])) 
			{
				write('Невозможно прочитать таймштамп блока.', 3);
				$wrong_items = true;
			}
			if ($this->head['parameters']['t'] >= gmdate('U')) 
			{
				write('Время блока еще не пришло. Указан таймштамп будущего времени.', 3);
				$wrong_items = true;
			}
			if ($this->head['parameters']['t'] != ($this->previous->head['parameters']['t']+BLOCK_TIME)) 
			{
				write('Таймштамп блока указан неверно.', 3);
				$wrong_items = true;
			}
			// Если не найдено ни одной ошибки
			if (!$wrong_items) 
			{
				write('Заголовок блока '.$this->id.' прошел проверку.', 3);
				return true;
			}
			else 
			{
				write('Неправильный заголовок блока '.$this->id.'.', 3, 'error');
				return false;
			}
		}
		else 
		{
			write('Не найден заголовок блока '.$this->id.'.', 3, 'error');
			return false;
		}
	}

	// Тестирование транзакций в блоке
	private function test_transactions ()
	{
		if ((!empty($this->transactions))&&(is_array($this->transactions))) 
		{
			$wrong_items = false;
			$this->indicators['3'] = 0; // сила транзакций
			$this->fee = 0;
			if (($this->transactions['0']['key'] == 'no')&&(count($this->transactions) == 1)) 
			{
				write('Проставлена отметка об отсутствии транзакций в блоке '.$this->id.'.', 3);
			}
			else 
			{
				foreach ($this->transactions as $transaction) 
				{
					$arTest = transaction_test($transaction['key'], $transaction['parameters']);
					if ((!$arTest['ok'])||($arTest['is'] != 'transaction')) $wrong_items = true;
					$this->indicators['3'] += $arTest['denomination']; // сила транзакций
					$this->fee += $arTest['fee'];
					foreach ($arTest['number'] as $bill_number) array_push($this->bills['input']['transactions'], $bill_number);
					foreach ($arTest['output'] as $bill_number) array_push($this->bills['output']['transactions'], $bill_number);
				}
			}
			$this->indicators['3'] = to_cent($this->indicators['3']);
			$this->fee = to_cent($this->fee);
			if (!$wrong_items) 
			{
				write('Все транзакции блока '.$this->id.' валидны.', 3);
				return true;
			}
			else 
			{
				write('Некоторые транзакции блока '.$this->id.' не смогли пройти проверку.', 3, 'error');
				return false;
			}
		}
		else 
		{
			write('В блоке '.$this->id.' отсутствуют транзакции.', 3, 'error');
			return false;
		}
	}

	// Тестирование намерений в блоке
	private function test_intentions ()
	{
		if ((!empty($this->intentions))&&(is_array($this->intentions))) 
		{
			$wrong_items = false;
			$this->indicators['4'] = 0; // сила намерений
			if (($this->intentions['0']['key'] == 'no')&&(count($this->intentions) == 1)) 
			{
				write('Проставлена отметка об отсутствии намерений в блоке '.$this->id, 3);
			}
			else 
			{
				foreach ($this->intentions as $intention) 
				{
					$arTest = transaction_test($intention['key'], $intention['parameters']);
					if ((!$arTest['ok'])||($arTest['is'] != 'intention')) $wrong_items = true;
					$this->indicators['4'] += $arTest['denomination']; // сила намерений
					foreach ($arTest['number'] as $bill_number) array_push($this->bills['input']['intentions'], $bill_number);
					foreach ($arTest['output'] as $bill_number) array_push($this->bills['output']['intentions'], $bill_number);
				}
			}
			$this->indicators['4'] = to_cent($this->indicators['4']);
			if (!$wrong_items) 
			{
				write('Все намерения блока '.$this->id.' валидны.', 3);
				return true;
			}
			else 
			{
				write('Некоторые намерения блока '.$this->id.' не смогли пройти проверку.', 3, 'error');
				return false;
			}
		}
		else 
		{
			write('В блоке '.$this->id.' отсутствуют намерения.', 3, 'error');
			return false;
		}
	}

	// Тестирование строки об эмиссии
	private function test_issue ()
	{
		if ((!empty($this->issue))&&(is_array($this->issue))) 
		{
			$issue = $this->issue['parameters'];
			$algorithm = $this->proof['parameters'][8]; // алгоритмы шифрования эмитируемой банкноты и блока совпадают
			$timestamp = $this->head['parameters']['t']; // момент создания эмитируемой банкноты и блока совпадают
			$wrong_items = false;
			if (count($issue) != 3) 
			{
				write('Ошибочный формат строки об эмиссии.', 3);
				$wrong_items = true;
			}
			$block_award = $this->calculate_issue();
			if ((!float_equals($issue[2], $block_award))||($block_award <= 0)) 
			{
				write('Ошибочно указан номинал эмитируемой банкноты.', 3);
				$wrong_items = true;
			}
			$encrypt = new cEncrypt(['algorithm'=>$algorithm]);
			if (!$encrypt->algorithm) 
			{
				write('Ошибочно указан алгоритм шифрования эмитируемой банкноты.', 3);
				$wrong_items = true;
			}
			$example = array
			(
				'number' => $issue[0],
				'sign' => $issue[1],
				'algorithm' => $algorithm,
				'denomination' => to_cent($issue[2]),
				'timestamp' => $timestamp,
			);
			$arTest = transaction_test($this->issue['key'], $example);
			if ((!$arTest['ok'])||($arTest['is'] != 'issue')) $wrong_items = true;
		}
		else 
		{
			write('В блоке отсутствует запись об эмиссии '.$this->id, 3, 'error');
			$wrong_items = true;
		}
		// Выдача результата
		if (!$wrong_items) 
		{
			write('Запись об эмиссии в блоке '.$this->id.' прошла проверку', 3);
			return true;
		}
		else 
		{
			write('Запись об эмиссии в блоке '.$this->id.' содержит ошибки', 3, 'error');
			return false;
		}
	}

	// Тестирование задания для следующего блока
	private function test_quest ()
	{
		$result = true;
		if ((!empty($this->quest))&&(is_array($this->quest))) 
		{
			if (is_array($this->quest['parameters'])) 
			{
				foreach ($this->quest['parameters'] as $item) if (!is_num($item)) $result = false;
			}
			else 
			{
				$result = false;
			}
			if (count($this->quest['parameters']) != QUEST_NUM) $result = false;
			if (array_sum($this->quest['parameters']) != QUEST_SUM) $result = false;
			if ($this->quest['parameters'] != $this->calculate_quest()) $result = false;
		}
		else 
		{
			$result = false;
		}
		if (!$result) write($this->id.': ошибка при проверке задания для следующего блока.', 3, 'error');
		if ($result) write($this->id.': задание для следующего блока указано верно.', 3);
		return $result;
	}

	// Тестирование проверки Proof of Points
	private function test_proof ()
	{
		if ((!empty($this->proof))&&(is_array($this->proof))) 
		{
			if 
				(
				(is_num($this->proof['parameters']['0']))            // число транзакций (штук)
				&&(is_num($this->proof['parameters']['1']))          // число намерений (штук)
				&&(is_num($this->proof['parameters']['2']))          // соотношение намерений и транзакций round(min(i,t)/(i+t)*200)
				&&(is_denomination($this->proof['parameters']['3'])) // сила транзакций (в номиналах банкнот)
				&&(is_denomination($this->proof['parameters']['4'])) // сила намерений (в номиналах банкнот)
				&&(is_num($this->proof['parameters']['5']))          // соотношение сил намерений и транзакций round(min(i,t)/(i+t)*200)
				&&(is_num($this->proof['parameters']['6']))          // длина содержимого блока (символов)
				&&(is_string($this->proof['parameters']['7']))       // хэш блока (строка hex)
				&&(is_string($this->proof['parameters']['8']))       // алгоритм хэширования блока (строка, оценивается pubkey)
				&&(is_num($this->proof['parameters']['9']))          // количество банкнот в транзакциях и намерениях
				) 
			{
				ksort($this->indicators); // упорядочивание показателей блока для сравнения с Proof of Points
				if ($this->indicators == $this->proof['parameters']) 
				{
					write('Проверка Proof of Points для блока '.$this->id.' прошла успешно', 3);
					return true;
				}
				else 
				{
					write('Ошибка при проверке Proof of Points для блока '.$this->id, 3, 'error');
					return false;
				}
			}
			else 
			{
				write('Невозможно распознать Proof of Points для блока '.$this->id, 3, 'error');
				return false;
			}
		}
		else 
		{
			write('Отсутствует информация о проверке Proof of Points блока '.$this->id, 3, 'error');
			return false;
		}
	}

	// Расчет количества банкнот, участвующих в транзакциях
	private function count_bills ()
	{
		$output = count($this->bills['input']['transactions']);
		$output += count($this->bills['input']['intentions']);
		$output += count($this->bills['output']['transactions']);
		$output += count($this->bills['output']['intentions']);
		return $output;
	}

	// Проверка входных банкнот блока на уникальность номеров
	private function test_bills ()
	{
		$result = true;
		// Проверка входных банкнот транзакций и намерений на уникальность
		$arBills = $this->bills['input']['transactions'];
		foreach ($this->bills['input']['intentions'] as $item) array_push($arBills, $item);
		array_push($arBills, $this->issue['parameters'][0]);
		if ($arBills != array_unique($arBills)) 
		{
			write('Обнаружены одинаковые входные банкноты. Блок отклонен.', 3, 'error');
			$result = false;
		}
		// Сверка намерений блока на наличие транзакций в пуле
		$pool = new cPool;
		foreach ($this->bills['input']['intentions'] as $item) 
		{
			$pool_transaction = $pool->show($item, 'transaction');
			if (!empty($pool_transaction)) 
			{
				write('Блок содержит намерения к банкнотам, транзакции к которым находятся в пуле.', 3, 'error');
				$result = false;
			}
		}
		// Проверка выходных банкнот транзакций и намерений на уникальность
		$arBills = $this->bills['output']['transactions'];
		foreach ($this->bills['output']['intentions'] as $item) array_push($arBills, $item);
		array_push($arBills, $this->issue['parameters'][0]);
		if ($arBills != array_unique($arBills)) 
		{
			write('Обнаружены одинаковые выходные банкноты. Блок отклонен.', 3, 'error');
			$result = false;
		}
		if ($result) write('Все банкноты блока прошли проверку.', 3);
		return $result;
	}
}
?>