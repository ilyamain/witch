<?define('AJAX_FORM', true);?>
<?require_once ($_SERVER[DOCUMENT_ROOT].DIRECTORY_SEPARATOR.'load'.DIRECTORY_SEPARATOR.'start.php');?>
<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта

if ($modules->is_enabled('transactions')) 
{
	$transaction = new cTransactions;
	if ($_POST['p']['form'] == 'issue') 
	{
		$newpassword = (DEMO) ? $_POST['p']['newnumber'] : $_POST['p']['newpass']; // В демоверсии все пароли равны номерам банкнот
		$newdenom = (DEMO) ? '40.55000000' : $_POST['p']['newdenom']; // В демоверсии эмитируемые банкноты одного номинала
		$bill_example = array(
			'number' => $_POST['p']['newnumber'],
			'key' => $newpassword,
			'algorithm' => $_POST['p']['algo'],
			'denomination' => to_cent($newdenom),
			'timestamp' => $_POST['p']['notime'] ? 0 : gmdate('U'),
		);
		$encrypt = new cEncrypt($bill_example);
		if ($encrypt->algorithm) {
			$bill_example['sign'] = $encrypt->sign;
			$new_bill = new cIssue($bill_example);
			$new_bill->create(true);
		}
		else
		{
			console_line('Неправильно указан алгоритм шифрования.', 2, 'error');
		}
	}
	if ($_POST['p']['form'] == 'bco') 
	{
		$newpassword = (DEMO) ? $_POST['p']['number'] : $_POST['p']['newpass']; // В демоверсии все пароли равны номерам банкнот
		$bill = $base->bill_get_row($_POST['p']['number']);
		if (!empty($bill)) 
		{
			$bill_example = array
			(
				'number' => $_POST['p']['number'], 
				'key' => $newpassword, 
				'algorithm' => $_POST['p']['algo'], 
				'denomination' => to_cent($bill['denomination']),
				'timestamp' => $_POST['p']['notime'] ? 0 : gmdate('U'),
			);
			$encrypt = new cEncrypt($bill_example);
			$bill_example['sign'] = $encrypt->sign;
			if ($encrypt->algorithm) 
			{
				$transaction->bill_change_owner
				(
					$bill_example['number'],
					$_POST['p']['oldpass'], 
					$bill_example['sign'], 
					$bill_example['algorithm'],
					$bill_example['timestamp'],
					to_cent($_POST['p']['fee']),
					true
				);
			}
			else
			{
				console_line('Неправильно указан алгоритм шифрования.', 2, 'error');
			}
		}
		else
		{
			console_line('Данные указаны неверно.', 2, 'error');
		}
	}
	if ($_POST['p']['form'] == 'bu') 
	{
		$newpassword = (DEMO) ? $_POST['p']['newnumber'] : $_POST['p']['newpass']; // В демоверсии все пароли равны номерам банкнот
		$bill1 = $base->bill_get_row($_POST['p']['oldnumber-01']);
		$bill2 = $base->bill_get_row($_POST['p']['oldnumber-02']);
		if ((!empty($bill1))&&(!empty($bill2))) 
		{
			$bill_example = array
			(
				'number' => $_POST['p']['newnumber'], 
				'key' => $newpassword, 
				'algorithm' => $_POST['p']['algo'], 
				'denomination' => to_cent($bill1['denomination']+$bill2['denomination']),
				'timestamp' => $_POST['p']['notime'] ? 0 : gmdate('U'),
			);
			$encrypt = new cEncrypt($bill_example);
			$bill_example['sign'] = $encrypt->sign;
			if ($encrypt->algorithm) 
			{
				$transaction->bill_unite
				(
					[
						[
							'n' => $_POST['p']['oldnumber-01'],
							'k' => $_POST['p']['oldpass-01'],
						],
						[
							'n' => $_POST['p']['oldnumber-02'],
							'k' => $_POST['p']['oldpass-02'],
						],
					],
					$bill_example['number'],
					$bill_example['sign'],
					$bill_example['algorithm'],
					$bill_example['timestamp'],
					to_cent($_POST['p']['fee']),
					true
				);
			}
			else
			{
				console_line('Неправильно указан алгоритм шифрования.', 2, 'error');
			}
		}
		else
		{
			console_line('Данные указаны неверно.', 2, 'error');
		}
	}
	if ($_POST['p']['form'] == 'bs') 
	{
		$newpassword1 = (DEMO) ? $_POST['p']['newnumber-01'] : $_POST['p']['newpass-01']; // В демоверсии все пароли равны номерам банкнот
		$newpassword2 = (DEMO) ? $_POST['p']['newnumber-02'] : $_POST['p']['newpass-02']; // В демоверсии все пароли равны номерам банкнот
		$bill = $base->bill_get_row($_POST['p']['oldnumber']);
		if (!empty($bill)) 
		{
			$bill_example = array
			(
				[
					'number' => $_POST['p']['newnumber-01'], 
					'key' => $newpassword1, 
					'algorithm' => $_POST['p']['algo'], 
					'denomination' => to_cent($_POST['p']['newdenom-01']),
					'timestamp' => $_POST['p']['notime'] ? 0 : gmdate('U'),
				],
				[
					'number' => $_POST['p']['newnumber-02'], 
					'key' => $newpassword2, 
					'algorithm' => $_POST['p']['algo'], 
					'denomination' => to_cent($_POST['p']['newdenom-02']),
					'timestamp' => $_POST['p']['notime'] ? 0 : gmdate('U'),
				],
			);
			$encrypt = array
			(
				new cEncrypt($bill_example[0]),
				new cEncrypt($bill_example[1]),
			);
			$bill_example[0]['sign'] = $encrypt[0]->sign;
			$bill_example[1]['sign'] = $encrypt[1]->sign;
			if (($encrypt[0]->algorithm)&&($encrypt[1]->algorithm)) 
			{
				$transaction->bill_split 
				(
					$_POST['p']['oldnumber'], 
					$_POST['p']['oldpass'], 
					[
						[
							'n' => $bill_example[0]['number'],
							's' => $bill_example[0]['sign'],
							'a' => $bill_example[0]['algorithm'],
							'd' => to_cent($bill_example[0]['denomination']),
						],
						[
							'n' => $bill_example[1]['number'],
							's' => $bill_example[1]['sign'],
							'a' => $bill_example[1]['algorithm'],
							'd' => to_cent($bill_example[1]['denomination']),
						],
					],
					$bill_example[0]['timestamp'],
					to_cent($_POST['p']['fee']),
					true
				);
			}
			else
			{
				console_line('Неправильно указан алгоритм шифрования.', 2, 'error');
			}
		}
		else
		{
			console_line('Данные указаны неверно.', 2, 'error');
		}
	}
	if ($_POST['p']['form'] == 'br') 
	{
		$newpassword1 = (DEMO) ? $_POST['p']['newnumber-01'] : $_POST['p']['newpass-01']; // В демоверсии все пароли равны номерам банкнот
		$newpassword2 = (DEMO) ? $_POST['p']['newnumber-02'] : $_POST['p']['newpass-02']; // В демоверсии все пароли равны номерам банкнот
		$bill1 = $base->bill_get_row($_POST['p']['oldnumber-01']);
		$bill2 = $base->bill_get_row($_POST['p']['oldnumber-02']);
		if ((!empty($bill1))&&(!empty($bill2))) 
		{
			$bill_example = array
			(
				[
					'number' => $_POST['p']['newnumber-01'], 
					'key' => $newpassword1, 
					'algorithm' => $_POST['p']['algo'], 
					'denomination' => to_cent($_POST['p']['newdenom-01']),
					'timestamp' => $_POST['p']['notime'] ? 0 : gmdate('U'),
				],
				[
					'number' => $_POST['p']['newnumber-02'], 
					'key' => $newpassword2, 
					'algorithm' => $_POST['p']['algo'], 
					'denomination' => to_cent($_POST['p']['newdenom-02']),
					'timestamp' => $_POST['p']['notime'] ? 0 : gmdate('U'),
				],
			);
			$encrypt = array
			(
				new cEncrypt($bill_example[0]),
				new cEncrypt($bill_example[1]),
			);
			$bill_example[0]['sign'] = $encrypt[0]->sign;
			$bill_example[1]['sign'] = $encrypt[1]->sign;
			if (($encrypt[0]->algorithm)&&($encrypt[1]->algorithm)) 
			{
				$transaction->bill_resort
				(
					[
						[
							'n' => $_POST['p']['oldnumber-01'],
							'k' => $_POST['p']['oldpass-01'],
						],
						[
							'n' => $_POST['p']['oldnumber-02'],
							'k' => $_POST['p']['oldpass-02'],
						],
					],
					[
						[
							'n' => $bill_example[0]['number'],
							's' => $bill_example[0]['sign'],
							'a' => $bill_example[0]['algorithm'],
							'd' => to_cent($bill_example[0]['denomination']),
						],
						[
							'n' => $bill_example[1]['number'],
							's' => $bill_example[1]['sign'],
							'a' => $bill_example[1]['algorithm'],
							'd' => to_cent($bill_example[1]['denomination']),
						],
					],
					$bill_example[0]['timestamp'],
					to_cent($_POST['p']['fee']),
					true
				);
			}
			else
			{
				console_line('Неправильно указан алгоритм шифрования.', 2, 'error');
			}
		}
		else
		{
			console_line('Данные указаны неверно.', 2, 'error');
		}
	}
}
else 
{
	console_line('Необходимые модули не установлены.', 2, 'error');
}
echo $console;
?>