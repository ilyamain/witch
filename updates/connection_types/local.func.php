<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Локальное соединение. Предназначено для отправки ответов другим майнерам, обращающимся по другим соединениям

// Запрос состояния соединения. $options - Не применяется. Локальное соединение всегда считается установленным
function updates_connection_types_local_ok ($options)
{
	return true;
}

// Запрос характеристик майнера. $options - ключ выходного массива, если требуется
function updates_connection_types_local_info ($options)
{
	$miner = array
	(
		'miner_name' => (new cBase)->constant_get('miner_name'), 
		'miner_type' => (new cBase)->constant_get('miner_type'), 
		'miner_link' => (new cBase)->constant_get('miner_link'), 
		'connections' => (new cBase)->constant_get('connections'), 
		'free block' => updates_blocks_stores(BLOCK_MAIN_STORE, 'min_block'), 
		'last_block' => updates_blocks_stores(BLOCK_MAIN_STORE, 'max_block'), 
		'miner_list' => (new cBase)->miners_get_all(), 
	);
	return (empty($options)) ? $miner : $miner[$options];
}

// Запрос списка майнеров соединения. $options - максимальное количество опрашиваемых майнеров
function updates_connection_types_local_miners ($options = 0)
{
	if (empty($options)) $options = (new cBase)->constant_get('connections');
	if (empty($options)) $options = 5;
	$output = (new cBase)->miners_get_all($options); // указываем максимальное количество опрашиваемых майнеров
	return $output;
}

// Добавление нового майнера в список. $options - массив, описывающий майнера в формате JSON
function updates_connection_types_local_miner_send ($options)
{
	$arOptions = json_decode($options, true);
	$base = new cBase;
	if ((!empty($arOptions))&&(is_array($arOptions))) 
	{
		$miner_name = $arOptions['miner_name'];
		$miner_type = $arOptions['miner_type'];
		$miner_link = $arOptions['miner_link'];
		$wrong_items = false;
		if ((empty($miner_name))||(!is_alphabet($miner_name))) $wrong_items = true;
		if ((empty($miner_type))||(!is_alphabet($miner_type))) $wrong_items = true;
		if ((empty($miner_link))||(!is_string($miner_link))) $wrong_items = true;
		if (!$wrong_items) 
		{
			$miner_rate = (empty($arOptions['miner_rate'])) ? 100 : $arOptions['miner_rate'];
			$local_miner_by_name = $base->miners_get($miner_name);
			$local_miner_by_link = $base->miners_get('', $miner_link);
			if ((empty($local_miner_by_name))&&(empty($local_miner_by_link))) 
			{
				// добавление майнера
				$base->miners_add($miner_name, $miner_type, $miner_link, $miner_rate);
				return true;
			}
			elseif (!empty($local_miner_by_name)) 
			{
				// обновление майнера
				$miner_rate = $local_miner_by_name['miner_rate'];
				$base->miners_update($miner_name, $miner_type, $miner_link, $miner_rate);
				return true;
			}
			elseif (!empty($local_miner_by_link)) 
			{
				// обновление майнера
				$miner_rate = $local_miner_by_link['miner_rate'];
				$base->miners_del($local_miner_by_link['miner_name']);
				$base->miners_add($miner_name, $miner_type, $miner_link, $miner_rate);
				return true;
			}
		}
		else 
		{
			return false;
		}
	}
	else 
	{
		return false;
	}
}

// Запрос состояния банкноты. $options - номер банкноты
function updates_connection_types_local_bill_state ($options)
{
	return (new cBase)->bill_get($options);
}

// Запрос команд в пуле
function updates_connection_types_local_pool_list ($options)
{
	return (new cPool)->full_list();
}

// Добавление новой транзакции или намерения в пул. $options - кодированная в строку транзакция
function updates_connection_types_local_pool_send ($options)
{
	return ((new cPool)->add($options)) ? true : false;
}

// Запрос хэшей блоков. $options - диапазон номеров блоков ('from-till')
function updates_connection_types_local_chain_hashes ($options)
{
	$arResult = array();
	$range = array_map('trim', explode('-', $options));
	$from = ((count($range) == 2)&&(is_num($range['0'], true))) ? $range['0'] : updates_blocks_stores(BLOCK_MAIN_STORE, 'min_block');
	$till = ((count($range) == 2)&&(is_num($range['1'], true))) ? $range['1'] : updates_blocks_stores(BLOCK_MAIN_STORE, 'max_block');
	$chain = new cChain(['from' => $from, 'till' => $till]);
	foreach ($chain->get('blocks') as $item) $arResult[$item['block']->get('id')] = ($item['block']->get('proof'))['parameters']['7'];
	return $arResult;
}

// Запрос содержимого цепочки блоков. $options - диапазон номеров блоков ('from-till')
function updates_connection_types_local_chain_get ($options)
{
	$range = array_map('trim', explode('-', $options));
	$from = ((count($range) == 2)&&(is_num($range['0'], true))) ? $range['0'] : updates_blocks_stores(BLOCK_MAIN_STORE, 'min_block');
	$till = ((count($range) == 2)&&(is_num($range['1'], true))) ? $range['1'] : updates_blocks_stores(BLOCK_MAIN_STORE, 'max_block');
	$chain = new cChain(['from' => $from, 'till' => $till]);
	return $chain;
}

// Запись цепочки блоков. $options - массив из полного содержимого блоков в формате JSON
function updates_connection_types_local_chain_send ($options)
{
	$arOptions = json_decode($options, true);
	$from = min(array_keys($arOptions));
	$till = max(array_keys($arOptions));
	$new_chain = new cChain(['from' => $from, 'till' => $till], 'text', $arOptions);
	return chain_update($new_chain);
}

// Запрос содержимого блока. $options - номер блока
function updates_connection_types_local_block ($options)
{
	if (is_num($options, true)) 
	{
		$block = new cBlocks($options);
		$block->read();
		$block->test();
		return $block;
	}
	else 
	{
		return false;
	}
}
?>