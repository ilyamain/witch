<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
//	Алгоритмы шифрования

class cEncrypt 
{
	public $bill_example;
	public $algorithm;
	public $pubkey_proper;
	public $pubkey;
	public $sign_proper;
	public $sign;
	public $score = 0;

	// Загрузка экземпляра. Возможные значения ключей:
	// $bill_example['number']
	// $bill_example['key']
	// $bill_example['algorithm']
	// $bill_example['denomination']
	// $bill_example['timestamp']
	// $bill_example['entity']
	// $bill_example['entity_encrypted'] = false
	public function cEncrypt ($bill_example)
	{
		$this->bill_example = $bill_example;
		$this->test();
	}

	// Проверка алгоритма шифрования, публичного ключа и подписи
	public function test ()
	{
		$algorithm = 'crypt_'.$this->bill_example['algorithm'];
		if (method_exists($this, $algorithm)) 
		{
			$this->algorithm     = true;
			$this->pubkey        = $this->$algorithm($this->bill_example, true);
			$this->sign          = $this->$algorithm($this->bill_example);
		}
		else 
		{
			$this->algorithm     = false;
			$this->pubkey_proper = false;
			$this->sign_proper   = false;
			$this->pubkey        = '';
			$this->sign          = '';
		}
	}

	// Anti-rainbow. Для защиты от радужных таблиц.
	private function crypt_ar ($input, $return_pubkey = false)
	{
		$this->score = 50;
		if (!empty($input['key'])) 
		{
			$pubkey = hash('sha256', $input['key'].$input['number'].$input['timestamp'].$input['algorithm'].to_cent($input['denomination']));
			$this->pubkey_proper = ($input['pubkey']==$pubkey) ? true : false;
		}
		else 
		{
			$pubkey = $input['pubkey'];
			$this->pubkey_proper = false;
		}
		$sign = hash('sha256', $pubkey.$input['number'].$input['timestamp'].$input['algorithm'].to_cent($input['denomination']));
		$this->sign_proper = ($input['sign']==$sign) ? true : false;
		return $return_pubkey ? $pubkey : $sign;
	}
}
?>