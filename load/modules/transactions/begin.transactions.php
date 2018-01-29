<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта

// Загрузка параметров модуля
module_config (__DIR__);

class cTransactions 
{
	private $ok;
	private $is;
	private $json;
	private $type;
	private $entity;
	private $fee;
	private $denomination;
	private $number;
	private $output;

	// Отправка транзакции на исполнение
	public function execute ($transaction_name, $input, $compile = false)
	{
		$output = updates_transaction_types($transaction_name, $input, $compile);
		$this->ok = $output['ok'];
		$this->is = $output['is'];
		$this->json = $output['json'];
		$this->type = $output['type'];
		$this->entity = $output['entity'];
		$this->fee = $output['fee'];
		$this->denomination = $output['denomination'];
		$this->number = $output['number'];
		$this->output = $output['output'];
		return $output;
	}

	// Чтение параметров транзакции (например, JSON)
	public function get ($option)
	{
		return (property_exists($this, $option)) ? $this->$option : false;
	}
}
?>