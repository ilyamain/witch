<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта

if (local()) 
{
	?>
	<h2>Module installation / uninstallation</h2>
	<?
	foreach ((new cModules)->list_full as $module) 
	{
		if ($module['install']) 
		{
			$add_class = '';
			if ($module['enabled']) 
			{
				$install_button = '<a class="button action-uninstall icon-uninstall">Uninstall</a>';
			}
			else 
			{
				$install_button = '<a class="button action-install icon-install">Install</a>';
			}
		}
		else 
		{
			$add_class = ' install-disable';
			$install_button = '';
		}
		?>
		<form class="module-row<?=$add_class;?>" module="<?=$module['id'];?>">
			<div class="form-row form-row-float-right">
				<div class="form-field execute-button"><?=$install_button;?></div>
			</div>
			<div class="module-weight"><?=$module['weight'];?></div>
			<div class="module-name"><?=$module['name'];?> ver.<?=$module['version'];?> (<?=$module['state'];?>)</div>
			<div class="module-description"><?=$module['description'];?></div>
		</form>
		<?
	}
	?>
	<h2>Edit constants</h2>
	<?
	$arConstants = (new cBase())->constant_list();
	foreach ($arConstants as $item) 
	{
		?>
		<div class="constant-field input-field">
			<a class="button icon-ok" doit="constant_edit">Update</a>
			<input type="text" title="<?=$item['name'];?>" placeholder="<?=$item['name'];?>" constant="<?=$item['parameter'];?>" value="<?=$item['value'];?>">
			<span class="constant-caption"><?=$item['name'];?></span>
		</div>
		<?
	}
}
?>