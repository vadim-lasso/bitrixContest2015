<?php

class Archive 
{
	const LENGTH_INFO = 8; // длина строки содержащая размер о сериализованном массиве данных
	const SIZE_PIECES = 8192; // количество байт для размера одной части максимального файла (например по 8кб)
	const FILE_MODE = 0755; // режим доступа к распакованным файлам
	
	/**
	 * Получает информацию о всех папках и файлах в директории
	 * @param string $dir путь к директории
	 * @param string $relativeDir используется для формирования относительного пути к файлу/папке
	 * @return array структура директории
	 */
	private static function getDataDir($dir, $relativeDir = '') 
	{
		$result = array();
		$cdir = scandir($dir . $relativeDir);
		unset($cdir[array_search('.', $cdir)], $cdir[array_search('..', $cdir)]);
		if (!$cdir) // если папка пустая
			$result[] = array(
				'RELATIVE_PATH' => $relativeDir // сохраняется только путь
			);
		else
			foreach ($cdir as $key => $value) {
				$relativeName = $relativeDir . DIRECTORY_SEPARATOR . $value; 
				if (is_dir($dir . $relativeName)) {
					$dirResult = self::getDataDir($dir, $relativeName);
					$result = !$result ? $dirResult : array_merge($dirResult, $result);
				} else {
					/* для файла сохраняется путь + размер */
					$result[] = array(
						'RELATIVE_PATH' => $relativeName,
						'SIZE' => filesize($dir . $relativeName)
					);
				}
			}
		return $result;
	}
	
	/**
	 * Ищет максимальный размер файла и добавляет в массив элемент MAX_FILE с ключем максимальному файлу
	 * @param array &$arResult ссылка на структуру директории
	 */
	private static function searchMaxFile(&$arResult) 
	{
		array_walk($arResult, function($arItem, $ID) use (&$arResult) {
			if (isset($arItem['SIZE']))
				if (!isset($arResult['MAX_FILE']) || $arResult[$arResult['MAX_FILE']]['SIZE'] < $arItem['SIZE'])
					$arResult['MAX_FILE'] = $ID;
		});
	}

	/**
	 * Копирует содержимое файла по частям
	 * @param mixed $fileFrom путь к файлу или указатель на файл
	 * @param string $fileTo
	 * @param int $sizeCopy количество байт, которое необходимо скопировать с файла $fileFrom
	 */
	private static function copyPiecemeal(&$fileFrom, $fileTo, $sizeCopy = 0) 
	{
		if (!is_resource($fileFrom))
			$handleFrom = fopen($fileFrom, 'rb');
		else
			$handleFrom = &$fileFrom;
		$handleTo = fopen($fileTo, 'ab');
		while($sizeCopy > 0) { // пока не скопировано $sizeCopy байт
			$partSize = ($sizeCopy > self::SIZE_PIECES) ? self::SIZE_PIECES : $sizeCopy;
			$partFileFrom = fread($handleFrom, $partSize); // читает часть файла
			fwrite($handleTo, $partFileFrom); // и пишет в $fileTo
			$sizeCopy -= self::SIZE_PIECES;
		}
		fclose($handleTo);
		if (!is_resource($fileFrom))
			fclose($handleFrom);
	}

	/**
	 * Создает архив
	 * @param string $dirIn путь к директории для архивирования
	 * @param string $fileArchive путь к создаваемому архиву
	 * @return bool
	 */
	public static function pack($dirIn, $fileArchive)
	{
		if (!is_dir($dirIn))
			return false;
		if (!$arResult = self::getDataDir($dirIn))
			return false;
		self::searchMaxFile($arResult);
		$arResultSerialize = serialize($arResult);
		/* определение размера сериализованного массива, учитывая параметр mbstring.func_overload */
		$arResultSerializeSize = ini_get('mbstring.func_overload') ? mb_strlen($arResultSerialize, '8bit') : strlen($arResultSerialize);
		/* формируется строка, длиной LENGTH_INFO, содержащая размер сериализованного массива со структурой директории */
		$arResultSerializeSize = str_pad($arResultSerializeSize, self::LENGTH_INFO, "0", STR_PAD_LEFT);
		/* в начало архива пишется размер сериализованного массива и сам массив */
		if (!file_put_contents($fileArchive, $arResultSerializeSize . $arResultSerialize))
			return false;
		/* и последовательно записывается контент со всех файлов */
		foreach($arResult as $ID => $arItem) {
			if (isset($arItem['SIZE'])) {
				if ($ID == $arResult['MAX_FILE']) {
					/* если файл является самым большим, то пишется по частям, без полного сохранения в память */
					$fileName = $dirIn . $arItem['RELATIVE_PATH'];
					self::copyPiecemeal($fileName, $fileArchive, $arItem['SIZE']);
				} else {
					$fileContent = file_get_contents($dirIn . $arItem['RELATIVE_PATH']);
					file_put_contents($fileArchive, $fileContent, FILE_APPEND);
				}
			}
		}
		return true;
	}

	/**
	 * Распаковывает архив
	 * @param string $dirIn путь к директории для разархивирования
	 * @param string $fileArchive путь к архиву
	 * @return bool
	 */
	public static function unpack($dirOut, $fileArchive)
	{
		if (file_exists($dirOut) && (!is_dir($dirOut)))
			return false;
		if (!is_dir($dirOut))
			mkdir($dirOut, FILE_MODE, true);
		$handle = fopen($fileArchive, 'rb');
		/* получение размера сериализованного массива со структурой директории */
		$arResultSerializeSize = fread($handle, self::LENGTH_INFO);
		/* и самого массива */
		$arResultSerialize = fread($handle, (int)$arResultSerializeSize);
		$arResult = unserialize($arResultSerialize);
		if (!$arResult) 
			return false;
		foreach($arResult as $ID => $arItem) {
			$parts = explode(DIRECTORY_SEPARATOR, $arItem['RELATIVE_PATH']);
			/* создание папок */
			if (isset($arItem['SIZE'])) 
				array_pop($parts); // если текущий элемент является файлом, то из пути удаляется его название
			if ($parts) {
				$folderName = $dirOut . implode(DIRECTORY_SEPARATOR, $parts);
				if (!file_exists($folderName) && !is_dir($folderName))
					mkdir($folderName, FILE_MODE, true);
			}
			/* создание файла */
			if (isset($arItem['SIZE'])) {
				if ($ID == $arResult['MAX_FILE']) {
					self::copyPiecemeal($handle, $dirOut . $arItem['RELATIVE_PATH'], $arItem['SIZE']);
				} else {
					$fileContent = (($arItem['SIZE'] > 0)) ? fread($handle, $arItem['SIZE']) : '';
					file_put_contents($dirOut . $arItem['RELATIVE_PATH'], $fileContent);
				}
			}
		}
		fclose($handle);
		return true;
	}
}