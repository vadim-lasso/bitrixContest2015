<?php
error_reporting(-1);

// для демонстрации копирования максимального файла без полного занесения в память
ini_set('memory_limit', '1M');

require_once __DIR__.DIRECTORY_SEPARATOR.'archive.php';

$dirIn = __DIR__.DIRECTORY_SEPARATOR.'in';
$dirOut = __DIR__.DIRECTORY_SEPARATOR.'out';
$fileArchive = __DIR__.DIRECTORY_SEPARATOR.'archive';
?>
<!DOCTYPE html>
<html lang="ru">
    <head><meta charset="utf-8"></head>
	<body>
		<? if (Archive::pack($dirIn, $fileArchive)): ?>
			Создан архив <b><?=$fileArchive?><br/></b>
		<? endif ?>
		<? if (Archive::unpack($dirOut, $fileArchive)): ?>
			И успешно распакован в директорию <b><?=$dirOut?></b>
		<? endif ?>	
	</body>
</html>
