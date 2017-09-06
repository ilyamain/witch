<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
foreach ((new cModules)->list_enabled as $item) 
{
	$initfile = $item['route'].DS.$item['initfile'];
	if (is_file($initfile)) require_once($initfile);
}
// Если нужно вывести в консоль список всех найденных модулей, раскомментируйте следующую строку
// console_line(print_r($modules->list_full, true), 100, 'array');
// Если нужно вывести в консоль список всех включенных модулей, раскомментируйте следующую строку
// console_line(print_r($modules->list_enabled, true), 100, 'array');
class cModules 
{
	public $list_full = array();
	public $list_enabled = array();
	private $modules_enabled_file = SCRIPTS.'modules'.DS.'modules_enabled.info';
	private $folder = SCRIPTS.'modules'.DS;
	// Инициализация класса модулей
	public function cModules () 
	{
		$this->list_full = array();
		$this->list_enabled = array();
		$arScan = scandir($this->folder);
		foreach ($arScan as $iScan) 
		{
			$module_folder = way($this->folder.$iScan.DS);
			if ((is_dir($module_folder))&&($iScan!='.')&&($iScan!='..')) 
			{
				$info_file = $module_folder.'module.info';
				if (is_file($info_file)) 
				{
					$module_info = array();
					$arModule_info = array_map('trim', file($info_file));
					foreach ($arModule_info as $iModule_info) 
					{
						$item = preg_split('/[\s]{0,}={1,}[\s]{0,}/', $iModule_info);
						$module_info[trim($item[0])] = trim($item[1]);
					}
					if (is_file($this->modules_enabled_file)) 
					{
						$arModule_enabled = array_map('trim', file($this->modules_enabled_file));
						if (in_array(trim($iScan), $arModule_enabled)) 
						{
							$module_info['enabled'] = true;
						} 
						else 
						{
							$module_info['enabled'] = false;
						}
					}
					$module_info['route'] = $module_folder;
					array_push($this->list_full, $module_info);
				}
			}
		}
		// Сортируем модули по весу и имени
		usort($this->list_full, 'sort_modules');
		// Выбираем включенные модули
		foreach ($this->list_full as $item) 
		{
			if ($item['enabled']) 
			{
				array_push($this->list_enabled, $item);
			}
		}
	}
}
// Функция сортировки модулей
function sort_modules($item_a, $item_b)
{
	$result = strnatcmp($item_a['weight'], $item_b['weight']);
	if (!$result) $result = strnatcmp($item_a['name'], $item_b['name']);
	return $result;
}
?>