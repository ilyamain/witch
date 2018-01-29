<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Обработка блоков в текстовом хранилище (кратковременное хранение для консенсуса и передачи между майнерами)

// Получение содержимого блока из текста
function updates_blocks_stores_text_get ($block_id, $input)
{
	if (is_string($input)) 
	{
		write('<b>'.$block_id.':</b> данные получены из текста.', 3);
		$output = explode(PHP_EOL, $input);
		return $output;
	}
	else 
	{
		write('Невозможно загрузить текст содержимого блока', 3, 'error');
		return false;
	}
}
?>