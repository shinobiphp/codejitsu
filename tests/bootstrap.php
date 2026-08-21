<?php

declare(strict_types=1);

$autoload = dirname(__DIR__) . '/lib/autoload.php';

if (!is_file($autoload)) {
    throw new RuntimeException(
        'Composer autoloader not found. Run `composer install` before running tests.',
    );
}

require $autoload;
