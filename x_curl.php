<?
// Загрузка системных файлов, модулей. Или установка программы
require_once ($_SERVER[DOCUMENT_ROOT].DIRECTORY_SEPARATOR.'load'.DIRECTORY_SEPARATOR.'start.php');
// Выдача запрошенной в curl информации
$base = new cBase;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
	<head>
		<title>
			<?=$base->constant_get('miner_name');?>
		</title>
	</head>
	<body>
		<?
		$sender =    (!empty($_POST['sender']))    ? json_decode($_POST['sender'], true)    : array();
		$recipient = (!empty($_POST['recipient'])) ? json_decode($_POST['recipient'], true) : array();
		$options =   (!empty($_POST['options']))   ? $_POST['options']                      : '';
		$request =   (!empty($_POST['request']))   ? $_POST['request']                      : '';
		// Список запросов, запрещенных к отправке без предварительной проверки
		$restrict_request = array
		(
			'miner_send', 
			'pool_send', 
			'chain_send', 
		);
		$local = new cConnect(CONNECT_ANSWER);
		// Результат запроса
		$output = array();
		$output['status'] = $local->connected;
		$wrong_items = false;
		if (in_array($request, $restrict_request)) // если запрос требует дополнительную проверку
		{
			// Информация о запрашивающем соединение майнере
			// Для отправки данных допускаются только проверенные майнеры, состоящие в списке
			// Отсутствующие в списке майнеры получают 0 рейтинг
			$sender_by_name = $base->miners_get($sender['miner_name']);
			$sender_by_link = $base->miners_get('', $sender['miner_link']);
			$sender_by_name_rate = (empty($sender_by_name)) ? '0' : $sender_by_name['miner_rate'];
			$sender_by_link_rate = (empty($sender_by_link)) ? '0' : $sender_by_link['miner_rate'];
			$sender['miner_rate'] = min($sender_by_name_rate, $sender_by_link_rate);
			// Проверка возможности добавления нового майнера
			if ($request == $restrict_request[0]) 
			{
				// Проверяем характеристики добавляемого майнера
				$miner = json_decode($options, true);
				$miner_rate = (is_num($miner['miner_rate'])) ? $miner_rate : '100';
				if (!miners_test($miner['miner_name'], $miner['miner_type'], $miner['miner_link'], $miner_rate)) $wrong_items = true;
				// Проверяем рейтинг запрашивающего соединение майнера
				// Отклоняем его в том случае, если он отправляет не сам себя впервые
				if (($sender['miner_name'] != $miner['miner_name'])&&($sender['miner_rate'] <= $base->constant_get('ban_miner'))) $wrong_items = true;
			}
			// Проверка возможности добавления новой транзакции или намерения
			if ($request == $restrict_request[1]) 
			{
				// Проверка допустимости транзакции
				$type = transaction_split($options, true);
				$json = transaction_split($options, false);
				$arTest = transaction_test($type, json_decode($json, true));
				if (!$arTest['ok']) $wrong_items = true;
			}
			// Проверка возможности удаленного обновления цепочки блоков
			// if ($request == $restrict_request[2]) 
			// {
				// в разработке (активная рассылка блоков)
			// }
		}
		else 
		{
			// Если запрос не требует дополнительных проверок
			// Не требуется каких-либо действий
		}
		// Выполнение запроса, получение результата
		$result = (!$wrong_items) ? $local->handle($request, $options) : false;
		// Преобразование результата в JSON-формат
		$output['result'] = json_encode($result);
		?>
		<div id="status"><?=$output['status'];?></div>
		<div id="result"><?=$output['result'];?></div>
	</body>
</html>