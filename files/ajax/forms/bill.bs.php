<?define('AJAX_FORM', true);?>
<?require_once ($_SERVER[DOCUMENT_ROOT].DIRECTORY_SEPARATOR.'load'.DIRECTORY_SEPARATOR.'start.php');?>
<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта

if (!empty($_POST['message'])) 
{
	$bill_number = $_POST['message'];
	$bill = (new cWallet)->read($bill_number);
	?>
	<div id="transaction-form" class="bs-form" bill_number="<?=$bill_number;?>">
		<h2>Транзакция размена банкноты</h2>
		<div class="bill-details bill-input-item">
			<div class="bill-image bill-bg-<?=$bill['img'];?>"></div>
			<div class="bill-description">
				<div class="bill-item bill-number">
					<span class="bill-attr-name">Номер банкноты:</span>
					<span class="bill-attr-value"><?=$bill['number'];?></span>
				</div>
				<div class="bill-item bill-key">
					<span class="bill-attr-name">Пароль:</span>
					<span class="bill-attr-value"><?=$bill['key'];?></span>
				</div>
				<div class="bill-item bill-pubkey">
					<span class="bill-attr-name">Pubkey:</span>
					<span class="bill-attr-value"><?=$bill['pubkey'];?></span>
				</div>
				<div class="bill-item bill-sign">
					<span class="bill-attr-name">Подпись:</span>
					<span class="bill-attr-value"><?=$bill['sign'];?></span>
				</div>
				<div class="bill-item bill-algorithm">
					<span class="bill-attr-name">Алгоритм шифрования:</span>
					<span class="bill-attr-show"><?=$bill['algo_name'];?>. ID - </span>
					<span class="bill-attr-value"><?=$bill['algorithm'];?></span>
				</div>
				<div class="bill-item bill-timestamp">
					<span class="bill-attr-name">Временная метка:</span>
					<span class="bill-attr-show"><?=date('d.m.Y H:i:s', $bill['timestamp']);?>. UNIX - </span>
					<span class="bill-attr-value"><?=$bill['timestamp']?></span>
				</div>
				<div class="bill-item bill-denomination">
					<span class="bill-attr-name">Номинал:</span>
					<span class="bill-attr-value"><?=$bill['denomination'];?></span>
				</div>
			</div>
		</div>
		<form class="bill-actions">
			<div class="form-field command-field"><input type="hidden" name="command" value="bs"></div>
			<div class="form-row">
				<a class="button icon-add" doit="outputadd">Добавить выходную банкноту</a>
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
	<div class="bs-form">
		<h2>Произошла ошибка при вызове транзакции.</h2>
	</div>
	<?
}
?>