<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Обработка блоков в текстовом хранилище (кратковременное хранение для консенсуса и передачи между майнерами)

// Получение содержимого блока из текста
if (empty($action)) return updates_blocks_stores_text_get($block_id, $input);
switch ($action) 
{
	case 'get':				// Получение содержимого блока из текста
		$output = updates_blocks_stores_text_get($block_id, $input);
		break;
	case 'max_block':		// Получение номера текущего блока
		$output = $block_id;
		break;
	case 'min_block':		// Получение номера первого незафиксированного блока
		$output = $block_id;
		break;
	case 'get_last':		// Получение последних блоков начиная с $block_id
		$output = false;
		break;
	case 'commited':		// Получение информации о фиксировании блока
		$output = false;
		break;
	case 'backup_create':	// Резервное копирование распределенных данных
		$output = false;
		break;
	case 'backup_restore':	// Восстановление распределенных данных из резервной копии
		$output = false;
		break;
	case 'backup_empty':	// Удаление резервной копии распределенных данных
		$output = false;
		break;
	case 'block_delete':	// Удаление указанного блока и всех последующих блоков
		$output = false;
		break;
	case 'block_create':	// Добавление блока
		$output = false;
		break;
	case 'block_mark':		// Проставление отметки о фиксации блока
		$output = false;
		break;
	default:
		write('Не распознано запрашиваемое действие', 3);
		$output = false;
}
return $output;
?>