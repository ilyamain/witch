<?define('AJAX_FORM', true);?>
<?require_once ($_SERVER[DOCUMENT_ROOT].DIRECTORY_SEPARATOR.'load'.DIRECTORY_SEPARATOR.'start.php');?>
<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Установка программы или модуля
if (local()) 
{
	if (empty($_POST['options']['module'])) 
	{
		require_once(SCRIPTS.'install'.DS.'install.php');
		if ($_POST['options']['form'] == 'install') 
		{
			$install->set_parameters($_POST['options']['host'], $_POST['options']['name'], $_POST['options']['user'], $_POST['options']['pass']);
			$install->install();
		}
		if ($_POST['options']['form'] == 'uninstall') 
		{
			$install->set_parameters(DB_HOST, DB_NAME, DB_USER, DB_PASS);
			$install->uninstall();
		}
	}
	else 
	{
		$mode = $_POST['options']['form'];
		$install_file = SCRIPTS.'modules'.DS.$_POST['options']['module'].DS.'module.install.php';
		if (is_file($install_file)) require_once($install_file); else write('Неустанавливаемый модуль.', 5, 'error');
	}
}
else 
{
	write('Автоматическая установка возможна только на локальном компьютере.', 5, 'error');
}
echo $console;
?>