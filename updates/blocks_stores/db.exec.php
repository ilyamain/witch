<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Обработка блоков в SQL хранилище (оперативная обработка блоков для пересчета состояния базы данных)

// Получение содержимого блока из базы данных
if (empty($action)) return updates_blocks_stores_db_get($block_id);
switch ($action) 
{
	case 'get':				// Получение содержимого блока из базы данных
		$output = updates_blocks_stores_db_get($block_id);
		break;
	case 'max_block':		// Получение номера текущего блока
		$output = updates_blocks_stores_db_max_block();
		break;
	case 'min_block':		// Получение номера первого незафиксированного блока
		$output = updates_blocks_stores_db_min_block();
		break;
	case 'get_last':		// Получение последних блоков начиная с $block_id
		$output = updates_blocks_stores_db_get_last($block_id);
		break;
	case 'commited':		// Получение информации о фиксировании блока
		$output = updates_blocks_stores_db_commited($block_id);
		break;
	case 'backup_create':	// Резервное копирование распределенных данных
		$output = updates_blocks_stores_db_backup_create();
		break;
	case 'backup_restore':	// Восстановление распределенных данных из резервной копии
		$output = updates_blocks_stores_db_backup_restore();
		break;
	case 'backup_empty':	// Удаление резервной копии распределенных данных
		$output = updates_blocks_stores_db_backup_empty();
		break;
	case 'block_delete':	// Удаление указанного блока и всех последующих блоков
		$output = updates_blocks_stores_db_block_delete($block_id);
		break;
	case 'block_create':	// Добавление блока в базу данных
		$output = updates_blocks_stores_db_block_create($block_id, $input);
		break;
	case 'block_mark':		// Проставление отметки в базе о фиксации блока
		$output = updates_blocks_stores_db_block_mark($block_id);
		break;
	default:
		write('Не распознано запрашиваемое действие', 3);
		$output = false;
}
return $output;
?>