<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта

// Расчет значения эмиссии
function issue_value ($block_id) 
{
	$result = ISSUE_MULTIPLIER*(REDUCTION**$block_id)+ISSUE_CONST;
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
		'denomination' => to_cent($issue['parameters']['3']), // номинал эмитируемой банкноты
		'timestamp' => $head['parameters']['t'], // время создания блока
	);
	$output = (new cEncrypt($example))->sign;
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
		$arItem['key'] = substr($item, 1, strpos($item, ':')-1);
		$line = substr($item, strpos($item, ':')+1, strlen($item));
		$arItem['parameters'] = json_decode($line, true);
		array_push($arResult, $arItem);
	}
	if ((is_array($arResult))&&(count($arResult)>=1)) 
	{
		if ($symbol=='*') 
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
function block_section_encode ($symbol, $input) 
{
	$output = '';
	if ((is_string($symbol))&&(is_array($input))) 
	{
		if (!empty($input['key'])) 
		{
			$output .= $symbol.$input['key'].':';
			if (!empty($input['parameters'])) $output .= json_encode($input['parameters']);
		}
		else 
		{
			foreach ($input as $input_row)
			{
				if (!empty($input_row['key'])) 
				{
					$output .= $symbol.$input_row['key'].':';
					if (!empty($input_row['parameters'])) $output .= json_encode($input_row['parameters']);
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
	if ($block_id<720)                            $multiplier = 1;                     // первые 10 дней
	if (($block_id>=720)&&($block_id<4320))       $multiplier = 10;                    // первые от 10 дня до 2 месяцев
	if (($block_id>=4320)&&($block_id<13176))     $multiplier = 30;                    // со 2-го месяца до 6-го
	if (($block_id>=13176)&&($block_id<26280))    $multiplier = 100;                   // с 6-го месяца до конца первого года
	if (($block_id>=26280)&&($block_id<100000))   $multiplier = 1000;                  // период становления и популяризации проекта (около 3-4 лет)
	if (($block_id>=100000)&&($block_id<300000))  $multiplier = round($block_id/100);  // дальнейший плавный рост пропускной способности
	$caliber = array
	(
		3,                      // число транзакций (штук)
		5,                      // число намерений (штук)
		50,                     // соотношение намерений и транзакций round(min(i,t)/(i+t)*200)
		6000,                   // сила транзакций (в номиналах банкнот)
		10000,                  // сила намерений (в номиналах банкнот)
		50,                     // соотношение сил намерений и транзакций round(min(i,t)/(i+t)*200)
		3000,                   // длина содержимого блока (символов)
		128,                    // хэш блока (строка hex)
		1000,                   // алгоритм хэширования блока (строка, оценивается pubkey)
		issue_value($block_id), // номинал эмитируемой банкноты
	);
	// Баллы за количество транзакций и намерений
	$output[0] = ($input[0]-($caliber[0]*$multiplier))/($caliber[0]*$multiplier);
	$output[1] = ($input[1]-($caliber[1]*$multiplier))/($caliber[1]*$multiplier);
	$output[2] = $input[2]/$caliber[2];
	// Баллы за силу транзакций и намерений
	$output[3] = ($input[3]-($caliber[3]*(log($multiplier)+1)))/($caliber[3]*(log($multiplier)+1));
	$output[4] = ($input[4]-($caliber[4]*(log($multiplier)+1)))/($caliber[4]*(log($multiplier)+1));
	$output[5] = $input[5]/$caliber[5];
	// Баллы за минимизацию длины блока
	$output[6] = (($caliber[6]*$multiplier)-$input[6])/($caliber[6]*$multiplier);
	// Перевод хэша в число от 0 до 256, сравнение с калибратором
	$hash = str_split($input[7], 2);
	$hash_calc = 0;
	foreach ($hash as $snippet) $hash_calc ^= intval($snippet,16);
	$output[7] = $hash_calc/$caliber[7];
	// Получение баллов для алгоритма шифрования
	$output[8] = (new cEncrypt(['algorithm'=>$input[8]]))->score;
	$output[8] = ($output[8]-$caliber[8])/$caliber[8];
	// Расчет баллов за работу без комиссионных
	$output[9] = (($caliber[9]*(log($multiplier, 100)+1))-$input[9])/$caliber[9];
	$output = array_map('tally_indicators_arctg', $output);
	return $output;
}

//**********************************************
// Функции обработки массивов при чтении блоков.
//**********************************************
// Приведение баллов к арктангенциальной шкале
function tally_indicators_arctg ($item)
{
	$result = 1+(atan($item)/M_PI)*2;
	return $result;
}

// Расчет разницы показателей блоков
function quests_difference ($item_this, $item_prev) 
{
	return $item_this - $item_prev;
}

// Расчет задания текущего блока
function quests_calculate ($score, $quest) 
{
	$result = $quest;
	if (($score>0)&&($quest>MIN_QUEST)) $result--;
	if (($score<0)&&($quest<MAX_QUEST)) $result++;
	return $result;
}

// Сортировка рассчитываемого задания по разнице показателей
function quests_sort($item_a, $item_b)
{
	$result = 0;
	if ($item_a['difference']!=$item_b['difference']) $result = ($item_a['difference']>$item_b['difference']) ? +1 : -1;
	if ($item_a['quest']!=$item_b['quest']) $result = ($item_a['quest']>$item_b['quest']) ? +1 : -1;
	return $result;
}
?>