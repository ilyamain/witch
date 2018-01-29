<?define('AJAX_FORM', true);?>
<?require_once ($_SERVER[DOCUMENT_ROOT].DIRECTORY_SEPARATOR.'load'.DIRECTORY_SEPARATOR.'start.php');?>
<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
$message_content  = (!empty($_POST['message'])) ? $_POST['message'] : '<h2>Сообщение</h2>';
?>
<div><?=$message_content;?></div>
<div class="form-row form-row-center"><a class="button icon-close" doit="formclose">Закрыть</a></div>