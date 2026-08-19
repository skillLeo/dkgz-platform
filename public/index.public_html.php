<?php

/**
 * Front controller for the "public_html IS the document root" layout.
 *
 * Copy this file into public_html as index.php when the Laravel core sits one
 * level above the document root. The only difference from the stock front
 * controller is the two paths below.
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Adjust this one line if the core directory is named something else.
$core = __DIR__.'/../dkgz';

if (file_exists($maintenance = $core.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $core.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $core.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
