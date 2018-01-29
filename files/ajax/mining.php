<?define('AJAX_FORM', true);?>
<?require_once ($_SERVER[DOCUMENT_ROOT].DIRECTORY_SEPARATOR.'load'.DIRECTORY_SEPARATOR.'start.php');?>
<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Команды для майнера

if (local()) 
{
	if (!empty($_POST['action'])) 
	{
		$base = new cBase;
		$action = $_POST['action'];
		if ($action == 'peers_clean') 
		{
			write('Подключение к сети.', 5);
			miners_clean();
		}
		if ($action == 'peers_update') 
		{
			// Получение локального списка майнеров
			$local = new cConnect(CONNECT_ANSWER);
			$arMiners = ($local->connected) ? $local->handle('miners') : array();
			// Запрос списка пиров у дргуих майнеров
			$remote = new cConnect(CONNECT_REQEST, $arMiners);
			// Освобождение отключенных записей из таблицы
			if (!empty($remote->miners)) 
			{
				$write_once = true;
				foreach ($remote->miners as $key => $remote_miner) 
				{
					if (!$remote_miner['connected']) 
					{
						unset($remote->miners[$key]);
						$base->miners_del($remote_miner['miner_name']);
						if ($write_once) write('Освобождение отключенных пиров.', 5);
						$write_once = false;
					}
				}
			}
			// Если после освобождения отключенных записей из таблицы не осталось майнеров,
			// то добавляем подключения к трекерам. Если остались, то соединяемся с оставшимися майнерами
			if ((!empty($remote->miners))&&($remote->connected)) 
			{
				// Получение списка имеющихся пиров от каждого майнера, к которому подключились по запросу из локального списка
				write('Подключение к пирам.', 5);
				$miners_peers_list = ($remote->connected) ? $remote->handle('miners') : array();
				if (!empty($miners_peers_list)) foreach ($miners_peers_list as $arPeers) 
				{
					if (!empty($arPeers)) foreach ($arPeers as $miner) miners_request($miner); // загрузка списка пиров
				}
				// Отправка другим майнерам информации о себе, как о новом участнике
				if ($base->constant_get('white_ip')) 
				{
					$new_miner = array
					(
						'miner_name' => $base->constant_get('miner_name'), 
						'miner_type' => $base->constant_get('miner_type'), 
						'miner_link' => $base->constant_get('miner_link'), 
					);
					if ($remote->connected) $remote->handle('miner_send', $new_miner);
				}
			}
			else 
			{
				write('Соединение прервано.', 5, 'error');
				if (empty($base->miners_get_all())) miners_initiate();
			}
		}
		if ($action == 'chain_update') 
		{
			// Получение локального списка майнеров
			$local = new cConnect(CONNECT_ANSWER);
			$arMiners = ($local->connected) ? $local->handle('miners') : array();
			// Запрос хэшей блоков у дргуих майнеров
			$remote = new cConnect(CONNECT_REQEST, $arMiners);
			if ((!empty($remote->miners))&&($remote->connected)) 
			{
				// Получение списка хэшей блоков от каждого майнера, к которому подключились по запросу из локального списка
				$hashes_full_list = ($remote->connected) ? $remote->handle('chain_hashes') : array();
				//в разработке (Загрузка блоков от других майнеров)
			}
		}
		if ($action == 'mining_start') 
		{
			// Загрузка команд от других майнеров в пул
			$pool = new cPool;
			// Получение локального списка майнеров
			$local = new cConnect(CONNECT_ANSWER);
			$arMiners = ($local->connected) ? $local->handle('miners') : array();
			$remote = new cConnect(CONNECT_REQEST, $arMiners);
			if ((!empty($remote->miners))&&($remote->connected)) 
			{
				// Получение списка команд (транзакций и/или намерений) от каждого майнера
				$remote_pool_list = ($remote->connected) ? $remote->handle('pool_list') : array();
				foreach ($remote_pool_list as $remote_pool) 
				{
					if ((!empty($remote_pool))&&(is_array($remote_pool))) foreach ($remote_pool as $entity) 
					{
						$type = transaction_split($entity, true);
						$json = transaction_split($entity, false);
						$arAction = transaction_test($type, json_decode($json, true));
						if ($arAction['ok']) $pool->add($entity); // добавление валидных транзакций
					}
				}
			}
			// Простейший метод формирования блока. Не выбирает наиболее оптимальные команды для формирования блока
			$arPool = $pool->full_list();
			$transactions = array();
			$intentions = array();
			// Проверка наличия одинаковых банкнот в формируемом блоке и в нефиксированных блоках
			// Облегченная версия проверок отсутствия банкнот в формируемом и в нефиксированных блоках
			$arBills = array('input' => array(), 'output' => array()); 
			$first_free_block = updates_blocks_stores(BLOCK_MAIN_STORE, 'min_block');
			$free_blocks = updates_blocks_stores(BLOCK_MAIN_STORE, 'get_last', $first_free_block);
			foreach ($free_blocks as $block) 
			{
				$arTransactions = block_section_decode('>', block_section($block['content'], [], ['>']));
				foreach ($arTransactions as $iTransactions) 
				{
					$arTest = transaction_test($iTransactions['key'], $iTransactions['parameters']);
					foreach ($arTest['number'] as $bill_number) array_push($arBills['input'], $bill_number);
					foreach ($arTest['output'] as $bill_number) array_push($arBills['output'], $bill_number);
				}
				$arIntentions = block_section_decode('@', block_section($block['content'], [], ['@']));
				foreach ($arIntentions as $iIntentions) 
				{
					$arTest = transaction_test($iIntentions['key'], $iIntentions['parameters']);
					foreach ($arTest['number'] as $bill_number) array_push($arBills['input'], $bill_number);
					foreach ($arTest['output'] as $bill_number) array_push($arBills['output'], $bill_number);
				}
				$issue = block_section_decode('*', block_section($block['content'], [], ['*i']));
				array_push($arBills['output'], $issue['parameters'][0]);
			}
			$fee_sum = 0;
			if (!empty($arPool)) foreach ($arPool as $entity) 
			{
				$type = transaction_split($entity, true);
				$json = transaction_split($entity, false);
				$arAction = transaction_test($type, json_decode($json, true));
				if (!$arAction['ok']) 
				{
					$pool->del($entity); // удаление невалидных транзакций
				}
				else 
				{
					$pass_it = false;
					// Проверка на уникальность входных банкнот
					foreach ($arAction['number'] as $bill_number) 
					{
						if (in_array($bill_number, $arBills['input'])) $pass_it = true; else array_push($arBills['input'], $bill_number);
					}
					// Проверка на уникальность выходных банкнот
					foreach ($arAction['output'] as $bill_number) 
					{
						if (in_array($bill_number, $arBills['output'])) $pass_it = true; else array_push($arBills['output'], $bill_number);
					}
					if (!$pass_it) 
					{
						if ($arAction['is'] == 'transaction') 
						{
							$fee_sum += $arAction['fee'];
							array_push($transactions, $entity);
						}
						if ($arAction['is'] == 'intention') array_push($intentions, $entity);
					}
				}
			}
			if (empty($transactions)) $transactions = array('no:[]');
			if (empty($intentions)) $intentions = array('no:[]');
			$issue = '';
			// Транзакции и намерения проверены ранее. Формируем из них текстовый блок
			$prev_id = updates_blocks_stores(BLOCK_MAIN_STORE, 'max_block');
			$block_id = $prev_id + 1;
			$prev_block_text = updates_blocks_stores(BLOCK_MAIN_STORE, 'get', $prev_id);
			$prev_block_head = block_section_decode('*', block_section($prev_block_text, [], ['*b']));
			$prev_block_proof = block_section_decode('*', block_section($prev_block_text, [], ['*p']));
			$timestamp = $prev_block_head['parameters']['t'];
			// Формирование заголовка блока
			$string_head = '*b:{"h":"'.strval($prev_block_proof['parameters']['7']).'","n":"'.strval($block_id).'","p":"'.strval($prev_id).'","t":"'.strval($timestamp+BLOCK_TIME).'"}';
			// Проверка допустимости таймштампа блока
			if ($timestamp >= gmdate('U')) die('{"world_time":"'.strval(gmdate('U')).'","block_time":"'.strval($timestamp).'"}');
			// Формирование транзакций блока
			$string_transactions = '';
			foreach ($transactions as $item) $string_transactions .= '>'.$item.PHP_EOL;
			// Формирование намерений блока
			$string_intentions = '';
			foreach ($intentions as $item) $string_intentions .= '@'.$item.PHP_EOL;
			// Формирование текста блока
			$result_text = $string_head.$string_transactions.$string_intentions;
			// Расчет номинала эмитируемой банкноты
			$result_sum = to_cent($fee_sum + issue_value($block_id));
			// Расчет требуемой сложности создаваемого блока (все предыдущие блоки принимаются из основного хранилища)
			$arEase = array();
			$iterator = EASE_LENGTH;
			$current_block_id = $block_id;
			while ($iterator > 0) 
			{
				if ($current_block_id <= GENESIS_ID) break; // прерывание цикла, если длина проверяемой цепочки достигла генезис-блока
				$current_block_id--;
				$current_block_text = updates_blocks_stores(BLOCK_MAIN_STORE, 'get', $current_block_id);
				$current_block_issue = block_section_decode('*', block_section($current_block_text, [], ['*i']));
				$current_block_proof = block_section_decode('*', block_section($current_block_text, [], ['*p']));
				$arHash = array 
				(
					'hash_issue' => $current_block_issue['parameters'][1],
					'hash_proof' => $current_block_proof['parameters'][7],
				);
				array_push($arEase, $arHash);
				$iterator--;
			}
			$result_ease = chain_ease($arEase);
			// Выбор номера банкноты для эмиссии. Этот код допускается изменять осторожно, чтобы не 
			// совпал номер эмитируемой банкноты с имеющимися. В случае ошибки, сеть может отклонить 
			// сформированный блок
			$result_bill = 'i'.str_pad($block_id, 8, '0', STR_PAD_LEFT).abra(3);
			while (in_array($result_bill, $arBills['output'])) $result_bill = 'i'.str_pad($block_id, 8, '0', STR_PAD_LEFT).abra(3);
			array_push($arBills['output'], $result_bill);
			$result = array
			(
				'id' => strval($block_id), 
				'text' => $result_text, 
				'sum' => $result_sum, 
				'ease' => $result_ease, 
				'bill' => $result_bill, 
				'score' => (new cBase)->constant_get('hash_score'), 
			);
			// Возвращаем в JS результат в чистом виде, без комментариев модулей, отправляемых в консоль
			die(json_encode($result));
		}
		if ($action == 'block_create') 
		{
			$content = $_POST['content'];
			$bill = $_POST['issue'];
			$arBlock = updates_blocks_stores('text', '', $content['id'], $content['txt']);
			if (!empty($arBlock)) 
			{
				$transactions = block_section($arBlock, [], ['>']);
				if ((!empty($transactions))&&(is_array($transactions))) 
				{
					$transactions = array_map(function ($item) {return mb_substr($item, 1);}, $transactions);
				}
				else 
				{
					$transactions = array('no:[]');
				}
				$intentions = block_section($arBlock, [], ['@']);
				if ((!empty($intentions))&&(is_array($intentions))) 
				{
					$intentions = array_map(function ($item) {return mb_substr($item, 1);}, $intentions);
				}
				else 
				{
					$intentions = array('no:[]');
				}
				$issue = block_section($arBlock, [], ['*i']);
				if ((!empty($issue))&&(is_array($issue))) 
				{
					$issue = $issue[0];
					$block = new cBlocks($content['id']);
					$result_text = $block->shaping($issue, 'ar', $transactions, $intentions); // загрузка части блока для расчета хэш в JS
					if (($block->test())&&(is_string($result_text))) 
					{
						// Запись блока в базу данных
						updates_blocks_stores(BLOCK_MAIN_STORE, 'block_create', $block->get('id'), $result_text);
						// Фиксация предыдущих блоков. Облегченная версия. В случае возникновения ошибок, 
						// закомментируйте этот вариант фиксации предыдущих блоков перед пересчетом блоков
						$commit_block_from = updates_blocks_stores(BLOCK_MAIN_STORE, 'min_block');
						$commit_block_till = updates_blocks_stores(BLOCK_MAIN_STORE, 'max_block');
						// Фиксация только блоков от минимального свободного до нормативного (т.е. текущий блок минус BLOCK_FREE)
						if ($commit_block_from <= ($commit_block_till-BLOCK_FREE)) 
						{
							$commit_block_id = $commit_block_from;
							while ($commit_block_id <= ($commit_block_till-BLOCK_FREE)) 
							{
								$commit_block = new cBlocks($commit_block_id);
								$commit_block->test();
								$commit_block->compile();
								updates_blocks_stores(BLOCK_MAIN_STORE, 'block_mark', $commit_block_id);
								$commit_block_id++;
							}
						}
						// Фиксация предыдущих блоков. Усложненная версия. В случае возникновения ошибок, 
						// раскомментируйте этот вариант фиксации предыдущих блоков и пересчитайте блоки
						//chain_commit_main_store();
						// Очистка пула транзакций
						$block->empty_pool();
						// Добавление эмитируемой банкноты в кошелек
						(new cWallet)->add_bill($bill['number'], $bill['key'], CONNECT_ANSWER, true);
						write('Блок '.$block->get('id').' успешно создан.', 5);
					}
					else 
					{
						write('Блок не прошел проверку на валидность.', 5, 'error');
					}
				}
				else 
				{
					write('Произошла ошибка при чтении банкноты об эмиссии.', 5, 'error');
				}
			}
			else 
			{
				write('Произошла ошибка при создании блока.', 5, 'error');
			}
		}
	}
	else 
	{
		write('Невозможно определить запрошенное действие. Повторите попытку позже.', 5, 'error');
	}
}
else 
{
	write('Данная программа майнинга рассчитана только на локальную работу.', 5, 'error');
}
echo $console;
?>