<?define('AJAX_FORM', true);?>
<?require_once ($_SERVER[DOCUMENT_ROOT].DIRECTORY_SEPARATOR.'load'.DIRECTORY_SEPARATOR.'start.php');?>
<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
$demo_text = '';
$demo_text .= 'Это основная транзакция для смены владельца банкноты. Меняется пароль банкноты, ее временная метка, алгоритм шифрования.';
$demo_text .= '</br>=============================</br>';
$demo_text .= 'Так как в демонстрационной версии пароль всегда равен номеру банкноты, то при выполнении этой транзакции меняется только временная метка и алгоритм шифрования. ';
$demo_text .= 'Если Вы выберете алгоритм шифрования с защитой от радужных таблиц (Anti-rainbow), то одновременно с изменением временной метки поменяется и подпись (несмотря на то, что пароль остается старый). ';
$demo_text .= 'Если Вы введете неправильный пароль от банкноты (изначально он показывается правильно, но его можно поменять), то в консоли увидите сообщение о том, что транзакция отклонена. ';
$demo_text .= '</br>=============================</br>';
$demo_text .= 'В дальнейшем, когда будут разработаны кошельки, банкноту можно будет скачать к себе на компьютер, а также можно будет загрузить ее на сайт. ';
$demo_text .= 'Наиболее простым способом оплаты в Интернет-магазинах или досках объявлений, использующих framework или CMS Witch будет простая загрузка на сервер Вашей банкноты. Она сама создаст все необходимые транзакции и передаст токены Интернет-магазину.';


$bill_list = '';
$sql = 'SELECT * FROM bill_bills';
$bills = q($sql);
$first_bill = '';
if (!empty($bills)) 
{
	$arBills = array();
	while ($iBills = $bills->fetch_assoc()) 
	{
		array_push($arBills, $iBills);
	}
	if (!empty($arBills)) 
	{
		$first_bill = $arBills[0]['number'];
		$bill_list .= '<option disabled>Выберите банкноту</option>';
		foreach ($arBills as $iBills) 
		{
			$bill_list .= '<option value="'.$iBills['number'].'" denomination="'.$iBills['denomination'].'">'.$iBills['number'].' ('.$iBills['denomination'].')</option>';
		}
	}
	else 
	{
		$bill_list .= '<option disabled>Ошибка. Банкноты не найдены</option>';
	}
}
else 
{
		$bill_list .= '<option disabled>Внутренняя ошибка</option>';
}
?>

<div class="transaction-form bco-form">
	<form>
		<?if (DEMO) echo '<div class="form-paragraph form-illustration">'.$demo_text.'</div>';?>
		<div class="form-header">Смена пароля банкноты</div>
		<div class="form-field">
			<input type="hidden" name="form" value="bco">
		</div>
		<div class="form-field select-field number-field requires">
			<select name="number"><?=$bill_list;?></select>
		</div>
		<div class="form-field input-field pass-field requires">
			<input name="oldpass" type="text" placeholder="Старый пароль" value="<?=$first_bill;?>">
		</div>
		<div class="form-field input-field sign-field requires">
			<input name="newpass" type="text" placeholder="Новый пароль" value="<?=$first_bill;?>" disabled>
		</div>
		<div class="form-caption">Алгоритм шифрования и временная метка</div>
		<div class="form-field select-field algo-field requires">
			<select name="algo">
				<option disabled>Алгоритм</option>
				<option value="s">Simple</option>
				<option value="t">Twice</option>
				<option selected value="ar">Anti-rainbow</option>
			</select>
		</div>
		<div class="form-field checkbox-field notime-field">
			<input name="notime" type="checkbox" id="bco-notime">
			<label for="bco-notime">Не фиксировать время</label>
		</div>
		<div class="form-error"></div>
		<div class="form-field send-button"><a>Отправить</a></div>
	</form>
</div>