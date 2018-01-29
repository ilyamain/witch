<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
?>
<div>
	<h2>Messages:</h2>
	<fieldset class="selector message-selector">
		<input type="radio" name="message-selector" id="left-selector-0" value="0"<?=(CONSOLE_LEVEL == 0) ? ' checked' : '';?>>
		<label for="left-selector-0">All</label>
		<input type="radio" name="message-selector" id="left-selector-1" value="1"<?=(CONSOLE_LEVEL == 1) ? ' checked' : '';?>>
		<label for="left-selector-1">System</label>
		<input type="radio" name="message-selector" id="left-selector-2" value="2"<?=(CONSOLE_LEVEL == 2) ? ' checked' : '';?>>
		<label for="left-selector-2">Transactions</label>
		<input type="radio" name="message-selector" id="left-selector-3" value="3"<?=(CONSOLE_LEVEL == 3) ? ' checked' : '';?>>
		<label for="left-selector-3">Blocks</label>
		<input type="radio" name="message-selector" id="left-selector-4" value="4"<?=(CONSOLE_LEVEL == 4) ? ' checked' : '';?>>
		<label for="left-selector-4">Main</label>
		<input type="radio" name="message-selector" id="left-selector-5" value="5"<?=(CONSOLE_LEVEL == 5) ? ' checked' : '';?>>
		<label for="left-selector-5">User</label>
	</fieldset>
</div>

<div>
	<div class="form-row form-row-width">
		<a href="/" class="button icon-mainpage">Main</a>
	</div>
	<?
	// Wallet
	$hide_user_pages = false;
	if (!defined('INSTALLED')) $hide_user_pages = true;
	if (!(new cModules)->is_enabled('transactions')) $hide_user_pages = true;
	if (!(new cModules)->is_enabled('blocks')) $hide_user_pages = true;
	if (!(new cModules)->is_enabled('connect')) $hide_user_pages = true;
	if (!(new cWallet)->get('enabled')) $hide_user_pages = true;
	if (!$hide_user_pages) 
	{
		?>
		<div class="form-row form-row-width">
			<a href="/miner" class="button icon-fee">Miner</a>
		</div>
		<div class="form-row form-row-width">
			<a href="/wallet" class="button icon-wallet">Wallet</a>
		</div>
		<?
	}
	// Installation / uninstallation of program
	if (local()) 
	{
		?>
		<div class="form-row form-row-width">
			<a href="/admin" class="button icon-install">Setup</a>
		</div>
		<?
		if (is_file(SCRIPTS.'config.php')) 
		{
			?>
			<div class="form-row form-row-width">
				<a class="button form-install-link icon-uninstall">Uninstall</a>
			</div>
			<?
		}
		else 
		{
			?>
			<div class="form-row form-row-width">
				<a class="button form-install-link icon-install">Install</a>
			</div>
			<?
		}
	}
	?>
</div>