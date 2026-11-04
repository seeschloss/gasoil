<?php

class Math {
	static function percentile($array, $percentile = 50) {
		if (count($array) < 4) {
			return array_sum($array) / count($array);
		}

		sort($array);

		$index = ($percentile/100) * count($array) - 1;

		if (floor($index) == $index) {
			$result = $array[$index];
		} else {
			$result = ($array[floor($index)] + $array[ceil($index)])/2;
		}

		return $result;
	}

	static function mean($array, $trim = 10) {
		if (count($array) < 4 or $trim < 1) {
			return array_sum($array) / count($array);
		}

		if ($trim > 50) {
			$trim = $trim - 50;
		}

		$percentile_min = Math::percentile($array, $trim);
		$percentile_max = Math::percentile($array, 100 - $trim);

		$trimmed_array = [];
		foreach ($array as $n) {
			if ($n >= $percentile_min and $n <= $percentile_max) {
				$trimmed_array[] = $n;
			}
		}

		$result = array_sum($trimmed_array) / count($trimmed_array);

		return $result;
	}

	static function odd($number) {
		return ($number%2) > 0;
	}
}
