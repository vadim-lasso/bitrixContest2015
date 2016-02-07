<?php
error_reporting(-1);

/* Ищет палиндром наибольшей длины,
 * возвращает строку палиндрома или false */
function searchPalindromeMaxLength($string) 
{
	/* сохранение текущей кодировки и установка UTF-8 */
	$tmpEncoding = mb_internal_encoding();
	mb_internal_encoding("UTF-8");
	$stringLength = mb_strlen($string);
	/* "массивы палиндромностей" */
	$arPalindromes[0] = array(); // четной длины
	$arPalindromes[1] = array(); // нечетной длины
	for ($type = 0; $type <= 1; $type++) { // две итерации для заполнения двух массивов "палиндромностей"
		/* для быстроты вычисления сохраняются границы ($left,$right) самого правого из найденных подпалиндрома,
		 * т.е. подпалиндрома с наибольшим значением right */
		$left = 0; $right = -1; 
		for ($position = 0; $position < $stringLength; $position++) {
			/* если $position не находится в пределах текущего подпалиндрома, 
			 * то значения $arPalindromes[$type][$position] последовательно увеличиваются, пока текущая строка является палиндромом,
			 * иначе вычисляется новая позиция внутри подпалиндрома ($left,$right), и в случае их симметрии, 
			 * палиндром вокруг [$left + $right - $position] фактически "копируется" в палиндром вокруг $position
			 */
			if ($position > $right)
				$palindromes = 1;
			else
				$palindromes = min($arPalindromes[$type][$left + $right - $position] - $type, $right - $position) + 1;
			while (
				$position + $palindromes - 1 < $stringLength
				&&
				$position - $palindromes + $type >= 0 
				&&
				mb_substr($string, $position + $palindromes - 1, 1) == mb_substr($string, $position - $palindromes + $type, 1)
			) {
				++$palindromes;
			}
			$arPalindromes[$type][$position] = --$palindromes;
			/* Обновление значений ($left,$right) после вычисления очередного значения $arPalindromes[$type][$position] */
			if ($position + $palindromes > $right) {
				$left = $position - $palindromes + $type;
				$right = $position + $palindromes - 1;
			}
		}
	}
	/* нахождение положения центра максимального палиндрома в строке */
	$centerMaxPalindromeEven = current(array_keys($arPalindromes[0], max($arPalindromes[0]))); // для четной длины
	$centerMaxPalindromeOdd = current(array_keys($arPalindromes[1], max($arPalindromes[1]))); // и нечетной 
	/* сравнение половины длин четного и нечетного палиндрома, получение их начала и длины в строке */
	if ($arPalindromes[0][$centerMaxPalindromeEven] >= $arPalindromes[1][$centerMaxPalindromeOdd]) {
		$start = $centerMaxPalindromeEven - $arPalindromes[0][$centerMaxPalindromeEven];
		$length = $arPalindromes[0][$centerMaxPalindromeEven] * 2;
	} else {
		$start = $centerMaxPalindromeOdd - $arPalindromes[1][$centerMaxPalindromeOdd] + 1;
		$length = $arPalindromes[1][$centerMaxPalindromeOdd] * 2 - 1;
	}
	$palindrome = mb_substr($string, $start, $length);
	if (mb_strlen($palindrome) < 2)
		$palindrome = false;
	mb_internal_encoding($tmpEncoding); // восстановление предыдущей кодировки
	return $palindrome;
}


$inFile = __DIR__.DIRECTORY_SEPARATOR.'in.txt';
$outFile = __DIR__.DIRECTORY_SEPARATOR.'out.txt';

$string = file_exists($inFile) ? file_get_contents($inFile) : '';

$start = microtime(true);
$palindrome = searchPalindromeMaxLength($string);
$time = sprintf("%.8F", microtime(true) - $start); // получение времени выполнения поиска

if ($palindrome)
	file_put_contents($outFile, $palindrome);
?>
<!DOCTYPE html>
<html lang="ru">
    <head><meta charset="utf-8"></head>
	<body>
		В тексте из файла <?=$inFile?>, <?if($palindrome):?>найден наибольший палиндром: <b><?=$palindrome?></b><?else:?>не найден ни один палиндром<?endif?><br/>
		за <?=$time?> ceк., со сложностью O(n)
	</body>
</html>