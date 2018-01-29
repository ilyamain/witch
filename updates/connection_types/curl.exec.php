<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Удаленное соединение по cURL. Обмен распределенными данными с другими майнерами

// Проверка установки всех необходимых модулей:
if (!(new cModules)->is_enabled('connect')) return false;
if (!(new cModules)->is_enabled('blocks')) return false;
if (!(new cModules)->is_enabled('transactions')) return false;

// Если модули установлены, то получаем запрошенные данные
switch ($request) 
{
	case 'type':			// Если файл существует, существует и тип соединения
		$output = true;
		break;
	case 'ok':				// Запрос состояния соединения
		$output = updates_connection_types_curl_ok($recipient);
		break;
	case 'miners':			// Запрос списка майнеров соединения
		$output = updates_connection_types_curl_request($request, $options, $recipient, true);
		break;
	case 'miner_send':		// Добавление нового майнера в список
		$output = updates_connection_types_curl_request($request, $options, $recipient);
		break;
	case 'info':			// Запрос характеристик майнера
		$output = updates_connection_types_curl_request($request, $options, $recipient, true);
		break;
	case 'bill_state':		// Запрос состояния банкноты
		$output = updates_connection_types_curl_request($request, $options, $recipient, true);
		break;
	case 'pool_list':		// Запрос команд в пуле
		$output = updates_connection_types_curl_request($request, $options, $recipient, true);
		break;
	case 'pool_send':		// Добавление новой транзакции или намерения в пул
		$output = updates_connection_types_curl_request($request, $options, $recipient);
		break;
	case 'chain_hashes':	// Запрос хэшей блоков
		$output = updates_connection_types_curl_request($request, $options, $recipient, true);
		break;
	case 'chain_get':		// Запрос содержимого цепочки блоков
		$output = updates_connection_types_curl_request($request, $options, $recipient, true);
		break;
	case 'block':			// Запрос блока
		$output = updates_connection_types_curl_request($request, $options, $recipient, true);
		break;
	case 'chain_send':		// Запись цепочки блоков
		$output = updates_connection_types_curl_request($request, $options, $recipient);
		break;
	default:
		$output = false;
}
return $output;
?>