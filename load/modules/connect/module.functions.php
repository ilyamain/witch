<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта

// Обращение к файлам типа соединения
function updates_connection_types ($type, $request, $options = '', $recipient = array())
{
	$output = '';
	$file['func'] = way(DR.DS.'updates'.DS.'connection_types'.DS.$type.'.func.php');
	$file['exec'] = way(DR.DS.'updates'.DS.'connection_types'.DS.$type.'.exec.php');
	if (is_file($file['func'])) require_once($file['func']);
	if (is_file($file['exec'])) $output = require($file['exec']);
	return $output;
}

// Добавление подключения к трекерам. Применяется при удалении всех майнеров из списка
function miners_initiate ()
{
	$arInitial = array();
	$arTemp = bc_data();
	if (!empty($arTemp['miners'])) $arInitial = $arTemp['miners']['table_rows'];
	if (!empty($arInitial)) foreach ($arInitial as $item) (new cBase)->miners_add($item['0'], $item['1'], $item['2'], $item['3']);
}

// Проверка соответствия майнера параметрам ранее загруженной сети
function miners_test ($miner_name, $miner_type, $miner_link, $miner_rate)
{
	$wrong_items = false;
	if ((empty($miner_name))||(!is_alphabet($miner_name))) $wrong_items = true;
	if ((empty($miner_type))||(!is_alphabet($miner_type))) $wrong_items = true;
	if ((empty($miner_link))||(!is_string($miner_link))) $wrong_items = true;
	$miner_rate = (is_num($miner_rate)) ? $miner_rate : '100';
	$miner = array
	(
		'miner_name' => $miner_name, 
		'miner_type' => $miner_type, 
		'miner_link' => $miner_link, 
		'miner_rate' => $miner_rate, 
	);
	$remote = new cConnect(CONNECT_REQEST, array($miner));
	// Проверяем соединение с майнером
	if (!$remote->connected) $wrong_items = true;
	// Проверяем имя майнера
	if (!empty((new cBase)->miners_get($miner_name))) $wrong_items = true;
	return ($wrong_items) ? false : true;
}

// Удаление неактивных майнеров из базы данных
function miners_clean ()
{
	$base = new cBase;
	$arMiners = $base->miners_get_all();
	$still = false;
	// Удаление майнеров, к которым отсутствуют подключения
	if (!empty($arMiners)) 
	{
		foreach ($arMiners as $miner) 
		{
			$connection = new cConnect(CONNECT_REQEST, array($miner));
			if (!$connection->connected) $base->miners_del($miner['miner_name']); else $still = true;
		}
	}
	// Если не осталось ни одного майнера, добавляем подключения к трекерам
	if (!$still) miners_initiate();
}

function miners_request ($miner)
{
	$base = new cBase;
	$wrong_items = false;
	$output = false;
	if (is_object($miner)) $miner = (array)$miner;
	// Не добавляем соединение с самим собой
	$local = array
	(
		'miner_name' => $base->constant_get('miner_name'), 
		'miner_type' => $base->constant_get('miner_type'), 
		'miner_link' => $base->constant_get('miner_link'), 
	);
	if ($miner['miner_name'] == $local['miner_name']) $wrong_items = true;
	if (($miner['miner_type'] == $local['miner_type'])&&($miner['miner_link'] == $local['miner_link'])) $wrong_items = true;
	// Проверяем соединение с отдельно взятым майнером и если соединение установлено, то обновляем записи
	$connection = new cConnect(CONNECT_REQEST, array($miner));
	if (!$connection->connected) $wrong_items = true;
	if (!$wrong_items) 
	{
		// Проверяем майнер на наличие аналогичного майнера в списке.
		// Обновляем его в случае отсутствия в сети майнера, соответствующего старой записи
		$existing = $base->miners_get($miner['miner_name']);
		if (!empty($existing)) 
		{
			$existing_connection = new cConnect(CONNECT_REQEST, array($miner));
			if (!$existing_connection->connected) 
			{
				$base->miners_update($miner['miner_name'], $miner['miner_type'], $miner['miner_link'], $miner['miner_rate']);
			}
			else 
			{
				$output = false;
			}
		}
		else 
		{
			// В случае отсутствия аналогичного майнера в списке, добавляем его
			$base->miners_add($miner['miner_name'], $miner['miner_type'], $miner['miner_link'], $miner['miner_rate']);
		}
		$output = true;
	}
	else 
	{
		$output = false;
	}
	return $output;
}
?>