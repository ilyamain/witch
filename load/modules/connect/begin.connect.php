<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта

// Загрузка параметров модуля
module_config (__DIR__);

class cConnect 
{
	public $type;
	public $connected = false;
	public $miners = array();
	public $respond = array();

	public function cConnect ($type, $arMiners = array())
	{
		// Проверка состояния соединения
		if (updates_connection_types($type, 'type')) 
		{
			$this->type = $type;
			if ((!empty($arMiners))&&(is_array($arMiners))) // для сетевых запросов
			{
				$this->miners = $arMiners;
				$success_connected = 0;
				foreach ($this->miners as $key => $recipient) 
				{
					$connected = updates_connection_types($this->type, 'ok', '', $recipient);
					$this->miners[$key]['connected'] = $connected;
					if ($connected) $success_connected++;
				}
				// Если хотя-бы половина майнеров ответили, то соединение 
				// считается допустимым. Данный параметр можно менять каждому 
				// майнеру отдельно, он не влияет на работу всей сети
				$this->connected = ($success_connected >= round(count($arMiners)/2)) ? true : false;
			}
			else // для локальных запросов или для broadcast-запросов
			{
				$this->connected = updates_connection_types($this->type, 'ok', '');
			}
		}
	}

	// Обработка данных майнера
	public function handle ($request = '', $options = '')
	{
		$result = false;
		// Проверка состояния соединения
		if (updates_connection_types($this->type, 'type')) 
		{
			if ((!empty($this->miners))&&(is_array($this->miners))) // для сетевых запросов
			{
				$this->respond = array();
				if ($this->connected) 
				{
					foreach ($this->miners as $recipient) 
					{
						if ($recipient['connected']) 
						{
							$respond = updates_connection_types($this->type, $request, $options, $recipient);
							array_push($this->respond, $respond);
						}
					}
				}
			}
			else // для локальных запросов или для broadcast-запросов
			{
				$this->respond = ($this->connected) ? updates_connection_types($this->type, $request, $options) : false;
			}
			$result = $this->respond;
		}
		else 
		{
			$result = false;
		}
		return $result;
	}
}
?>