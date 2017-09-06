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
	
	public function cEncrypt ($bill_example)
	{
		$this->bill_example = $bill_example;
		$this->test();
	}
	
	public function test ()
	{
		$algorithm = $this->bill_example['algorithm'];
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
	private function s ($input, $return_pubkey = false) // Simple. Однократное хэширование SHA-256
	{
		if (!empty($input['key']))
		{
//			$pubkey = $input['key']; // Для тестирования. Чтобы видеть пароли.
			$pubkey = hash('sha256', $input['key']); // Для рабочей версии
			$this->pubkey_proper = ($input['pubkey']==$pubkey) ? true : false;
		}
		else
		{
			$pubkey = $input['pubkey'];
			$this->pubkey_proper = false;
		}
		$sign = hash('sha256', $pubkey);
		$this->sign_proper = ($input['sign']==$sign) ? true : false;
		return $return_pubkey ? $pubkey : $sign;
	}
	private function t ($input, $return_pubkey = false) // Twice. Двукратное хэширование SHA-256
	{
		if (!empty($input['key']))
		{
//			$pubkey = $input['key']; // Для тестирования. Чтобы видеть пароли.
			$pubkey = hash('sha256', hash('sha256', $input['key'])); // Для рабочей версии
			$this->pubkey_proper = ($input['pubkey']==$pubkey) ? true : false;
		}
		else
		{
			$pubkey = $input['pubkey'];
			$this->pubkey_proper = false;
		}
		$sign = hash('sha256', hash('sha256', $pubkey));
		$this->sign_proper = ($input['sign']==$sign) ? true : false;
		return $return_pubkey ? $pubkey : $sign;
	}
	private function ar ($input, $return_pubkey = false) // Anti-rainbow. Для защиты от радужных таблиц.
	{
		if (!empty($input['key']))
		{
//			$pubkey = $input['key']; // Для тестирования. Чтобы видеть пароли.
			$pubkey = hash('sha256', $input['key'].$input['number'].$input['timestamp'].$input['algorithm'].$input['denomination']); // Для рабочей версии
			$this->pubkey_proper = ($input['pubkey']==$pubkey) ? true : false;
		}
		else
		{
			$pubkey = $input['pubkey'];
			$this->pubkey_proper = false;
		}
		$sign = hash('sha256', $pubkey.$input['number'].$input['timestamp'].$input['algorithm'].$input['denomination']);
		$this->sign_proper = ($input['sign']==$sign) ? true : false;
		return $return_pubkey ? $pubkey : $sign;
	}
	private function l ($input, $return_pubkey = false) // Подпись Лампорта.
	{
		// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! в разработке
		if (!empty($input['key']))
		{
			$pubkey = $input['key']; // в разработке. Сложный алгоритм.
			$this->pubkey_proper = ($input['pubkey']==$pubkey) ? true : false;
		}
		else
		{
			$pubkey = $input['pubkey'];
			$this->pubkey_proper = false;
		}
		$sign = 'В разработке';
		$this->sign_proper = ($input['sign']==$sign) ? true : false;
		return $return_pubkey ? $pubkey : $sign;
	}
}
?>