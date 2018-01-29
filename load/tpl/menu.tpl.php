<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта

if (local()) 
{
	?>
	<div>
		<a href="/" class="icon-mainpage">Main</a>
		<?
		// Wallet
		if (defined('INSTALLED')) 
		{
			if (((new cModules)->is_enabled('blocks'))&&((new cWallet)->get('enabled'))) 
			{
				?>
				<a href="/wallet" class="icon-wallet">Wallet</a>
				<?
			}
		}
		// Installation / uninstallation of program
		if (is_file(SCRIPTS.'config.php')) 
		{
			?>
			<a class="form-install-link icon-uninstall">Uninstall</a>
			<?
		}
		else 
		{
			?>
			<a class="form-install-link icon-install">Install</a>
			<?
		}
		?>
	</div>
	<?
}
?>