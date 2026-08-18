<?php
declare(strict_types=1);

namespace Codejitsu\Kernel;

use Codejitsu\Contracts\Scrolls\ScrollCodex;
use Codejitsu\Enums\Environment;
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
    ) {
        echo "--> Kernel::__construct for '{$name}' initialized\n";
    }

    /**
     * Retrieve or initialize a named Kernel instance.
     */
    public static function instance(
        string $name,
        ?ScrollCodex $scrollCodex = null,
    ): self {
        echo "--> Kernel::instance called with name: '{$name}'\n";
        if (!isset(self::$instances[$name])) {
            echo "--> Instance '{$name}' not set. Checking ScrollCodex...\n";
            if ($scrollCodex === null) {
                echo "--> CRITICAL: ScrollCodex is NULL inside Kernel::instance\n";
                exit;
            }
            
            echo "--> Instantiating new Kernel('{$name}')...\n";
            self::$instances[$name] = new self(
                $name,
                $scrollCodex,
            );
            echo "--> New Kernel instance created and stored.\n";
        } else {
            echo "--> Returning existing Kernel instance for '{$name}'.\n";
        }
    
        return self::$instances[$name];
    }

    public static function hasInstance(
        string $name,
    ): bool {
        return isset(self::$instances[$name]);
    }

    public static function destroy(
        string $name,
    ): void {
        unset(self::$instances[$name]);
    }

    /**
     * Boot the kernel instance.
     */
    public function boot(
        ?string $rootDir = null,
        ?Environment $environment = null,
    ): self {
        echo "--> Kernel::boot() ENTERED\n";
        if ($this->booted) {
            echo "--> Kernel already booted, returning.\n";
            return $this;
        }

        $resolvedRoot = $rootDir
            ?? (
                defined('CODEJITSU_ROOT')
                    ? CODEJITSU_ROOT
                    : getcwd()
            );

        $this->root = rtrim(
            $resolvedRoot,
            '/\\',
        );

        $this->env = Environment::PRODUCTION;
        // $this->env = $environment
        //     ?? Environment::current();

        echo "--> Registering error pipeline...\n";
        $this->registerErrorPipeline(
            $this->env,
        );

        echo "--> Hit checkpoint before scroll discovery / exit\n";
        var_dump(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5));
    }

    private function registerErrorPipeline(
        Environment $environment,
    ): void {
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

                Environment::error(
                    $exception,
                );

                return true;
            },
        );

        set_exception_handler(
            function (Throwable $e): void {
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