<?php

declare(strict_types=1);

namespace Codejitsu;

use Codejitsu\Apps\Cli;
use Codejitsu\Apps\Swoole;
use Codejitsu\Apps\Web;
use Codejitsu\Contracts\App;
use Codejitsu\Enums\Environment;
use Codejitsu\Kernel\Kernel;
use Codejitsu\Scrolls\CommandDiscovery;
use Codejitsu\Scrolls\ScrollCodex;

final class Boot
{
    public static function app(
        ?string $name = null,
        ?ScrollCodex $codex = null,
        ?string $rootDir = null,
        ?Environment $environment = null,
        string $swooleHost = '127.0.0.1',
        int $swoolePort = 9501,
    ): App {
        [$flagRoot, $flagEnv] = self::parseCliOptions();
        $resolvedRoot = $rootDir ?? $flagRoot;
        $resolvedEnv = $environment ?? $flagEnv;

        if (self::isSwooleContext()) {
            return self::swoole($swooleHost, $swoolePort, $name ?? 'swoole', $codex, $resolvedRoot, $resolvedEnv);
        }

        if (self::isCliContext()) {
            return self::cli($name ?? 'cli', $codex, $resolvedRoot, $resolvedEnv);
        }

        return self::web($name ?? 'web', $codex, $resolvedRoot, $resolvedEnv);
    }

    public static function cli(
        ?string $name = 'cli',
        ?ScrollCodex $codex = null,
        ?string $rootDir = null,
        ?Environment $environment = null,
    ): Cli {
        $root = $rootDir ?? (defined('CODEJITSU_ROOT') ? CODEJITSU_ROOT : getcwd());
        $scrolls = $codex ?? new ScrollCodex();

        CommandDiscovery::fromDirectory(
            $root . DIRECTORY_SEPARATOR . 'scrolls' . DIRECTORY_SEPARATOR . 'commands',
            $scrolls,
        );

        $kernel = Kernel::instance($name ?? 'cli', $scrolls);
        return new Cli($kernel);
    }

    public static function swoole(
        string $host = '127.0.0.1',
        int $port = 9501,
        ?string $name = 'swoole',
        ?ScrollCodex $codex = null,
        ?string $rootDir = null,
        ?Environment $environment = null,
    ): Swoole {
        $kernel = Kernel::instance($name ?? 'swoole', $codex ?? new ScrollCodex());
        $kernel->boot($rootDir, $environment);
        return new Swoole($kernel, $host, $port);
    }

    public static function web(
        ?string $name = 'web',
        ?ScrollCodex $codex = null,
        ?string $rootDir = null,
        ?Environment $environment = null,
    ): Web {
        $kernel = Kernel::instance($name ?? 'web', $codex ?? new ScrollCodex());
        $kernel->boot($rootDir, $environment);
        return new Web($kernel);
    }

    /** @return array{0: ?string, 1: ?Environment} */
    private static function parseCliOptions(): array
    {
        $argv = $_SERVER['argv'] ?? [];
        $root = null;
        $env = null;

        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--root=')) {
                $root = substr($arg, 7);
            } elseif (str_starts_with($arg, '--env=')) {
                $env = Environment::tryFrom(strtolower(substr($arg, 6)));
            }
        }

        return [$root, $env];
    }

    private static function isSwooleContext(): bool
    {
        return extension_loaded('swoole')
            && class_exists(\Swoole\Coroutine::class)
            && \Swoole\Coroutine::getCid() >= 0;
    }

    private static function isCliContext(): bool
    {
        return in_array(PHP_SAPI, ['cli', 'phpdbg'], true);
    }
}