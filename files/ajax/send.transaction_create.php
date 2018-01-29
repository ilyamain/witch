<?define('AJAX_FORM', true);?>
<?require_once ($_SERVER[DOCUMENT_ROOT].DIRECTORY_SEPARATOR.'load'.DIRECTORY_SEPARATOR.'start.php');?>
<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Создание транзакции
if ((new cWallet)->get('enabled')) 
{
	// Итоговые значения
	$wrong_items = false;
	$action = array();
	if ($modules->is_enabled('transactions')) 
	{
		if (!empty($_POST['options'])) 
		{
			$identifier = $_POST['options']['type'];
			$transaction_entity = array();
			$action['inputs'] = array();
			$action['outputs'] = array();
			$base = new cBase;
			// Для исключения ошибки на ранних стадиях развития проекта
			// в интерфейсной части указан алгоритм шифрования 'ar'
			$new_algorithm = 'ar';
			// Для исключения ошибки на ранних стадиях развития проекта
			// в интерфейсной части указана временная метка по UNIX = 0
			// Сеть примет и другие допустимые временные метки, но их 
			// использование не рекомендуется до появления соответствующих 
			// протестированных интерфейсов
			$new_timestamp = 0;
			$fee = to_cent($_POST['options']['fee']);
			if (($identifier == 'bco')&&(!empty($_POST['options']['old_bills']))&&(!empty($_POST['options']['new_bills']))) 
			{
				$intention_type = 'bai';
				$number = $_POST['options']['old_bills']['0']['number'];
				$old_key = $_POST['options']['old_bills']['0']['key'];
				$new_key = $_POST['options']['new_bills']['0']['key'];
				// Проверка существования банкноты
				$old = $base->bill_get($number);
				if (!empty($old)) 
				{
					// Создание транзакции
					$bill_example = array
					(
						'number' => $number, 
						'key' => $new_key, 
						'algorithm' => $new_algorithm, 
						'denomination' => to_cent($old['denomination']-$fee), 
						'timestamp' => $new_timestamp, 
					);
					$new_sign = (new cEncrypt($bill_example))->sign;
					$transaction_entity = array
					(
						$number, 
						$old_key, 
						$new_sign, 
						$new_algorithm, 
						$new_timestamp, 
						$fee, 
					);
					ksort($transaction_entity);
					$action['test'] = transaction_test($identifier, $transaction_entity);
					if ($action['test']['ok']) 
					{
						array_push($action['inputs'], ['number' => $number, 'key' => $new_key]);
						$action['transaction'] = transaction_code($identifier, $transaction_entity);
						// Создание намерения
						$current = array();
						$current['args'] = $transaction_entity;
						$current['example'] = array
						(
							'number' => $number, 
							'key' => $old_key, 
							'algorithm' => $old['algorithm'], 
							'denomination' => to_cent($old['denomination']), 
							'timestamp' => $old['timestamp'], 
							'entity' => $action['transaction'], 
						);
						$current['intention'] = transaction_code($identifier, $current['args'], $current['example']);
						$current['pubkey'] = (new cEncrypt($current['example']))->pubkey;
						$intention_entity = array
						(
							$number, 
							$current['pubkey'], 
							$current['intention'], 
						);
						ksort($intention_entity);
						$action['intention'] = transaction_code($intention_type, $intention_entity);
						$arTest =              transaction_test($intention_type, $intention_entity);
						if (!$arTest['ok']) $wrong_items = true;
					}
					else 
					{
						$wrong_items = true;
					}
				}
				else 
				{
					$wrong_items = true;
				}
			}
			elseif (($identifier == 'bs')&&(!empty($_POST['options']['old_bills']))&&(!empty($_POST['options']['new_bills']))) 
			{
				$intention_type = 'bai';
				$old_number = $_POST['options']['old_bills']['0']['number'];
				$old_key = $_POST['options']['old_bills']['0']['key'];
				// Проверка существования банкноты
				$old = $base->bill_get($old_number);
				if (!empty($old)) 
				{
					$arBills = array();
					// Создание транзакции
					foreach ($_POST['options']['new_bills'] as $new_bill) 
					{
						array_push($action['outputs'], ['number' => $new_bill['number'], 'key' => $new_bill['key']]);
						$bill_example = array
						(
							'number' => $new_bill['number'], 
							'key' => $new_bill['key'], 
							'algorithm' => $new_algorithm, 
							'denomination' => to_cent($new_bill['denomination']), 
							'timestamp' => $new_timestamp, 
						);
						$new_sign = (new cEncrypt($bill_example))->sign;
						$iBills = array
						(
							'a' => $new_algorithm, 
							'd' => to_cent($new_bill['denomination']), 
							'n' => $new_bill['number'], 
							's' => $new_sign, 
						);
						array_push($arBills, $iBills);
					}
					usort($arBills, 'sort_numbers'); // сортировка выходных банкнот
					foreach ($arBills as $key => $item) if (is_array($arBills[$key])) ksort($arBills[$key]); else $wrong_items = true; // сортировка a,d,n,s.
					$transaction_entity = array
					(
						$old_number, 
						$old_key, 
						$arBills, 
						$new_timestamp, 
						$fee, 
					);
					$action['test'] = transaction_test($identifier, $transaction_entity);
					if ($action['test']['ok']) 
					{
						array_push($action['inputs'], ['number' => $old_number, 'key' => $old_key]);
						$action['transaction'] = transaction_code($identifier, $transaction_entity);
						// Создание намерения
						$current = array();
						$current['args'] = $transaction_entity;
						$current['example'] = array
						(
							'number' => $old_number,
							'key' => $old_key,
							'algorithm' => $old['algorithm'],
							'denomination' => to_cent($old['denomination']),
							'timestamp' => $old['timestamp'],
							'entity' => $action['transaction'],
						);
						$current['intention'] = transaction_code($identifier, $current['args'], $current['example']);
						$current['pubkey'] = (new cEncrypt($current['example']))->pubkey;
						$intention_entity = array
						(
							$old_number, 
							$current['pubkey'], 
							$current['intention'], 
						);
						ksort($intention_entity);
						$action['intention'] = transaction_code($intention_type, $intention_entity);
						$arTest =              transaction_test($intention_type, $intention_entity);
						if (!$arTest['ok']) $wrong_items = true;
					}
					else 
					{
						$wrong_items = true;
					}
				}
				else 
				{
					$wrong_items = true;
				}
			}
			elseif (($identifier == 'bu')&&(!empty($_POST['options']['old_bills']))&&(!empty($_POST['options']['new_bills']))) 
			{
				$intention_type = 'bgi';
				$arBills = array();
				$sum = 0;
				// Создание транзакции
				foreach ($_POST['options']['old_bills'] as $old_bill) 
				{
					$old = $base->bill_get($old_bill['number']);
					if (!empty($old)) $sum += $old['denomination'];
					array_push($action['inputs'], ['number' => $old_bill['number'], 'key' => $old_bill['key']]);
					array_push($arBills, ['n' => $old_bill['number'], 'k' => $old_bill['key']]);
				}
				$new_number = $_POST['options']['new_bills']['0']['number'];
				$new_key = $_POST['options']['new_bills']['0']['key'];
				$bill_example = array
				(
					'number' => $new_number, 
					'key' => $new_key, 
					'algorithm' => $new_algorithm, 
					'denomination' => to_cent($sum-$fee), 
					'timestamp' => $new_timestamp, 
				);
				$new_sign = (new cEncrypt($bill_example))->sign;
				usort($arBills, 'sort_numbers'); // сортировка входных банкнот
				foreach ($arBills as $key => $item) if (is_array($arBills[$key])) ksort($arBills[$key]); else $wrong_items = true; // сортировка k,n.
				$transaction_entity = array
				(
					$arBills, 
					$new_number, 
					$new_sign, 
					$new_algorithm, 
					$new_timestamp, 
					$fee, 
				);
				$action['test'] = transaction_test($identifier, $transaction_entity);
				if ($action['test']['ok']) 
				{
					array_push($action['outputs'], ['number' => $new_number, 'key' => $new_key]);
					$action['transaction'] = transaction_code($identifier, $transaction_entity);
					// Создание намерения
					$current = array();
					$current['args'] = $transaction_entity;
					$arIntention = array();
					foreach ($_POST['options']['old_bills'] as $old_bill) 
					{
						$old = $base->bill_get($old_bill['number']);
						if (!empty($old)) 
						{
							$current['example'] = array
							(
								'number' => $old_bill['number'],
								'key' => $old_bill['key'],
								'algorithm' => $old['algorithm'],
								'denomination' => to_cent($old['denomination']),
								'timestamp' => $old['timestamp'],
								'entity' => $action['transaction'],
							);
							$current['intention'] = transaction_code($identifier, $current['args'], $current['example']);
							$current['pubkey'] = (new cEncrypt($current['example']))->pubkey;
							$intention_entity = array
							(
								$old_bill['number'], 
								$current['pubkey'], 
								$current['intention'], 
							);
							ksort($intention_entity);
							array_push($arIntention, $intention_entity);
						}
						else 
						{
							$wrong_items = true;
						}
					}
					usort($arIntention, function ($item_a, $item_b) {return strnatcmp($item_a['0'], $item_b['0']);});
					$action['intention'] = transaction_code($intention_type, $arIntention);
					$arTest =              transaction_test($intention_type, $arIntention);
					if (!$arTest['ok']) $wrong_items = true;
				}
				else 
				{
					$wrong_items = true;
				}
			}
			elseif (($identifier == 'br')&&(!empty($_POST['options']['old_bills']))&&(!empty($_POST['options']['new_bills']))) 
			{
				$intention_type = 'bgi';
				$arOld = array();
				$arNew = array();
				// Создание транзакции
				foreach ($_POST['options']['old_bills'] as $old_bill) 
				{
					array_push($action['inputs'], ['number' => $old_bill['number'], 'key' => $old_bill['key']]);
					array_push($arOld, ['n' => $old_bill['number'], 'k' => $old_bill['key']]);
				}
				foreach ($_POST['options']['new_bills'] as $new_bill) 
				{
					array_push($action['outputs'], ['number' => $new_bill['number'], 'key' => $new_bill['key']]);
					$bill_example = array
					(
						'number' => $new_bill['number'], 
						'key' => $new_bill['key'], 
						'algorithm' => $new_algorithm, 
						'denomination' => to_cent($new_bill['denomination']), 
						'timestamp' => $new_timestamp, 
					);
					$new_sign = (new cEncrypt($bill_example))->sign;
					$iNew = array
					(
						'a' => $new_algorithm, 
						'n' => $new_bill['number'], 
						's' => $new_sign, 
						'd' => to_cent($new_bill['denomination']), 
					);
					array_push($arNew, $iNew);
				}
				usort($arOld, 'sort_numbers'); // сортировка входных банкнот
				foreach ($arOld as $key => $item) if (is_array($arOld[$key])) ksort($arOld[$key]); else $wrong_items = true; // сортировка k,n.
				usort($arNew, 'sort_numbers'); // сортировка выходных банкнот
				foreach ($arNew as $key => $item) if (is_array($arNew[$key])) ksort($arNew[$key]); else $wrong_items = true; // сортировка a,d,n,s.
				$transaction_entity = array
				(
					$arOld, 
					$arNew, 
					$new_timestamp, 
					$fee, 
				);
				$action['test'] = transaction_test($identifier, $transaction_entity);
				if ($action['test']['ok']) 
				{
					$action['transaction'] = transaction_code($identifier, $transaction_entity);
					// Создание намерения
					$current = array();
					$current['args'] = $transaction_entity;
					$arIntention = array();
					foreach ($_POST['options']['old_bills'] as $old_bill) 
					{
						$old = $base->bill_get($old_bill['number']);
						if (!empty($old)) 
						{
							$current['example'] = array
							(
								'number' => $old_bill['number'],
								'key' => $old_bill['key'],
								'algorithm' => $old['algorithm'],
								'denomination' => to_cent($old['denomination']),
								'timestamp' => $old['timestamp'],
								'entity' => $action['transaction'],
							);
							$current['intention'] = transaction_code($identifier, $current['args'], $current['example']);
							$current['pubkey'] = (new cEncrypt($current['example']))->pubkey;
							$intention_entity = array
							(
								$old_bill['number'], 
								$current['pubkey'], 
								$current['intention'], 
							);
							ksort($intention_entity);
							array_push($arIntention, $intention_entity);
						}
						else 
						{
							$wrong_items = true;
						}
					}
					usort($arIntention, function ($item_a, $item_b) {return strnatcmp($item_a['0'], $item_b['0']);});
					$action['intention'] = transaction_code($intention_type, $arIntention);
					$arTest =              transaction_test($intention_type, $arIntention);
					if (!$arTest['ok']) $wrong_items = true;
				}
				else 
				{
					$wrong_items = true;
				}
			}
			else 
			{
				$wrong_items = true;
			}
		}
		else 
		{
			$wrong_items = true;
		}
	}
	else 
	{
		write('Необходимые модули не установлены.', 2, 'error');
		$wrong_items = true;
	}
	if ((empty($action['test']))||(empty($action['transaction']))||(empty($action['intention']))) $wrong_items = true;
	if (!$wrong_items) (new cWallet)->action_add($action);
	echo $console;
}
?>