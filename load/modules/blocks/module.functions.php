<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта

// Обращение к обработчикам хранилищ блоков
function updates_blocks_stores ($store = BLOCK_MAIN_STORE, $action = '', $block_id = GENESIS_ID, $input = '')
{
	$output = '';
	$block_id = intval($block_id);
	$file['func'] = way(DR.DS.'updates'.DS.'blocks_stores'.DS.$store.'.func.php');
	$file['exec'] = way(DR.DS.'updates'.DS.'blocks_stores'.DS.$store.'.exec.php');
	if (is_file($file['func'])) require_once($file['func']);
	if (is_file($file['exec'])) $output = require($file['exec']);
	return $output;
}

// Расчет значения эмиссии
function issue_value ($block_id)
{
	$result = ISSUE_MULTIPLIER*(ISSUE_REDUCTION**$block_id)+ISSUE_CONST;
	return $result;
}

// Хэширование блока
function block_hash ($content, $head, $issue, $algorithm)
{
	$example = array
	(
		'number' => $head['parameters']['n'], // номер блока
		'key' => $content, // содержимое блока
		'algorithm' => $algorithm, // алгоритм шифрования блока
		'denomination' => to_cent($issue['parameters']['2']), // номинал эмитируемой банкноты
		'timestamp' => $head['parameters']['t'], // время создания блока
		'entity' => block_section_encode('*', $head),
	);
	$output = (new cEncrypt($example))->sign;
	return $output;
}

// Расчет показателей соотношения транзакций и намерений
function block_percent_indicators ($transactions, $intentions)
{
	$output = 1;
	if (!float_equals(($transactions+$intentions), 0)) 
	{
		$output = round((min($transactions, $intentions)/($transactions+$intentions))*200);
	}
	return $output;
}

// Считывание раздела из блока в строку либо в массив
function block_section ($arContent, $arExclude, $arInclude, $to_string = false)
{
	$arResult = array();
	foreach ($arContent as $iContent) 
	{
		$include_row = true;
		foreach ($arExclude as $iExclude) if (substr($iContent, 0, strlen($iExclude)) == $iExclude) $include_row = false;
		foreach ($arInclude as $iInclude) if (substr($iContent, 0, strlen($iInclude)) != $iInclude) $include_row = false;
		if ($include_row) array_push($arResult, $iContent);
	}
	if ($to_string) 
	{
		$result = '';
		foreach ($arResult as $iResult) $result .= $iResult;
		return $result;
	} 
	else 
	{
		return $arResult;
	}
}

// Декодирование раздела из блока в массив
function block_section_decode ($symbol, $input)
{
	$arResult = array();
	foreach ($input as $item) 
	{
		$arItem = array();
		$arItem['key'] = transaction_split(mb_substr($item, 1), true);
		$line = transaction_split(mb_substr($item, 1), false);
		$arItem['parameters'] = json_decode($line, true);
		array_push($arResult, $arItem);
	}
	if ((is_array($arResult))&&(count($arResult) >= 1)) 
	{
		if ($symbol == '*') 
		{
			ksort($arResult[0]['parameters']);
			$output = $arResult[0];
		}
		else 
		{
			$output = array();
			foreach ($arResult as $iResult) array_push($output, $iResult);
		}
		return $output;
	}
	else 
	{
		return false;
	}
}

// Кодирование раздела блока в строку JSON (или в набор строк)
function block_section_encode ($symbol, $input, $set_eol = false)
{
	$output = '';
	if ((is_string($symbol))&&(is_array($input))) 
	{
		if (!empty($input['key'])) 
		{
			$output .= $symbol.$input['key'].':';
			if (!empty($input['parameters'])) $output .= json_encode(array_map('to_string', $input['parameters']));
			if ($set_eol) $output .= PHP_EOL;
		}
		else 
		{
			foreach ($input as $input_row)
			{
				if (!empty($input_row['key'])) 
				{
					$output .= $symbol.$input_row['key'].':';
					$output .= json_encode(array_map('to_string', $input_row['parameters']));
					if ($set_eol) $output .= PHP_EOL;
				}
			}
		}
	}
	else 
	{
		$output = false;
	}
	return $output;
}

// Расчет баллов блока
function tally_indicators ($input, $block_id)
{
	$output = array();
	$multiplier = 1;
	if ($block_id < 1440)                            $multiplier = 1;                     // первые 10 дней
	if (($block_id >= 1440)&&($block_id < 8640))     $multiplier = 10;                    // первые от 10 дня до 2 месяцев
	if (($block_id >= 8640)&&($block_id < 26352))    $multiplier = 30;                    // со 2-го месяца до 6-го
	if (($block_id >= 26352)&&($block_id < 52560))   $multiplier = 100;                   // с 6-го месяца до конца первого года
	if (($block_id >= 52560)&&($block_id < 200000))  $multiplier = 1000;                  // период становления и популяризации проекта (около 3-4 лет)
	if (($block_id >= 200000)&&($block_id < 600000)) $multiplier = round($block_id/200);  // дальнейший плавный рост пропускной способности
	$caliber = array
	(
		1,     // число транзакций (штук)
		2,     // число намерений (штук)
		50,    // соотношение намерений и транзакций round(min(i,t)/(i+t)*200)
		1500,  // сила транзакций (в номиналах банкнот)
		2500,  // сила намерений (в номиналах банкнот)
		50,    // соотношение сил намерений и транзакций round(min(i,t)/(i+t)*200)
		10000, // длина содержимого блока (символов)
		128,   // хэш блока (строка hex)
		2000,  // алгоритм хэширования блока (строка, оценивается pubkey)
		1500,  // количество участвующих входных и выходных банкнот
	);
	// Баллы за количество транзакций и намерений
	$output[0] = ($input[0]-($caliber[0]*$multiplier))/($caliber[0]*$multiplier);
	$output[1] = ($input[1]-($caliber[1]*$multiplier))/($caliber[1]*$multiplier);
	$output[2] = ($input[2]-$caliber[2])/max($input[2], $caliber[2]);
	// Баллы за силу транзакций и намерений
	$output[3] = ($input[3]-($caliber[3]*(log($multiplier)+1)))/($caliber[3]*(log($multiplier)+1));
	$output[4] = ($input[4]-($caliber[4]*(log($multiplier)+1)))/($caliber[4]*(log($multiplier)+1));
	$output[5] = ($input[5]-$caliber[5])/max($input[5], $caliber[5]);
	// Выравнивающие баллы за минимизацию длины блока
	$output[6] = (($caliber[6]*$multiplier)-$input[6])/($caliber[6]*$multiplier);
	// Перевод хэша в число от 0 до 256, сравнение с калибратором
	$hash = str_split($input[7], 2);
	$hash_calc = 0;
	foreach ($hash as $snippet) $hash_calc ^= intval($snippet, 16);
	$output[7] = $hash_calc/$caliber[7];
	// Получение баллов для алгоритма шифрования
	$encrypt_score = (new cEncrypt(['algorithm'=>$input[8]]))->score;
	$output[8] = ($encrypt_score-$caliber[8])/min(max($encrypt_score, 1), $caliber[8]);
	// Выравнивающие баллы за количество банкнот в транзакциях и намерениях
	$output[9] = ($input[9]-($caliber[9]*$multiplier))/($caliber[9]*$multiplier);
	// Пересчет баллов в арктангенциальную шкалу
	$output = array_map('array_arctg', $output);
	return $output;
}

//**********************************************
// Функции обработки массивов при чтении блоков.
//**********************************************
// Расчет задания текущего блока
function quests_calculate ($score, $quest)
{
	$result = $quest;
	if (($score > 0)&&($quest > QUEST_MIN)) $result--;
	if (($score < 0)&&($quest < QUEST_MAX)) $result++;
	return $result;
}

// Сортировка рассчитываемого задания по разнице показателей
function quests_sort($item_a, $item_b)
{
	$result = 0;
	if ($item_a['difference'] != $item_b['difference']) $result = ($item_a['difference'] > $item_b['difference']) ? +1 : -1;
	if ($item_a['quest'] != $item_b['quest']) $result = ($item_a['quest'] > $item_b['quest']) ? +1 : -1;
	return $result;
}
?>