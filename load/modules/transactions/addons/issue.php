<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Эмиссия банкнот для формирования блока.

class cIssue 
{
	public $number = '';
	public $sign = '';
	public $algorithm = '';
	public $denomination = 0;
	public $timestamp = 0;

	public function cIssue ($input)
	{
		if (!empty($input['number'])) 
		{
			$this->number = $input['number'];
		}
		if (!empty($input['sign'])) 
		{
			$this->sign = $input['sign'];
		}
		if (!empty($input['algorithm'])) 
		{
			$this->algorithm = $input['algorithm'];
		}
		if (!empty($input['denomination'])) 
		{
			$this->denomination = to_cent($input['denomination']);
		}
		if (!empty($input['timestamp'])) 
		{
			$this->timestamp = $input['timestamp'];
		}
	}

	public function create ($compile = false)
	{
		$base = new cBase;
		$has_bill = $base->bill_get_row($this->number);
		if (empty($has_bill)) 
		{
			if 
				(
					(!empty($this->number))
					&&(is_string($this->number))
					&&(!empty($this->sign))
					&&(is_string($this->sign))
					&&(!empty($this->algorithm))
					&&(is_string($this->algorithm))
					&&(!empty($this->denomination))
					&&(is_denomination($this->denomination))
					&&($this->denomination>0)
					&&((is_timestamp($this->timestamp))||($this->timestamp == 0))
				) 
			{
				if ($compile) 
				{
					console_line('Банкнота эмитирована', 2, 'success');
					$base->bill_create_bill($this->number, $this->sign, $this->algorithm, to_cent($this->denomination), $this->timestamp);
					return true;
				}
				else 
				{
					console_line('Эмиссия допустима', 2, 'success');
					return true;
				}
			}
			else 
			{
				console_line('Неверная информация об эмиссии', 2, 'error');
				return false;
			}
		}
		else 
		{
			console_line('Неверная информация об эмиссии. Банкнота с таким номером уже существует.', 2, 'error');
			return false;
		}
	}
}
?>