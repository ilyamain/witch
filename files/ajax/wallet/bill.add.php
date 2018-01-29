<?define('AJAX_FORM', true);?>
<?require_once ($_SERVER[DOCUMENT_ROOT].DIRECTORY_SEPARATOR.'load'.DIRECTORY_SEPARATOR.'start.php');?>
<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта

if ((new cWallet)->get('enabled')) 
{
	$output = '<h2>Добавление банкнот</h2>';
	$have_items = false;

	// Добавление загруженных банкнот
	if (!empty($_FILES)) 
	{
		$have_items = true;
		foreach ($_FILES as $file) 
		{
			$arDOM = array();
			$content = file($file['tmp_name']);
			$arFile = new DOMDocument;
			libxml_use_internal_errors(true);
			$arFile->loadHTML($content['0']);
			libxml_clear_errors();
			$bill_number = $arFile->getElementById('number')->textContent;
			$bill_key = $arFile->getElementById('key')->textContent;
			// Загрузка банкноты
			if (!(new cWallet)->add_bill($bill_number, $bill_key)) 
			{
				$output .= '<p>Невозможно добавить банкноту '.$bill_number.'</p>';
			}
			else 
			{
				$output .= '<p>Банкнота '.$bill_number.' добавлена</p>';
			}
		}
	}

	// Добавление банкнот по введенному номеру и паролю
	if (!empty($_POST['message'])) 
	{
		$have_items = true;
		$bill_number = $_POST['message']['number'];
		$bill_key = $_POST['message']['key'];
		if (!(new cWallet)->add_bill($bill_number, $bill_key)) 
		{
			$output .= '<p>Невозможно добавить банкноту '.$bill_number.'</p>';
		}
		else 
		{
			$output .= '<p>Банкнота '.$bill_number.' добавлена</p>';
		}
	}
	if (!$have_items) $output .= '<p>Не указаны номер и пароль банкноты</p>';

	// Вывод результата
	echo $output;
}
?>