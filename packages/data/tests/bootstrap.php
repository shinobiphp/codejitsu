<?php

declare(strict_types=1);

$root = dirname(__DIR__, 3);
require $root . '/lib/autoload.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'Codejitsu\\Data\\';
    if (!str_starts_with($class, $prefix)) return;
    $path = dirname(__DIR__) . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) require $path;
});
