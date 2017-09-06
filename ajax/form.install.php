<?define('AJAX_FORM', true);?>
<?require_once ($_SERVER[DOCUMENT_ROOT].DIRECTORY_SEPARATOR.'load'.DIRECTORY_SEPARATOR.'start.php');?>
<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
$inactive_form = false;
if (version_compare(PHP_VERSION, '7.0.0') >= 0) 
{
	console_line('Установка '.PROGRAM_NAME.' на версию PHP '.PHP_VERSION.' возможна', 5, 'success');
}
else
{
	$inactive_form = true;
	console_line('Установка '.PROGRAM_NAME.' возможна на версии PHP 7.0.0 и выше', 5, 'error');
}
$uninstall_caption = '';
$uninstall_caption .= '<p>При удалении программы '.PROGRAM_NAME.' будут удалены:</p>';
$uninstall_caption .= '<p>- таблицы базы данных '.DB_NAME.'</p>';
$uninstall_caption .= (!DEMO) ? '' : '<p>- база данных '.DB_NAME.'</p>';
$uninstall_caption .= '<p>- файл конфигурации config.php</p>';
$uninstall_caption .= '<p class="space"></p>';
$uninstall_caption .= '<p>Файлы с банкнотами, блоки блокчейна и файлы кошелька не будут удалены.</p>';
$uninstall_caption .= '<p>Тем не менее, во избежание потери банкнот рекомендуется сделать резервную копию этих файлов.</p>';
?>
<div class="install-form">
	<form>
		<?if (!is_file($config_php)) {?>
			<div class="form-header">Установка <?=PROGRAM_NAME;?></div>
			<div class="form-sides">
				<div class="form-left">
					<?=$console;?>
				</div>
				<div class="form-right">
					<div class="form-field">
						<input type="hidden" name="form" value="install">
					</div>
					<div class="form-field input-field host-field requires">
						<input name="host" type="text" placeholder="Сервер базы данных" value="localhost">
					</div>
					<div class="form-field input-field name-field requires">
						<input name="name" type="text" placeholder="Название базы данных" value="testbase">
					</div>
					<div class="form-field input-field user-field requires">
						<input name="user" type="text" placeholder="Пользователь базы данных" value="root">
					</div>
					<div class="form-field input-field pass-field">
						<input name="pass" type="text" placeholder="Пароль базы данных" value="">
					</div>
					<div class="form-field checkbox-field demo-field">
						<input name="demo" type="checkbox" id="demo">
						<label for="demo">Демонстрационная версия</label>
					</div>
				</div>
			</div>
			<div class="form-error"></div>
			<div class="form-field execute-button">
				<a class="install-button">Установить</a>
			</div>
		<?}
		else
		{?>
			<div class="form-header">Удаление <?=PROGRAM_NAME;?></div>
			<div class="form-paragraph form-attention"><?=$uninstall_caption;?></div>
			<div class="form-field">
				<input type="hidden" name="form" value="uninstall">
			</div>
			<div class="form-field execute-button">
				<a class="uninstall-button">Удалить</a>
			</div>
		<?}?>
	</form>
</div>