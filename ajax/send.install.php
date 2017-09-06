<?define('AJAX_FORM', true);?>
<?require_once ($_SERVER[DOCUMENT_ROOT].DIRECTORY_SEPARATOR.'load'.DIRECTORY_SEPARATOR.'start.php');?>
<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
require_once(SCRIPTS.'install'.DS.'install.php');

if ($_POST['p']['form'] == 'install') 
{
	$demoversion = ($_POST['p']['demo']) ? true : false;
	$install->set_parameters($_POST['p']['host'], $_POST['p']['name'], $_POST['p']['user'], $_POST['p']['pass'], $demoversion);
	$install->install();
}
if ($_POST['p']['form'] == 'uninstall') 
{
	$install->set_parameters(DB_HOST, DB_NAME, DB_USER, DB_PASS, DEMO);
	$install->uninstall();
}
echo $console;
?>