<?if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта?>
<?
$demo_text = '';
$demo_text .= 'Когда в недемонстрационной версии Вы отправляете транзакцию о смене пароля банкноты или какую-нибудь другую транзакцию, Вы отправляете ее разным майнерам. ';
$demo_text .= 'Однако может получиться так, что половина майнеров, которым Вы отправили свою транзакцию, окажутся принадлежащими одному и тому же лицу. ';
$demo_text .= 'В этом случае он может воспользоваться присланным Вами ему паролем к банкноте и сменить его на нужный ему пароль. Таким образом, он перехватит Вашу банкноту. ';
$demo_text .= 'Чтобы этого избежать, в стандартных кошельках будет использоваться двухэтапная отправка транзакции. На первом этапе Вы отправляете не саму транзакцию, а хэш о намерении ее совершить. ';
$demo_text .= 'Сам &laquo;текст&raquo; транзакции не высвечивается до тех пор, пока Вы не отправите ее вторую часть (потому что в тексте транзакции тоже написан старый пароль). ';
$demo_text .= 'Хэш намерения совершить транзакцию подписывается не паролем, а публичным ключом. То есть, сам пароль еще никому не виден. Но теперь уже все видят Ваш публичный ключ. ';
$demo_text .= 'Другие пользователи с этого момента тоже могут отправить транзакции о намерении в отношении Вашей банкноты. Но их намерения только добавляются в список, а не заменяют Ваше намерение. ';
$demo_text .= 'После того, как все майнеры уже определятся и примут Ваше намерение о совершении транзакции, Ваш кошелек отправит в блокчейн команду о проведении второго этапа - ее саму. ';
$demo_text .= 'Майнеры при проверке валидности транзакции сначала проверяют, было ли опубликовано намерение о ее осуществлении (через проверку хэша). ';
$demo_text .= 'Если было точь-в-точь такое намерение или если в отношении Вашей банкноты вообще не было опубликовано ни одного намерения, то транзакция считается валидной. В противном случае, транзакция отклоняется. ';
$demo_text .= 'Таким образом защищается транзакция от подделки ее недобросовестным майнером. С момента отправки самого текста транзакции в блокчейн, никакие новые намерения в отношении этой банкноты уже не принимаются майнерами.';
$demo_text .= '</br>=============================</br>';
$demo_text .= 'В демонстрационной версии разные люди создают разные банкноты в одном и том же кошельке. ';
$demo_text .= 'Заранее намерения к банкнотам неизвестны. Известен лишь ее хэш. Неизвестно и то, какие намерения установил другой человек к банкноте. ';
$demo_text .= 'В итоге получится так, что банкноты окажутся заблокированными. ';
$demo_text .= 'Поэтому в демонстрационной версии отключена функция добавления намерений.';
?>

<?$sql = 'SELECT * FROM bill_bills';
$bills = q($sql);
if (!empty($bills)) 
{
	$arBills = array();
	while ($iBills = $bills->fetch_assoc()) 
	{
		array_push($arBills, $iBills);
	}?>
	<p>Таблица банкнот</p>
	<div class="desktop">
		<table>
			<tr>
				<td>Банкнота</td>
				<td>Подпись</td>
				<td>Алгоритм</td>
				<td>Номинал</td>
				<td>Timestamp</td>
			</tr>
			<?if (!empty($arBills)) 
			{
				foreach ($arBills as $iBills) 
				{?>
					<tr>
						<td><?=$iBills['number'];?></td>
						<td><?=$iBills['sign'];?></td>
						<td><?=$iBills['algorithm'];?></td>
						<td class="right-text"><?=$iBills['denomination'];?></td>
						<td><?=date('d.m.Y H:i:s', $iBills['timestamp']);?></td>
					</tr>
				<?}
			}
			else 
			{?>
				<tr><td colspan="5">Банкноты не найдены</td></tr>
			<?}?>
		</table>
	</div>
	<div class="mobile">
		<?if (!empty($arBills)) 
		{
			foreach ($arBills as $iBills) 
			{?>
				<div class="mobile-table-row">
					<div class="mobile-table-item">Банкнота: <?=$iBills['number'];?></div>
					<div class="mobile-table-item">Подпись: <?=$iBills['sign'];?></div>
					<div class="mobile-table-item">Алгоритм: <?=$iBills['algorithm'];?></div>
					<div class="mobile-table-item">Номинал: <?=$iBills['denomination'];?></div>
					<div class="mobile-table-item">Timestamp: <?=date('d.m.Y H:i:s', $iBills['timestamp']);?></div>
				</div>
			<?}
		}
		else 
		{?>
			<div class="mobile-table-row">Банкноты не найдены</div>
		<?}?>
	</div>
<?}
else 
{?>
	<div>Произошла внутренняя ошибка сервера</div>
<?}?>

<?$sql = 'SELECT * FROM intentions';
$intention = q($sql);
if (!empty($intention)) 
{
	$arIntentions = array();
	while ($iIntentions = $intention->fetch_assoc()) 
	{
		array_push($arIntentions, $iIntentions);
	}?>
	<p>Таблица намерений к банкнотам</p>
	<div class="desktop">
		<table>
			<tr>
				<td>Объект</td>
				<td>Публичный ключ</td>
				<td>Намерение</td>
			</tr>
			<?if (!empty($arIntentions)) 
			{
				foreach ($arIntentions as $iIntentions) 
				{?>
					<tr>
						<td><?=$iIntentions['goal'];?></td>
						<td><?=$iIntentions['pubkey'];?></td>
						<td><?=$iIntentions['intention'];?></td>
					</tr>
				<?}
			}
			else 
			{?>
				<tr><td colspan="3">Намерения не опубликованы</td></tr>
				<?if (DEMO) {?>
					<tr><td colspan="3" class="left-text word-text"><?=$demo_text;?></td></tr>
				<?}?>
			<?}?>
		</table>
	</div>
	<div class="mobile">
		<?if (!empty($arIntentions)) 
		{
			foreach ($arIntentions as $iIntentions) 
			{?>
				<div class="mobile-table-row">
					<div class="mobile-table-item">Объект: <?=$iIntentions['goal'];?></div>
					<div class="mobile-table-item">Публичный ключ: <?=$iIntentions['pubkey'];?></div>
					<div class="mobile-table-item">Намерение: <?=$iIntentions['intention'];?></div>
				</div>
			<?}
		}
		else 
		{?>
			<div class="mobile-table-row">Намерения не опубликованы</div>
			<?if (DEMO) {?>
				<div class="mobile-table-row"><?=$demo_text;?></div>
			<?}?>
		<?}?>
	</div>
<?}
else 
{?>
	<div>Произошла внутренняя ошибка сервера</div>
<?}?>
