<?define('AJAX_FORM', true);?>
<?require_once ($_SERVER[DOCUMENT_ROOT].DIRECTORY_SEPARATOR.'load'.DIRECTORY_SEPARATOR.'start.php');?>
<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта

if ((new cWallet)->get('enabled')) 
{
	$output = '<h2>Обновление банкноты</h2>';
	// Обновление пароля банкноты
	if (!empty($_POST['bill'])) 
	{
		$bill_number = $_POST['bill']['number'];
		$bill_key = $_POST['bill']['key'];
		if (!(new cWallet)->update($bill_number, $bill_key)) 
		{
			$output .= '<p>Невозможно обновить банкноту '.$bill_number.'</p>';
		}
		else 
		{
			$output .= '<p>Банкнота '.$bill_number.' обновлена</p>';
		}
	}
	// Вывод результата
	echo $output;
}
?>