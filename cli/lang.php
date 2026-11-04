<?php

class Lang_CLI {
	function __construct() {
	}

	function lang($args) {
		if (!count($args)) {
			$args[0] = "--help";
		}

		switch ($args[0]) {
			case "--add":
				return $this->_lang_add($args[1], array_slice($args, 2))."\n";
				break;
			case "--delete":
				return $this->_lang_delete($args[1])."\n";
				break;
			case "--list-missing":
				return $this->_lang_list()."\n";
				break;
			case "--help":
			default:
				global $argv;
				return <<<EOT
Usage: {$argv[0]} --lang --add    <untranslated> [<language-spec>:<translation> ...]
       {$argv[0]} --lang --delete <untranslated>
       {$argv[0]} --lang --list-missing

    Options --add and --delete respectively add and delete translations,
	  while option --list-missing shows all entries needing a translation.

	<untranslated>      Untranslated string
	<language-spec>     Language identifier, for example:
	                      "fr" for all French variants
	                      "fr_CA" for Canadian French
	<translation>       Translation for the corresponding language

	  When adding a new string, if no translation is specified for a language
	  it will be set to "XXX".


	Example:
		{$argv[0]} --lang --add Partners fr:Partenaires en:Partners
		{$argv[0]} --lang --delete "Add partner"


EOT;
				break;
		}
	}

	function _lang_files() {
		return glob(__DIR__.'/../lang/*.lang.php');
	}

	function _lang_list() {
		$messages = array();

		foreach ($this->_lang_files() as $file) {
			$language_spec = basename($file, ".lang.php");

			include $file;

			foreach ($__ as $entry => $translation) {
				if (preg_match("/^X*$/", $translation)) {
					$messages[] = $language_spec.": ".$entry;
				}
			}
		}

		return implode($messages, "\n");
	}

	function _lang_add($string, $args) {
		$placeholder = "XXX";

		$translations = array();
		foreach ($args as $arg) {
			list($lang, $translation) = explode(":", $arg, 2);
			$translations[$lang] = $translation;
		}

		$messages = array();

		foreach ($this->_lang_files() as $file) {
			$language_spec = basename($file, ".lang.php");
			list($language,) = @explode("_", $language_spec);

			if (isset($translations[$language_spec])) {
				$messages[] = $this->_lang_add_to_file($file, $string, $translations[$language_spec]);
			} else if (isset($translations[$language])) {
				$messages[] = $this->_lang_add_to_file($file, $string, $translations[$language]);
			} else if ($language == "en") {
				$messages[] = $this->_lang_add_to_file($file, $string, $string);
			} else if ($translation = $this->_lang_ask_interactively("en", $language_spec, $string)) {
				$messages[] = $this->_lang_add_to_file($file, $string, $translation);
			} else {
				$messages[] = $this->_lang_add_to_file($file, $string, $placeholder)." (placeholder translation)";
			}
		}

		return implode($messages, "\n");
	}

	function _lang_delete($string) {
		$messages = array();

		foreach ($this->_lang_files() as $file) {
			$messages[] = $this->_lang_delete_from_file($file, $string);
		}

		return implode($messages, "\n");
	}

	function _lang_add_to_file($file, $key, $translation) {
		$new_line = "\$__['".str_replace("'", "\\'", $key)."'] = \"".str_replace("\"", "\\\"", $translation)."\";\n";
		$lines = file($file);
		foreach ($lines as $line_no => $line) {
			if (strpos($line, "\$__[") === 0 && strtolower($line) == strtolower($new_line)) {
				return "Translation already exists in file $file.";
			} else if (strpos($line, "\$__[") === 0 && strcmp(strtolower($line), strtolower($new_line)) > 0) {
				break;
			}
		}

		array_splice($lines, $line_no, 0, array($new_line));
		file_put_contents($file, $lines);

		return "Added string at line ".($line_no+1)." to file $file";
	}

	function _lang_delete_from_file($file, $key) {
		$line_start = "\$__['".str_replace("'", "\\'", $key)."'] = \"";
		$lines = file($file);
		foreach ($lines as $line_no => $line) {
			if (strpos($line, $line_start) === 0) {
				break;
			}
		}

		array_splice($lines, $line_no, 1);
		file_put_contents($file, $lines);

		return "Removed line ".($line_no+1)." from file $file";
	}

	function _lang_ask_interactively($from_lang, $to_lang, $string) {
		$translation = $this->_lang_translate($from_lang, $to_lang, $string);

		echo "The string `$string` was automatically translated to `$to_lang` as:\n $translation\nDoes this sound good? (Y/n) ";

		$stdin = fopen('php://stdin', 'r');
		$response = fgetc($stdin);
		if (strtolower($response) == 'n') {
			echo "Do you know a better translation?\n> ";
			$stdin = fopen('php://stdin', 'r');
			$user_translation = trim(fgets($stdin));

			if (trim($user_translation)) {
				$translation = $user_translation;
			} else {
				$translation = "XXX";
			}
		}

		return $translation;
	}

	function _lang_translate($from_lang, $to_lang, $string) {
		$url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=".$from_lang."&tl=".$to_lang."&dt=t&q=".urlencode($string);

		$opts = array(
			'http' => array(
				'method' => "GET",
				'header' => "User-Agent: Mozilla/5.0 (Linux) Chromium\r\n",
			)
		);

		$context = stream_context_create($opts);

		$result = file_get_contents($url, false, $context);

		$result = str_replace(',,', ',"",', $result);
		$result = str_replace(',,', ',"",', $result);

		$data = json_decode($result);

		$translated = "";
		if (isset($data[0][0][0])) {
			$translated = $data[0][0][0];
		}

		return $translated;
	}
}

array_shift($argv);
$lang = new Lang_CLI();
echo $lang->lang($argv);
