<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Обработка блоков в файловом хранилище (хранение архивной копии блоков)

// Получение содержимого блока из файлового хранилища
function updates_blocks_stores_file_get ($block_id)
{
	$block_file = BLOCK_FILE_DIR.$block_id.'.block';
	if (is_file($block_file)) 
	{
		write('<b>'.$block_id.':</b> данные получены из файла.', 3);
		return array_map('trim', file($block_file));
	}
	else 
	{
		write('<b>'.$block_id.':</b> файл блока не найден.', 3, 'error');
		return false;
	}
}

// Получение номера текущего блока
function updates_blocks_stores_file_max_block ()
{
	$output = 0;
	$arFiles = scandir(BLOCK_FILE_DIR);
	foreach ($arFiles as $key => $block_file) 
	{
		$block_id = strval(stristr($block_file, '.', true));
		if ((!is_file(BLOCK_FILE_DIR.$block_file))||(!is_num($block_id))) unset($arFiles[$key]); else $arFiles[$key] = $block_id;
	}
	$output = max($arFiles);
	return $output;
}

// Получение номера первого незафиксированного блока
function updates_blocks_stores_file_min_block ()
{
	$output = 0;
	$arFiles = scandir(BLOCK_FILE_DIR);
	foreach ($arFiles as $key => $block_file) 
	{
		$block_id = strval(stristr($block_file, '.', true));
		if ((!is_file(BLOCK_FILE_DIR.$block_file))||(!is_num($block_id))) unset($arFiles[$key]); else $arFiles[$key] = $block_id;
	}
	$output = min($arFiles);
	return $output;
}

// Удаление указанного блока и всех последующих блоков
function updates_blocks_stores_file_block_delete ($block_id)
{
	if (is_num($block_id)) 
	{
		$output = true;
		$arFiles = scandir(BLOCK_FILE_DIR);
		foreach ($arFiles as $file) 
		{
			$file_name = strval(stristr($file, '.', true));
			if ((($file_name >= $block_id)||(!is_num($file_name)))&&(is_file(BLOCK_FILE_DIR.$file))) unlink(BLOCK_FILE_DIR.$file);
		}
	}
	else 
	{
		$output = false;
	}
	return $output;
}

// Добавление блока в файловое хранилище
function updates_blocks_stores_file_block_create ($block_id, $input)
{
	if ((is_string($input))&&(is_num($block_id))) 
	{
		$output = true;
		file_put_contents(BLOCK_FILE_DIR.$block_id.'.block', $input);
	}
	else 
	{
		$output = false;
	}
	return $output;
}
?>