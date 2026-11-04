<?php

class Format {
	static function number($number, $decimals = null) {
		return number_format(round($number, $decimals), $decimals);
	}
}
