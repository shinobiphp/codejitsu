<?php

declare(strict_types=1);

namespace Codejitsu\Commands;

use Codejitsu\ExecutionContext;
use RuntimeException;

final class Packages
{
    public static function list(ExecutionContext $context): string
    {
        $composer = self::composer($context);
        $packages = [];

        foreach (['require', 'require-dev'] as $section) {
            foreach (($composer[$section] ?? []) as $name => $constraint) {
                $packages[$name] = $constraint;
            }
        }

        if ($packages === []) {
            return "No Composer packages are required.\n";
        }

        ksort($packages);
        $output = '';
        foreach ($packages as $name => $constraint) {
            $output .= sprintf("%-40s %s\n", $name, $constraint);
        }

        return $output;
    }

    public static function info(ExecutionContext $context): string
    {
        $package = self::argument($context);
        $result = self::runComposer(['show', $package, '--format=json']);

        if ($result['exit'] !== 0) {
            throw new RuntimeException($result['output']);
        }

        return $result['output'];
    }

    public static function install(ExecutionContext $context): int
    {
        return self::runMutation('require', self::argument($context));
    }

    public static function remove(ExecutionContext $context): int
    {
        return self::runMutation('remove', self::argument($context));
    }

    public static function update(ExecutionContext $context): int
    {
        $package = self::argument($context, false);
        $result = self::runComposer($package === null ? ['update', '--no-interaction', '--no-progress'] : ['update', $package, '--no-interaction', '--no-progress']);

        if ($result['output'] !== '') {
            echo $result['output'];
        }

        return $result['exit'];
    }

    /** @return array<string, mixed> */
    private static function composer(ExecutionContext $context): array
    {
        $root = getcwd();
        if (isset($context->arguments[1]) && is_string($context->arguments[1])) {
            $root = $context->arguments[1];
        }

        if ($root === false || !is_file($root . '/composer.json')) {
            throw new RuntimeException('composer.json was not found in the current working directory.');
        }

        $data = json_decode((string) file_get_contents($root . '/composer.json'), true);
        if (!is_array($data)) {
            throw new RuntimeException('composer.json is invalid.');
        }

        return $data;
    }

    private static function argument(ExecutionContext $context, bool $required = true): ?string
    {
        $argument = $context->arguments[0] ?? null;
        if (is_string($argument) && preg_match('/^[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?\/[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?$/i', $argument) === 1) {
            return $argument;
        }

        if (!$required) {
            return null;
        }

        throw new RuntimeException('A valid Composer package name is required.');
    }

    private static function runMutation(string $operation, string $package): int
    {
        $result = self::runComposer([$operation, $package, '--no-interaction', '--no-progress']);

        if ($result['output'] !== '') {
            echo $result['output'];
        }

        return $result['exit'];
    }

    /** @param list<string> $arguments @return array{exit:int,output:string} */
    private static function runComposer(array $arguments): array
    {
        $binary = getenv('COMPOSER_BINARY') ?: 'composer';
        $command = array_merge([$binary], $arguments);
        $pipes = [];
        $process = proc_open(
            $command,
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            getcwd() ?: null,
        );

        if (!is_resource($process)) {
            throw new RuntimeException('Unable to start Composer.');
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);

        return [
            'exit' => $exit,
            'output' => trim((string) $stdout . ((string) $stderr !== '' ? PHP_EOL . $stderr : '')),
        ];
    }
}
