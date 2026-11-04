<?php
# vim:ft=html

require __DIR__.'/../inc/common.inc.php';

$site = new Site();
$site->session_init();

$theme = new Theme();

$router = new Router($site, $theme);
if ($router->handle(str_replace('/gasoil/gasoil.seos.fr/www', '', $_SERVER['REQUEST_URI']), $_GET, $_POST)) {
	die();
}
