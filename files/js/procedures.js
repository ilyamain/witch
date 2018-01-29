var miner = 
{
	options: 
	{
		ajax_file: '/files/ajax/mining.php', 
		action: 'peers_clean', //peers_clean
	}, 
	handler: false, 
	active: false, 
	interval: 1000, 
	rest: false, 
	button_toggle: function (selector, caption, icon) 
	{
		$(selector).html(caption);
		$(selector).removeClass('icon-ok');
		$(selector).removeClass('icon-del');
		$(selector).addClass(icon);
	}, 
	execute_toggle: function () 
	{
		if (!miner.active) 
		{
			miner.handler = setInterval(miner.peers_update, miner.interval);
			miner.button_toggle('[doit="mining"]', 'Stop mining', 'icon-del');
			miner.active = true;
		}
		else 
		{
			clearInterval(miner.handler);
			miner.button_toggle('[doit="mining"]', 'Start mining', 'icon-ok');
			miner.active = false;
		}
	}, 
	peers_update: function () 
	{
		let current_action = miner.options.action;
		if (!miner.rest) 
		{
			miner.rest = true;
			$.ajaxSetup({cache: false});
			$.post(miner.options.ajax_file, {action: miner.options.action}).done(function(data) 
			{
				if (current_action == 'peers_clean') miner.options.action = 'peers_update';
				if (current_action == 'peers_update') miner.options.action = 'mining_start';
				if (current_action == 'mining_start') //mining_start
				{
					let calc = new Object();
					let issue = new Object();
					let content = new Object();
					let block = $.parseJSON(data);
					miner.interval = 5000; // mining interval change
					content.id = block.id;
					calc.breaker = false;
					calc.score = 0;
					calc.ease = block.ease;
					calc.test_count = 0;
					calc.test_iterations = 1000000000/block.text.length;
					while ((calc.ease >= block.ease)||(calc.score < block.score)) 
					{
						// Calculate issue bill parameters
						issue.number = block.bill;
						issue.key = abra(64);
						issue.sign = encrypt_ar(issue.key, block.bill, true);
						issue.denomination = block.sum;
						issue.section = '*i:["'+block.bill+'","'+issue.sign+'","'+block.sum+'"]';
						// Calculate result content
						content.txt = block.text + issue.section;
						content.string = content.txt.replace(/\r?\n/g, "");
						content.hash = encrypt_ar(content.string, block.id, true);
						calc.ease = hash_difference(issue.sign, content.hash);
						calc.score = tally_hash(content.hash);
						// Stop mining and try again
						calc.test_count++;
						if (calc.test_count > calc.test_iterations) 
						{
							write('Answers tested: ' + calc.test_count, true);
							calc.breaker = true;
							break;
						}
					};
					if (!calc.breaker) 
					{
						write(hhmmss() + '. Block: ' + block.id + ' The correct answer was found. Net ease: ' + block.ease + '. Block ease: ' + calc.ease + '.', true, 'success');
						$.post(miner.options.ajax_file, {action: 'block_create', content: content, issue: issue}).done(function (messages) 
						{
							miner.rest = false;
							write(messages);
						});
					}
					else 
					{
						miner.rest = false;
						write(hhmmss() + '. Block: ' + block.id + ' The correct answer was not found. Net ease: ' + block.ease + '. Selected score: ' + block.score + '.', true);
					}
				}
				else 
				{
					miner.rest = false;
					write(data);
				}
			});
		}
	}, 
};

// Download this bill on your computer
function bill_download (caller) 
{
	let number = $(caller).closest('[bill_number]').attr('bill_number');
	let pass = $(caller).closest('[bill_number]').find('.bill-key .bill-attr-value').first().html();
	window.location.href = '/files/ajax/wallet/bill.download.php?n='+number+'&k='+pass;
}

// Get, add, del output bills in list for transactions: "bill_split" and "bill_resort"
function bill_output_list (caller, action) 
{
	if (action == 'add') 
	{
		let ajax_file = '/files/ajax/wallet/bill.output.php';
		let new_row = $('<div class="form-row form-row-underline bill-output-item">');
		$.post(ajax_file).done(function(data) {$(new_row).append(data);});
		$(caller).closest('.form-row').before(new_row);
	}
	if (action == 'del') $(caller).closest('.bill-output-item').remove();
}

// Get, add, del input bills in list for transactions: "bill_unite" and "bill_resort"
function bill_input_list (caller, action) 
{
	let wallet = $(caller).closest('#wallet');
	let list = 
	{
		input: $('.bill-input-list .bill-input-item'),
		bills: new Array(),
	};
	if (action != 'get') 
	{
		let output = $('<div>');
		let example = $(caller).closest('[bill_number]');
		let bill = 
		{
			number: $(example).find('.bill-number .bill-attr-value').first().html(),
			key: $(example).find('.bill-key .bill-attr-value').first().html(),
		};
		if (action == 'add') $(example).addClass('input-added');
		if (action == 'del') $(example).removeClass('input-added');
		$(list.input).each(function() 
		{
			let input_item = new Object();
			input_item.number = $(this).find('.bill-number .bill-attr-value').html();
			input_item.key = $(this).find('.bill-key .bill-attr-value').html();
			if (input_item.number != bill.number) list.bills.push(input_item);
		});
		if (action == 'add') list.bills.push(bill);
		$(list.bills).each(function() 
		{
			let item = $('<div>');
			item.addClass('bill-input-item');
			$(item).append('<div class="bill-number"><span class="bill-attr-value">'+this.number+'</span></div>');
			$(item).append('<div class="bill-key"><span class="bill-attr-value">'+this.key+'</span></div>');
			$(output).append(item);
		});
		if (list.bills.length > 0) $(wallet).addClass('show-input'); else $(wallet).removeClass('show-input');
		$(wallet).find('.bill-input-list').html(output);
	}
	else 
	{
		$(list.input).each(function() 
		{
			let input_item = new Object();
			input_item.number = $(this).find('.bill-number .bill-attr-value').html();
			input_item.key = $(this).find('.bill-key .bill-attr-value').html();
			list.bills.push(input_item);
		});
		return JSON.stringify(list.bills);
	}
}

// Upload your bills from your computer or input bill accesses manually
function bill_add (caller) 
{
	let ajax_file = '/files/ajax/wallet/bill.add.php';
	let file_field = $(caller).closest('form').find('input[type="file"]').first();
	file_field.click();
	if (!is_empty(file_field)) 
	{
		$(file_field).off('change').on('change', function() 
		{
			let bills = new FormData();
			let num_files = 0;
			for (let i = 0; i <= this.files.length; i++) num_files = i;
			caption = (num_files > 0) ? 'Bills uploaded: '+num_files : 'Upload bills';
			$(caller).html(caption);
			$.each(this.files, function(key,value) {bills.append(key,value);});
			$.ajax( 
			{
				url: ajax_file,
				type: 'POST',
				data: bills,
				cache: false,
				dataType: 'html',
				processData: false,
				contentType: false,
				success: function(data) 
				{
					form_open();
					form_fill('message', data);
					page_update();
				},
				error: function(data) 
				{
					form_open();
					form_fill('message', data);
					page_update();
				},
			});
		});
	}
	else 
	{
		let bills = {};
		let fields = $(caller).closest('form').find('input[type="text"], select');
		$(fields).each(function() {bills[this.name] = $(this).val();});
		$.ajaxSetup({cache: false});
		$.post(ajax_file, {message: bills}).done(function(data) 
		{
			form_open();
			form_fill('message', data);
			page_update();
		});
	}
}

// Edit bill in wallet
function bill_edit (caller) 
{
	let bill = {};
	let ajax_file = '/files/ajax/wallet/bill.edit.php';
	bill.number = $(caller).closest('[bill_number]').attr('bill_number');
	bill.key = $(caller).closest('.bill-actions').find('[name="key"]').first().val();
	$('.form').html('');
	$('.form').addClass('loading');
	$.ajaxSetup({cache: false});
	$.post(ajax_file, {bill: bill}).done(function(data) 
	{
		form_fill('message', data);
		page_update();
	});
}

// Fill AJAX-forms
function form_fill (formid, message) 
{
	let ajax_file = '/files/ajax/forms/'+formid+'.php';
	let txt = message||'';
	if (!is_empty(formid)) 
	{
		form_open();
		$('.form').attr('formid', formid);
		$.ajaxSetup({cache: false});
		$.post(ajax_file, {message: txt}).done(function(data) 
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
		$('.form').prepend('<div class="form-row form-row-center"><a href="/" class="button icon-return">Перейти на главную странцу</a></div>');
	}
}

// Create transaction in form and send to server
function transaction_create (caller) 
{
	if (!is_empty(caller)) 
	{
		let ajax_file = '/files/ajax/send.transaction_create.php';
		let call_form = $(caller).closest('#transaction-form');
		let options = 
		{
			type: $(call_form).find('.command-field input').first().val(),
			fee: $(call_form).find('.fee-field input').first().val(),
			old_bills: new Array(),
			new_bills: new Array(),
		};
		if (form_ok(caller)) // Error test
		{
			// Read input bills
			$(call_form).find('.bill-input-item').each(function() 
			{
				let old_bill = new Object();
				old_bill.number = $(this).find('.bill-number .bill-attr-value').first().html();
				old_bill.key = $(this).find('.bill-key .bill-attr-value').first().html();
				options.old_bills.push(old_bill);
			});
			// Read output bills
			$(call_form).find('.bill-output-item').each(function() 
			{
				let new_bill = new Object();
				$(this).find('input').each(function() {new_bill[this.name] = $(this).val();});
				options.new_bills.push(new_bill);
			});
			$('.form').html('');
			$('.form').addClass('loading');
			$.ajaxSetup({cache: false});
			$.post(ajax_file, {options: options}).done(function(data) 
			{
				$('.form').removeClass('loading');
				$.post('/files/ajax/get.body.php').done(function(body) 
				{
					$('#console').prepend(data);
					$('.page-body').html(body);
					form_close();
				});
			});
		}
	}
}

// Send transaction to network
function transaction_send (caller) 
{
	if (!is_empty(caller)) 
	{
		let ajax_file = '/files/ajax/send.transaction_send.php';
		let call_bill = $(caller).closest('.wallet-bill-item');
		let bill = new Object();
		if (!is_empty(call_bill)) 
		{
			bill.number = $(call_bill).attr('bill_number');
			if ($(call_bill).is('.bill-busy-1')) bill.type = 'transaction';
			if ($(call_bill).is('.bill-busy-2')) bill.type = 'intention';
			$.ajaxSetup({cache: false});
			$.post(ajax_file, {bill: bill}).done(function(data) {$('#console').prepend(data);});
		}
	}
}

// Send AJAX-form for installation
function install (caller) 
{
	if (!is_empty(caller)) 
	{
		let ajax_file = '/files/ajax/send.install.php';
		let call_form = $(caller).closest('.install-form');
		let fields = $(caller).closest('form').find('input, select, textarea');
		let module_row = $(caller).closest('.module-row');
		let options = new Object();
		if (form_ok(caller)) // Error test
		{
			$(fields).each(function() 
			{
				if (((!$(this).is('[type="radio"]'))&&(!$(this).is('[type="checkbox"]')))||($(this).prop('checked'))) 
				{
					options[this.name] = $(this).val();
				}
			});
			
			if (!is_empty(module_row)) options['module'] = $(module_row).attr('module');
			if (is_empty(options.form)) 
			{
				if ($(caller).is('.action-install')) options['form'] = 'install'; else options['form'] = 'uninstall';
			}
			$('.form').html('');
			$('.form').addClass('loading');
			$.ajaxSetup({cache: false});
			$.post(ajax_file, {options: options}).done(function(data) 
			{
				$.post('/files/ajax/get.body.php').done(function(body) 
				{
					$('#console').prepend(data);
					$('.form').removeClass('loading');
					$('.form-bg').addClass('inactive');
					if (is_empty(options.module)) form_fill(0, data); else page_update();
				});
			});
		}
	}
}

// Edit constants
function constant_edit (caller) 
{
	let constant = {};
	let ajax_file = '/files/ajax/constants.php';
	let input_field = $(caller).closest('.constant-field').find('[constant]').first();
	constant.parameter = $(input_field).attr('constant');
	constant.value = $(input_field).val();
	$.ajaxSetup({cache: false});
	$.post(ajax_file, {constant: constant});
}

//********************************
//***** Additional functions *****
//********************************
// Find errors in form fields
function form_ok (caller) 
{
	// Form fields testing
	let ok = true;
	let requires = $(caller).closest('form').find('.requires input');
	$.each(requires, function() 
	{
		$(this).closest('.requires').removeClass('unfilled');
		if (is_empty($(this).val())) 
		{
			$(this).closest('.requires').addClass('unfilled');
			ok = false;
		}
	});
	// Update form fields if error exist
	let error_line = $(caller).closest('form').find('.form-error');
	$(error_line).html((ok) ? '' : 'Please complete the highlighted fields');
	if (ok) $(error_line).removeClass('active'); else $(error_line).addClass('active');
	return ok;
}

// Change the level of displayed messages
function write (txt, clean = false, type = 'ok') 
{
	let line_class = '';
	if (type == 'error') line_class = ' error-line';
	if (type == 'attract') line_class = ' attract-line';
	if (type == 'success') line_class = ' success-line';
	if (type == 'array') line_class = ' array-line';
	let line = '<div class="console-line' + line_class + '" level="5">' + txt + '</div>';
	if (clean) $('#console').prepend(line); else $('#console').prepend(txt);
}

// Time format
function hhmmss () 
{
	let time = new Date();
	let output = ('0'+time.getHours()).slice(-2)+':'+('0'+time.getMinutes()).slice(-2)+':'+('0'+time.getSeconds()).slice(-2);
	return output;
}

// Change the level of displayed messages
function console_rewrite (level) 
{
	$.cookie('console', level, {path: '/'});
	$('.console-line').each(function() 
	{
		if (+$(this).attr('level') < +level) $(this).addClass('invisible-line'); else $(this).removeClass('invisible-line');
	});
}

// Update page element
function page_update () 
{
	$.get('/files/ajax/get.console.php').done(function(console) {$('#console').prepend(console);});
	$.get('/files/ajax/get.body.php').done(function(body) {$('.page-body').html(body);});
	$.get('/files/ajax/get.menu.php').done(function(menu) {$('.menu').html(menu);});
}

// Password generate
function abra (result_length = 64) 
{
	let alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	let result = '';
	if (is_empty(result_length)) result_length = 64;
	while (result.length < result_length) result += alphabet.charAt(Math.floor(Math.random() * alphabet.length));
	return result;
}

// Denomination input correction
function float_mask (caller) 
{
	while (($(caller).val().split('.').length-1) > 1) 
	{
		$(caller).val($(caller).val().slice(0, -1));
		if (($(caller).val().split('.').length-1) > 1) continue; else return false;
	}
	$(caller).val($(caller).val().replace(/[^0-9.]/g, '.'));
	let int_num_allow = 12;
	let float_num_allow = 8;
	let iof = $(caller).val().indexOf('.');
	if (iof != -1) 
	{
		if ($(caller).val().substring(0, iof).length > int_num_allow) 
		{
			$(caller).val('');
			$(caller).attr('placeholder', 'invalid number');
		}
		if (iof == 0) $(caller).val('0'+$(caller).val());
		$(caller).val($(caller).val().substring(0, iof+float_num_allow+1));
	}
	else 
	{
		$(caller).val($(caller).val().substring(0, int_num_allow));
	}
	return true;
}

// Test empty variables
function is_empty (a) 
{
	if ((a === undefined)||(a === '')||(a === null)||(a === 0)) return true;
	if ((typeof a === 'object')&&(a.length > 0)) return false;
	if ((typeof a === 'object')&&(a.length === 0)) return true;
	return false;
}

// Open any AJAX-forms
function form_open () 
{
	$('.site').addClass('covered');
	$('.form-bg').fadeIn();
	$('.form').addClass('active');
}

// Close opened AJAX-forms
function form_close () 
{
	$('.form').removeClass('active');
	$('.form').removeClass('inactive');
	$('.form-bg').fadeOut();
	$('.site').removeClass('covered');
	$('.form').html('');
	$('.form').addClass('loading');
	$('.form').removeAttr('formid');
}

// Anti-rainbow encryption
function encrypt_ar (key, name, return_sign = false) 
{
	let privkey = key+name+'ar';
	let pubkey = sha256_digest(privkey);
	return (return_sign) ? sha256_digest(pubkey+name+'ar') : pubkey;
}

// Calculate hash difference
function hash_difference (hash_a, hash_b) 
{
	let section = 4;
	let delimiter_1 = 0;
	let delimiter_2 = delimiter_1+4;
	let arr_a = new Array();
	let arr_b = new Array();
	while ((delimiter_2 <= hash_a.length)&&(delimiter_2 <= hash_b.length)) 
	{
		arr_a.push(hash_a.substring(delimiter_1, delimiter_2));
		arr_b.push(hash_b.substring(delimiter_1, delimiter_2));
		delimiter_1 += section;
		delimiter_2 += section;
	};
	let diff_ab = arr_a.map(function(item, k) {return Math.abs(parseInt(item, 16) - parseInt(arr_b[k], 16));});
	result = Math.max.apply(0, diff_ab);
	return result;
}

// Tally hash scores
function tally_hash (input) 
{
	let section = 2;
	let delimiter_1 = 0;
	let delimiter_2 = delimiter_1+2;
	let arr = new Array();
	while (delimiter_2 <= input.length) 
	{
		arr.push(input.substring(delimiter_1, delimiter_2));
		delimiter_1 += section;
		delimiter_2 += section;
	};
	result = 0;
	arr.map(function(item, k) {result ^= parseInt(item, 16);});
	return result;
}