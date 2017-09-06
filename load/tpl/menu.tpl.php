<?if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта?>
<div>
	<a href="/" class="mainpage-link">Главная</a>
		<?if (is_file(SCRIPTS.'config.php')) {?>
			<a class="form-install-link uninstall-link">Удаление <?=PROGRAM_NAME?></a>
		<?}
		else
		{?>
			<a class="form-install-link install-link">Установка <?=PROGRAM_NAME?></a>
		<?}?>
</div>