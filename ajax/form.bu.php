<?define('AJAX_FORM', true);?>
<?require_once ($_SERVER[DOCUMENT_ROOT].DIRECTORY_SEPARATOR.'load'.DIRECTORY_SEPARATOR.'start.php');?>
<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
$demo_text = '';
$demo_text .= 'При расчете обычными наличными денежными знаками, очень часто возникает проблема оплаты покупки без сдачи. В электронных банкнотах BILL эта проблема устранена. ';
$demo_text .= 'Вы можете создать из нескольких мелких банкнот одну крупную любого номинала. Даже дробного номинала, если это необходимо. Например, при соединении банкнот номиналами 35.00111 и 4.45870033, получится банкнота, достоинством 39.45981033. ';
$demo_text .= 'Также Вы можете задать пароль, временную метку и алгоритм шифрования для новой банкноты. Транзакция соединения банкнот будет востребована, когда Вы захотите оплатить одной банкнотой соглашение по смарт-контракту. В некоторых случаях это будет намного удобнее по сравнению с привязкой множества банкнот к контрактам.';
$demo_text .= '</br>=============================</br>';
$demo_text .= 'В демонстрационной версии стандартно приняты некоторые ограничения. Первое - как и в других транзакциях, пароли всех банкнот полностью совпадают с их номерами. Второе - можно соединить только 2 банкноты. В недемонстрационном режиме можно соединять неограниченное количество банкнот.';

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

<div class="transaction-form bu-form">
	<form>
		<?if (DEMO) echo '<div class="form-paragraph form-illustration">'.$demo_text.'</div>';?>
		<div class="form-header">Соединение банкнот</div>
		<div class="form-field">
			<input type="hidden" name="form" value="bu">
		</div>
		<div class="form-caption">Старая банкнота №1</div>
		<div class="form-field select-field number-field requires">
			<select name="oldnumber-01"><?=$bill_list;?></select>
		</div>
		<div class="form-field input-field pass-field requires">
			<input name="oldpass-01" type="text" placeholder="Пароль банкноты 1" value="<?=$first_bill;?>">
		</div>
		<div class="form-caption">Старая банкнота №2</div>
		<div class="form-field select-field number-field requires">
			<select name="oldnumber-02"><?=$bill_list;?></select>
		</div>
		<div class="form-field input-field pass-field requires">
			<input name="oldpass-02" type="text" placeholder="Пароль банкноты 2" value="<?=$first_bill;?>">
		</div>
		<div class="form-caption">=============================</div>
		<div class="form-caption">Новая банкнота</div>
		<div class="form-field input-field number-field requires">
			<input name="newnumber" type="text" placeholder="Новый номер банкноты">
		</div>
		<div class="form-field input-field sign-field requires">
			<input name="newpass" type="text" placeholder="Пароль" disabled>
		</div>
		<div class="form-caption">Алгоритм шифрования и временная метка</div>
		<div class="form-field select-field algo-field requires">
			<select name="algo">
				<option disabled>Алгоритм</option>
				<option selected value="ar">Anti-rainbow</option>
			</select>
		</div>
		<div class="form-field checkbox-field notime-field">
			<input name="notime" type="checkbox" id="bu-notime">
			<label for="bu-notime">Не фиксировать время</label>
		</div>
		<div class="form-field input-field denom-field requires">
			<input name="fee" type="text" placeholder="Комиссионные">
		</div>
		<div class="form-error"></div>
		<div class="form-field send-button"><a>Отправить</a></div>
	</form>
</div>