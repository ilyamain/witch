<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Удаленное соединение по cURL. Обмен распределенными данными с другими майнерами

// Общий запрос к серверу (для вызова из других функций соединения)
function updates_connection_types_curl_connect_post ($recipient, $postfields = '')
{
	$output = false;
	$link = curl_init();
	curl_setopt($link, CURLOPT_URL, 'http://'.$recipient['miner_link'].'/'.CONNECT_PAGE.'/');
	curl_setopt($link, CURLOPT_HEADER, 0);
	if (!empty($postfields)) curl_setopt($link, CURLOPT_POSTFIELDS, $postfields);
	curl_setopt($link, CURLOPT_RETURNTRANSFER, true);
	$output = curl_exec($link);
	curl_close($link);
	return $output;
}

// Запрос состояния соединения
function updates_connection_types_curl_ok ($recipient)
{
	$html = updates_connection_types_curl_connect_post($recipient);
	if ((!empty($html))&&(is_string($html))) 
	{
		$document = new DOMDocument();
		libxml_use_internal_errors(true);
		$document->loadHTML($html);
		libxml_clear_errors();
		return $document->getElementById('status')->textContent;
	}
	else 
	{
		return false;
	}
}

// Обработка запросов состояния других майнеров cURL
function updates_connection_types_curl_request ($request, $options, $recipient, $respond = false)
{
	// Загрузка параметров запрашивающего майнера
	$sender = array
	(
		'miner_name' => (new cBase)->constant_get('miner_name'), 
		'miner_type' => (new cBase)->constant_get('miner_type'), 
		'miner_link' => (new cBase)->constant_get('miner_link'), 
	);
	// Формирование параметров POST-запроса
	$string_options = (is_array($options)) ? json_encode($options) : strval($options);
	$postfields = array
	(
		'sender' => json_encode($sender), 
		'recipient' => json_encode($recipient), 
		'options' => $string_options, 
		'request' => $request, 
	);
	// Создание запроса
	$html = updates_connection_types_curl_connect_post($recipient, $postfields);
	// Обработка и вывод результата
	if ($respond) 
	{
		if ((!empty($html))&&(is_string($html))) 
		{
			$document = new DOMDocument();
			libxml_use_internal_errors(true);
			$document->loadHTML($html);
			libxml_clear_errors();
			$result = $document->getElementById('result')->textContent;
			return json_decode($result, true);
		}
		else 
		{
			return false;
		}
	}
	else 
	{
		return (empty($html)) ? false : true;
	}
}
?>