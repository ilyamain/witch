<?if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
	<head>
		<meta charset="UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="shortcut icon" href="/favicon.ico" type="image/ico">
		<title>
			<?=PROGRAM_NAME;?>
		</title>
		<link href="/css/styles.css" type="text/css" rel="stylesheet">
		<link href="/css/responsive.css" type="text/css" rel="stylesheet">
		<script src="/js/jquery-3.1.1.min.js"></script>
		<script src="/js/jquery.cookie.js"></script>
		<script src="/js/jquery.youtubebackground.js"></script>
		<script src="/js/jquery.mousewheel.min.js"></script>
		<script src="/js/scripts.js"></script>
	</head>
	<body>
		<div id="background-video"></div>
		<div class="site">
			<header class="top">
				<nav class="menu">
					<?section($page->element['menu']);?>
				</nav>
			</header>
			<article class="wrapper">
				<nav class="left">
					<?section($page->element['left']);?>
				</nav>
				<article class="main">
					<aside class="page-body">
						<?section($page->element['body']);?>
					</aside>
					<aside class="console">
						<div class="console-header">Консоль <?=PROGRAM_NAME;?></div>
						<div id="console">
							<?=$console;?>
						</div>
					</aside>
				</article>
			</article>
			<footer id="footer">
				<?section($page->element['foot']);?>
			</footer>
		</div>
		<div class="form-bg"></div>
		<div class="form loading"></div>
	</body>
</html>