$(document).ready(function()
{
	$('a[href^="#"], a[href^="."]').click(function()
	{
		var myblock_element = $(this).attr('href');
		if ($(myblock_element).length != 0)
		{
			$('html, body').animate({ scrollTop: $(myblock_element).offset().top-50 }, 500);
		}
		return false;
	});
	
	$('#background-video').YTPlayer
	(
		{
			videoId: '-sF9Iu7glT4',
			containment: 'self',
			fitToBackground: false,
		}
	);
	$('.message-selector').on('change', function() {$.cookie('console', ($(this).find(':checked').val())); page_update();});
	$(document).on('click', '.issue-link', function() {form_open(); form_fill('issue');});
	$(document).on('click', '.bco-link', function() {form_open(); form_fill('bco');});
	$(document).on('click', '.bu-link', function() {form_open(); form_fill('bu');});
	$(document).on('click', '.bs-link', function() {form_open(); form_fill('bs');});
	$(document).on('click', '.br-link', function() {form_open(); form_fill('br');});
	$(document).on('click', '.form-install-link', function() {form_open(); form_fill('install');});
	$('.form-bg').on('click', function() {if (!$(this).is('.inactive')) form_close ();});
	$(document).on('mousewheel', '.form, .form-bg', function(e) 
	{
		$('.form').animate({scrollTop: $('.form').scrollTop()-e.originalEvent.wheelDelta}, 0);
		return false;
	});
	$(document).on('click', '.transaction-form .send-button a', function() {form_send($(this), 'transaction');});
	$(document).on('click', '.execute-button a', function() {form_send($(this), 'install');});
	$(document).on('click', '.module-button a', function() {form_send($(this), 'module');});

	$(document).on('change', '.form [name="number"]', function() {fill_passes($(this), 'oldpass'); fill_passes($(this), 'newpass');});
	$(document).on('change', '.form [name="oldnumber"]', function() {fill_passes($(this), 'oldpass');});
	$(document).on('change', '.form [name="oldnumber-01"]', function() {fill_passes($(this), 'oldpass-01');});
	$(document).on('change', '.form [name="oldnumber-02"]', function() {fill_passes($(this), 'oldpass-02');});

	$(document).on('keydown keypress keyup paste input', '.form [name="newnumber"]', function() {fill_passes($(this), 'newpass');});
	$(document).on('keydown keypress keyup paste input', '.form [name="newnumber-01"]', function() {fill_passes($(this), 'newpass-01');});
	$(document).on('keydown keypress keyup paste input', '.form [name="newnumber-02"]', function() {fill_passes($(this), 'newpass-02');});

	$(document).on('keydown keypress keyup paste input', '.form [name="fee"]', function () {float_mask (this)});
	$(document).on('keydown keypress keyup paste input', '.form [name="newdenom"]', function () {float_mask (this)});
	$(document).on('keydown keypress keyup paste input', '.form [name="newdenom-01"]', function () {float_mask (this)});
	$(document).on('keydown keypress keyup paste input', '.form [name="newdenom-02"]', function () {float_mask (this)});
});

function fill_passes (caller, goal_field)
{
	$(caller).closest('form').find('[name="'+goal_field+'"]').val($(caller).val());
}

function float_mask (caller)
{
	while (($(caller).val().split('.').length-1)>1)
	{
		$(caller).val($(caller).val().slice(0, -1));
		if (($(caller).val().split('.').length-1)>1)
		{
			continue;
		}
		else
		{
			return false;
		}

	}
	$(caller).val($(caller).val().replace(/[^0-9.]/g, ''));
	var int_num_allow = 12;
	var float_num_allow = 8;
	var iof = $(caller).val().indexOf('.');
	if (iof!=-1)
	{
		if ($(caller).val().substring(0, iof).length>int_num_allow)
		{
			$(caller).val('');
			$(caller).attr('placeholder', 'invalid number');
		}
		$(caller).val($(caller).val().substring(0, iof+float_num_allow+1));
	}
	else
	{
		$(caller).val($(caller).val().substring(0, int_num_allow));
	}
	return true;
}

function form_open ()
{
	$('.site').addClass('covered');
	$('.form-bg').fadeIn();
	$('.form').addClass('active');
}

function form_close ()
{
	$('.form').removeClass('active');
	$('.form-bg').fadeOut();
	$('.site').removeClass('covered');
	$('.form').html('');
	$('.form').addClass('loading');
	$('.form').removeAttr('formid');
}

function form_fill (formid, message)
{
	var file = '/ajax/form.'+formid+'.php';
	var page = window.location.pathname;
	var txt = message||'';
	if (!is_empty(formid))
	{
		form_open();
		$('.form').attr('formid', formid);
		$.ajaxSetup({cache: false});
		$.post(file, {message: txt}).done(function(data)
		{
			$('.form').html(data);
			$('.form').removeClass('loading');
		});
	}
	else
	{
		form_open();
		$('.form').attr('formid', 'message');
		$('.form').html(message);
		$('.form').prepend('<div class="reload-field"><a class="reload-button" href="/">Возврат на главную страницу</a></div>');
	}
}

function form_send (caller, send_type)
{
	var file = '/ajax/send.'+send_type+'.php';
	if (!is_empty(caller))
	{
		var requires = $(caller).closest('form').find('.requires input');
		var fields = $(caller).closest('form').find('input, select, textarea');
		var form_error = false;
		var params = {};
		var is_install = false;
		if (send_type == 'install') is_install = true;
// Проверка на ошибки
		$(caller).closest('form').find('.form-error').html('');
		$(caller).closest('form').find('.form-error').removeClass('active');
		$(caller).closest('form').find('.requires').removeClass('unfilled');
		$.each(requires, function()
		{
			if (is_empty($(this).val()))
			{
				$(this).closest('.requires').addClass('unfilled');
				form_error = true;
			}
		});
		if (form_error)
		{
			$(caller).closest('form').find('.form-error').html('Заполнены не все обязательные поля');
			$(caller).closest('form').find('.form-error').addClass('active');
		}
		else
		{
// Загрузка параметров
			$(fields).each(function()
			{
				if (((!$(this).is('[type="radio"]'))&&(!$(this).is('[type="checkbox"]')))||($(this).prop('checked')))
				{
					params[this.name] = $(this).val();
				}
			});
			$('.form').html('');
			$('.form').addClass('loading');
			$.ajaxSetup({cache: false});
			$.post(file, {p: params}).done(function(data)
			{
				$.post('/ajax/get.body.php').done(function(body)
				{
					$('#console').prepend(data);
					if (!is_install)
					{
						$('.page-body').html(body);
						form_close();
					}
					else
					{
						$('.form').removeClass('loading');
						$('.form-bg').addClass('inactive');
						form_fill (0, data);
					}
				});
			});
		}
	}
	else
	{
		$('.form').html('');
		$('.form').addClass('loading');
		$.ajaxSetup({cache: false});
		$.post(file).done(function(data)
		{
			$.post('/ajax/get.body.php').done(function(body)
			{
				$('#console').prepend(data);
				if (!is_install)
				{
					$('.page-body').html(body);
					form_close();
				}
				else 
				{
					$('.form').removeClass('loading');
					$('.form-bg').addClass('inactive');
					form_fill (0, data);
				}
			});
		});
	}
}

//*******************************
// Вспомогательные функции
//*******************************
// Обновление консоли и страницы
function page_update ()
{
	$.get('/ajax/get.console.php').done(function(console)
	{
		$('#console').prepend(console);
	});
	$.get('/ajax/get.body.php').done(function(body)
	{
		$('.page-body').html(body);
	});
	$.get('/ajax/get.menu.php').done(function(menu)
	{
		$('.menu').html(menu);
	});
}
// Проверка пустое ли значение
function is_empty (a)
{
	if ((a == undefined)||(a == '')||(a == null)||(a == 0))
	{
		return true;
	}
	else
	{
		return false;
	}
}