<?php

class Lang {
	public static $translated_arrays = [];
	public static $translated_strings = [];

	private static $initialized = false;
	public static $lang = "";

	public static function determine_lang() {
		$lang = "en";

		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) and strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'], 'nl') !== false) {
			$lang = "nl";
		} else if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) and strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'], 'fr') !== false) {
			$lang = "fr";
		}

		if (isset($_SESSION['lang'])) {
			$lang = $_SESSION['lang'];
		}

		if (isset($_REQUEST['lang'])) {
			$lang = $_REQUEST['lang'];
		}

		return $lang;
	}

	public static function lang_file($lang) {
		$lang_file = __DIR__.'/../lang/'.$lang.'.lang.php';
		if (file_exists($lang_file)) {
			$_SESSION['lang'] = $lang;
			return $lang_file;
		}

		return __DIR__.'/../lang/en.lang.php';
	}

	public static function switch($lang) {
		self::$lang = $lang;
		require self::lang_file(self::$lang);
		static::$translated_arrays = isset($___) ? $___ : [];
		static::$translated_strings = $__;
		static::$initialized = true;
	}

	public static function init() {
		if (!static::$initialized) {
			self::$lang = self::determine_lang();
			require self::lang_file(self::$lang);
			static::$translated_arrays = isset($___) ? $___ : [];
			static::$translated_strings = $__;
			static::$initialized = true;
		}
	}

	public static function translated_array() {
		static::init();

		$args = func_get_args();
		$string = array_shift($args);

		if (isset(static::$translated_arrays[$string])) {
			if (count($args)) {
				return static::$translated_arrays[$string][$args[0]];
			} else {
				return static::$translated_arrays[$string];
			}
		} else {
			return $string;
		}
	}

	public static function translated_string() {
		static::init();

		$args = func_get_args();
		$string = array_shift($args);

		if (isset(static::$translated_strings[$string])) {
			return call_user_func_array('sprintf', array_merge([static::$translated_strings[$string]], $args));
		} else {
			return call_user_func_array('sprintf', array_merge([$string], $args));
		}
	}
}

function _a() {
	return call_user_func_array('Lang::translated_array', func_get_args());
}

function __() {
	return call_user_func_array('Lang::translated_string', func_get_args());
}

function __js() {
	echo str_replace("'", "\\'", call_user_func_array('Lang::translated_string', func_get_args()));
}

$__ = '__';
