<?define('AJAX_FORM', true);?>
<?require_once ($_SERVER[DOCUMENT_ROOT].DIRECTORY_SEPARATOR.'load'.DIRECTORY_SEPARATOR.'start.php');?>
<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта

if (!empty($_POST['message'])) 
{
	$arOld = json_decode($_POST['message'], true);
	$old_sum = 0;
	?>
	<div id="transaction-form" class="bu-form" bill_number="<?=$bill_number;?>">
		<h2>Транзакция соединения банкнот</h2>
		<div class="bill-input-list">
			<div class="form-header">Список входных банкнот:</div>
			<?
			foreach ($arOld as $iOld) 
			{
				$bill_number = $iOld['number'];
				$bill = (new cWallet)->read($bill_number);
				?>
				<div class="bill-input-item">
					<div class="bill-number"><span class="bill-attr-value"><?=$bill['number'];?></span></div>
					<div class="bill-key"><span class="bill-attr-value"><?=$bill['key'];?></span></div>
					<div class="bill-pubkey"><span class="bill-attr-value"><?=$bill['pubkey'];?></span></div>
				</div>
				<?
				$old_sum += $bill['denomination'];
			}
			?>
			<div class="form-total">
				<span class="bill-attr-name">Сумма входных банкнот: </span>
				<span class="bill-attr-value"><?=to_cent($old_sum);?></span>
			</div>
		</div>
		<form class="bill-actions">
			<div class="form-field command-field"><input type="hidden" name="command" value="bu"></div>
			<div class="form-row bill-output-item">
				<div class="form-field input-field requires">
					<input class="icon-number" name="number" type="text" title="Номер новой банкноты" placeholder="Номер банкноты" value="<?=(new cWallet)->number_generate();?>" disabled>
				</div>
				<div class="form-field input-field requires">
					<input class="icon-sign" name="key" type="text" title="Пароль новой банкноты" placeholder="Пароль банкноты" value="<?=(new cWallet)->key_generate();?>">
					<a class="button icon-abra" abra="64">Сгенерировать</a>
				</div>
			</div>
			<div class="form-row">
				<div class="form-field input-field requires fee-field">
					<input class="icon-fee cent" name="fee" type="text" title="Комиссионные" placeholder="Комиссионные">
				</div>
				<div class="form-error"></div>
				<a class="button icon-ok" doit="transaction">Создать транзакцию</a>
			</div>
		</form>
	</div>
	<?
}
else 
{
	?>
	<div class="bu-form">
		<h2>Произошла ошибка при вызове транзакции.</h2>
	</div>
	<?
}
?>