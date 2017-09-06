<?define('AJAX_FORM', true);?>
<?require_once ($_SERVER[DOCUMENT_ROOT].DIRECTORY_SEPARATOR.'load'.DIRECTORY_SEPARATOR.'start.php');?>
<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
$demo_text = '';
$demo_text .= 'Это не транзакция. Это эмиссия банкноты. Эмиссия выполняется один раз при формировании каждого блока. ';
$demo_text .= 'Номинал эмитируемой банкноты не может превышать максимально допустимое значение, которое зависит от номера формируемого блока и от комиссий, которые указаны в транзакциях. ';
$demo_text .= 'Комиссии, указанные в транзакции, могут быть отрицательными. В этом случае майнер платит комиссию пользователю. В некоторых случаях даже отрицательная комиссия будет выгодна майнерам в силу особых правил расчета PoW.';
?>

<?$number = 'w'.gmdate('U').abra(10);?>
<div class="transaction-form issue-form">
	<form>
		<?if (DEMO) echo '<div class="form-paragraph form-illustration">'.$demo_text.'</div>';?>
		<div class="form-header">Эмиссия банкноты</div>
		<div class="form-field">
			<input type="hidden" name="form" value="issue">
		</div>
		<div class="form-field input-field number-field requires">
			<input name="newnumber" type="text" placeholder="Номер эмитируемой банкноты" value="<?=$number;?>">
		</div>
		<div class="form-field input-field pass-field requires">
			<input name="newpass" type="text" placeholder="Пароль эмитируемой банкноты" value="<?=$number;?>" disabled>
		</div>
		<div class="form-field input-field denom-field requires">
			<input name="newdenom" type="text" placeholder="Номинал эмитируемой банкноты" value="40.55000000" disabled>
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