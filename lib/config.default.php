<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

// XMPP via BOSH
define('XMPP_SERVER', 'busch-jaeger.de');
define('XMPP_HOST_PORT', 5280);
define('XMPP_HTTP_HOST', ''); // IP of System Access Point
define('XMPP_HTTP_URL', 'http://' . XMPP_HTTP_HOST . ':' . XMPP_HOST_PORT . '/http-bind'); // IP of System Access Point
define('XMPP_USER_NAME', ''); // see settings.json on System Access Point
define('XMPP_USER_PASSWORD', '');

// Database
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASSWORD', '');

// Backend
define('API_URL', 'http://example.com/pathToApi');


?>
