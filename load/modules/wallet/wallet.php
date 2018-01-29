<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Обработка локальных функций кошелька

class cWallet 
{
	private $enabled = false;

	// Проверка разрешения доступа к кошельку
	public function cWallet ()
	{
		$this->enabled = (local()) ? true : false;
		if (!(new cModules)->is_enabled('connect')) $this->enabled = false;
		if (!(new cModules)->is_enabled('blocks')) $this->enabled = false;
		if (!(new cModules)->is_enabled('transactions')) $this->enabled = false;
	}

	// Вывод банкноты
	public function read ($bill_number, $connection = CONNECT_ANSWER)
	{
		if ((empty($bill_number))||(!is_string($bill_number))||(!$this->enabled)) 
		{
			$bill_example = array();
		}
		else 
		{
			$base = new cBase;
			$bill_local = $base->wallet_get($bill_number);
			$bill_example = array();
			if (!empty($bill_local)) 
			{
				// Считываем по умолчанию состояние банкноты из локальной базы (закомментируйте, если хотите считывать из сети)
				$bill = $base->bill_get($bill_local['bill_number']);
				// Запросить состояние банкноты от соседних майнеров (раскомментируйте строки ниже)
				//$getting = new cConnect($connection);
				//$bill = $getting->handle('bill_state', $bill_local['bill_number']);
				if (!empty($bill)) 
				{
					$denomination = to_cent($bill['denomination']);
					// Заполнение экземпляра банкноты
					$bill_example['number'] = $bill_local['bill_number'];
					$bill_example['key'] = $bill_local['bill_key'];
					$bill_example['busy'] = $bill_local['busy'];
					$bill_example['intention'] = (empty($base->intentions_get($bill_local['bill_number']))) ? false : true;
					$bill_example['algorithm'] = $bill['algorithm'];
					$bill_example['denomination'] = $denomination;
					$bill_example['timestamp'] = $bill['timestamp'];
					$bill_example['entity'] = '';
					$bill_example['entity_encrypted'] = false;
					$bill_example['sign'] = $bill['sign'];
					// Публичный ключ
					$encrypt = new cEncrypt($bill_example);
					$bill_example['pubkey'] = $encrypt->pubkey;
					$bill_example['sign_proper'] = $encrypt->sign_proper;
					// Человекопонятное название алгоритма шифрования
					$bill_example['algo_name'] = $encrypt->name;
					// Картинка банкноты
					$img = '10000';
					if ($denomination < 10000) $img = '5000';
					if ($denomination < 5000) $img = '1000';
					if ($denomination < 1000) $img = '500';
					if ($denomination < 500) $img = '100';
					if ($denomination < 100) $img = '50';
					if ($denomination < 50) $img = '10';
					if ($denomination < 10) $img = '5';
					if ($denomination < 5) $img = '1';
					if ($denomination < 1) $img = 'cent';
					if ($denomination < to_cent(pow(10, -CENT_ACCURACY))) $img = 'empty';
					$bill_example['img'] = $img;
				}
			}
		}
		return $bill_example;
	}

	// Вывод списка банкнот в кошельке
	public function bill_list ()
	{
		$base = new cBase;
		$arBills = $base->wallet_get_all();
		$arOutput = array();
		if (!empty($arBills)&&($this->enabled)) 
		{
			foreach ($arBills as $iBills) 
			{
				$bill_example = $this->read($iBills['bill_number']);
				if ($bill_example['denomination'] > 0) array_push($arOutput, $bill_example);
			}
		}
		return $arOutput;
	}

	// Добавление банкнот к кошельку
	public function add_bill ($bill_number, $bill_key, $connection = CONNECT_ANSWER, $forced = false)
	{
		$wrong_items = false;
		if ((empty($bill_number))||(!is_string($bill_number))||(empty($bill_key))||(!is_string($bill_key))||(!$this->enabled)) 
		{
			write('Невозможно прочитать отправленную в кошелек банкноту.', 5, 'error');
			$wrong_items = true;
		}
		else 
		{
			if (!$this->in_wallet ($bill_number)) 
			{
				$base = new cBase;
				// Считываем по умолчанию состояние банкноты из локальной базы (закомментируйте, если хотите считывать из сети)
				$bill = $base->bill_get($bill_number);
				// Запросить состояние банкноты от соседних майнеров (раскомментируйте строки ниже и выберите тип соединения)
				//$getting = new cConnect($connection);
				//$bill = $getting->handle('bill_state', $bill_number);
				// Если банкнота не найдена, в кошелек загружаем пустые недостающие данные
				if (empty($bill)) $bill = array('sign' => '', 'algorithm' => 'ar', 'denomination' => '0', 'timestamp' => '0');
				$bill_example = array
				(
					'number' => $bill_number,
					'key' => $bill_key,
					'algorithm' => $bill['algorithm'],
					'denomination' => to_cent($bill['denomination']),
					'timestamp' => $bill['timestamp'],
					'entity' => '',
					'entity_encrypted' => false,
				);
				if ((new cEncrypt($bill_example))->sign == $bill['sign']) 
				{
					write($bill_number.': банкнота добавлена в кошелек.', 5);
					$base->wallet_add($bill_number, $bill_key);
				}
				elseif ($forced) 
				{
					write($bill_number.': сохранение предварительной банкноты в кошелек.', 5);
					$base->wallet_add($bill_number, $bill_key);
				}
				else 
				{
					write($bill_number.': банкнота не была добавлена в кошелек, так как пароль не подтвержден.', 5, 'error');
					$wrong_items = true;
				}
			}
			else 
			{
				write($bill_number.': банкнота уже имеется в кошельке.', 5);
				$wrong_items = true;
			}
		}
		return ($wrong_items) ? false : true;
	}

	// Проверка наличия банкноты в кошельке
	public function in_wallet ($bill_number)
	{
		if ((empty($bill_number))||(!is_string($bill_number))||(!$this->enabled)) 
		{
			return false;
		}
		else 
		{
			$bill_local = (new cBase)->wallet_get($bill_number);
			return (empty($bill_local)) ? false : true;
		}
	}

	// Обновление банкноты в кошельке (или добавление в случае ее отсутствия)
	public function update ($bill_number, $bill_key)
	{
		if ((empty($bill_number))||(!is_string($bill_number))||(empty($bill_key))||(!is_string($bill_key))||(!$this->enabled)) 
		{
			write('Невозможно обновить банкноту.', 5, 'error');
			return false;
		}
		else 
		{
			$base = new cBase;
			$base->wallet_del($bill_number);
			$base->wallet_add($bill_number, $bill_key);
			return true;
		}
	}

	// Добавление команды в локальный пул
	public function action_add ($action)
	{
		if ((empty($action))||(!is_array($action))||(!$this->enabled)) 
		{
			return false;
		}
		else 
		{
			if ((!empty($action['test']))&&(is_array($action['test']))&&(!empty($action['transaction']))&&(!empty($action['intention']))) 
			{
				if (($action['test']['ok'])&&(!empty($action['inputs']))) 
				{
					$wrong_items = false;
					$base = new cBase;
					// Обновление входных банкнот в кошельке и блокировка их до выполнения команд
					foreach ($action['inputs'] as $input) 
					{
						$this->stack_set($input['number']); // локальное сохранение прежних паролей (на случай непреднамеренной утери доступа)
						$base->wallet_del($input['number']);
						$base->wallet_add($input['number'], $input['key']);
						$base->wallet_busy($input['number']);
						write($input['number'].': банкнота в кошельке обновлена.', 5);
					}
					// Добавление выходных банкнот в кошелек (будут показаны пустыми до выполнения команд)
					if (!empty($action['outputs'])) foreach ($action['outputs'] as $output) $base->wallet_add($output['number'], $output['key']);
					// Добавление команд в локальный пул
					if (!$base->action_add('intention', $action['intention'])) $wrong_items = true;
					if (!$base->action_add('transaction', $action['transaction'])) $wrong_items = true;
				}
				else 
				{
					write('Произошла ошибка при отправке команды.', 5, 'error');
					$wrong_items = true;
				}
			}
			else 
			{
				write('Невозможно прочитать отправленную команду.', 5, 'error');
				$wrong_items = true;
			}
			return ($wrong_items) ? false : true;
		}
	}

	// Отметка о выполнении команды в локальном пуле, отправка команды в сеть
	public function action_execute ($number, $type, $connection = CONNECT_ANSWER, $arMiners = array())
	{
		if ((empty($number))||(!is_string($number))||(empty($type))||(!is_string($type))||(!$this->enabled)) 
		{
			write('В отправляемой команде обнаружена ошибка.', 5, 'error');
			return false;
		}
		else 
		{
			$wrong_items = false;
			$arEntity = array();
			$base = new cBase;
			$arAction = $base->action_list(true);
			if (!empty($arAction)) foreach ($arAction as $key => $action) 
			{
				if ($action['type'] == $type) 
				{
					$test_type = transaction_split($action['entity'], true);
					$test_json = transaction_split($action['entity'], false);
					$arTest = transaction_test($test_type, json_decode($test_json, true));
					if (($arTest['ok'])&&(in_array($number, $arTest['number']))) array_push($arEntity, $arTest);
					if (!$arTest['ok']) 
					{
						write('Отправляемая команда не валидна.', 5, 'error');
						$wrong_items = true;
					}
				}
			}
			if (empty($arEntity)) 
			{
				write('Не обнаружены неотправленные команды.', 5, 'error');
				$wrong_items = true;
			}
			if (!$wrong_items) foreach ($arEntity as $arTest) 
			{
				// Отправка команды в пул самому себе (закомментируйте, если хотите отправлять команды в сеть)
				if ((new cPool)->add($arTest['json'])) 
				{
					write('Команда отправлена в пул.', 5);
					$base->action_execute($arTest['json']); // отметка в базе данных об отправке команды
					if ($arTest['is'] == 'transaction') foreach ($arTest['output'] as $bill_number) $base->wallet_free($bill_number);
				}
				else 
				{
					write('Произошла ошибка при локальной отправке команды.', 5, 'error');
					$wrong_items = true;
				}
				// Отправка команды соседним майнерам (раскомментируйте строки ниже и выберите тип соединения)
				// Также для отправки команды соседним майнерам необходимо раскомментировать соответствующие строки 
				// в скрипте отправки транзакции
				//$sending = new cConnect($connection, $arMiners);
				//if ($sending->handle('pool_send', $arTest['json'])) 
				//{
				//	write('Команда отправлена в сеть.', 5);
				//	$base->action_execute($arTest['json']); // отметка в базе данных об отправке команды
				//	if ($arTest['is'] == 'transaction') foreach ($arTest['output'] as $bill_number) $base->wallet_free($bill_number);
				//}
				//else 
				//{
				//	write('Ошибка при отправке команды. Отменять данную команду не рекомендуется. Возможно, часть майнеров приняли ее.', 5, 'error');
				//	$wrong_items = true;
				//}
			}
			return ($wrong_items) ? false : true;
		}
	}

	// Сохранение прежних паролей на случай утери контроля над своими банкнотами
	// Данную функцию можно отключить, закомментировав содержимое метода
	// Ни на какие другие задачи этот метод не оказывает влияние
	public function stack_set ($bill_number)
	{
		if ($this->enabled) 
		{
			$bill = $this->read($bill_number);
			(new cBase)->wallet_stack($bill_number, $bill['key'], gmdate('U'));
			write($bill_number.': прежний пароль банкноты сохранен.', 5);
			return true;
		}
		else 
		{
			write($bill_number.': невозможно сохранить прежний пароль.', 5);
			return false;
		}
	}

	// Cчитывание прежних паролей своей банкноты (если они были ранее записаны)
	public function stack_get ($bill_number = '')
	{
		$output = array();
		if ($this->enabled) $output = (new cBase)->wallet_stack_read($bill_number);
		return $output;
	}

	// Генерация номера банкноты. Сеть принимает любые названия банкнот 
	// из букв английского языка и цифр (без знаков препинания и пробелов)
	// однако, для исключения ошибок и защиты от взлома рекомендуется
	// использовать автоматически сгенерированные номера банкнот
	public function number_generate ()
	{
		return 'b'.(new cBase())->constant_get('miner_name').gmdate('U').abra(5);
	}

	// Генерация пароля банкноты. Сеть принимает любые пароли из букв 
	// английского языка и цифр (без знаков препинания и пробелов)
	// однако, для исключения ошибок и защиты от взлома рекомендуется
	// использовать автоматически сгенерированные пароли для банкнот
	public function key_generate ()
	{
		return abra(64);
	}

	// Чтение параметров кошелька
	public function get ($option)
	{
		return (property_exists($this, $option)) ? $this->$option : false;
	}
}
?>