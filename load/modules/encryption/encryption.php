<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Алгоритмы шифрования

class cEncrypt 
{
	public $bill_example;
	public $algorithm;
	public $allow_transfer;
	public $name;
	public $pubkey;
	public $pubkey_proper;
	public $sign;
	public $sign_proper;
	public $score = 0;

	// Загрузка экземпляра. Возможные значения ключей:
	// $bill_example['number']
	// $bill_example['key']
	// $bill_example['pubkey']
	// $bill_example['algorithm']
	// $bill_example['denomination']
	// $bill_example['timestamp']
	// $bill_example['entity']
	// $bill_example['entity_encrypted'] = false
	// $bill_example['sign']
	public function cEncrypt ($bill_example)
	{
		$this->bill_example = $bill_example;
		$this->test();
	}

	// Проверка алгоритма шифрования, публичного ключа и подписи
	public function test ()
	{
		$this->algorithm      = false;
		$this->algorithm      = updates_encrypt_algorithms($this->bill_example, 'algorithm');
		$this->allow_transfer = false;
		$this->allow_transfer = updates_encrypt_algorithms($this->bill_example, 'allow_transfer');
		$this->name           = $this->bill_example['algorithm'];
		$this->name           = updates_encrypt_algorithms($this->bill_example, 'name');
		$this->pubkey         = '';
		$this->pubkey         = updates_encrypt_algorithms($this->bill_example, 'pubkey');
		$this->pubkey_proper  = false;
		$this->pubkey_proper  = updates_encrypt_algorithms($this->bill_example, 'pubkey_proper');
		$this->sign           = '';
		$this->sign           = updates_encrypt_algorithms($this->bill_example, 'sign');
		$this->sign_proper    = false;
		$this->sign_proper    = updates_encrypt_algorithms($this->bill_example, 'sign_proper');
		$this->score          = 0;
		$this->score          = updates_encrypt_algorithms($this->bill_example, 'score');
	}
}

// Обращение к файлам алгоритма шифрования
function updates_encrypt_algorithms ($example, $caller)
{
	$output = '';
	$file['func'] = way(DR.DS.'updates'.DS.'encrypt_algorithms'.DS.$example['algorithm'].'.func.php');
	$file['exec'] = way(DR.DS.'updates'.DS.'encrypt_algorithms'.DS.$example['algorithm'].'.exec.php');
	if (is_file($file['func'])) require_once($file['func']);
	if (is_file($file['exec'])) $output = require($file['exec']);
	return $output;
}
?>