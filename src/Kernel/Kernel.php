<?php

declare(strict_types=1);

namespace Codejitsu\Kernel;

use Codejitsu\Contracts\Scrolls\ScrollCodex;
use Codejitsu\Enums\Environment;
use Codejitsu\Enums\Scrolls\Types as ScrollTypes;
use ErrorException;
use RuntimeException;
use Throwable;

final class Kernel
{
    /** @var array<string, self> Active multiton instances */
    private static array $instances = [];

    public bool $isBooted {
        get => $this->booted;
    }

    public Environment $environment {
        get => $this->env ?? Environment::current();
    }

    public string $rootDir {
        get => $this->root
            ?? (
                defined('CODEJITSU_ROOT')
                    ? CODEJITSU_ROOT
                    : getcwd()
            );
    }

    public ScrollCodex $scrolls {
        get => $this->scrollsCodex;
    }

    private bool $booted = false;

    private ?Environment $env = null;

    private ?string $root = null;

    private function __construct(
        public readonly string $name,
        private readonly ScrollCodex $scrollsCodex,
    ) {}

    public static function instance(
        string $name,
        ?ScrollCodex $scrollCodex = null,
    ): self {
        if (!isset(self::$instances[$name])) {
            if ($scrollCodex === null) {
                throw new RuntimeException(sprintf(
                    'Cannot create Kernel [%s] without a ScrollCodex.',
                    $name,
                ));
            }

            self::$instances[$name] = new self($name, $scrollCodex);
        }

        return self::$instances[$name];
    }

    public static function hasInstance(string $name): bool
    {
        return isset(self::$instances[$name]);
    }

    public static function destroy(string $name): void
    {
        unset(self::$instances[$name]);
    }

    public function boot(
        ?string $rootDir = null,
        ?Environment $environment = null,
    ): self {
        if ($this->booted) {
            return $this;
        }

        $resolvedRoot = $rootDir
            ?? (
                defined('CODEJITSU_ROOT')
                    ? CODEJITSU_ROOT
                    : getcwd()
            );

        $this->root = rtrim($resolvedRoot, '/\\');
        $this->env = $environment ?? Environment::PRODUCTION;

        $this->registerErrorPipeline($this->env);
        $this->booted = true;

        return $this;
    }

    private function registerErrorPipeline(Environment $environment): void
    {
        set_error_handler(
            function (
                int $severity,
                string $message,
                string $file,
                int $line,
            ): bool {
                if (!(error_reporting() & $severity)) {
                    return false;
                }

                $exception = new ErrorException(
                    $message,
                    0,
                    $severity,
                    $file,
                    $line,
                );

                Environment::error($exception);
                return true;
            },
        );

        set_exception_handler(
            static function (Throwable $e): void {
                Environment::error($e);
            },
        );
    }

    private function __clone() {}

    public function __wakeup(): void
    {
        throw new RuntimeException(
            'Cannot unserialize multiton ' . static::class,
        );
    }
}
