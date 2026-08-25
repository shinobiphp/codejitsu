<?php

declare(strict_types=1);

namespace Codejitsu\Commands;

use Codejitsu\Codecs\Neon;
use Codejitsu\ExecutionContext;
use Codejitsu\Enums\Scrolls\Types;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\SubstrateRegistry;
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
            return self::interactive($context);
        }

        return self::create($context, $uri, $arguments);
    }

    private static function create(ExecutionContext $context, string $uri, array $arguments): string
    {
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
        $source = self::option($arguments, '--source=');
        $substrate = self::option($arguments, '--substrate=') ?? ($source !== null ? 'auto' : null);
        $payload = [
            'name' => $name,
            'type' => $type->value,
            'version' => $parsed->version ?? '1.0.0',
        ];

        if ($target !== null) {
            $payload['target'] = $target;
        }

        if ($source !== null) {
            $payload['substrate'] = $substrate;
            $payload['source'] = rtrim($source, "\r\n") . "\n";
        }

        self::write($path, $payload);

        return sprintf("Created %s Scroll [%s].%s", $type->value, $uri, PHP_EOL);
    }

    private static function interactive(ExecutionContext $context): string
    {
        $codex = $context->codex;
        $types = Types::cases();

        fwrite(STDOUT, PHP_EOL . "\033[1;36mCreate Scroll\033[0m" . PHP_EOL . PHP_EOL);
        foreach ($types as $index => $type) {
            fwrite(STDOUT, sprintf("  \033[1;33m%d\033[0m) %s\n", $index + 1, $type->value));
        }

        $selection = self::prompt('Scroll type [1]: ', '1');
        $index = (int) $selection - 1;
        if (!isset($types[$index])) {
            throw new InvalidArgumentException('Invalid Scroll type selection.');
        }
        $type = $types[$index];

        $name = trim(self::prompt(sprintf('%s name/identifier: ', $type->long_name())));
        if ($name === '') {
            throw new InvalidArgumentException('Scroll name cannot be empty.');
        }

        $defaultVersion = self::nextVersion($codex, $type, $name);
        $version = trim(self::prompt(sprintf('Version [%s]: ', $defaultVersion), $defaultVersion));

        $payload = [
            'name' => $name,
            'type' => $type->value,
            'version' => $version,
        ];

        if ($type === Types::CAPABILITY) {
            $registry = $codex?->substrates() ?? self::defaultSubstrates();
            $substrates = $registry->names();
            fwrite(STDOUT, PHP_EOL . "\033[1;36mSubstrate\033[0m" . PHP_EOL);
            foreach ($substrates as $index => $substrate) {
                fwrite(STDOUT, sprintf("  \033[1;33m%d\033[0m) %s\n", $index + 1, $substrate));
            }

            $substrateIndex = (int) self::prompt('Substrate [1]: ', '1') - 1;
            $substrate = $substrates[$substrateIndex] ?? 'php';
            $payload['substrate'] = $substrate;
            $payload['source'] = self::edit(self::template($substrate));
        } else {
            $payload['description'] = self::prompt('Description (optional): ');
            $payload['content'] = self::edit(self::template($type->value));
        }

        $uri = $type->scheme() . $name . '#' . $version;
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

        self::write($path, $payload);

        return sprintf("Created %s Scroll [%s].%s", $type->value, $uri, PHP_EOL);
    }

    private static function write(string $path, array $payload): void
    {
        if (file_put_contents($path, (new Neon())->encode($payload), LOCK_EX) === false) {
            throw new RuntimeException(sprintf('Unable to write Scroll [%s].', $path));
        }
    }

    private static function nextVersion(?ScrollCodex $codex, Types $type, string $name): string
    {
        if ($codex === null) {
            return '1.0.0';
        }

        $entries = $codex->query(['type' => $type->value, 'name' => $name]);
        if ($entries === []) {
            return '1.0.0';
        }

        $highest = [1, 0, 0];
        foreach ($entries as $entry) {
            $parts = array_map('intval', explode('.', $entry->version));
            $parts = array_pad(array_slice($parts, 0, 3), 3, 0);
            if ($parts > $highest) {
                $highest = $parts;
            }
        }

        return sprintf('%d.%d.%d', $highest[0], $highest[1], $highest[2] + 1);
    }

    private static function edit(string $contents): string
    {
        $editor = trim((string) ($_ENV['EDITOR'] ?? $_ENV['VISUAL'] ?? getenv('EDITOR') ?: getenv('VISUAL') ?: 'nano'));
        $path = tempnam(sys_get_temp_dir(), 'codejitsu-scroll-');
        if ($path === false) {
            throw new RuntimeException('Unable to create temporary editor file.');
        }

        try {
            file_put_contents($path, $contents, LOCK_EX);
            $command = $editor . ' ' . escapeshellarg($path);
            passthru($command, $exitCode);
            if ($exitCode !== 0) {
                throw new RuntimeException(sprintf('Editor exited with status %d.', $exitCode));
            }

            $source = file_get_contents($path);
            if ($source === false || trim($source) === '') {
                throw new InvalidArgumentException('Scroll contents cannot be empty.');
            }

            return rtrim($source, "\r\n") . "\n";
        } finally {
            @unlink($path);
        }
    }

    private static function template(string $type): string
    {
        return match ($type) {
            'php' => "<?php\n\nreturn null;\n",
            'lua' => "return nil\n",
            'javascript' => "undefined\n",
            'wasm' => "# base64-encoded WASM module exporting `run`\n",
            'capability' => "<?php\n\nreturn null;\n",
            'schema' => "# schema definition\n",
            default => "# Scroll contents\n",
        };
    }

    private static function defaultSubstrates(): SubstrateRegistry
    {
        $registry = new SubstrateRegistry();
        $registry->register('php', new \Codejitsu\Substrate\Php());
        $registry->register('lua', new \Codejitsu\Substrate\Lua());
        $registry->register('javascript', new \Codejitsu\Substrate\Javascript());
        $registry->register('wasm', new \Codejitsu\Substrate\Wasm());
        return $registry;
    }

    private static function prompt(string $message, string $default = ''): string
    {
        fwrite(STDOUT, $message);
        $value = fgets(STDIN);
        if ($value === false) {
            return $default;
        }

        $value = trim($value);
        return $value === '' ? $default : $value;
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
}
