<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта

if (defined('INSTALLED')) {
	$modules = new cModules;
	foreach ($modules->list_enabled as $item) 
	{
		$initfile = $item['route'].DS.$item['initfile'];
		if (is_file($initfile)) require_once($initfile);
	}
}

// Если нужно вывести в консоль список всех найденных модулей, раскомментируйте следующую строку
// $modules = new cModules;
// console_line(print_r($modules->list_full, true), 100, 'array');
// Если нужно вывести в консоль список всех включенных модулей, раскомментируйте следующую строку
// $modules = new cModules;
// console_line(print_r($modules->list_enabled, true), 100, 'array');
// Если нужно проверить установлен ли модуль, раскомментируйте следующую строку (либо по id либо по названию модуля)
// $modules = new cModules;
// if ($modules->is_enabled('blocks')) console_line('enabled', 100, 'array'); else  console_line('disabled', 100, 'array');
// if ($modules->is_enabled('Blocks', true)) console_line('enabled', 100, 'array'); else  console_line('disabled', 100, 'array');

class cModules 
{
	public $list_full = array();
	public $list_enabled = array();
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
					$module_info = array('id'=>$iScan);
					$arModule_info = array_map('trim', file($info_file));
					foreach ($arModule_info as $iModule_info) 
					{
						$item = preg_split('/[\s]{0,}={1,}[\s]{0,}/', $iModule_info);
						$module_info[trim($item[0])] = trim($item[1]);
					}
					$module_info['route'] = $module_folder;
					// Если модуль установлен (имеется файл module.config.php) или если модуль не требует установку
					// (отсутствует файл module.install.php), то модуль считается разрешенным
					if((is_file($module_folder.DS.'module.config.php'))||(!is_file($module_folder.DS.'module.install.php'))) 
					{
						$module_info['enabled'] = true;
					}
					else
					{
						$module_info['enabled'] = false;
					}
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
	
	// Проверка установлен ли модуль (по id или по названию)
	public function is_enabled ($module, $test_by_name = false) 
	{
		$output = false;
		$arModules = $this->list_enabled;
		foreach ($arModules as $iModules) 
		{
			if ($test_by_name) 
			{
				if (($iModules['name']==$module)&&($iModules['enabled'])) $output = true;
			}
			else 
			{
				if (($iModules['id']==$module)&&($iModules['enabled'])) $output = true;
			}
		}
		return $output;
	}
}

// Функция сортировки модулей
function sort_modules($item_a, $item_b)
{
	$result = strnatcmp($item_a['weight'], $item_b['weight']);
	if (!$result) $result = strnatcmp($item_a['name'], $item_b['name']);
	return $result;
}

// Установка модуля
function module_config ($module_dir) 
{
	$config_file = way($module_dir.DS.'module.config.php');
	$install_file = way($module_dir.DS.'module.install.php');
	$functions_file = way($module_dir.DS.'module.functions.php');
	$addons_dir = way($module_dir.DS.'addons');
	if (is_dir($addons_dir)) 
	{
		$arAddons = scandir($addons_dir);
		foreach ($arAddons as $key => $iAddons) if (is_file(way($addons_dir.DS.$iAddons))) require_once(way($addons_dir.DS.$iAddons));
	}
	if (is_file($config_file)) 
	{
		require_once($config_file);
		if (is_file($functions_file)) require_once($functions_file);
		return true;
	}
	else 
	{
		if ((!defined('AJAX_FORM'))&&(is_file($install_file))) require_once($install_file);
		return false;
	}
}
?>