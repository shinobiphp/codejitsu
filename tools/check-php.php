<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$paths = [$root . '/src', $root . '/tests'];
$files = [$root . '/bin/codejitsu'];

foreach ($paths as $path) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
}

sort($files);
foreach ($files as $file) {
    try {
        token_get_all((string) file_get_contents($file), TOKEN_PARSE);
    } catch (ParseError $error) {
        fwrite(STDERR, sprintf("%s: %s%s", $file, $error->getMessage(), PHP_EOL));
        exit(1);
    }
}

printf("PHP syntax valid for %d files.%s", count($files), PHP_EOL);
