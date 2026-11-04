<?php

class Theme {
	public $topbar = "";
	public $content = "";

	public $content_file = null;
	public $content_env = [];

	public $head = "";

	public $admin = false;

	function __construct() {
	}

	function title() {
		return $GLOBALS['config']['title'];
	}

	function topbar() {
		$topbar = <<<HTML
			<form method="POST" action="" id="lang">
				<button name="lang" value="nl">nl</button>
				<button name="lang" value="fr">fr</button>
				<button name="lang" value="en">en</button>
			</form>
			<h1><a href="{$GLOBALS['config']['base_path']}/">{$this->title()}</a></h1>
HTML;

		return $topbar;
	}

	function footer() {
		$footer = <<<HTML
			<a href="mailto:see@seos.fr">see@seos.fr</a> &mdash;
			<a href="https://ssz.fr">ssz.fr</a>
HTML;

		return $footer;
	}

	function css() {
		$html = "";

		return $html;
	}

	function js() {
		$html = "";

		return $html;
	}

	function html() {
		$html = <<<HTML
<!DOCTYPE html>
<html>
	<head>
		<title>{$this->title()}</title>
		<link rel="stylesheet" href="{$GLOBALS['config']['base_path']}/css/style.css" />
		<link rel="shortcut icon" type="image/jpeg" href="{$GLOBALS['config']['base_path']}/ginkgo.png?" />
		<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1, maximum-scale=1.0">
		{$this->head}
	</head>
	<body>
		<div id="topbar">{$this->topbar()}</div>
		<div id="middle">
			<div id="content-box">
				<div id="content">{$this->content_string()}</div>
				<div id="footer">{$this->footer()}</div>
			</div>
		</div>
	</body>
</html>
HTML;

		return $html;
	}

	function bare() {
		return $this->content_string();
	}


	function content_string() {
		if ($this->content_file and file_exists(__DIR__.'/../content/'.$this->content_file)) {
			ob_start();
			foreach ($this->content_env as $key => $value) {
				${$key} = $value;
			}
			require __DIR__.'/../content/'.$this->content_file;
			return ob_get_clean();
		} else {
			return $this->content;
		}
	}
}

