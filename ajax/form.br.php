<?define('AJAX_FORM', true);?>
<?require_once ($_SERVER[DOCUMENT_ROOT].DIRECTORY_SEPARATOR.'load'.DIRECTORY_SEPARATOR.'start.php');?>
<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
$demo_text = '';
$demo_text .= 'Например, у Вас есть две банкноты: 200 единиц и 50 единиц. А надо совершить покупки на 80 и 130 единиц. Теоретически можно сначала объединить банкноты, получить из них новую банкноту номиналом 250 единиц. ';
$demo_text .= 'Потом ее разбить на 3 части: 80, 130 и 40 единиц. Две первые отправить на оплату покупок, а последнюю оставить себе. Однако, как известно, в блокчейн транзакции проводятся непривычно медленно. Особенно, если Вы отправляете двухэтапную транзакцию. ';
$demo_text .= 'Сначала фиксируется намерение об объединении. Потом банкноты объединяются. Потом фиксируется намерение о размене. Потом происходит размен. ';
$demo_text .= 'В итоге, на совершение всех указанных транзакций потребуется несколько часов. Чтобы ускорить этот процесс, добавлена возможность перемешивания бнкнот одной транзакцией.';
$demo_text .= '</br>=============================</br>';
$demo_text .= 'Кроме того, эта же транзакция может применяться для анонимизации ранее отслеженных банкнот. Например, несколько пользователей могут отправить свои банкноты сервису анонимизации. ';
$demo_text .= 'Он их перемешивает и возвращает пользователям их же суммы, только с другими номерами и другими номиналами.';
$demo_text .= '</br>=============================</br>';
$demo_text .= 'В демонстрационной версии стандартно приняты некоторые ограничения. Первое - как и в других транзакциях, пароли всех банкнот полностью совпадают с их номерами. Второе - можно перемешать только 2 старые банкноты на 2 новые. ';
$demo_text .= 'В недемонстрационном режиме можно перемешивать неограниченное количество банкнот. Количество банкнот на входе и на выходе может различаться. Алгоритмы шифрования каждой новой банкноты могут устанавливаться отдельно.';

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

<div class="transaction-form br-form">
	<form>
		<?if (DEMO) echo '<div class="form-paragraph form-illustration">'.$demo_text.'</div>';?>
		<div class="form-header">Перемешивание банкнот</div>
		<div class="form-field">
			<input type="hidden" name="form" value="br">
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
			<input name="notime" type="checkbox" id="br-notime">
			<label for="br-notime">Не фиксировать время</label>
		</div>
		<div class="form-error"></div>
		<div class="form-field send-button"><a>Отправить</a></div>
	</form>
</div>