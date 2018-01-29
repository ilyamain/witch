<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Алгоритм шифрования с базовой защитой от создания радужных таблиц (ar - anti-rainbow)

switch ($caller) 
{
	case 'algorithm':		// Проверка существования алгоритма (если файл существует, то существует и алгоритм)
		$output = true;
		break;
	case 'allow_transfer':	// Разрешен перевод средств между банкнотами с данным алгоритмом шифрования 
		$output = true;		// (так как результат не зависит от номинала и временной метки)
		break;
	case 'name':			// Получение названия алгоритма
		$output = 'Anti-rainbow';
		break;
	case 'pubkey':			// Вычисление публичного ключа
		$output = updates_encrypt_algorithms_ar_pubkey($example);
		break;
	case 'pubkey_proper':	// Проверка соответствия публичного ключа
		$output = false;
		if (!empty($example['key'])) $output = ($example['pubkey'] == updates_encrypt_algorithms_ar_pubkey($example)) ? true : false;
		break;
	case 'sign':			// Вычисление подписи
		$pubkey = (empty($example['key'])) ? $example['pubkey'] : updates_encrypt_algorithms_ar_pubkey($example);
		$output = updates_encrypt_algorithms_ar_sign($pubkey, $example);
		break;
	case 'sign_proper':		// Проверка соответствия подписи
		$pubkey = (empty($example['key'])) ? $example['pubkey'] : updates_encrypt_algorithms_ar_pubkey($example);
		$sign = updates_encrypt_algorithms_ar_sign($pubkey, $example);
		$output = ($example['sign'] == $sign) ? true : false;
		break;
	case 'score':			// Начисление баллов при хэшировании блоков
		$output = 50;
		break;
	default:
		$output = false;
}
return $output;
?>