$(document).ready(function() 
{
	// Interface features
	$('a[href^="#"], a[href^="."]').click(function() 
	{
		let goal = $(this).attr('href');
		if ($(goal).length != 0) $('html, body').animate({scrollTop: $(goal).offset().top-50}, 500);
		return false;
	});
	$('.message-selector').on('change', function() {console_rewrite($(this).find(':checked').val());});

	// Call program installation form
	$(document).on('click', '.form-install-link', function() {form_open(); form_fill('install', '');});
	$(document).on('click', '.action-install, .action-uninstall', function() {install($(this));});

	// Edit constants
	$(document).on('click', '[doit="constant_edit"]', function() {constant_edit($(this));});

	// Call forms (bill-form, transactions-form)
	$(document).on('click', '[doit="info"]', function() {form_open(); form_fill('bill.info', $(this).closest('[bill_number]').attr('bill_number'))});
	$(document).on('click', '[doit="bco"]', function() {form_open(); form_fill('bill.bco', $(this).closest('[bill_number]').attr('bill_number'));});
	$(document).on('click', '[doit="bs"]', function() {form_open(); form_fill('bill.bs', $(this).closest('[bill_number]').attr('bill_number'));});
	$(document).on('click', '[doit="bu"]', function() {form_open(); form_fill('bill.bu', bill_input_list(this, 'get'));});
	$(document).on('click', '[doit="br"]', function() {form_open(); form_fill('bill.br', bill_input_list(this, 'get'));});

	// Close forms
	$('.form-bg').on('click', function() {if (!$(this).is('.inactive')) form_close();});
	$(document).on('click', '[doit="formclose"]', function() {form_close();});

	// Scroll forms
	$(document).on('mousewheel', '.form, .form-bg', function(e) 
	{
		$('.form').animate({scrollTop: $('.form').scrollTop()-e.originalEvent.wheelDelta}, 0);
		return false;
	});

	// Filling in forms
	$(document).on('keydown keypress keyup paste input', '.cent', function() {float_mask (this)});
	$(document).on('click', '[abra]', function() {$(this).closest('.form-field').find('input[type="text"]').val(abra($(this).attr('abra')));});
	$(document).on('click', '[doit="inputadd"]', function() {bill_input_list(this, 'add');});
	$(document).on('click', '[doit="inputdel"]', function() {bill_input_list(this, 'del');});
	$(document).on('click', '[doit="outputadd"]', function() {bill_output_list(this, 'add');});
	$(document).on('click', '[doit="outputdel"]', function() {bill_output_list(this, 'del');});

	// Transactions sending to actions
	$(document).on('click', '[doit="transaction"]', function() {transaction_create(this);});
	$(document).on('click', '[doit="connect"]', function() {transaction_send(this);});

	// Bills (upload/download/edit)
	$(document).on('click', '[doit="download"]', function() {bill_download($(this));});
	$(document).on('click', '[doit="billadd"]', function() {bill_add(this);});
	$(document).on('click', '[doit="billedit"]', function() {bill_edit(this);});

	// Mining
	$(document).on('click', '[doit="mining"]', function() {miner.execute_toggle();});
});