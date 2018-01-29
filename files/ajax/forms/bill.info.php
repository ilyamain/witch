<?define('AJAX_FORM', true);?>
<?require_once ($_SERVER[DOCUMENT_ROOT].DIRECTORY_SEPARATOR.'load'.DIRECTORY_SEPARATOR.'start.php');?>
<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта

if (!empty($_POST['message'])) 
{
	$bill_number = $_POST['message'];
	$bill = (new cWallet)->read($bill_number);
	?>
	<div class="bill-form" bill_number="<?=$bill['number'];?>">
		<h2>Информация о банкноте</h2>
		<div class="bill-details bill-input-item">
			<div class="bill-image bill-bg-<?=$bill['img'];?>"></div>
			<div class="bill-description">
				<div class="bill-item bill-number">
					<span class="bill-attr-name">Номер банкноты:</span>
					<span class="bill-attr-value"><?=$bill['number'];?></span>
				</div>
				<?
				if ($bill['sign_proper']) 
				{
					?>
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
					<?
				}
				else 
				{
					?>
					<div class="bill-item">
						<span class="bill-attr-name">Пароль:</span>
						<span class="bill-attr-value"><?=$bill['key'];?></span>
					</div>
					<div class="bill-item">Пароль для банкноты указан неверно</div>
					<?
				}
				?>
			</div>
		</div>
		<div class="bill-actions">
			<?
			if ($bill['sign_proper']) 
			{
				?>
				<a class="button icon-download" doit="download">Скачать</a>
				<a class="button icon-bco" doit="bco">Сменить пароль</a>
				<a class="button icon-bs" doit="bs">Разделить</a>
				<?
			}
			else 
			{
				?>
				<div class="form-row">
					<div class="form-field input-field requires">
						<input class="icon-sign" name="key" type="text" title="Пароль банкноты" placeholder="Пароль банкноты">
					</div>
					<a class="button icon-edit" doit="billedit">Редактировать</a>
				</div>
				<?
			}
			?>
		</div>
		<table>
			<tr><td>Прежний пароль</td><td>Время изменения</td></tr>
			<?
			$arKeys = (new cWallet)->stack_get($bill_number);
			if (!empty($arKeys)) foreach ($arKeys as $iKeys) 
			{
				?>
				<tr>
					<td><?=$iKeys['bill_key'];?></td>
					<td><?=date('d.m.Y H:i:s', $iKeys['timestamp']);?></td>
				</tr>
				<?
			}
			?>
		</table>
	</div>
	<?
}
else 
{
	?>
	<div class="bill-form">
		<h2>Произошла ошибка при запросе банкноты</h2>
	</div>
	<?
}
?>