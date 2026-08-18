<?php

declare(strict_types=1);

define('CODEJITSU_ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('CODEJITSU_SCROLLS_DIR', CODEJITSU_ROOT . 'scrolls' . DIRECTORY_SEPARATOR);

require_once CODEJITSU_ROOT . 'lib/autoload.php';

use Codejitsu\Boot;

// Automatically detects HTTP SAPI, boots the multiton kernel, and dispatches the Web Intent
$app = Boot::app();

$app->run();