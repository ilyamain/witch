<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Обработка блоков в SQL хранилище (оперативная обработка блоков для пересчета состояния базы данных)

// Получение содержимого блока из базы данных
function updates_blocks_stores_db_get ($block_id)
{
	$result = false;
	$block = q('SELECT * FROM bc_blocks WHERE number=\''.$block_id.'\'');
	if (!empty($block)) 
	{
		$arBlock = $block->fetch_assoc();
		if (!empty($arBlock)) 
		{
			write('<b>'.$block_id.':</b> данные получены из базы.', 3);
			$result = explode(PHP_EOL, $arBlock['content']);
		}
		else 
		{
			write('<b>'.$block_id.':</b> блок в базе данных не найден.', 3, 'error');
			$result = false;
		}
	}
	else 
	{
		write('<b>'.$block_id.':</b> ошибка при поиске блока в базе.', 3, 'error');
		$result = false;
	}
	return $result;
}

// Получение номера текущего блока
function updates_blocks_stores_db_max_block ()
{
	$result = false;
	$sql = 'SELECT max(CAST(number AS UNSIGNED)) FROM bc_blocks';
	$max_block = q($sql);
	if (!empty($max_block)) $result = ($max_block->fetch_assoc())['max(CAST(number AS UNSIGNED))'];
	return $result;
}

// Получение номера первого незафиксированного блока
function updates_blocks_stores_db_min_block ()
{
	$result = false;
	$sql = 'SELECT min(CAST(number AS UNSIGNED)) FROM bc_blocks WHERE commited=\'0\'';
	$min_block = q($sql);
	if (!empty($min_block)) $result = ($min_block->fetch_assoc())['min(CAST(number AS UNSIGNED))'];
	return $result;
}

// Получение номера первого незафиксированного блока
function updates_blocks_stores_db_get_last ($block_id)
{
	$result = false;
	$sql = 'SELECT * FROM bc_blocks WHERE CAST(number AS UNSIGNED) >= '.$block_id.' ORDER BY CAST(number AS UNSIGNED) ASC;';
	$blocks = q($sql);
	if (!empty($blocks)) 
	{
		$arBlocks = array();
		while ($iBlocks = $blocks->fetch_assoc()) 
		{
			$iBlocks['content'] = explode(PHP_EOL, $iBlocks['content']);
			array_push($arBlocks, $iBlocks);
		}
		$result = (!empty($arBlocks)) ? $arBlocks : false;
	}
	return $result;
}

// Получение информации о фиксировании блока
function updates_blocks_stores_db_commited ($block_id)
{
	$result = false;
	$block = q('SELECT * FROM bc_blocks WHERE number=\''.$block_id.'\'');
	if (!empty($block)) 
	{
		$arBlock = $block->fetch_assoc();
		if (!empty($arBlock)) $result = (empty($arBlock['commited'])) ? false : true; else $result = false;
	}
	else 
	{
		$result = false;
	}
	return $result;
}

// Резервное копирование распределенных данных
function updates_blocks_stores_db_backup_create ()
{
	$result = false;
	$result = (new cBase)->backup_create();
	return $result;
}

// Восстановление распределенных данных из резервной копии
function updates_blocks_stores_db_backup_restore ()
{
	$result = false;
	$result = (new cBase)->backup_restore();
	return $result;
}

// Удаление резервной копии распределенных данных
function updates_blocks_stores_db_backup_empty ()
{
	$result = false;
	$result = (new cBase)->backup_empty();
	return $result;
}

// Удаление указанного блока и всех последующих блоков
function updates_blocks_stores_db_block_delete ($block_id)
{
	if (is_num($block_id)) 
	{
		$sql = 'DELETE FROM bc_blocks WHERE CAST(number AS UNSIGNED) >= '.$block_id.';';
		if (q($sql)) return true; else return false;
	}
	else 
	{
		return false;
	}
}

// Добавление блока в базу данных
function updates_blocks_stores_db_block_create ($block_id, $input)
{
	if ((is_string($input))&&(is_num($block_id))) 
	{
		$sql = 'INSERT INTO bc_blocks (number, content, commited) VALUES (\''.$block_id.'\',\''.$input.'\',\'0\')';
		if (q($sql)) return true; else return false;
	}
	else 
	{
		return false;
	}
}

// Проставление отметки в базе о фиксации блока
function updates_blocks_stores_db_block_mark ($block_id)
{
	if (is_num($block_id)) 
	{
		$sql = 'UPDATE bc_blocks SET commited = \'1\' WHERE number = \''.$block_id.'\';';
		if (q($sql)) return true; else return false;
	}
	else 
	{
		return false;
	}
}
?>