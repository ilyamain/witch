<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта

if ((new cWallet)->get('enabled')) 
{
	?>
	<h1 class="numerata-image">Numerata wallet</h1>
	<div id="wallet">
		<form class="input-bills">
			<div class="form-header">Mix or unite this bill list:</div>
			<div class="form-row bill-input-list"></div>
			<div class="form-row form-row-center">
				<a class="button icon-bu" doit="bu">Unite bills</a>
				<a class="button icon-br" doit="br">Mix bills</a>
			</div>
		</form>
		<div class="form-header">Bills in wallet:</div>
		<?
		$bills = (new cWallet)->bill_list();
		if (!empty($bills)) 
		{
			foreach ($bills as $item) 
			{
				if ($item['busy']) $busy_level = ($item['intention']) ? ' bill-busy-1' : ' bill-busy-2'; else $busy_level = '';
				$sign_proper = ($item['sign_proper']) ? '' : ' wrong-key';
				?>
				<div class="wallet-bill-item bill-bg-<?=$item['img'].$busy_level.$sign_proper;?>" bill_number="<?=$item['number'];?>">
					<div class="wallet-bill-cover"></div>
					<div class="wallet-bill-caption bill-number">
						<span class="bill-attr-value"><?=$item['number'];?></span>
					</div>
					<div class="wallet-bill-caption bill-key">
						<span class="bill-attr-value"><?=$item['key'];?></span>
					</div>
					<div class="wallet-bill-caption bill-denomination">
						<span class="bill-attr-value"><?=$item['denomination'];?></span>
					</div>
					<div class="wallet-bill-actions">
						<?
						if (!$item['busy']) 
						{
							if ($item['sign_proper']) 
							{
								?>
								<a class="icon-info" title="View bill details" doit="info"></a>
								<a class="icon-download" title="Download bill as file" doit="download"></a>
								<a class="icon-add" title="Add bill for mix or unite" doit="inputadd"></a>
								<a class="icon-del" title="Exclude bill from mix or unite" doit="inputdel"></a>
								<?
							}
							else 
							{
								?>
								<span>Wrong key (<a doit="info">edit</a>)</span>
								<?
							}
						}
						else 
						{
							?>
							<a class="icon-ok" title="Send current action" doit="connect"></a>
							<?
						}
						?>
					</div>
				</div>
				<?
			}
		}
		else 
		{
			?>
			<div>None bill in wallet</div>
			<?
		}
		?>
		<form class="bill-actions wallet-bill-add">
			<div class="form-row">
				<div class="form-field input-field requires">
					<input class="icon-number" name="number" type="text" title="Bill number" placeholder="Bill number">
				</div>
				<div class="form-field input-field requires">
					<input class="icon-sign" name="key" type="text" title="Bill keyword" placeholder="Bill keyword">
				</div>
				<a class="button icon-add" doit="billadd">Add bill</a>
			</div>
		</form>
		<form class="wallet-bill-upload">
			<span doit="billadd">Upload bills</span>
			<input class="file-field" type="file" name="file" accept="text/html" multiple/>
		</form>
	</div>
	<?
}
?>