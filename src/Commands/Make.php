<?php

declare(strict_types=1);

namespace Codejitsu\Commands;

use Codejitsu\Console\Editor;
use Codejitsu\Console\Questioner;
use Codejitsu\Console\TerminalEditor;
use Codejitsu\Console\TerminalQuestioner;
use Codejitsu\Enums\Scrolls\Types;
use Codejitsu\ExecutionContext;
use Codejitsu\Scrolls\ScrollCodex;
use Codejitsu\Scrolls\TypeDefinition;
use Codejitsu\Scrolls\TypeRegistry;
use Codejitsu\Scaffolding\ProjectScaffolder;
use Codejitsu\SubstrateRegistry;
use Codejitsu\Uri\Uri;
use InvalidArgumentException;
use RuntimeException;

final class Make
{
    public static function context(ExecutionContext $context): string
    {
        return Contexts::create($context);
    }

    public static function catalog(ExecutionContext $context): string
    {
        $name = self::requiredArgument($context, 0, 'A Catalog name is required.');
        (new ProjectScaffolder(self::projectRoot()))->catalog($name);
        return sprintf("Created Catalog Scroll [%s].\n", $name);
    }

    public static function package(ExecutionContext $context): string
    {
        $name = self::requiredArgument($context, 0, 'A package name is required.');
        $description = trim((string) ($context->arguments[1] ?? ''));
        (new ProjectScaffolder(self::projectRoot()))->package($name, $description);
        return sprintf("Created uninstalled Codejitsu package [%s] and added it to the project catalog.\n", $name);
    }

    public static function scroll(ExecutionContext $context): string
    {
        $arguments = is_array($context->arguments) ? array_values($context->arguments) : [$context->arguments];
        $uri = array_values(array_filter($arguments, static fn (mixed $argument): bool => is_string($argument) && !str_starts_with($argument, '--')))[0] ?? null;

        if (!is_string($uri) || trim($uri) === '') {
            return self::interactive(
                $context->codex,
                new TerminalQuestioner(),
                new TerminalEditor(),
                $context->codex?->substrates() ?? self::defaultSubstrates(),
            );
        }

        return self::create($uri, $arguments, $context->codex);
    }

    public static function interactive(
        ?ScrollCodex $codex,
        Questioner $questioner,
        Editor $editor,
        SubstrateRegistry $registry,
    ): string {
        $types = $codex?->types() ?? TypeRegistry::builtins();
        $selected = $questioner->select('Scroll type', array_map(
            static fn (TypeDefinition $type): string => $type->name,
            $types->all(),
        ));
        if (!$types->has($selected)) {
            throw new InvalidArgumentException('Invalid Scroll type selection.');
        }
        $type = $types->get($selected);

        $name = trim($questioner->ask(sprintf('%s name/identifier: ', $type->name)));
        if ($name === '') {
            throw new InvalidArgumentException('Scroll name cannot be empty.');
        }

        $defaultVersion = self::nextVersion($codex, $type, $name);
        $version = trim($questioner->ask(sprintf('Version [%s]: ', $defaultVersion), $defaultVersion));

        $payload = [
            'name' => $name,
            'type' => $type->name,
            'version' => $version,
        ];

        if ($type->name === Types::CAPABILITY->value) {
            $substrate = $questioner->select('Substrate', $registry->names());
            $payload['substrate'] = $substrate;
            if ($substrate === 'wasm') {
                $payload['sourceEncoding'] = 'base64';
            }
            $payload['source'] = $editor->edit(self::template($substrate));
        } else {
            $description = trim($questioner->ask('Description (optional): '));
            if ($description !== '') {
                $payload['description'] = $description;
            }
            $payload['content'] = $editor->edit(self::template($type->name));
        }

        $uri = $type->scheme . $name . '#' . $version;
        $path = self::path($type, $name);
        self::ensureDirectory(dirname($path));
        if (is_file($path)) {
            throw new RuntimeException(sprintf('Scroll [%s] already exists at [%s].', $uri, $path));
        }

        self::write($path, $payload, $type);

        return sprintf("Created %s Scroll [%s].%s", $type->name, $uri, PHP_EOL);
    }

    private static function create(string $uri, array $arguments, ?ScrollCodex $codex): string
    {
        $parsed = Uri::make($uri, defaultVersion: '1.0.0');
        $types = $codex?->types() ?? TypeRegistry::builtins();
        $type = $types->forScheme($parsed->type);
        if (!$type instanceof TypeDefinition) {
            throw new InvalidArgumentException(sprintf('Unknown Scroll type [%s].', $parsed->type));
        }

        $name = trim($parsed->resourcePath, '/');
        if ($name === '') {
            throw new InvalidArgumentException(sprintf('Scroll URI [%s] has no logical path.', $uri));
        }

        $path = self::path($type, $name);
        self::ensureDirectory(dirname($path));
        if (is_file($path)) {
            throw new RuntimeException(sprintf('Scroll [%s] already exists at [%s].', $uri, $path));
        }

        $target = self::option($arguments, '--target=');
        $source = self::option($arguments, '--source=');
        $substrate = self::option($arguments, '--substrate=') ?? ($source !== null ? 'auto' : null);
        $sourceEncoding = self::option($arguments, '--source-encoding=');
        $payload = [
            'name' => $name,
            'type' => $type->name,
            'version' => $parsed->version ?? '1.0.0',
        ];

        if ($target !== null) {
            $payload['target'] = $target;
        }

        if ($source !== null) {
            $payload['substrate'] = $substrate;
            $payload['source'] = rtrim($source, "\r\n") . "\n";
            if ($sourceEncoding !== null) {
                $payload['sourceEncoding'] = $sourceEncoding;
            }
        }

        self::write($path, $payload, $type);

        return sprintf("Created %s Scroll [%s].%s", $type->name, $uri, PHP_EOL);
    }

    private static function path(TypeDefinition $type, string $name): string
    {
        $root = defined('CODEJITSU_ROOT') ? CODEJITSU_ROOT : getcwd() . DIRECTORY_SEPARATOR;
        $directory = rtrim($root, '/\\') . DIRECTORY_SEPARATOR . 'scrolls' . DIRECTORY_SEPARATOR . $type->plural;
        $filename = str_replace(['/', '\\'], '_', $name) . '.' . $type->extension;

        return $directory . DIRECTORY_SEPARATOR . $filename;
    }

    private static function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create Scroll directory [%s].', $directory));
        }
    }

    private static function write(string $path, array $payload, TypeDefinition $type): void
    {
        if (file_put_contents($path, $type->makeCodec()->encode($payload), LOCK_EX) === false) {
            throw new RuntimeException(sprintf('Unable to write Scroll [%s].', $path));
        }
    }

    private static function nextVersion(?ScrollCodex $codex, TypeDefinition $type, string $name): string
    {
        if ($codex === null) {
            return '1.0.0';
        }

        $entries = $codex->query(['type' => $type->name, 'name' => $name]);
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

    private static function template(string $type): string
    {
        return match ($type) {
            'php' => "<?php\n\nreturn null;\n",
            'lua' => "return nil\n",
            'javascript' => "undefined\n",
            'wasm' => "",
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

    private static function requiredArgument(ExecutionContext $context, int $index, string $message): string
    {
        $value = trim((string) ($context->arguments[$index] ?? ''));
        if ($value === '') throw new InvalidArgumentException($message);
        return $value;
    }

    private static function projectRoot(): string
    {
        return getcwd() ?: throw new RuntimeException('Unable to determine the project root.');
    }
}
