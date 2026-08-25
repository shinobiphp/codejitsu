<?php

declare(strict_types=1);

namespace Codejitsu\Tests\Substrate;

use Codejitsu\ExecutionContext;
use Codejitsu\ExecutionPolicy;
use Codejitsu\Substrate\Php;
use PHPUnit\Framework\TestCase;

final class PhpTest extends TestCase
{
    public function testExecutesPhpSourceWithContext(): void
    {
        $result = (new Php())->execute(
            '<?php return $context->arguments[0] ?? "missing";',
            new ExecutionContext(['shinobi']),
        );

        self::assertSame('shinobi', $result);
    }

    public function testProcessRunnerUsesControlledEnvironment(): void
    {
        $result = (new Php())->execute(
            '<?php return getenv("SECRET") ?: "none";',
            new ExecutionContext([], null, new ExecutionPolicy(environment: [])),
        );

        self::assertSame('none', $result);
    }

    public function testProcessRunnerDeniesProcessFunctions(): void
    {
        $result = (new Php())->execute(
            '<?php return function_exists("shell_exec");',
            new ExecutionContext(),
        );

        self::assertFalse($result);
    }
}
