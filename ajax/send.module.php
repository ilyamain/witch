<?define('AJAX_FORM', true);?>
<?require_once ($_SERVER[DOCUMENT_ROOT].DIRECTORY_SEPARATOR.'load'.DIRECTORY_SEPARATOR.'start.php');?>
<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта

$mode = $_POST['p']['uninstall'] ? 'uninstall' : 'install';
$install_file = SCRIPTS.'modules'.DS.$_POST['p']['module-name'].DS.'module.install.php';
if (is_file($install_file)) require_once($install_file); else console_line('Неустанавливаемый модуль.', 5, 'error');
echo $console;
?>