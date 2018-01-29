<?define('AJAX_FORM', true);?>
<?require_once ($_SERVER[DOCUMENT_ROOT].DIRECTORY_SEPARATOR.'load'.DIRECTORY_SEPARATOR.'start.php');?>
<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта

if ((new cWallet)->get('enabled')) 
{
	$content = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">';
	$content .= '<html>';
	// Создание заголовка страницы с банкнотой
	$content .= '<head>';
	$filename = (!empty($_GET['n'])) ? $_GET['n'] : 'Error';
	$content .= '<title>Экземпляр банкноты '.$filename.'</title>';
	$content .= '<style>';
	$content .= 'body {position: fixed; top: 0px; bottom: 0px; left: 0px; right: 0px; width: 750px; height: 200px; margin: auto;';
	$content .= 'padding: 10px; border: 3px double #a5a5a5;}';
	$content .= 'h2 {margin: 10px 0px; text-align: center; border-bottom: 1px solid #a5a5a5;}';
	$content .= '.row {padding: 5px 30px; border-bottom: 1px dashed #a5a5a5; font-weight: bold;}';
	$content .= '.row [id] {font-weight: normal; color: #a5a5a5;}';
	$content .= '</style>';
	$content .= '</head>';
	// Создание текста страницы с банкнотой
	$content .= '<body>';
	$content .= '<h2>'.$filename.'</h2>';
	if ((!empty($_GET['n']))&&(!empty($_GET['k']))) 
	{
		$bill_number = $_GET['n'];
		$bill_key = $_GET['k'];
		$bill = (new cWallet)->read($bill_number);
		$content .= '<div class="row">';
		$content .= '<span>Номер банкноты: </span>';
		$content .= '<span id="number">'.$bill_number.'</span>';
		$content .= '</div>';
		$content .= '<div class="row">';
		$content .= '<span>Пароль: </span>';
		$content .= ($bill['key'] == $bill_key) ? '<span id="key">'.$bill_key.'</span>' : '<span id="key"></span>';
		$content .= '</div>';
	}
	$content .= '</body>';
	$content .= '</html>';
	// Вывод страницы с банкнотой
	header('Content-disposition: attachment; filename=numerata_'.$filename.'.html');
	header('Content-type: text/html');
	echo $content;
	exit();
}
?>