<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Обработка блоков в файловом хранилище (хранение архивной копии блоков)

// Получение содержимого блока из файлового хранилища
if (empty($action)) return updates_blocks_stores_file_get($block_id);
switch ($action) 
{
	case 'get':				// Получение содержимого блока из файлового хранилища
		$output = updates_blocks_stores_file_get($block_id);
		break;
	case 'max_block':		// Получение номера текущего блока
		$output = updates_blocks_stores_file_max_block();
		break;
	case 'min_block':		// Получение номера первого незафиксированного блока
		$output = updates_blocks_stores_file_min_block();
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
		$output = updates_blocks_stores_file_block_delete($block_id);
		break;
	case 'block_create':	// Добавление блока в файловое хранилище
		$output = updates_blocks_stores_file_block_create($block_id, $input);
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