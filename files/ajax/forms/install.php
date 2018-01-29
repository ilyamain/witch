<?define('AJAX_FORM', true);?>
<?require_once ($_SERVER[DOCUMENT_ROOT].DIRECTORY_SEPARATOR.'load'.DIRECTORY_SEPARATOR.'start.php');?>
<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
$inactive_form = false;
if (version_compare(PHP_VERSION, '7.0.0') >= 0) 
{
	write('Установка '.PROGRAM_NAME.' на версию PHP '.PHP_VERSION.' возможна.', 5, 'success');
}
else 
{
	$inactive_form = true;
	write('Установка '.PROGRAM_NAME.' возможна на версии PHP 7.0.0 и выше.', 5, 'error');
}
$uninstall_caption = '';
$uninstall_caption .= '<p>При удалении программы '.PROGRAM_NAME.' будут удалены:</p>';
$uninstall_caption .= '<p>- таблицы базы данных '.DB_NAME.'</p>';
$uninstall_caption .= '<p>- файл конфигурации config.php</p>';
$uninstall_caption .= '<p class="space"></p>';
$uninstall_caption .= '<p>Во избежание потери банкнот рекомендуется сделать их резервную копию.</p>';
?>
<div class="install-form">
	<form>
		<?
		if (!is_file($config_php)) 
		{
			?>
			<div class="form-header">Установка <?=PROGRAM_NAME;?></div>
			<div class="form-sides">
				<div class="form-left">
					<?=$console;?>
				</div>
				<div class="form-right">
					<div class="form-row form-row-width">
						<div class="form-field input-field host-field requires">
							<input class="icon-server" name="host" type="text" placeholder="Сервер базы данных" value="localhost">
						</div>
					</div>
					<div class="form-row form-row-width">
						<div class="form-field input-field name-field requires">
							<input class="icon-database" name="name" type="text" placeholder="Название базы данных" value="testbase">
						</div>
					</div>
					<div class="form-row form-row-width">
						<div class="form-field input-field user-field requires">
							<input class="icon-user" name="user" type="text" placeholder="Пользователь базы данных" value="root">
						</div>
					</div>
					<div class="form-row form-row-width">
						<div class="form-field input-field pass-field">
							<input class="icon-pass" name="pass" type="text" placeholder="Пароль базы данных" value="">
						</div>
					</div>
				</div>
			</div>
			<div class="form-row form-row-center">
				<div class="form-error"></div>
				<?
				if (!$inactive_form) 
				{
					?>
					<div class="form-field execute-button">
						<a class="button action-install icon-install">Установить</a>
					</div>
					<?
				}
				?>
				<div class="form-field">
					<a class="button icon-close" doit="formclose">Закрыть</a>
				</div>
			</div>
			<?
		}
		else 
		{
			?>
			<div class="form-header">Удаление <?=PROGRAM_NAME;?></div>
			<div class="form-paragraph form-attention"><?=$uninstall_caption;?></div>
			<div class="form-row form-row-center">
				<?
				if (!$inactive_form) 
				{
					?>
					<div class="form-field execute-button">
						<a class="button action-uninstall icon-uninstall">Удалить</a>
					</div>
					<?
				}
				?>
				<div class="form-field">
					<a class="button icon-close" doit="formclose">Закрыть</a>
				</div>
			</div>
			<?
		}
		?>
	</form>
</div>