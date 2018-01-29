<?define('AJAX_FORM', true);?>
<?require_once ($_SERVER[DOCUMENT_ROOT].DIRECTORY_SEPARATOR.'load'.DIRECTORY_SEPARATOR.'start.php');?>
<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта

if ((new cWallet)->get('enabled')) 
{
	?>
	<a class="little-button icon-del" doit="outputdel"></a>
	<div class="form-field input-field requires">
		<div>Номер новой банкноты</div>
		<input class="icon-number" name="number" type="text" title="Номер новой банкноты" placeholder="Номер банкноты" value="<?=(new cWallet)->number_generate();?>" disabled>
	</div>
	<div class="form-field input-field requires">
		<div>Пароль новой банкноты</div>
		<input class="icon-sign" name="key" type="text" title="Пароль новой банкноты" placeholder="Пароль банкноты" value="<?=(new cWallet)->key_generate();?>">
		<a class="button icon-abra" abra="64">Сгенерировать</a>
	</div>
	<div class="form-field input-field requires">
		<div>Номинал новой банкноты</div>
		<input class="icon-denomination cent" name="denomination" type="text" title="Номинал новой банкноты" placeholder="Номинал">
	</div>
	<?
}
?>