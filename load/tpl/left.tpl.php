<?if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта?>
<div>
	<p>Уровень показываемых сообщений в консоли</p>
	<fieldset class="selector message-selector">
		<input type="radio" name="message-selector" id="left-selector-0" value="0"<?=(CONSOLE_LEVEL==0)?' checked':'';?>>
		<label for="left-selector-0">0</label>
		<input type="radio" name="message-selector" id="left-selector-1" value="1"<?=(CONSOLE_LEVEL==1)?' checked':'';?>>
		<label for="left-selector-1">1</label>
		<input type="radio" name="message-selector" id="left-selector-2" value="2"<?=(CONSOLE_LEVEL==2)?' checked':'';?>>
		<label for="left-selector-2">2</label>
		<input type="radio" name="message-selector" id="left-selector-3" value="3"<?=(CONSOLE_LEVEL==3)?' checked':'';?>>
		<label for="left-selector-3">3</label>
		<input type="radio" name="message-selector" id="left-selector-4" value="4"<?=(CONSOLE_LEVEL==4)?' checked':'';?>>
		<label for="left-selector-4">4</label>
		<input type="radio" name="message-selector" id="left-selector-5" value="5"<?=(CONSOLE_LEVEL==5)?' checked':'';?>>
		<label for="left-selector-5">5</label>
	</fieldset>
	<p>Тестовые задачи</p>
	<a class="issue-link">Эмиссия банкноты</a>
	<a class="bco-link">Смена пароля</a>
	<a class="bu-link">Объединить банкноты</a>
	<a class="bs-link">Разделить банкноты</a>
	<a class="br-link">Перемешать банкноты</a>
</div>