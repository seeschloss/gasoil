<?php

class Router {
	public $site = null;
	public $theme = null;

	public $routes = [];

	public $json = false;
	public $bare = false;

	public function __construct($site, $theme) {
		$this->site = $site;
		$this->theme = $theme;

		$this->routes = [
			'/' => [$this, 'show_home'],
			'/plein' => [$this, 'show_plein'],
			'/(.+)' => [$this, 'show_home'],
		];

		if (isset($_SERVER['HTTP_ACCEPT']) and $_SERVER['HTTP_ACCEPT'] == 'application/json') {
			$this->json = true;
		}

		if (isset($_SERVER['HTTP_X_MODAL']) and $_SERVER['HTTP_X_MODAL'] == 'modal') {
			$this->bare = true;
		}
	}

	public function handle($uri, $get, $post) {
		$uri = str_replace($GLOBALS['config']['base_path'], '', $uri);
		$uri = str_replace("/?", "/", $uri);
		$uri = strtok($uri, '?');

		if (strpos($uri, '/plein') === 0) {
			$this->auth_admin($uri);
		} else if (strpos($uri, '/data') === 0) {
			$this->auth_api();
		}

		foreach ($this->routes as $pattern => $function) {
			if (preg_match('/^' . str_replace('/', '\/', $pattern) . '$/', $uri)) {
				return $function(explode('/', $uri), $get, $post);
			}
		}

		header("HTTP/1.0 404 Not Found");
	}

	function auth_admin($uri) {
		$htpasswd = __DIR__.'/../cfg/htpasswd';

		if (!isset($_SERVER['PHP_AUTH_USER'])) {
			header('WWW-Authenticate: Basic realm="'.utf8_decode($GLOBALS['config']['title']).'"');
			header('HTTP/1.0 401 Unauthorized');
			echo 'You need to be authentified to view this section';
			exit;
		} else if ($_SERVER['PHP_AUTH_USER'] == "logout") {
			if ($uri == "/admin/logout") {
				header('HTTP/1.0 401 Unauthorized');
				// At this point, we manually redirect the user to the home
				// taking care to strip off the possible "logout@" authentication
				// that some browsers silently carry on.
				echo <<<HTML
					<script>
						window.location = window.location.protocol + "//" + window.location.host + "/";
					</script>
					Logging out..
HTML;
			} else {
				header('HTTP/1.0 401 Unauthorized');
				header('WWW-Authenticate: Basic realm="'.utf8_decode($GLOBALS['config']['title']).'"');
			}
			die();
		} else {
			$submitted_user = $_SERVER['PHP_AUTH_USER'];
			$submitted_pass = $_SERVER['PHP_AUTH_PW'];

			$ok = false;

			foreach (file($htpasswd) as $line) {
				list($user, $pass) = explode(":", trim($line), 2);

				if ($user == $submitted_user) {
					if (Crypto::check_htpasswd_pass($user, $submitted_pass, $pass)) {
						$ok = true;
					}
				}
			}

			if (!$ok) {
				header('HTTP/1.0 401 Unauthorized');
				header('WWW-Authenticate: Basic realm="'.utf8_decode($GLOBALS['config']['title']).'"');
				echo "You are not authorized to access this page\n";
				die();
			}
		}
	}

	public function handle_admin_logout($parts, $get, $post) {
		$this->theme->content = '';

		header("Location: {$GLOBALS['config']['base_path']}/");

		return true;
	}

	public function show_home($parts, $get, $post) {
		$this->theme->content_file = 'home.php';
		$this->theme->head .= '<link rel="stylesheet" href="/css/home.css" />';

		$this->theme->content_env = ['voiture' => $parts[1] ?? null];

		if ($this->json) {
			header('Content-Type: application/json;charset=UTF-8');
			print $this->theme->bare();
		} else {
			print $this->theme->html();
		}

		return true;
	}

	public function show_plein($parts, $get, $post) {
		$this->theme->content_file = 'plein.php';

		$plein = new Plein();

		$this->theme->content_env = ['plein' => $plein];

		if ($this->json) {
			header('Content-Type: application/json;charset=UTF-8');
			print $this->theme->bare();
		} else {
			print $this->theme->html();
		}

		return true;
	}

	function auth_api() {
		$submitted_api_key = "";
		if (isset($_REQUEST['key'])) {
			$submitted_api_key = $_REQUEST['key'];
		}

		if (isset($_SERVER['HTTP_X_API_KEY'])) {
			$submitted_api_key = $_SERVER['HTTP_X_API_KEY'];
		}

		if (!empty($GLOBALS['config']['api-key'])) {
			if ($GLOBALS['config']['api-key'] != $submitted_api_key) {
				header('HTTP/1.0 403 Forbidden');
				die();
			}
		}
	}
}
