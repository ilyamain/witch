<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
	<head>
		<meta charset="UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="shortcut icon" href="/favicon.ico" type="image/ico">
		<title>
			<?=PROGRAM_NAME.' | Error';?>
		</title>
		<link href="/files/css/styles.css" type="text/css" rel="stylesheet">
	</head>
	<body>
		<div class="site">
			<article class="wrapper">
				<div class="console-header"><?=PROGRAM_NAME;?></div>
				<aside id="console" class="console">
					<?exit($console);?>
				</aside>
			</article>
		</div>
	</body>
</html>