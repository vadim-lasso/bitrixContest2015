<?php

class Visualizator {
	
	/**
	 * Создает изображение треугольника и минимального расстояния
	 * @param array $triangle
	 * @param array $point
	 * @param array $minPoint
	 * @param int $size
	 * @return string изображение в бинарном виде
	 */
	public static function getImage($triangle, $point, $minPoint, $size) 
	{
		$coordinates = $triangle;
		$coordinates[] = $point;
		$coordinates[] = $minPoint;
		/* нахождение минимального и максимального значений координат */
		array_walk_recursive($coordinates, function(&$item) use (&$coordinates) {
			if (!isset($coordinates['MAX']) || $item > $coordinates['MAX'])
				$coordinates['MAX'] = &$item;
			if (!isset($coordinates['MIN']) || $item < $coordinates['MIN'])
				$coordinates['MIN'] = &$item;
		});
		$min = $coordinates['MIN'];
		unset($coordinates['MIN']);
		
		/* если есть трицательные координаты, сдвигаем значения до положительных */
		if ($min < 0) {
			array_walk_recursive($coordinates, function(&$item, $key) use ($min) {
				if ($key != 'MAX')
					$item += abs($min) + 1;
			});
		}
		
		$max = $coordinates['MAX'];
		/* преобразование значений координат в растровый вид */
		$ratio = ($size / $max);
		array_walk_recursive($coordinates, function(&$item, $key) use ($ratio, $size) {
			if ($key != 'MAX') {
				$itemRatio = $item * $ratio;
				$item = round($itemRatio);
			}
		});
		
		$cellSize = ($coordinates['MAX'] / $max); // размер одного деления на сетке
		unset($coordinates['MAX']);
		
		/* инверсия по Y */
		array_walk_recursive($coordinates, function(&$item, $key) use ($size, $cellSize) {
				if ($key == 'Y')
					$item = ($size - $item) + $cellSize;
		});
		
		/* отступ всех объектов на одно деление сетки, от границ изобажения */
		array_walk_recursive($coordinates, function(&$item) use ($cellSize) {
			$item -= $cellSize;
		});

		$img = @imagecreatetruecolor($size, $size)
			  or die('Невозможно инициализировать GD поток');
		$white = imagecolorallocate($img, 255, 255, 255);
		$black = imagecolorallocate($img, 0, 0, 0);
		$blackAlpha = imagecolorallocatealpha($img, 0, 0, 0, 120);
		$green = imagecolorallocate($img, 0, 200, 0);
		imagefill($img, 0, 0, $white);
		/* формирование сетки */
		$position = 0;
		for ($i = 0; $i < ($size/$cellSize); $i++) {
			imageline($img, 0, $position, $size, $position, $blackAlpha);
			imageline($img, $position, 0, $position, $size, $blackAlpha);
			$position += $cellSize;
		}
		/* система координат */
		imagestring($img, 4, $size*0.05, $size*0.82, 'Y', $black);
		imageline($img, $size*0.05, $size*0.85, $size*0.05, $size*0.95, $black);
		imagestring($img, 4, $size*0.16, $size*0.92, 'X', $black);
		imageline($img, $size*0.05, $size*0.95, $size*0.15, $size*0.95, $black);
		
		$minPoint = array_pop($coordinates);
		$point = array_pop($coordinates);
		$triangle = $coordinates;
		/* формирование треугольника */
		foreach ($triangle as $key => $trianglePoint) {
			$firstPointSegment = $trianglePoint;
			$secondPointSegment = (isset($triangle[($key + 1)])) ? $triangle[($key + 1)] : $triangle[0];
			imageline($img, $firstPointSegment['X'], $firstPointSegment['Y'], $secondPointSegment['X'], $secondPointSegment['Y'], $black);
		}
		/* формирование точки и минимального отрезка до треугольника */
		imagefilledellipse($img, $minPoint['X'], $minPoint['Y'], 6, 6, $green);
		imageline($img, $point['X'], $point['Y'], $minPoint['X'], $minPoint['Y'], $green);
		imagefilledellipse($img, $point['X'], $point['Y'], 6, 6, $black);

		//imagestring($img, 5, ($cellSize/2), ($cellSize/2), "min distance = {$minDistance}", $green);
		ob_start();
		imagepng($img);
		$image = ob_get_clean();
		imagedestroy($img);
		return $image;
    }
}
