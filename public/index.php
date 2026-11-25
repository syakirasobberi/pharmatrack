<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// When the project-root .htaccess forwards e.g. /pharmatrack/login into public/,
// the script path is /pharmatrack/public/index.php, which doesn't match the URL,
// so Laravel can't find the base path and every route 404s. Point the script
// path at the project root instead so the base URL becomes /pharmatrack.
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if (str_ends_with($scriptDir, '/public')
    && ! str_starts_with($_SERVER['REQUEST_URI'] ?? '', $scriptDir.'/')
    && ($_SERVER['REQUEST_URI'] ?? '') !== $scriptDir) {
    $_SERVER['SCRIPT_NAME'] = substr($scriptDir, 0, -strlen('/public')).'/index.php';
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
}
unset($scriptDir);

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
