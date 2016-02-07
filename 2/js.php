<?php
error_reporting(-1);

define('FILENAME_SEPARATOR', '@'); // разделитель для имени файла со статистикой показов, в виде: домен@ip_сервера
define('COLUMN_SEPARATOR', ';'); // разделитель столбцов с данными в файлах 

/**
 * Получает данные из файла и преобразует в массив
 */
function getContentsFileInArray($fileName) 
{
	$arrayOfStrings = file($fileName, FILE_IGNORE_NEW_LINES);
	$data = array();
	foreach ($arrayOfStrings as $string) {
		$row = explode(COLUMN_SEPARATOR, $string);
		$data[$row[0]] = $row;
	}
	return $data; // формат: array('ID баннера' => array('элементы из строки'))
}

/**
 * Получает доступные для показа баннеры
 */
function getAvailableBanners(
	$arBanners,   // данные о всех баннерах
	$arSiteStats  // статистика показов баннеров для одного сайта
) 
{
	foreach ($arBanners as $ID => $arBanner)
		if (isset($arSiteStats[$ID]) && $arSiteStats[$ID][1] == $arBanner[3])
			unset($arBanners[$ID]); // исключение баннера, если в статистике сайта у него закончились показы
	return $arBanners;
}

/**
 * Обновляет/создает статистику показов для сайта
 */
function updateSiteStats(
	$fileName,    // путь к файлу статистики сайта
	$arSiteStats, // статистика показов баннеров для одного сайта
	$arBanner     // данные о показанном баннере
) 
{
	/* если в статистике сайта уже есть информация о показах баннера */
	if (isset($arSiteStats[$arBanner[0]][1]))
		$arSiteStats[$arBanner[0]][1]++; // то увеличивается на единицу
	/* иначе в статистику добавляется баннер с одним показом */
	else
		$arSiteStats[$arBanner[0]] = array($arBanner[0], 1);
	/* преобразование массива в текстовый формат */
	$content = '';
	foreach ($arSiteStats as $arItem)
		$content .= implode(COLUMN_SEPARATOR, $arItem) . PHP_EOL;

	$fileLock = __DIR__ . DIRECTORY_SEPARATOR . basename($fileName) . '.lock';
	$fp = fopen($fileLock, "w");
	flock($fp, LOCK_EX); // блокировка обновления статистики, если от сайта одновнеменно поступило более одного запроса
	if (!file_put_contents($fileName, $content))
		return false;
	flock($fp, LOCK_UN);
	fclose($fp);
	unlink($fileLock);
	return true;
}

/**
  * Формирует HTML баннера
  */
function getBannerHTML($arBanner) 
{
	$dir = implode('/', array_slice(explode('/', $_SERVER['REQUEST_URI']), 0, -1));
	$image = 'http://'.$_SERVER['HTTP_HOST']. $dir . '/' . $arBanner[1]; // формирование полного url адреса к изображению
	$url = $arBanner[2]; // ссылка на сайт баннера
	return sprintf('<a href="%1$s" target="_blank"><img src="%2$s" alt="%1$s" width="200" /></a>', $url, $image);
}


$remoteIP = $_SERVER['REMOTE_ADDR'];
/* получение домена сайта, запросившего показ баннера */
$remoteDomain = isset($_SERVER['HTTP_REFERER']) ? str_ireplace('www.', '', parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST)) : die;

/* путь к директории со статистикой показов баннеров для каждого сайта */
$folder = __DIR__.DIRECTORY_SEPARATOR.'files';
if (!is_dir($folder)) mkdir($folder, 0755, true);

/* путь к файлу со статистикой показов баннеров для текущего сайта */
$fileSiteStats = $folder.DIRECTORY_SEPARATOR.$remoteDomain.FILENAME_SEPARATOR.$remoteIP;
/* путь к файлу с данными о всех баннерах */
$fileBanners = __DIR__.DIRECTORY_SEPARATOR.'banners.txt';

$arSiteStats = array();
$arBanners = file_exists($fileBanners) ? getContentsFileInArray($fileBanners) : false;
/* если существует файл статистики показов, то исключаются баннеры, которые уже были показаны нужное количество раз */
if (file_exists($fileSiteStats)) {
	$arSiteStats = getContentsFileInArray($fileSiteStats);
	if (!$arBanners = getAvailableBanners($arBanners, $arSiteStats))
		die;
}
$arBannerForShow = $arBanners[array_rand($arBanners)]; // получение случайного баннера
?>
<? if (updateSiteStats($fileSiteStats, $arSiteStats, $arBannerForShow)): // обновление/создание статистики для сайта ?>
	document.write('<?=getBannerHTML($arBannerForShow)?>');
<? endif; ?>