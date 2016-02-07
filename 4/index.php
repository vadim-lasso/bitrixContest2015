<?php
error_reporting(-1);

/* находит минимальное расстояние от точки до отрезка */
function distToSegment($firstPointSegment, $secondPointSegment, $point) 
{
	/* нахождение длины отрезка */
	$sizeSegment = distance($firstPointSegment, $secondPointSegment);
	/* нахождение проекции точки $point на отрезке по формуле t = [(p-v) . (w-v)] / |w-v|^2
	 * где t - $projection, p - $point, v - $firstPointSegment, w - $secondPointSegment */
	$projection =  (
					($point['X'] - $firstPointSegment['X']) * ($secondPointSegment['X'] - $firstPointSegment['X']) 
					 + 
					($point['Y'] - $firstPointSegment['Y']) * ($secondPointSegment['Y'] - $firstPointSegment['Y'])
					) / $sizeSegment;
	if ($projection < 0) // если проекция на точке $firstPointSegment
		$pointOnSegment = $firstPointSegment;
	elseif ($projection > 1) // если проекция на точке $secondPointSegment
		$pointOnSegment = $secondPointSegment;
	else // если проекция не на границах отрезка 
		$pointOnSegment = array(
			'X' => $firstPointSegment['X'] + $projection * ($secondPointSegment['X'] - $firstPointSegment['X']),
			'Y' => $firstPointSegment['Y'] + $projection * ($secondPointSegment['Y'] - $firstPointSegment['Y'])
		);
	/* массив с минимальным расстоянием, и расположением точки на отрезке */
	return array(
		'DISTANCE' => sqrt(distance($point, $pointOnSegment)),
		'POINT' => $pointOnSegment
	);
}

/* находит расстояние между двумя точками (в квадрате) */
function distance($firstPoint, $secondPoint)
{
	return pow($firstPoint['X'] - $secondPoint['X'], 2) + pow($firstPoint['Y'] - $secondPoint['Y'], 2);
}


/* файл с координатами треугольника (первые три строки), и координатами точки (последняя строка), в формате: X Y */
$fileIn = dirname(__FILE__) . '/in.txt';
/* файл с минимальным расстоянием */
$fileOut = dirname(__FILE__) . '/out.txt';

/* формирование массива со всеми координатами */
$position = array_map(function($string) {
	$coordinates = explode("\x20", $string);
	return array('X' => $coordinates[0], 'Y' => $coordinates[1]);
}, file($fileIn, FILE_IGNORE_NEW_LINES));

$point = array_pop($position); // координаты для точки
$triangle = $position; // координаты для треугольника

$minDistance = false;
$minPoint = false;
/* вычисляется минимальное расстояние от точки до каждого отрезка треугольника */
foreach ($triangle as $key => $trianglePoint) {
	/* формирование двух точек отрезка */
	$firstPointSegment = $trianglePoint;
	$secondPointSegment = (isset($triangle[($key + 1)])) ? $triangle[($key + 1)] : $triangle[0];
	/* вычисление минимального расстояния до отрезка */
	$result = distToSegment($firstPointSegment, $secondPointSegment, $point);
	/* нахождение минимального расстояния из трех минимальных расстояний до отрезка */
	if ($result['DISTANCE'] < $minDistance || $minDistance === false) {
		$minDistance = $result['DISTANCE']; // результат с минимальным расстоянием от точки до треугольника
		$minPoint = $result['POINT']; // координаты точки с минимальным расстоянием 
	}
}
file_put_contents($fileOut, $minDistance);
?>
<!DOCTYPE html>
<html lang="ru">
    <head><meta charset="utf-8"></head>
	<style type="text/css">span{color:green;font-weight:bold;} img{border:1px solid #DDD}</style>
	<body>
		Минимальное расстояние от точки до треугольника = <span><?=$minDistance?></span><br/>
		<? require_once __DIR__.DIRECTORY_SEPARATOR.'visualizator.php'; // визуализатор для более наглядного отображения результата ?>
		<img src="data:image/png;base64,<?=base64_encode(Visualizator::getImage($triangle, $point, $minPoint, 480))?>" alt="" />
	</body>
</html>






