<?php

declare(strict_types=1);

namespace Codejitsu\Commands;

use Codejitsu\ExecutionContext;
use Codejitsu\Enums\Scrolls\Types;
use Codejitsu\Uri\Uri;
use InvalidArgumentException;
use RuntimeException;

final class Make
{
    public static function scroll(ExecutionContext $context): string
    {
        $arguments = is_array($context->arguments) ? array_values($context->arguments) : [$context->arguments];
        $uri = array_values(array_filter($arguments, static fn (mixed $argument): bool => is_string($argument) && !str_starts_with($argument, '--')))[0] ?? null;

        if (!is_string($uri) || trim($uri) === '') {
            throw new InvalidArgumentException('A Scroll URI is required.');
        }

        $parsed = Uri::make($uri, defaultVersion: '1.0.0');
        $type = Types::normalize($parsed->type, null);
        if (!$type instanceof Types) {
            throw new InvalidArgumentException(sprintf('Unknown Scroll type [%s].', $parsed->type));
        }

        $name = trim($parsed->resourcePath, '/');
        if ($name === '') {
            throw new InvalidArgumentException(sprintf('Scroll URI [%s] has no logical path.', $uri));
        }

        $root = defined('CODEJITSU_ROOT') ? CODEJITSU_ROOT : getcwd() . DIRECTORY_SEPARATOR;
        $directory = rtrim($root, '/\\') . DIRECTORY_SEPARATOR . 'scrolls' . DIRECTORY_SEPARATOR . $type->plural();
        $filename = str_replace(['/', '\\'], '_', $name) . '.' . $type->extension();
        $path = $directory . DIRECTORY_SEPARATOR . $filename;

        if (is_file($path)) {
            throw new RuntimeException(sprintf('Scroll [%s] already exists at [%s].', $uri, $path));
        }

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create Scroll directory [%s].', $directory));
        }

        $target = self::option($arguments, '--target=');
        $payload = [
            'name' => $name,
            'type' => $type->value,
            'version' => $parsed->version ?? '1.0.0',
        ];

        if ($target !== null) {
            $payload['target'] = $target;
        }

        $content = self::encode($payload);
        if (file_put_contents($path, $content, LOCK_EX) === false) {
            throw new RuntimeException(sprintf('Unable to write Scroll [%s].', $path));
        }

        return sprintf("Created %s Scroll [%s].%s", $type->value, $uri, PHP_EOL);
    }

    private static function option(array $arguments, string $prefix): ?string
    {
        foreach ($arguments as $argument) {
            if (is_string($argument) && str_starts_with($argument, $prefix)) {
                $value = trim(substr($argument, strlen($prefix)));
                return $value !== '' ? $value : null;
            }
        }

        return null;
    }

    private static function encode(array $payload): string
    {
        $lines = [];
        foreach ($payload as $key => $value) {
            $lines[] = sprintf('%s: %s', $key, is_string($value) ? self::quote($value) : $value);
        }

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    private static function quote(string $value): string
    {
        return preg_match('/^[A-Za-z0-9_.\\\\:-]+$/', $value) === 1
            ? $value
            : "'" . str_replace("'", "''", $value) . "'";
    }
}
