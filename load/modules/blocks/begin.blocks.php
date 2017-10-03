<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта

// Загрузка параметров модуля
module_config (__DIR__);

class cBlocks 
{
	public $id;
	public $head = array();
	public $transactions = array();
	public $intentions = array();
	public $issue = array();
	public $quest = array();
	public $content = '';
	public $proof = array();
	public $accepted = false;

	private $success = false;
	private $is_genesis = false;
	private $previous;
	private $fee = 0;
	private $indicators = array();

	public function cBlocks ($block_id)
	{
		$this->id = $block_id;
	}

	// Чтение текста блока. Распознавание и упорядочивание разделов блока
	// По умолчанию читаем из всех источников: база->файл->текст
	// Если нужно прочитать из определенного источника, то указываем его явно.
	// Например: $this->read('db', $blockID), $this->read('file', $filename), $this->read('text', $text)
	public function read ($read_type = 'all', $request = '')
	{
		// Читаем блок. По умолчанию из всех источников, но с возможностью указания источника.
		$call_method = 'get_from_'.$read_type;
		if (method_exists($this, $call_method)) $arBlock = $this->$call_method($request); else $arBlock = $this->get_from_all($request);
		if (!empty($arBlock)) 
		{
			$this->head =                          block_section_decode('*', block_section($arBlock, [], ['*b']));
			$this->transactions = transaction_sort(block_section_decode('>', block_section($arBlock, [], ['>'])));
			$this->indicators['0'] = count($this->transactions); // количество транзакций
			$this->intentions =   transaction_sort(block_section_decode('@', block_section($arBlock, [], ['@'])));
			$this->indicators['1'] = count($this->intentions); // количество намерений
			$this->indicators['2'] = (float_equals(($this->indicators['0']+$this->indicators['1']), 0)) ? 1 : round((min($this->indicators['0'], $this->indicators['1'])/($this->indicators['0']+$this->indicators['1']))*200); // соотношение количества намерений и транзакций
			$this->issue =                         block_section_decode('*', block_section($arBlock, [], ['*i']));
			$this->indicators['9'] = to_cent($this->issue['parameters']['3']); // номинал эмитируемой банкноты
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
		// Список проверок. Блок считается валидным, если по окончании тестирования все параметры установлены в true
		$arTest = array
		(
			'head'=>false, 
			'transactions'=>false,
			'intentions'=>false,
			'issue'=>false,
			'quest'=>false,
			'proof'=>false,
		);
		// Загружаем текущий блок
		if (empty($this->content)) $this->read();
		if ((!empty($this->head))&&(is_array($this->head))) 
		{
			if ((!empty($this->head['parameters']))&&(is_array($this->head['parameters']))) 
			{
				$block_head = $this->head['parameters'];
				if ((is_num($block_head['p']))&&($block_head['p']>=0)) 
				{
					// Загружаем предыдущий блок
					$this->previous = new cBlocks($block_head['p']);
					if (empty($previous_block['previous_source'])) $previous_block['previous_source'] = 'all';
					if (empty($previous_block['previous_request'])) $previous_block['previous_request'] = '';
					$this->previous->read($previous_block['previous_source'], $previous_block['previous_request']);
					// Проверяем разделы
					$arTest['head'] = $this->test_head();
					$arTest['transactions'] = $this->test_transactions();
					$arTest['intentions'] = $this->test_intentions();
					$this->indicators['5'] = (float_equals(($this->indicators['3']+$this->indicators['4']), 0)) ? 1 : round(min($this->indicators['3'], $this->indicators['4'])/($this->indicators['3']+$this->indicators['4'])*200); // соотношение силы намерений и транзакций
					$arTest['issue'] = $this->test_issue();
					$arTest['quest'] = $this->test_quest();
					$arTest['proof'] = $this->test_proof();
				}
				// Проверка генезис-блока
				elseif (($block_head['n']=='0')&&($block_head['n']==$this->id)&&($block_head['p']=='genesis')) 
				{
					$this->is_genesis = true;
					// Заголовок
					$arTest['head'] = (($block_head['h']=='genesis')&&($block_head['t']=='1504224000')) ? true : false;
					// Транзакции и намерения (в генезис-блоке отсутствуют)
					$arTest['transactions'] = (empty($this->transactions)) ? true : false;
					$arTest['intentions'] = (empty($this->intentions)) ? true : false;
					$this->indicators['3'] = to_cent(0); // сила транзакций
					$this->indicators['4'] = to_cent(0); // сила намерений
					$this->indicators['5'] = 1; // соотношение силы намерений и транзакций
					// Эмиссия премайна для инвесторов
					$arTest['issue'] = (block_section_encode('*', $this->issue)=='*i:["premine_invest","a254488e155ba7da90d1033fa7502611e82287d283f9d80f70962768670fa941","ar","20000000.00000000"]') ? true : false;
					// Первоначальное задание на следующий блок
					$arTest['quest'] = ($this->quest['parameters']==[10,10,10,10,10,10,10,10,10,10]) ? true : false;
					$arTest['proof'] = $this->test_proof();
				}
				else 
				{
					console_line('Блок '.$this->id.'. Невозможно прочитать заголовок блока', 3, 'error');
					return false;
				}
				// Подводим итог проверок
				$this->accepted = (in_array(false, $arTest)) ? false : true;
				if ($this->accepted) 
				{
					console_line('Блок '.$this->id.' успешно прошел проверку', 3, 'success');
					$this->success = true;
					return true;
				}
				else 
				{
					console_line('Блок '.$this->id.' содержит ошибки', 3, 'error');
					$this->success = false;
					return false;
				}
			}
			else 
			{
				console_line('Блок '.$this->id.'. Невозможно прочитать заголовок блока', 3, 'error');
				return false;
			}
		}
		else 
		{
			console_line('Блок '.$this->id.'. Невозможно прочитать заголовок блока', 3, 'error');
			return false;
		}
	}

	// Расчет баллов блока
	public function get_score ()
	{
		$output = 0;
		if (!$this->is_genesis) 
		{
			if ($this->success) 
			{
				$arIndicators = tally_indicators($this->proof['parameters'], $this->id);
				$arQuest = $this->previous->quest['parameters'];
				if ((count($arIndicators)==count($arQuest))&&(count($arIndicators)==NUM_QUEST)) 
				{
					foreach ($arIndicators as $key => $iIndicators) $output += $iIndicators*$arQuest[$key];
					console_line('Сумма баллов блока:'.$output, 3);
				}
				else 
				{
					console_line('Блок не проверен или содержит ошибки', 3, 'error');
				}
			}
			else 
			{
				console_line('Блок не проверен или содержит ошибки', 3, 'error');
			}
		}
		else 
		{
			$output = 0;
			console_line('Сумма баллов блока:'.$output, 3);
		}
		return $output;
	}

	// Получение блока из всех источников (база, файлы, текст)
	private function get_from_all ($request = '')
	{
		$arBlock = $this->get_from_db($request); // сначала берем из базы данных
		if (empty($arBlock)) $arBlock = $this->get_from_file($request); // если не найдено ранее, то берем из файла
		if (empty($arBlock)) $arBlock = $this->get_from_text($request); // если не найдено ранее, то берем из текста
		return (!empty($arBlock)) ? $arBlock : false;
	}

	// Получение блока из базы
	private function get_from_db ($block_id = '')
	{
		$arBlock = array();
		if (empty($block_id)) $block_id = $this->id;
		$sql =	'SELECT * FROM blocks WHERE number=\''.$block_id.'\'';
		$block = q($sql);
		if (!empty($block)) 
		{
			$arBlock = $block->fetch_assoc();
			if (!empty($arBlock)) 
			{
				console_line('<b>'.$block_id.':</b> данные получены из базы.', 3);
				return explode(PHP_EOL, $arBlock['content']);
			}
			else 
			{
				console_line('<b>'.$block_id.':</b> блок в базе данных не найден.', 3, 'error');
				return false;
			}
		}
		else 
		{
			console_line('<b>'.$block_id.':</b> ошибка при поиске блока в базе.', 3, 'error');
			return false;
		}
	}

	// Получение блока из файла
	private function get_from_file ($file_name = '')
	{
		if (empty($file_name)) $file_route = BLOCKS_DIR.$this->id; else $file_route = BLOCKS_DIR.$file_name;
		if (is_file($file_route)) 
		{
			console_line('<b>'.$this->id.':</b> данные получены из файла.', 3);
			return array_map('trim', file(BLOCKS_DIR.$this->id));
		}
		else 
		{
			console_line('<b>'.$this->id.':</b> файл блока не найден.', 3, 'error');
			return false;
		}
	}

	// Чтение блока из текста JSON
	private function get_from_text ($text = '')
	{
		if (is_string($text)) 
		{
			console_line('<b>'.$this->id.':</b> данные получены из текста.', 3);
			return explode(PHP_EOL, $text);
		}
		else 
		{
			console_line('Невозможно загрузить текст содержимого блока', 3, 'error');
			return false;
		}
	}

	// Тестирование заголовка блока
	private function test_head ()
	{
		if ((!empty($this->head))&&(is_array($this->head))) 
		{
			$wrong_items = false;
			if ($this->head['parameters']['n']!=$this->id) 
			{
				console_line('Ошибочный номер блока.', 3);
				$wrong_items = true;
			}
			if ($this->head['parameters']['p']!=$this->previous->head['parameters']['n']) 
			{
				console_line('Номер предыдущего блока указан неверно.', 3);
				$wrong_items = true;
			}
			if ($this->head['parameters']['h']!=$this->previous->proof['parameters']['7']) 
			{
				console_line('Хэш предыдущего блока указан неверно.', 3);
				$wrong_items = true;
			}
			if (!is_timestamp($this->head['parameters']['t'])) 
			{
				console_line('Невозможно прочитать таймштамп блока.', 3);
				$wrong_items = true;
			}
			if ($this->head['parameters']['t']>=gmdate('U')) 
			{
				console_line('Время блока еще не пришло. Указан таймштамп будущего времени.', 3);
				$wrong_items = true;
			}
			if ($this->head['parameters']['t']<=$this->previous->head['parameters']['t']+MIN_TIME) 
			{
				console_line('Слишком быстро сформирован блок с момента формирования прошлого блока.', 3);
				$wrong_items = true;
			}
			// Если не найдено ни одной ошибки
			if (!$wrong_items) 
			{
				console_line('Заголовок блока '.$this->id.' прошел проверку.', 3);
				return true;
			}
			else 
			{
				console_line('Неправильный заголовок блока '.$this->id.'.', 3, 'error');
				return false;
			}
		}
		else 
		{
			console_line('Не найден заголовок блока '.$this->id.'.', 3, 'error');
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
			if (($this->transactions['0']['key']=='no')&&(count($this->transactions)==1)) 
			{
				console_line('Проставлена отметка об отсутствии транзакций в блоке '.$this->id.'.', 3);
			}
			else 
			{
				foreach ($this->transactions as $transaction) 
				{
					$arTest = transaction_test($transaction['key'], $transaction['parameters']);
					if (!$arTest['is_ok']) $wrong_items = true;
					$this->indicators['3'] += $arTest['denomination']; // сила транзакций
					$this->fee += $arTest['fee'];
				}
			}
			$this->indicators['3'] = to_cent($this->indicators['3']);
			$this->fee = to_cent($this->fee);
			if (!$wrong_items) 
			{
				console_line('Все транзакции блока '.$this->id.' валидны.', 3);
				return true;
			}
			else 
			{
				console_line('Некоторые транзакции блока '.$this->id.' не смогли пройти проверку.', 3, 'error');
				return false;
			}
		}
		else 
		{
			console_line('В блоке '.$this->id.' отсутствуют транзакции.', 3, 'error');
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
			if (($this->intentions['0']['key']=='no')&&(count($this->intentions)==1)) 
			{
				console_line('Проставлена отметка об отсутствии намерений в блоке '.$this->id, 3);
			}
			else 
			{
				foreach ($this->intentions as $intention) 
				{
					$arTest = transaction_test($intention['key'], $intention['parameters']);
					if (!$arTest['is_ok']) $wrong_items = true;
					$this->indicators['4'] += $arTest['denomination']; // сила намерений
				}
			}
			$this->indicators['4'] = to_cent($this->indicators['4']);
			if (!$wrong_items) 
			{
				console_line('Все намерения блока '.$this->id.' валидны.', 3);
				return true;
			}
			else 
			{
				console_line('Некоторые намерения блока '.$this->id.' не смогли пройти проверку.', 3, 'error');
				return false;
			}
		}
		else 
		{
			console_line('В блоке '.$this->id.' отсутствуют намерения.', 3, 'error');
			return false;
		}
	}

	// Тестирование строки об эмиссии
	private function test_issue ()
	{
		if ((!empty($this->issue))&&(is_array($this->issue))) 
		{
			$issue = $this->issue['parameters'];
			$wrong_items = false;
			if (count($issue)!=4) 
			{
				console_line('Ошибочный формат строки об эмиссии.', 3);
				$wrong_items = true;
			}
			$block_award = to_cent(issue_value($this->id)+$this->fee);
			if ((!float_equals($issue[3], $block_award))||($block_award<=0)) 
			{
				console_line('Ошибочно указан номинал эмитируемой банкноты.', 3);
				$wrong_items = true;
			}
			$encrypt = new cEncrypt(['algorithm'=>$issue[2]]);
			if (!$encrypt->algorithm) 
			{
				console_line('Ошибочно указан алгоритм шифрования эмитируемой банкноты.', 3);
				$wrong_items = true;
			}
			$example = array
			(
				'number' => $issue[0],
				'sign' => $issue[1],
				'algorithm' => $issue[2],
				'denomination' => to_cent($issue[3]),
				'timestamp' => $this->head['parameters']['t'],
			);
			$issue_bill = new cIssue($example);
			if (!$issue_bill->create(false)) 
			{
				console_line('Указанная банкнота не может быть эмитирована.', 3);
				$wrong_items = true;
			}
			if (!$wrong_items) 
			{
				console_line('Запись об эмиссии в блоке '.$this->id.' прошла проверку', 3);
				return true;
			}
			else 
			{
				console_line('Запись об эмиссии в блоке '.$this->id.' содержит ошибки', 3, 'error');
				return false;
			}
		}
		else 
		{
			console_line('В блоке отсутствует запись об эмиссии '.$this->id, 3, 'error');
			return false;
		}
	}

	// Тестирование задания для следующего блока
	private function test_quest ()
	{
		if ((!empty($this->quest))&&(is_array($this->quest))) 
		{
			$wrong_items = false;
			// Проверка формата значений
			foreach ($this->quest['parameters'] as $item) if (!is_num($item)) $wrong_items = true;
			if (count($this->quest['parameters'])!=NUM_QUEST) $wrong_items = true;
			if (array_sum($this->quest['parameters'])!=SUM_QUEST) $wrong_items = true;
			// Расчет правильности показателей
			if (count($this->indicators)==NUM_QUEST) ksort($this->indicators); else $wrong_items = true;
			$block_score = tally_indicators($this->indicators, $this->id);
			$prev_score = tally_indicators($this->previous->proof['parameters'], $this->previous->id);
			if ((count($prev_score)==count($block_score))&&(count($prev_score)==NUM_QUEST)) 
			{
				$diff_score = array_map('quests_difference', $block_score, $prev_score);
				if ((count($diff_score)==count($this->previous->quest['parameters']))&&(count($diff_score)==NUM_QUEST)) 
				{
					$block_quest = array_map('quests_calculate', $diff_score, $this->previous->quest['parameters']);
					while (array_sum($block_quest)!=SUM_QUEST) 
					{
						foreach ($block_quest as $key => $item) $arQuest[$key] = array('k'=>$key, 'quest'=>$block_quest[$key], 'difference'=>$diff_score[$key]);
						usort($arQuest, 'quests_sort');
						if (array_sum($block_quest)<SUM_QUEST) 
						{
							$level = 0;
							$is_updated = false;
							while (!$is_updated) 
							{
								if ($arQuest[$level]['quest']<MAX_QUEST) 
								{
									$arQuest[$level]['quest']++;
									$is_updated = true;
								}
								$level++;
							}
						}
						if (array_sum($block_quest)>SUM_QUEST) 
						{
							$level = NUM_QUEST-1;
							$is_updated = false;
							while (!$is_updated) 
							{
								if ($arQuest[$level]['quest']>MIN_QUEST) 
								{
									$arQuest[$level]['quest']--;
									$is_updated = true;
								}
								$level--;
							}
						}
						foreach ($arQuest as $item) $block_quest[$item['k']] = $item['quest'];
					}
					if ($this->quest['parameters']!=$block_quest) $wrong_items = true;
				}
				else 
				{
					$wrong_items = true;
				}
			}
			else 
			{
				$wrong_items = true;
			}
			$result = !$wrong_items;
		}
		else 
		{
			$result = false;
		}
		if (!$result) console_line($this->id.': ошибка при проверке задания для следующего блока.', 3, 'error');
		if ($result) console_line($this->id.': задание для следующего блока указано верно.', 3);
		return $result;
	}

	// Тестирование проверки PoW
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
				&&(is_denomination($this->proof['parameters']['9'])) // номинал эмитируемой банкноты
				) 
			{
				ksort($this->indicators); // упорядочивание показателей блока для сравнения с PoW
				if ($this->indicators==$this->proof['parameters']) 
				{
					console_line('Проверка PoW для блока '.$this->id.' прошла успешно', 3);
					return true;
				}
				else 
				{
					console_line('Ошибка при проверке PoW для блока '.$this->id, 3, 'error');
					return false;
				}
			}
			else 
			{
				console_line('Невозможно распознать PoW для блока '.$this->id, 3, 'error');
				return false;
			}
		}
		else 
		{
			console_line('Отсутствует информация о проверке PoW блока '.$this->id, 3, 'error');
			return false;
		}
	}
}
?>