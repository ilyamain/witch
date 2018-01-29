<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Алгоритм шифрования с базовой защитой от создания радужных таблиц (ar - anti-rainbow)

// Вычисление публичного ключа
function updates_encrypt_algorithms_ar_pubkey ($input)
{
	return hash('sha256', $input['key'].$input['number'].$input['algorithm']);
}

// Вычисление подписи
function updates_encrypt_algorithms_ar_sign ($pubkey, $input)
{
	return hash('sha256', $pubkey.$input['number'].$input['algorithm']);
}
?>