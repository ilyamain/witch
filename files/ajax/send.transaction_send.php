<?define('AJAX_FORM', true);?>
<?require_once ($_SERVER[DOCUMENT_ROOT].DIRECTORY_SEPARATOR.'load'.DIRECTORY_SEPARATOR.'start.php');?>
<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Отправка транзакций или намерений в пул
if ((new cWallet)->get('enabled')) 
{
	if (($modules->is_enabled('transactions'))&&($modules->is_enabled('blocks'))&&($modules->is_enabled('connect'))) 
	{
		if (!empty($_POST['bill'])) 
		{
			$number = $_POST['bill']['number'];
			$type = $_POST['bill']['type'];
			// Отправка команды в пул самому себе (закомментируйте, если хотите отправлять команды в сеть)
			(new cWallet)->action_execute($number, $type);
			// Отправка команды соседним майнерам (раскомментируйте строки ниже)
			//$local = new cConnect(CONNECT_ANSWER);
			//$arMiners = ($local->connected) ? $local->handle('miners') : array();
			//(new cWallet)->action_execute($number, $type, CONNECT_REQEST, $arMiners);
		}
	}
	echo $console;
}
?>