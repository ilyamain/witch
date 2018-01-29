<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Обработка цепочек блоков

class cChain 
{
	private $from;
	private $till;
	private $ok = false;
	private $store;
	private $blocks = array();
	private $request_list = array();
	private $bills = array // списки банкнот в незафиксированных блоках
	(
		'input' => array(),
		'output' => array(),
	);

	// Получение цепочки блоков из заданного интервала
	// $chain_interval['from'] - номер блока начала интервала
	// $chain_interval['till'] - номер блока конца интервала
	// $store - источник хранения блоков. Например: 'db' (по умолчанию), 'file', 'text'.
	// $request_list - если источник блока требует указать запрос (например, если источник 'text'), 
	// то для каждого блока интервала задается текст этого запроса. Формат массива:
	// array($block_number_1 => 'block_request_1', $block_number_2 => 'block_request_2', ..., $block_number_last => 'block_request_last')
	public function cChain ($chain_interval = array('from' => '', 'till' => ''), $store = '', $request_list = array())
	{
		if (is_array($chain_interval)) 
		{
			if ((empty($this->from))&&(is_num($chain_interval['from']))) $this->from = $chain_interval['from'];
			if ((empty($this->till))&&(is_num($chain_interval['till']))) $this->till = $chain_interval['till'];
		}
		if (empty($this->from)) $this->from = GENESIS_ID;
		if (empty($this->till)) $this->till = GENESIS_ID;
		if ((is_num($this->from))&&(is_num($this->till))&&($this->from <= $this->till)) 
		{
			$wrong_items = false;
			$this->store = (empty($store)) ? BLOCK_MAIN_STORE : $store;
			$this->request_list = $request_list;
			if ((is_string($this->store))&&(is_array($this->request_list))) 
			{
				$this->blocks = array();
				$block_id = $this->from;
				// Чтение блоков цепочки
				while ($block_id <= $this->till) 
				{
					$arTemp = array();
					$block = new cBlocks($block_id);
					$block->read($this->store, $this->request_list[$block_id]);
					$previous_block = array
					(
						'previous_store' => $this->store,
						'previous_request' => $this->request_list[($block->get('head'))['parameters']['p']],
					);
					if ($block_id > $this->from) $block->test($previous_block); else $block->test();
					$arTemp['block'] = $block; // содержимое блока
					// Проверка допустимости блока и расчет баллов всей цепочки
					if ($block->get('ok')) 
					{
						$arTemp['score'] = $block->score(); // баллы блока
						write('Блок '.$block->get('id').' прошел проверку на валидность.', 4);
					}
					else 
					{
						write('Блок '.$block->get('id').' содержит ошибки.', 4, 'error');
						$wrong_items = true;
						$this->blocks = array();
						$this->ok = false;
						return false;
					}
					// Проверка соответствия хэшей и сложности
					$arEase = array();
					$iterator = EASE_LENGTH;
					$current_block = $block;
					while ($iterator > 0) 
					{
						$current_block_id = $current_block->get('id');
						if ($current_block_id > GENESIS_ID) 
						{
							$prev_id = $current_block_id-1;
							if ($current_block_id > $this->from) 
							{
								$current_block = $this->blocks[$prev_id]['block'];
							}
							else 
							{
								$current_block = new cBlocks($prev_id);
								$current_block->read();
							}
							$arHash = array();
							$arHash['hash_issue'] = ($current_block->get('issue'))['parameters'][1];
							$arHash['hash_proof'] = ($current_block->get('proof'))['parameters'][7];
							array_push($arEase, $arHash);
						}
						else 
						{
							break;
						}
						$iterator--;
					}
					$block_ease = $block->test_hashes();
					$chain_ease = chain_ease($arEase);
					if ($block_ease < $chain_ease) 
					{
						write('Сложность блока: '.$block_ease.'. Сложность сети: '.$chain_ease.'.', 4);
						write('Хэш блока '.$block->get('id').' принят.', 4);
					}
					else 
					{
						write('Сложность блока: '.$block_ease.'. Сложность сети: '.$chain_ease.'.', 4, 'error');
						write('Хэш блока '.$block->get('id').' не соответствует уровню сложности.', 4, 'error');
						$wrong_items = true;
						$this->blocks = array();
						$this->ok = false;
						return false;
					}
					// Проверка уникальности банкнот в последних BLOCK_FREE блоках
					if (($block_id > ($this->till-BLOCK_FREE))&&(is_array($block->get('bills')))) 
					{
						// Проверка входных банкнот
						$block_bills = $block->get('bills');
						if ((!empty($block_bills['input']))&&(is_array($block_bills['input']))) 
						{
							foreach ($block_bills['input']['transactions'] as $bill_number) 
							{
								if (in_array($bill_number, $this->bills['input'])) $wrong_items = true;
								array_push($this->bills['input'], $bill_number);
							}
							foreach ($block_bills['input']['intentions'] as $bill_number) 
							{
								if (in_array($bill_number, $this->bills['input'])) $wrong_items = true;
								array_push($this->bills['input'], $bill_number);
							}
						}
						// Проверка выходных банкнот
						if ((!empty($block_bills['output']))&&(is_array($block_bills['output']))) 
						{
							foreach ($block_bills['output']['transactions'] as $bill_number) 
							{
								if (in_array($bill_number, $this->bills['output'])) $wrong_items = true;
								array_push($this->bills['output'], $bill_number);
							}
							foreach ($block_bills['output']['intentions'] as $bill_number) 
							{
								if (in_array($bill_number, $this->bills['output'])) $wrong_items = true;
								array_push($this->bills['output'], $bill_number);
							}
						}
						// Проверка эмитируемых банкнот
						$block_issue = $block->get('issue');
						$bill_number = $block_issue['parameters'][0];
						if (in_array($bill_number, $this->bills['input'])) $wrong_items = true;
						if (in_array($bill_number, $this->bills['output'])) $wrong_items = true;
						array_push($this->bills['input'], $bill_number);
						array_push($this->bills['output'], $bill_number);
					}
					// Добавляем блок в цепочку
					if (!$wrong_items) $this->blocks[$block_id] = $arTemp;
					$block_id++;
				}
			}
			else 
			{
				write('Запрос цепочки блоков содержит ошибки.', 4, 'error');
				$wrong_items = true;
			}
			// Сортировка блоков
			ksort($this->blocks);
			// Загрузка цепочки блоков
			if (!$wrong_items) 
			{
				write('Цепочка блоков '.$this->from.' - '.$this->till.' прочитана.', 4);
				$this->ok = true;
				return true;
			}
			else 
			{
				write('Цепочка блоков содержит ошибки.', 4, 'error');
				$this->blocks = array();
				$this->ok = false;
				return false;
			}
		}
		else 
		{
			write('Ошибочно указаны крайние блоки цепочки.', 4, 'error');
			$this->blocks = array();
			$this->ok = false;
			return false;
		}
		
	}

	// Подсчет суммы баллов для всей цепочки
	public function score_sum ()
	{
		$chain_sum = 0;
		if ((!empty($this->blocks))&&(is_array($this->blocks))&&($this->ok)) 
		{
			foreach ($this->blocks as $item) $chain_sum += $item['score'];
			write('Сумма баллов цепочки блоков: '.$chain_sum.'.', 4);
		}
		return $chain_sum;
	}

	// Проверка наличия в цепочке зафиксированных блоков
	public function has_commited ()
	{
		$has_commited = false;
		if ((!empty($this->blocks))&&(is_array($this->blocks))&&($this->ok)) 
		{
			foreach ($this->blocks as $item) if ($item['block']->get('commited')) $has_commited = true;
		}
		return $has_commited;
	}

	// Чтение параметров цепочки блоков
	public function get ($option)
	{
		return (property_exists($this, $option)) ? $this->$option : false;
	}
}

// Сравнение цепочек блоков и выбор лучшей
function chain_comparison ($main_chain, $alter_chain)
{
	$main_length = $main_chain->get('till')-$main_chain->get('from');
	$alter_length = $alter_chain->get('till')-$alter_chain->get('from');
	if (($main_chain >= EASE_LENGTH)||($alter_length >= EASE_LENGTH)) 
	{
		write('Длина сравниваемых цепочек превышает максимально допустимую.', 4);
		return false;
	}
	if (!$alter_chain->get('ok')) 
	{
		write('В предложенной цепочке обнаружены ошибки.', 4);
		return false;
	}
	if (!$main_chain->get('ok')) 
	{
		write('В основной цепочке были обнаружены ошибки.', 4);
		return true;
	}
	if ($main_chain->get('from') != $alter_chain->get('from')) 
	{
		write('Основная и предложенная цепочки не имеют общего начала.', 4);
		return false;
	}
	if ($main_chain->score_sum() < $alter_chain->score_sum()) 
	{
		write('По баллам выбираем предложенную цепочку.', 4);
		return true;
	}
	else 
	{
		write('По баллам выбираем основную цепочку.', 4);
		return false;
	}
}

// Расчет сложности блока по хэшам предыдущих блоков
function chain_ease ($chain_hashes)
{
	$arDifferences = array(EASE_CORRECTION);
	foreach ($chain_hashes as $item) array_push($arDifferences, hash_difference($item['hash_proof'], $item['hash_issue']));
	$result = intdiv(array_sum($arDifferences), count($arDifferences));
	return $result;
}

// Список банкнот в цепочке незакоммиченных блоков
function chain_busy_bills ($chain = false)
{
	if (empty($chain)) 
	{
		$from = updates_blocks_stores(BLOCK_MAIN_STORE, 'min_block');
		$till = updates_blocks_stores(BLOCK_MAIN_STORE, 'max_block');
		$last_chain = new cChain(['from' => $from, 'till' => $till]);
	}
	else 
	{
		$last_chain = $chain;
	}
	return $last_chain->get('bills');
}

// Обновление цепочки блоков
function chain_update ($chain_new, $forced = false)
{
	$from = $chain_new->get('from');
	$till = updates_blocks_stores(BLOCK_MAIN_STORE, 'max_block');
	// Считывание прежней цепочки
	$chain_old = new cChain(['from' => $from, 'till' => $till]);
	if ((chain_comparison($chain_old, $chain_new))||($forced)) 
	{
		if ($forced) write('Усиленная замена цепочки.', 4, 'attract');
		write('Предложенная цепочка принята. Выполняется замена прежней цепочки.', 4);
		// Если блоки не зафиксированы, то замена прежних блоков на предложенные
		// Если блоки зафиксированы, то пересчет всей базы данных
		write('Создание резервной копии', 4);
		updates_blocks_stores(BLOCK_MAIN_STORE, 'backup_create');
		$wrong_items = false;
		if ($chain_old->has_commited()) 
		{
			// Считывание цепочки, предшествующей предложенной
			$from_temp = GENESIS_ID;
			$till_temp = $from-1;
			$base = new cBase;
			// Если новая цепочка начинается не с генезис-блока, то пересчет всех предыдущих блоков
			if ($till_temp >= $from_temp) 
			{
				$chain_temp = new cChain(['from' => $from_temp, 'till' => $till_temp]);
				if (!$chain_temp->get('ok')) $wrong_items = true;
				if (!$base->tables_del_mask('bc_')) $wrong_items = true;
				// Задание стартовых распределенных данных
				$arTables = array();
				foreach (bc_data() as $key => $table) $arTables[$key] = $table;
				if (!$base->tables_create($arTables)) 
				{
					write('Произошла ошибка при получении стартовых распределенных данных.', 4);
					$wrong_items = true;
				}
				// Ввод данных до предложенной цепочки
				foreach ($chain_temp->get('blocks') as $item) 
				{
					// Не добавляем генезис-блок, так как он уже добавлен
					if ($item['block']->get('id') > GENESIS_ID) 
					{
						write('Добавление блока '.$item['block']->get('id').' в цепочку.', 4);
						if (!updates_blocks_stores(BLOCK_MAIN_STORE, 'block_create', $item['block']->get('id'), $item['block']->assemble())) $wrong_items = true;
					}
					// Ранее проверялось, что новая цепочка содержит зафиксированные блоки,
					// поэтому все предыдущие блоки должны быть зафиксированы
					if ($item['block']->compile()) 
					{
						write('Блок '.$item['block']->get('id').' зафиксирован.', 4);
						// Проставление отметки в базе о фиксации блока
						if (!updates_blocks_stores(BLOCK_MAIN_STORE, 'block_mark', $item['block']->get('id'))) $wrong_items = true;
					}
					else 
					{
						$wrong_items = true;
					}
				}
			}
			else 
			{
				if (!$base->tables_del_mask('bc_')) $wrong_items = true;
				// Задание стартовых распределенных данных
				$arTables = array();
				foreach (bc_data() as $key => $table) $arTables[$key] = $table;
				if (!$base->tables_create($arTables)) 
				{
					write('Произошла ошибка при получении стартовых распределенных данных.', 4);
					$wrong_items = true;
				}
			}
			// Пересчет блоков предложенной цепочки
			foreach ($chain_new->get('blocks') as $item) 
			{
				// Не добавляем генезис-блок, так как он уже добавлен
				if ($item['block']->get('id') > GENESIS_ID) 
				{
					write('Добавление блока '.$item['block']->get('id').' в цепочку.', 4);
					if (!updates_blocks_stores(BLOCK_MAIN_STORE, 'block_create', $item['block']->get('id'), $item['block']->assemble())) $wrong_items = true;
				}
				$till_max = max($chain_new->get('till'), $till);
				if ($item['block']->get('id') <= $till_max-BLOCK_FREE) 
				{
					if ($item['block']->compile()) 
					{
						write('Блок '.$item['block']->get('id').' зафиксирован.', 4);
						// Проставление отметки в базе о фиксации блока
						if (!updates_blocks_stores(BLOCK_MAIN_STORE, 'block_mark', $item['block']->get('id'))) $wrong_items = true;
					}
					else 
					{
						$wrong_items = true;
					}
				}
			}
		}
		else 
		{
			write('Удаление блоков старой цепочки.', 4);
			if (!updates_blocks_stores(BLOCK_MAIN_STORE, 'block_delete', $from)) $wrong_items = true;
			foreach ($chain_new->get('blocks') as $item) 
			{
				write('Добавление блока '.$item['block']->get('id').' в цепочку.', 4);
				if (!updates_blocks_stores(BLOCK_MAIN_STORE, 'block_create', $item['block']->get('id'), $item['block']->assemble())) $wrong_items = true;
				$till_max = max($chain_new->get('till'), $till);
				// Если принимаемая цепочка длинее существующей, то часть блоков необходимо зафиксировать
				if ($item['block']->get('id') <= $till_max-BLOCK_FREE) 
				{
					if ($item['block']->compile()) 
					{
						write('Блок '.$item['block']->get('id').' зафиксирован.', 4);
						// Проставление отметки в базе о фиксации блока
						if (!updates_blocks_stores(BLOCK_MAIN_STORE, 'block_mark', $item['block']->get('id'))) $wrong_items = true;
					}
					else 
					{
						$wrong_items = true;
					}
				}
			}
		}
		// Удаление бэкапа. Или, в случае возникновения ошибок, восстанавление всех распределенных данных в исходное состояние
		if ($wrong_items) 
		{
			write('Произошла ошибка. Восстанавление из резервной копии.', 4, 'error');
			if (updates_blocks_stores(BLOCK_MAIN_STORE, 'backup_restore')) 
			{
				write('База данных восстановлена. Удаление резервной копии', 4);
				updates_blocks_stores(BLOCK_MAIN_STORE, 'backup_empty');
			}
		}
		else 
		{
			write('Удаление резервной копии.', 4);
			updates_blocks_stores(BLOCK_MAIN_STORE, 'backup_empty');
		}
		if (!$forced) foreach ($chain_old->get('blocks') as $item) $item['block']->orphan();
		return ($wrong_items) ? false : true;
	}
	else 
	{
		write('Предложенная цепочка отклонена.', 4);
		if (!$forced) foreach ($chain_new->get('blocks') as $item) $item['block']->orphan();
		return false;
	}
}

// Обновление цепочки блоков
function chain_commit_main_store ()
{
	$from = updates_blocks_stores(BLOCK_MAIN_STORE, 'min_block');
	$till = updates_blocks_stores(BLOCK_MAIN_STORE, 'max_block');
	$chain = new cChain(['from' => $from, 'till' => $till]);
	chain_update ($chain, true);
}

// Выгрузка цепочки блоков в файлы
function chain_backup ($only_commited = true, $specify_from = GENESIS_ID)
{
	$wrong_items = false;
	// Удаление имеющихся блоков
	$from = $specify_from;
	if (!updates_blocks_stores(BLOCK_FILE_STORE, 'block_delete', $from)) 
	{
		write('Блоки не удалены', 4);
		$wrong_items = true;
	}
	// Чтение блоков из базы данных
	$max = ($only_commited) ? (updates_blocks_stores(BLOCK_MAIN_STORE, 'min_block')-1) : updates_blocks_stores(BLOCK_MAIN_STORE, 'max_block');
	$till = max($max, $specify_from);
	$block_id = $from;
	while ($block_id <= $till) 
	{
		$block = new cBlocks($block_id);
		$block->read();
		$block_assemble = $block->assemble();
		if (!updates_blocks_stores(BLOCK_FILE_STORE, 'block_create', $block_id, $block_assemble)) $wrong_items = true;
		$block_id++;
	}
	if ($wrong_items) 
	{
		write('Произошла ошибка при создании резервной копии. Попробуйте скопировать блоки вручную.', 4, 'error');
	}
	else 
	{
		write('Блоки '.$from.' - '.$till.' успешно сохранены.', 4, 'success');
	}
}
?>