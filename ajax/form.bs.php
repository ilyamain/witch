<?define('AJAX_FORM', true);?>
<?require_once ($_SERVER[DOCUMENT_ROOT].DIRECTORY_SEPARATOR.'load'.DIRECTORY_SEPARATOR.'start.php');?>
<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
$demo_text = '';
$demo_text .= 'Представьте ситуацию, когда у Вас есть банкнота, достоинством 100 единиц, а Вам надо оплатить покупку стоиостью 20 единиц. Весьма часто люди сталкиваются с такой задачей при расчете наличными деньгами. Как правило, решение выглядит следующим образом: вы даете кассиру 100, а он Вам возвращает сдачу 80. Но иногда на кассе нет 80 и тогда и Вы  и расстроенный кассир отменяете сделку. Либо Вы что то еще докупаете до суммы, с которой сдача у кассира найдется. Например, стандартное &laquo;жвачку на сдачу не возьмете&raquo;? ';
$demo_text .= 'Чтобы устранить эту проблему, в электронной наличке добавлена транзакция размена банкнот. Вы просто отправляете транзакцию о том, что хотите поменять свою банкноту в 100 единиц на две банкноты по 80 и 20 единиц. После этого передаете продавцу банкноту 20 единиц, а у себя оставляете банкноту, номиналом 80 единиц.';
$demo_text .= '</br>=============================</br>';
$demo_text .= 'В демонстрационной версии стандартно приняты некоторые ограничения. Первое - как и в других транзакциях, пароли всех банкнот полностью совпадают с их номерами. Второе - можно разбить только на 2 банкноты. В недемонстрационном режиме можно разбить одну банкноту на неограниченное количество банкнот (например, если Вы хотите сделать несколько покупок в разных магазинах).';

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

<div class="transaction-form bs-form">
	<form>
		<?if (DEMO) echo '<div class="form-paragraph form-illustration">'.$demo_text.'</div>';?>
		<div class="form-header">Размен банкноты</div>
		<div class="form-field">
			<input type="hidden" name="form" value="bs">
		</div>
		<div class="form-caption">Старая банкнота</div>
		<div class="form-field select-field number-field requires">
			<select name="oldnumber"><?=$bill_list;?></select>
		</div>
		<div class="form-field input-field pass-field requires">
			<input name="oldpass" type="text" placeholder="Пароль" value="<?=$first_bill;?>">
		</div>
		<div class="form-caption">=============================</div>
		<div class="form-caption">Новая банкнота №1</div>
		<div class="form-field input-field number-field requires">
			<input name="newnumber-01" type="text" placeholder="Номер банкноты 1">
		</div>
		<div class="form-field input-field sign-field requires">
			<input name="newpass-01" type="text" placeholder="Пароль банкноты 1" disabled>
		</div>
		<div class="form-field input-field denom-field requires">
			<input name="newdenom-01" type="text" placeholder="Номинал банкноты 1">
		</div>
		<div class="form-caption">Новая банкнота №2</div>
		<div class="form-field input-field number-field requires">
			<input name="newnumber-02" type="text" placeholder="Номер банкноты 2">
		</div>
		<div class="form-field input-field sign-field requires">
			<input name="newpass-02" type="text" placeholder="Пароль банкноты 2" disabled>
		</div>
		<div class="form-field input-field denom-field requires">
			<input name="newdenom-02" type="text" placeholder="Номинал банкноты 2">
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
			<input name="notime" type="checkbox" id="bs-notime">
			<label for="bs-notime">Не фиксировать время</label>
		</div>
		<div class="form-field input-field denom-field requires">
			<input name="fee" type="text" placeholder="Комиссионные">
		</div>
		<div class="form-error"></div>
		<div class="form-field send-button"><a>Отправить</a></div>
	</form>
</div>