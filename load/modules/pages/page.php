<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Класс для определения параметров страницы (в зависимости от URL)

// Как называть файлы шаблонов:
// Например для страницы <mysitename.com/arg1/arg2/foo/bar/> имя файла будет выбираться в такой очередности:
// bar.foo.arg2.arg1.html.tpl.php
// foo.arg2.arg1.html.tpl.php
// arg2.arg1.html.tpl.php
// arg1.html.tpl.php
// html.tpl.php
// то есть, выбирается файл, у которого больше совпадающих аргументов указано в имени.
// Такое же правило действует для всех основных областей страницы из $this->element (не только для html)
$page = new cPages;

class cPages 
{
	public $element = array // Список основных областей страницы
	(
		'html' => '', 
		'menu' => '', 
		'left' => '', 
		'body' => '', 
		'foot' => ''
	);
	public $url; // очищенный от лишних символов адрес REDIRECT_URL
	public $arg = array(); // список аргументов из REDIRECT_URL
	private $tpl_folder; // Папка с шаблонами *.tpl.php
	
	public function cPages () 
	{
		$this->url = trim(substr($_SERVER['REDIRECT_URL'], 1, -1));
		$this->arg = explode('/', $this->url);
		$this->tpl_folder = SCRIPTS.'tpl'.DS;
		$this->get_default();
		$this->get_from_url();
	}
	// Определяем шаблоны по умолчанию на тот случай, когда нет более подходящего шаблона
	private function get_default () 
	{
		foreach ($this->element as $key => $item) 
		{
			if (is_file($this->tpl_folder.$key.'.tpl.php')) 
			{
				$this->element[$key] = $this->tpl_folder.$key.'.tpl.php';
			}
		}
	}
	// Определяем шаблоны в зависимости от аргументов из REDIRECT_URL
	private function get_from_url () 
	{
		foreach ($this->element as $key => $item) 
		{
			$file_name = $key.'.tpl.php';
			foreach ($this->arg as $arg) 
			{
				$file_name = $arg.'.'.$file_name;
				if (is_file($this->tpl_folder.$file_name)) 
				{
					$this->element[$key] = $this->tpl_folder.$file_name;
				}
			}
		}
	}
}
?>