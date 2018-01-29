<?define('AJAX_FORM', true);?>
<?require_once ($_SERVER[DOCUMENT_ROOT].DIRECTORY_SEPARATOR.'load'.DIRECTORY_SEPARATOR.'start.php');?>
<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта

if ((local())&&(!empty($_POST['constant']))) 
{
	$parameter = $_POST['constant']['parameter'];
	$value = $_POST['constant']['value'];
	(new cBase())->constant_set($parameter, $value);
}
?>